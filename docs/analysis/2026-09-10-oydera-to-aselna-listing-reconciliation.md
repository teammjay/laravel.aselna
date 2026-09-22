# Copying Shopify listing data from Oydera (easyesale) into Aselna — problem analysis and resolution plan

**Date:** 2026-09-10
**Branch:** `printing_optimization`
**Scope:** `store_product`, `listing_item`, `listing_item_detail`, `listing_item_product_images`,
`generated_product_image`, `manage_marketplace_products` — i.e. everything the Shopify push and the
Shopify feed path read.
**Status:** Analysis only. **No code was changed.**

---

## 0. One-paragraph summary

Aselna and Oydera both mint their own primary keys for the same physical product, so the two systems
can no longer be joined on `id`. The only stable, shared identifiers are **Fonus product id**
(`products.product_code` in Aselna = `product.reference_id1` in Oydera) and **Fonus custom label**
(`products.product_sku` = Oydera `product.code`). Everything below product level — `store_product.id`,
`listing_item.id`, `listing_item.sku` — is generated independently in each system, and `listing_item.sku`
is **random**, which is fatal because the Shopify variant SKU is the key Aselna uses to recognise its own
listings and to attach incoming orders. The existing `OdemobileListingItemSyncJob` tries to fix this by
copying Oydera's `listing_item` rows verbatim; it copies the right column (`sku`) but has a duplicate-
detection flaw, drags eleven foreign keys out of Oydera's ID space, hardcodes `status = 1`, and — most
importantly — never writes the three tables that actually stop Aselna from creating a second copy on
Shopify. The fix is not "copy more tables". It is: **copy from Oydera only the SKU dictionary, then let
the already-existing `FetchShopifyProductsJob` rebuild the Shopify mapping from Shopify itself**, which
is the one authoritative source for what really exists in the store.

---

## 1. How identity is assigned in each system

### 1.1 Aselna today (Fonus lineage)

Every Fonus sync job upserts on the **Fonus code** and lets the model `boot()` hook mint a **fresh local
UUID**:

| Aselna table | upsert key | source | code |
|---|---|---|---|
| `products` | `product_code` = Fonus `tbl_product.product_id` | Fonus | `app/Jobs/Fonus/ProductSyncJob.php:122` |
| `device` | `code` = Fonus `device_id` / `gadget_id` | Fonus | `app/Jobs/Fonus/DevicesSyncJob.php:123` |
| `manufacturer` | `code` = Fonus `manufacturer_id` | Fonus | `app/Jobs/Fonus/ManufacturerSyncJob.php:98` |
| `brand` | `code` = Fonus `brand_id` | Fonus | `app/Jobs/Fonus/BrandSyncJob.php:90` |
| `product_color` | `code` = Fonus `color_id` | Fonus | `app/Jobs/Fonus/ProductcolorSyncJob.php:75` |
| `carrier` | `code` = Fonus `carrier_id` | Fonus | `app/Jobs/Fonus/CarrierSyncJob.php:92` |
| `product_type` | `code` = `category_id` / `category-subcategory` | Fonus | `app/Jobs/Fonus/CategorySyncJob.php:105,129` |

The UUID comes from the model, not the source — e.g. `app/Models/Catalog.php:25-29`:

```php
static::creating(function ($model) {
    if (empty($model->product_id)) {
        $model->product_id = (string) Str::uuid();
    }
});
```

The same hook exists on `StoreProduct`, `ListingItem`, `ListingItemDetail` and
`ManageMarketplaceProduct`.

### 1.2 Aselna previously (Oydera lineage)

The legacy `app/Jobs/ProductSyncJob.php:202-216` did the opposite — it **adopted Oydera's key**:

```php
Catalog::updateOrInsert(
    ['product_id' => $product->id],                 // Oydera product.id
    [ 'product_id'  => $product->id,
      'product_code' => $product->reference_id1,    // Fonus product id
      'product_sku'  => $product->code, ... ]);
```

So `products.product_id` in production is **either** an Oydera product id (rows created by the legacy
job) **or** a locally minted UUID (rows created by the Fonus job), depending on which job saw the
product first. `products.product_sku` is `unique`
(`database/migrations/2024_10_23_062522_update_field_in_products_table.php:16`), which is what has kept
the `products` table from splitting into two rows per product — the second insert fails on that index and
is swallowed by a `catch` that only logs `Test Error`.

### 1.3 Oydera

Oydera is the PostgreSQL `easyesale_db` connection, table-prefixed (`oydera_`) via `DB_ESALE_PREFIX`
(`config/database.php:85-98`). Its `product.reference_id1` holds the **Fonus product id** — this is
established by the legacy sync above, which maps `reference_id1 → products.product_code`, exactly the
column the Fonus job keys on. That single fact is what makes any reconciliation possible.

### 1.4 What is shared and what is not

| Entity | Shared between Oydera and Aselna? | Why |
|---|---|---|
| Fonus product id | **Yes** | `products.product_code` = `oydera_product.reference_id1` |
| Product SKU / custom label | **Yes** (unique) | `products.product_sku` = `oydera_product.code` |
| `store.id` | **Yes** | `app/Jobs/StoreSyncJob.php:96-99` copies Oydera's store id verbatim |
| `products.product_id` | **Sometimes** | depends which sync created the row (§1.2) |
| `store_product.id` | **No** (in general) | Aselna mints its own in `SubmitShopifyProductsJob::addListingItem` (`:811-827`) |
| `listing_item.id` | **No** | same |
| `listing_item.sku` | **No** | **random** — see §2 |
| `device.id`, `brand.id`, `manufacturer.id`, `carrier.id`, `product_color.id` | **No** | Fonus lineage mints new UUIDs (§1.1) |

---

## 2. The core problem, stated precisely

### 2.1 The SKU is random, and Shopify already holds Oydera's version of it

`app/Models/StoreTypeListing.php:93-109`:

```php
public static function generateUniqueAlphabeticSku($product_code = '', $length = 6, &$skuCache = []) {
    do {
        $sku = $product_code != ''
            ? strtoupper($product_code . '-' . Str::random($length))
            : strtoupper(Str::random($length));
    } while (isset($skuCache[$sku]));
    ...
}
```

`Str::random()` means **the same product produces a different SKU in Aselna than it did in Oydera**.
The OdeMobile / AccessoryChoice Shopify stores were populated by Oydera, so the live variants carry
Oydera-shaped SKUs (the forensic log in
`docs/analysis/2026-09-07-missing-shopify-order-item-analysis.md` shows `B41627ED`, `BD3EE35D` — eight
characters, no `-`, i.e. Oydera's generator, not Aselna's `<product_sku>-<RANDOM6>`).

That SKU is not cosmetic. It is the join key in two places:

1. **Order import** — `app/Jobs/FetchShopifyOrders.php:283`:
   ```php
   $itemDetail = ListingItem::with('storeProduct','storeProduct.product')
       ->where('sku', $item['sku'])->whereIn('store_product_id', ...)->first();
   ```
   No match ⇒ `listing_item_id` and `client_id` are written as `NULL` on the `order_item`
   (`:306-307`), and the product is only recovered by a loose `explode('-', $sku)[0]` fallback on
   `product_sku` (`:290-293`). This is already visible in production: line item #3 of order `#215034`
   has `listing_item_id = NULL`.

2. **Store reconciliation** — `app/Jobs/FetchShopifyProductsJob.php:155`:
   ```php
   $listingItems = ListingItem::where('sku', $sku)->get();
   ```
   No match ⇒ the job falls into a degraded branch (`:194-232`) that writes only
   `manage_marketplace_products` and never repairs `listing_item` or the image references.

### 2.2 Aselna therefore believes the Shopify products do not exist, and creates them again

`SubmitShopifyProductsJob::handle()` decides "create" vs "update" purely from
`manage_marketplace_products` (`app/Jobs/SubmitShopifyProductsJob.php:108-118`):

```php
$AlreadyCreatedIds = ManageMarketplaceProduct::select('product_id')
    ->where('upload_status','success')->where('store_id',$store->id)->get();
$products = Catalog::…->whereNotIn('product_id', $AlreadyCreatedIds)->limit(100)->get();
foreach ($products as $product) { $this->createProduct($product, $store); }
```

Because Oydera never wrote `manage_marketplace_products` in Aselna, every OdeMobile product falls into
the "create" set. `createProduct()` then calls `productSet` with `handle = $listingItem->store_url`
(`:458`, set at `:382`), which is `NULL` on a fresh Aselna listing item — so Shopify **creates a brand-new product**
next to Oydera's. That is the duplication the business is seeing.

> **Secondary defect, worth fixing separately:** `$AlreadyCreatedIds` is a Collection of *Eloquent
> models*, not scalars. `whereNotIn('product_id', $AlreadyCreatedIds)` therefore binds non-scalar
> values and the `NOT IN` predicate cannot match anything, so the exclusion silently does nothing even
> once the mapping table *is* populated. The only thing preventing runaway duplication after that point
> is the `handle` round-trip. Compare `:552`, which reads the same table correctly with `->first()`.

### 2.3 The three tables that hold "this listing already exists on Shopify"

| Table | Column | Meaning | Written by |
|---|---|---|---|
| `manage_marketplace_products` | `marketplace_product_id`, `marketplace_variant_id`, `upload_status` | the Shopify Product/Variant GIDs | `SubmitShopifyProductsJob:388`, `FetchShopifyProductsJob:162,220` |
| `listing_item` | `store_url` (handle), `store_sku` (`<productId>-<variantId>`) | the Shopify address of the listing | `SubmitShopifyProductsJob:512-517`, `FetchShopifyProductsJob:183` |
| `listing_item_product_images` | `reference_id` (MediaImage id), `path` (CDN url) | which local image is which Shopify media | `SubmitShopifyProductsJob::handleMediaImages:953`, `FetchShopifyProductsJob:284` |

All three are empty for the Oydera-created listings. `generated_product_image` (per
`product_image_id`) additionally decides whether the image is re-uploaded to FTP
(`SubmitShopifyProductsJob:272-308`).

---

## 3. What `OdemobileListingItemSyncJob` does, and the eleven problems with it

The job (`app/Jobs/OdemobileListingItemSyncJob.php`) reads Oydera `listing_item` joined to
`store_product` and `product`, projects `p.reference_id1` as the bridge, resolves the local
`products` row by `product_code`, finds-or-creates the local `store_product`, and upserts a
`listing_item` **under Oydera's id** but with **Aselna's `store_product_id`**. The intent is right.
The execution has these defects:

**P1 — The duplicate check asks the wrong question.** (`:85-88`)
```php
$existingItem = $localItems->get($oyderaItem->id);
if ($existingItem) { continue; }
```
This only asks "have I already copied *this Oydera row*". It never asks "does Aselna already have its
own listing item for this store product". When Aselna has already created one via
`SubmitShopifyProductsJob::addListingItem` (`:829-843`), the job inserts a **second** `listing_item`
row on the same `store_product_id`.

**P2 — Nothing stops that at the schema level.** `listing_item` has no unique index on
`store_product_id` — only a non-unique composite added in
`database/migrations/2025_09_15_085803_add_indexes_to_listing_tables.php:31`. Contrast `store_product`,
which *does* have `unique(['product_id','store_id'])`
(`database/migrations/2025_10_31_110639_update_unique_indexes_on_store_product_table.php:15`).

**P3 — Downstream code assumes exactly one listing item per store product.**
`addListingItem` (`:829`) and the update path both do `ListingItem::where('store_product_id', $x)->first()`
with **no ordering**. With two rows present the winner is whatever MySQL returns first, so successive
runs can push a different SKU to the same Shopify product and flip it back and forth.

**P4 — One Oydera store product can carry many listing items.** Oydera models device/carrier variants
as multiple `listing_item` rows under one `store_product`. The loop calls `upsertListingItem()` for
every one of them against the same `$localStoreProduct->id` (`:130`), multiplying P1. There is no rule
selecting *which* Oydera listing item is the Shopify one. (The right rule: the one whose `store_sku`
and `store_url` are populated.)

**P5 — Eleven foreign keys are copied out of Oydera's ID space** (`:166-197`):
`store_type_listing_id`, `parent_id`, `linked_item_id`, `carrier_id`, `device_id`,
`generation_type_id`, `brand_id`, `device_carrier_id`, `color_id`, `size_id`, `manufacturer_id`,
`client`, `replaced_id`. Per §1.1 every one of those tables is now Fonus-keyed with locally minted
UUIDs, so these values will overwhelmingly point at nothing. They are written with no lookup and no
null-out.

**P6 — `device_id` in particular is dangerous.** Two schedules disagree about who owns `device`:
`app/Jobs/Fonus/DevicesSyncJob.php:123` upserts on Fonus `code` (new UUIDs), while
`app/Jobs/DevicesSyncJob.php:202-212` upserts on Oydera `id` and then **hard-deletes** any device
absent from `easyesale_db.cea_device`. Writing Oydera device ids onto Aselna listing items re-couples
the listing table to that delete loop. This is the same mechanism the 2026-09-07 order-item forensic
flagged.

**P7 — `status` is hardcoded.** `:188` writes `'status' => 1` (`ListingStatus::Active`) for every row.
Items that are suspended or deleted in Oydera are resurrected as Active in Aselna. Compare
`ListingItemSyncJob:106` (`$listingItem->status`) and `ShopifyListingProductsSyncJob:97`
(`$this->getStatus($item->status)`).

**P8 — `sync_status` is copied verbatim** (`:174`) with no mapping to `App\Constants\ListingSyncStatus`.
Oydera's enum is not known to be identical; a wrong value here silently changes what the price /
inventory feed paths do.

**P9 — `store_type_category_id` is given two different meanings.** This job writes Oydera's
`channel_category_id` (`:118`); `addListingItem` writes the local `products.product_type`
(`:815`). One of the two is wrong, and both land in the same column.

**P10 — The new `store_product` is created without `last_synch_date_time`** (`:110-121`). That column
drives the "needs update" predicate in `SubmitShopifyProductsJob:147-158`, so every backfilled row is
immediately queued for a push — before any Shopify mapping exists.

**P11 — Nothing carries the Shopify identity.** The job copies `store_sku` and `store_url` onto
`listing_item` (which is the useful part) but writes **no** `manage_marketplace_products` row, **no**
`listing_item_product_images`, **no** `generated_product_image`, and **no** `listing_item_detail`. So
after a full successful run, `SubmitShopifyProductsJob` still classifies every one of these products as
"never created" (§2.2) and duplicates them on Shopify anyway. **The job as written does not prevent the
problem it was written to prevent.**

Minor, same file: `Catalog::whereIn('product_code', …)->keyBy('product_code')` (`:74`) silently keeps
only the last row if `product_code` is ever non-unique; the store list is hardcoded by display name
(`:44`); and `$localStoreProducts` is keyed by `product_id`, which is correct only because of the
`unique(product_id, store_id)` index.

---

## 4. The two dead sync jobs nobody noticed

`StoreProductSyncJob:82-84` and `ShopifyStoreProductsSyncJob:74-76` both open with:

```php
if (Catalog::where('product_id', $storeProduct->product_id)->doesntExist()) {
    return;
}
```

That compares **Oydera's `store_product.product_id`** (an Oydera product id) against **Aselna's
`products.product_id`**. Under the old lineage that matched; under the Fonus lineage it matches only for
the subset of product rows the legacy job happened to create first (§1.2). For everything else these
jobs silently `return` on every row. They are effectively no-ops today, and they were the jobs
originally responsible for keeping `store_product.id` shared between the two systems. This is the
upstream cause of `store_product.id` divergence.

The correct guard is `Catalog::where('product_code', $oyderaProduct->reference_id1)`.

---

## 5. The resolution

### 5.1 The key insight

**Shopify itself is the authority for what exists in the store.** `FetchShopifyProductsJob` already
walks every product in a Shopify store and, per variant, writes the complete mapping
(`app/Jobs/FetchShopifyProductsJob.php:162-190`): `manage_marketplace_products` with
`upload_status = 'success'` and both GIDs, `listing_item.store_url` / `store_sku`, and every
`listing_item_product_images.reference_id` via `handleMediaImages()`.

It has one prerequisite it cannot satisfy on its own — line 155:

```php
$listingItems = ListingItem::where('sku', $sku)->get();
```

For an Oydera-created variant, no Aselna listing item carries that SKU, so it drops to the fallback
branch which parses `explode('-', $sku)[0]` as a product SKU — a format Oydera SKUs do not follow — and
repairs nothing but the mapping table.

**Therefore the only thing that must be copied from Oydera is the SKU dictionary.** Everything else can
and should be derived from Shopify. This is both less work and far safer than a full row copy, because
it avoids importing eleven stale foreign keys (P5), the wrong status (P7), and the wrong category
semantics (P9).

### 5.2 Target end state, per Shopify variant that Oydera created

```
products                     (already correct — Fonus lineage, joined via product_code)
  └─ store_product           (product_id, store_id)     ← unique index already enforces one row
       └─ listing_item       sku = the SKU that is live on the Shopify variant
            │                store_url / store_sku      ← filled by FetchShopifyProductsJob
            ├─ listing_item_detail          (per device/carrier, optional for Shopify)
            └─ listing_item_product_images  reference_id ← filled by FetchShopifyProductsJob
manage_marketplace_products  marketplace_product_id / _variant_id / upload_status='success'
                                                        ← filled by FetchShopifyProductsJob
generated_product_image      per product_image_id, path under /store-images/<store_id>/
```

### 5.3 Recommended sequence

**Phase 0 — Freeze.** Confirm `auto_generate_listings = 0` on OdeMobile and AccessoryChoice before
anything else, and keep the Odemobile Items Sync row inactive in `scheduled_cron_jobs`.

> **Trap to design around:** `FetchShopifyProductsJob:65-68` and `SubmitShopifyProductsJob:78-82` are
> gated by the *same* `store.auto_generate_listings` flag, and `SubmitShopifyProductsJob` does **not**
> honour the per-store `store_scheduled_cron_jobs` scoping that `FetchShopifyProductsJob` uses via
> `Common::getActiveStoreJobs()` (`app/Helpers/Common.php:86-106`). So you cannot arm the reconciler
> without also arming the duplicate-creator. Options, in order of preference: (a) run the reconciliation
> as a one-off artisan command / tinker script rather than through the scheduler; (b) deactivate the
> `Submit Product to Shopify Store` row in `scheduled_cron_jobs` for the duration; (c) give
> `SubmitShopifyProductsJob` the same per-store scoping. Do not simply flip the flag.

**Phase 1 — Audit (read-only).** Run §6. Establish four counts per store: Oydera listing items with a
populated `store_sku`; how many resolve to an Aselna `products` row via `reference_id1`; how many already
have an Aselna `store_product`; how many already have an Aselna `listing_item` and whether its `sku`
already matches. Nothing should be written until these numbers are understood — in particular, the count
of store products that already carry **more than one** listing item, which tells you whether P1/P4 have
already fired in production.

**Phase 2 — Backfill the dictionary, not the rows.** For each Oydera listing item that has a non-empty
`store_url`/`store_sku` (i.e. is genuinely live on Shopify):

1. Resolve Aselna product: `products.product_code = oydera_product.reference_id1`
   (fall back to `products.product_sku = oydera_product.code`, which is unique).
2. `store_product`: **find or create** on `(product_id, store_id)` — never on Oydera's `store_product.id`.
   Keep Aselna's own id if a row exists. Set `last_synch_date_time = now()` so Phase 4 does not
   immediately re-push (fixes P10). Decide the `store_type_category_id` convention once and apply it
   consistently (P9).
3. `listing_item`: **find or create on `store_product_id`**, not on Oydera's `id` (fixes P1).
   Keep Aselna's existing id. Write only these columns from Oydera:
   `sku`, `store_sku`, `store_url`, `upc`, `mpn`, `title`, `description`, `price`, `quantity`.
   Map `status` through `ListingStatus` and `sync_status` through `ListingSyncStatus` (fixes P7/P8);
   leave every other foreign key at its existing Aselna value, or `NULL` (fixes P5/P6).
   If more than one Oydera listing item competes for the same store product, prefer the one with a
   populated `store_sku`, then the most recently updated (fixes P4).
4. Record the decision. A small mapping/audit table (`oydera_listing_item_id`, `aselna_listing_item_id`,
   `store_id`, `sku_before`, `sku_after`, `action`) makes Phase 5 verifiable and the whole thing
   reversible. This is the single most valuable artefact of the exercise.

**Phase 3 — Reconcile against Shopify.** Run `FetchShopifyProductsJob` for the two stores only. With
Phase 2 done, branch A (`:155`) now hits, and the job fills `manage_marketplace_products`,
`listing_item.store_url`/`store_sku` and `listing_item_product_images.reference_id` from the live store.
This is what actually stops the duplicate creation. Re-run the Phase 1 audit afterwards: every
Oydera-origin listing item should now have a `manage_marketplace_products` row with
`upload_status = 'success'`.

**Phase 4 — Images.** `generated_product_image` is keyed on `product_image_id` alone
(`SubmitShopifyProductsJob:303-305`) even though its `path` embeds the store id
(`/store-images/<store_id>/<product_image_id>.jpg`). Decide deliberately whether to pre-populate it —
leaving it empty simply causes a one-time FTP re-upload of every image, which is safe but slow and
produces new Shopify media if `handleMediaImages` cannot match. Prefer letting Phase 3's
`handleMediaImages` establish the `reference_id` mapping first, then let the FTP upload happen naturally.

**Phase 5 — Unfreeze, one store, watched.** Enable `auto_generate_listings` on **one** store, let a
single `SubmitShopifyProductsJob` cycle run, and verify no `productSet` create-path call fires for an
already-mapped product. Then the second store.

**Phase 6 — Close the holes that let this happen.** Independently of the backfill:

- Fix the `whereNotIn('product_id', $AlreadyCreatedIds)` binding bug (§2.2) so the create/update split
  actually works.
- Fix the `product_id` guard in `StoreProductSyncJob:82` and `ShopifyStoreProductsSyncJob:74` to use
  `product_code` (§4), or retire both jobs explicitly.
- Add `unique(store_product_id)` to `listing_item` — or a documented rule and an ordered
  `->first()` — so P1/P3 cannot recur.
- Resolve the `device` table ownership conflict between `Fonus\DevicesSyncJob` and the legacy
  `DevicesSyncJob::checkAndDelete` (§P6). One system must own that table.
- Consider deriving `listing_item.sku` deterministically from `product_sku + store short_code` instead
  of `Str::random`, so this class of problem cannot return.

### 5.4 What NOT to do

- **Do not** rewrite Aselna's `listing_item.id` to Oydera's id. `order_item.listing_item_id` carries an
  `ON DELETE CASCADE` FK to `listing_item` in production (verified in the 2026-09-07 forensic); a
  delete-and-recreate to change a primary key would cascade order items away irrecoverably. Keep
  Aselna's ids and change the `sku`.
- **Do not** copy Oydera's `device_id` / `brand_id` / `manufacturer_id` / `color_id` (§P5, §P6).
- **Do not** run the current `OdemobileListingItemSyncJob` against production as-is — P1 plus P2 means
  it will silently double the `listing_item` table for those two stores, and P11 means it will not stop
  the Shopify duplication anyway.
- **Do not** enable `auto_generate_listings` to make the reconciler run (§5.3 Phase 0 trap).

---

## 6. Audit queries to run before anything is written

Aselna is MySQL; Oydera is PostgreSQL with the `oydera_` table prefix. These are cross-database, so run
each side separately and compare, or stage the Oydera side into a scratch table.

### Q1 — Oydera: what is actually live on Shopify for these stores? *(PostgreSQL)*

```sql
SELECT s.name              AS store_name,
       COUNT(*)                                                        AS listing_items,
       COUNT(li.store_sku)                                             AS with_store_sku,
       COUNT(DISTINCT sp.id)                                           AS store_products,
       COUNT(DISTINCT p.reference_id1)                                 AS distinct_fonus_products,
       SUM(CASE WHEN p.reference_id1 IS NULL THEN 1 ELSE 0 END)        AS missing_fonus_link
FROM   oydera_listing_item  li
JOIN   oydera_store_product sp ON sp.id = li.store_product_id
JOIN   oydera_product       p  ON p.id  = sp.product_id
JOIN   oydera_store         s  ON s.id  = sp.store_id
WHERE  s.name IN ('OdeMobile','AccessoryChoice')
GROUP  BY s.name;
```

`missing_fonus_link` is the population that **cannot** be reconciled automatically and will need a
manual decision.

### Q2 — Oydera: store products carrying more than one listing item *(PostgreSQL)*

This is the size of problem P4 — how often a selection rule is actually needed.

```sql
SELECT sp.store_id, COUNT(*) AS items_per_store_product
FROM   oydera_listing_item li
JOIN   oydera_store_product sp ON sp.id = li.store_product_id
JOIN   oydera_store s ON s.id = sp.store_id
WHERE  s.name IN ('OdeMobile','AccessoryChoice')
GROUP  BY sp.store_id, li.store_product_id
HAVING COUNT(*) > 1
ORDER  BY 2 DESC
LIMIT  50;
```

### Q3 — Aselna: current state of the same two stores *(MySQL)*

```sql
SELECT st.name                                                          AS store_name,
       COUNT(DISTINCT sp.id)                                            AS store_products,
       COUNT(DISTINCT li.id)                                            AS listing_items,
       SUM(CASE WHEN li.store_sku IS NULL THEN 1 ELSE 0 END)            AS listing_items_no_store_sku,
       COUNT(DISTINCT mmp.id)                                           AS marketplace_mappings,
       SUM(CASE WHEN mmp.upload_status = 'success' THEN 1 ELSE 0 END)   AS mapped_success
FROM   store st
JOIN   store_product sp ON sp.store_id = st.id
LEFT   JOIN listing_item li ON li.store_product_id = sp.id
LEFT   JOIN manage_marketplace_products mmp
            ON mmp.product_id = sp.product_id AND mmp.store_id = st.id
WHERE  st.name IN ('OdeMobile','AccessoryChoice')
GROUP  BY st.name;
```

If `mapped_success` is ~0 while `listing_items` is large, §2.2 is confirmed: every product is in the
"create" set.

### Q4 — Aselna: has P1/P4 already fired? *(MySQL)*

```sql
SELECT sp.store_id, li.store_product_id, COUNT(*) AS listing_items,
       GROUP_CONCAT(li.id)  AS ids,
       GROUP_CONCAT(li.sku) AS skus
FROM   listing_item li
JOIN   store_product sp ON sp.id = li.store_product_id
JOIN   store st ON st.id = sp.store_id
WHERE  st.name IN ('OdeMobile','AccessoryChoice')
GROUP  BY sp.store_id, li.store_product_id
HAVING COUNT(*) > 1;
```

**Any rows here mean the current job has already created duplicates and they must be de-duplicated
before Phase 2.** Keep the row referenced by `order_item.listing_item_id`; never delete one that is,
because of the cascade.

### Q5 — Aselna: how many orders are already failing to attach *(MySQL)*

```sql
SELECT DATE(oi.created_at) AS day,
       COUNT(*)                                                      AS order_items,
       SUM(CASE WHEN oi.listing_item_id IS NULL THEN 1 ELSE 0 END)   AS unlinked
FROM   order_item oi
JOIN   `order` o ON o.id = oi.order_id
JOIN   store st  ON st.id = o.store_id
WHERE  st.name IN ('OdeMobile','AccessoryChoice')
  AND  oi.created_at >= NOW() - INTERVAL 60 DAY
GROUP  BY DATE(oi.created_at)
ORDER  BY 1 DESC;
```

This quantifies the business impact of §2.1 and is the metric that should go to zero after Phase 3.

### Q6 — The bridge, spot-checked on one SKU *(run both halves)*

PostgreSQL:
```sql
SELECT li.id, li.sku, li.store_sku, li.store_url, li.status,
       p.code AS product_sku, p.reference_id1 AS fonus_product_id
FROM   oydera_listing_item li
JOIN   oydera_store_product sp ON sp.id = li.store_product_id
JOIN   oydera_product p ON p.id = sp.product_id
WHERE  li.sku = 'BD3EE35D';
```
MySQL, using the `reference_id1` returned above:
```sql
SELECT p.product_id, p.product_code, p.product_sku, p.product_status,
       sp.id AS store_product_id, li.id AS listing_item_id, li.sku, li.store_sku
FROM   products p
LEFT   JOIN store_product sp ON sp.product_id = p.product_id
LEFT   JOIN listing_item  li ON li.store_product_id = sp.id
WHERE  p.product_code = '<fonus_product_id from above>';
```

If the Aselna `li.sku` differs from `BD3EE35D`, that single row is the whole problem in miniature.

---

## 7. Scope confirmation — the tables that matter for Shopify

The user's stated scope is "store product, listing item, listing item images, listing item detail — whatever
is used for Shopify syncing and feed generation". Verified against the code, that set is:

| Table | Used by | Needed for the backfill? |
|---|---|---|
| `products` | everything | No — already correct via Fonus `product_code` |
| `store_product` | `SubmitShopifyProductsJob:373,547,776,813` | **Yes** — find-or-create on `(product_id, store_id)` |
| `listing_item` | `SubmitShopifyProductsJob:829`, `FetchShopifyOrders:283`, `FetchShopifyProductsJob:155`, `ShopifyService` | **Yes** — the `sku` is the whole point |
| `manage_marketplace_products` | `SubmitShopifyProductsJob:108,552,775` | **Yes** — but derive it from Shopify (Phase 3), not from Oydera |
| `listing_item_product_images` | `SubmitShopifyProductsJob:623-760`, `ShopifyService:104` | **Yes** — derive from Shopify (Phase 3) |
| `generated_product_image` | `SubmitShopifyProductsJob:272-308,438` | Optional — see Phase 4 |
| `listing_item_detail` | `ListingItemDetailStatusCheckJob`, listing screens | Low priority for Shopify; Shopify uses one variant per product (`productOptions` = `Default Title`, `:430-435`) |
| `listing`, `listing_item_link` | feed-based marketplaces (`ShopifyService::SubmitCreateFeed:65`, `submitInventoryFeed:324`, `submitPriceFeed:407`) | Only if the OdeMobile/AccessoryChoice stores are driven through the Feed screen rather than `SubmitShopifyProductsJob`. **Confirm which path these two stores actually use before deciding.** |

---

## 8. Open questions that need a human answer

1. **Which push path do OdeMobile and AccessoryChoice use** — `SubmitShopifyProductsJob` (catalog-driven,
   one variant per product), or the Feed screen via `ShopifyService::generateFeed` /
   `ListingItemLink` (listing-driven)? The answer decides whether `listing`/`listing_item_link` are in
   scope at all (§7).
2. **Is Oydera still writing to these Shopify stores?** If both systems remain live against the same
   store, reconciliation is a moving target and one of them must stop first.
3. **`store_type_category_id`** — should it hold a `store_type_category.id` or a `products.product_type`?
   The two writers disagree (§P9).
4. **Which Oydera listing item is canonical** when a store product has several (Q2)? The proposed rule is
   "populated `store_sku`, then most recently updated" — please confirm.
5. **Are Oydera's `status` and `sync_status` enums identical to `App\Constants\ListingStatus` and
   `ListingSyncStatus`?** If not, the mapping must be written out explicitly (§P7, §P8).
6. **Do `SHOPIFY_STORE_SETUP.md` / `SHOPIFY_SYNC_VERIFICATION.md` need correcting?** They reference
   `listing_items`, `product_images` and `listing_item_product_images` as if those were the table names;
   the real tables are `listing_item`, `product_images` and `listing_item_product_images`, and
   `GenerateProductImage` maps to `generated_product_image`, not `generate_product_images`. The queries
   in those documents will not run as written.

---

## 9. Reference — the code that matters

```
IDENTITY
  app/Models/Catalog.php:21-30                       fresh UUID on create
  app/Models/StoreProduct.php:19-28                  fresh UUID on create
  app/Models/ListingItem.php:16-25                   fresh UUID on create
  app/Models/StoreTypeListing.php:93-109             RANDOM sku  ← root cause
  app/Jobs/Fonus/ProductSyncJob.php:122-124          products keyed on Fonus product_code
  app/Jobs/ProductSyncJob.php:202-216                legacy: products keyed on Oydera id
  app/Jobs/StoreSyncJob.php:96-99                    store.id IS shared

THE BROKEN COPY
  app/Jobs/OdemobileListingItemSyncJob.php:57-64     the reference_id1 bridge (correct)
  app/Jobs/OdemobileListingItemSyncJob.php:85-88     wrong duplicate check          (P1)
  app/Jobs/OdemobileListingItemSyncJob.php:103-128   store product creation         (P9,P10)
  app/Jobs/OdemobileListingItemSyncJob.php:159-211   the upsert                     (P5,P7,P8,P11)

THE DEAD JOBS
  app/Jobs/StoreProductSyncJob.php:82-84             guard compares the wrong column
  app/Jobs/ShopifyStoreProductsSyncJob.php:74-76     same

WHAT DECIDES CREATE-VS-UPDATE
  app/Jobs/SubmitShopifyProductsJob.php:108-118      manage_marketplace_products + binding bug
  app/Jobs/SubmitShopifyProductsJob.php:365-399      createProduct / SKU minting
  app/Jobs/SubmitShopifyProductsJob.php:811-848      addListingItem (Aselna's own ids)

THE FIX ALREADY IN THE REPO
  app/Jobs/FetchShopifyProductsJob.php:63-68         auto_generate_listings gate    (the trap)
  app/Jobs/FetchShopifyProductsJob.php:155-192       branch A — full reconciliation
  app/Jobs/FetchShopifyProductsJob.php:194-232       branch B — degraded fallback
  app/Helpers/Common.php:86-106                      per-store job scoping

IMPACT
  app/Jobs/FetchShopifyOrders.php:283-310            orders join on listing_item.sku
  docs/analysis/2026-09-07-missing-shopify-order-item-analysis.md   cascade-delete forensic
```
