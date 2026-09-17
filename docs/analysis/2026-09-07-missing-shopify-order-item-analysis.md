# Missing `order_item` row on Shopify order #215034 — root cause analysis (v2)

**Date:** 2026-09-07
**Subject:** `app/Jobs/FetchShopifyOrders.php` — an `order_item` row is created and logged, and later is not in the database.
**Status:** Analysis only. **No code was changed.**

> **v2 supersedes v1.** My first pass concluded the row was never inserted (a unique-index
> collision). The production log you supplied disproves that: `order_item_id` was echoed back, so
> the row existed. That theory is dead. What follows is rebuilt from the new evidence.
![img.png](img.png)
---

## 1. The incident, stated precisely

**Shopify order `#215034` / `7092512620738`, OdeMobile, 2026-09-01 14:24 (Shopify) → imported 13:30:05.**

Shopify shows **three** line items:

| # | Title | Shopify SKU | Price | Custom attributes |
|---|---|---|---|---|
| 1 | 10000mAh Power Bank … ONG38 | `B41627ED` | 32.99 | — |
| 2 | Magnetic Case Cover … ONXW95 | `BD3EE35D` | 14.99 | Device Name: iPhone 14, Device Code: 4316 |
| 3 | Compact Fold-up Travel Holder | `P20` | 0.00 | — |

`order_item` now holds **two** rows for order `f27ebd8b-a2b3-4b98-9941-14f973bf1752`:

| id | sku | product_id | listing_item_id | client_id | id1 | created_at |
|---|---|---|---|---|---|---|
| `2d89f142-2977-462c-b28e-78e0319bf930` | `G38` | `e0a63e06-…` | `8db0525c-…` | `982dac34-…` | LineItem/16213017067714 | 13:30:05 |
| `6ce86920-7f76-469d-9eff-0f2f35c3fdbb` | `P20` | `967ee2b2-…` | **NULL** | **NULL** | LineItem/16213017133250 | 13:30:05 |

Item #2 is gone. Its id was **`e87fb727-caac-4f75-b2c0-30ce8717f1da`**, proven by the production log
at the same second:

```
[2026-09-01 13:30:05] production.INFO: Custom Attributes:
{"key":"Device Code","value":"4316","order_item_id":"e87fb727-caac-4f75-b2c0-30ce8717f1da"}
```

That line is `FetchShopifyOrders.php:364`, which sits **after** `OrderItem::updateOrCreate` (line 318)
and inside `if ($order_item->id)` (line 324). So at 13:30:05 the row was committed and had an id.

Two further facts follow from the surviving rows:

- Item #3 was processed **after** item #2 and succeeded → the line-items loop ran to completion,
  no exception was thrown. The import of this order finished normally.
- Both survivors carry the original `created_at` of `13:30:05` → **the `order` row was never
  deleted and recreated.** Only one child row went.

**So: the row was created, and later something removed it.**

---

## 2. What can remove an `order_item` row — complete audit

### 2.1 Nothing in the application deletes one

I enumerated **all 113** `->delete()` / `::destroy()` / `forceDelete()` / `truncate()` / `DB::delete()`
call sites in `app/`, including multi-line chained calls (the kind that hide from a naive grep, e.g.
`FetchShopifyProductsJob.php:257-260`).

```
OrderItem::…->delete()          → 0 occurrences
$order->orderItem()->delete()   → 0 occurrences
DELETE FROM order_item (raw)    → 0 occurrences
DB::delete(…)                   → 0 occurrences anywhere
```

`App\Models\OrderItem` has **no** `SoftDeletes`, **no** `deleting`/`deleted` hook, and there are
**no database triggers** (`SHOW TRIGGERS` → empty). So this is a genuine physical `DELETE`, and it
did not originate from a statement any of our code wrote.

Your statement "no delete query ran from our application" is therefore correct — and it is exactly
what the remaining explanation predicts.

### 2.2 Why `delete_queries.log` proves nothing here

`AppServiceProvider` installs a `DB::listen` that logs every `DELETE` to
`storage/logs/delete_queries.log`. **`DB::listen` only sees statements the PHP process sent.** A
MySQL `ON DELETE CASCADE` is executed inside the storage engine as a side effect of a delete on the
*parent* table. It is never reported to PHP, never hits the listener, and never appears in that log.

An empty `delete_queries.log` is consistent with — in fact expected from — a cascade.

### 2.3 The five ways the row could go

Verified against the live schema (`information_schema`):

```
FOREIGN KEYS ON order_item:
  order_id         -> order.id             ON DELETE CASCADE   UPDATE NO ACTION
  product_id       -> products.product_id  ON DELETE CASCADE   UPDATE NO ACTION
  listing_item_id  -> listing_item.id      ON DELETE CASCADE   UPDATE NO ACTION
  image_id         -> sys_file.id          ON DELETE CASCADE   UPDATE NO ACTION
  thumbnail_id     -> sys_file.id          ON DELETE CASCADE   UPDATE NO ACTION
```

Eliminating them one by one:

| Parent | Verdict |
|---|---|
| `order` | **Ruled out.** Both siblings survive with the original `created_at`. |
| `sys_file` (`image_id`) | **Ruled out.** `image_id` is NULL on every one of the 10 753 `order_item` rows. |
| `sys_file` (`thumbnail_id`) | **Ruled out.** Same — 0 rows populated. |
| `products` | **Live candidate.** |
| `listing_item` | **Live candidate.** |

Note also that `order_item.client_id` is declared with an FK in the create migration but **does not
have one in the database** — so a `client` deletion cannot touch it.

The survivors constrain this further. Item #3 (`P20`) has `listing_item_id = NULL`, so it is immune
to the `listing_item` cascade but fully exposed to the `products` cascade — and it survived. Item #1
has both set and survived. So whichever parent went, it was **the specific parent row belonging to
the Magnetic Case (`BD3EE35D` / `ONXW95`)**, not a shared one.

**Conclusion: the `products` row or the `listing_item` row for `ONXW95` was deleted, and MySQL
cascaded that into `order_item`, `order_operation_item` and `order_item_attribute`.**

(Those last two also cascade, which is why no orphan or audit trail survives on our side — every
table that references `order_item` is `ON DELETE CASCADE`.)

---

## 3. Three queries that settle it — please run these on production

### Q1 — Is the row actually deleted, or merely re-parented?

```sql
SELECT * FROM order_item WHERE id = 'e87fb727-caac-4f75-b2c0-30ce8717f1da';
```

If this returns a row, nothing was deleted at all and the whole cascade theory is off — the row was
moved to another `order_id` or had its `sku` rewritten. **Run this first; it is free and it forks the
entire investigation.**

### Q2 — Does the listing item still exist?

```sql
SELECT id, sku, store_sku, status, store_product_id, device_id, created_at, updated_at
FROM   listing_item
WHERE  sku = 'BD3EE35D';
```

### Q3 — Does the catalog product still exist?

```sql
SELECT product_id, product_sku, product_title, manufacturer_id, product_status, created_at, updated_at
FROM   products
WHERE  product_sku IN ('XW95', 'ONXW95')
   OR  product_title LIKE '%Magnetic Case Cover Armor Drop-Proof Defender%';
```

**Reading the result:**

- **Q2 empty, Q3 has a row** → the `listing_item` was deleted. Go to §5.1.
- **Q3 empty** → the `products` row was deleted. Go to §5.2, and check its manufacturer.
- **Both present** → neither cascade fired. The row was deleted by something outside the
  application — a manual query, a migration/rollback, a restore, or another client. Go to §4.

For contrast, run the same two queries for the SKU that survived (`B41627ED`, product `G38`) — the
difference between the surviving and the missing item is the whole answer.

---

### 3.1 — IMPORTANT: "the product still exists" does not clear the products cascade

**A row can be deleted and recreated.** The cascade fires on the delete; the recreate hides it.

[`Fonus\ProductSyncJob:122`](app/Jobs/Fonus/ProductSyncJob.php:122) rebuilds products with

```php
Catalog::updateOrCreate(['product_code' => $product->product_id], [ … ]);
```

and `Catalog::boot()` assigns a **fresh `Str::uuid()`** to `product_id` on create. So if a
`products` row is deleted, the very next sync run reinstates it — same `product_sku`, same
`product_code`, same title — under a **brand-new `product_id`**. Every `order_item` that pointed at
the old UUID is already gone, and cannot be relinked.

`ProductSyncJob` sits in the **same chain** as `ManufacturerSyncJob`, four positions later
([`FonusCombinedSyncJob:71-74`](app/Jobs/Sync/FonusCombinedSyncJob.php:71)). A manufacturer whose
`code` churns upstream is deleted (cascading its products and their order items away) and its
products are re-created minutes later in the same chain run.

The same argument applies to `listing_item`: `ListingItemSyncJob:80` and
`OdemobileListingItemSyncJob:161` upsert on `['id' => $source->id]`, so a row that reappears under a
new upstream id is a new row.

**So the question is not "does it exist" but "is it the same row".** The test:

```sql
SELECT p.product_sku, p.product_id, p.created_at,
       COUNT(oi.id)       AS order_items_ever,
       MIN(oi.created_at) AS oldest_order_item
FROM   products p
LEFT   JOIN order_item oi ON oi.product_id = p.product_id
WHERE  p.product_sku IN ('XW95','ONXW95','G38','P20')
GROUP  BY p.product_id;
```

If `G38` and `P20` show order items stretching back months while `XW95` shows zero — or a
`MIN(created_at)` that starts abruptly at a recent date — the row was replaced, and that date is
when the cascade fired.

### 3.2 Circumstantial evidence in favour of the `listing_item` path

The three line items differ in exactly the way this theory predicts:

| item | listing_item_id | device-specific? | outcome |
|---|---|---|---|
| #1 Power Bank | set (`8db0525c-…`) | no — universal accessory | survived |
| #2 Magnetic Case | almost certainly set | **yes — iPhone 14 / code 4316** | **deleted** |
| #3 Travel Holder | **NULL** | no | survived |

Item #3 is structurally immune to the `listing_item` cascade and survived. Item #2 is the only
device-bound listing, and it is the one that went.

Confirmed in the local snapshot: `device` code **`4316` = "iPhone 14"** — precisely the Device Code
in your log line. Also confirmed: the listing item for the *surviving* SKU `B41627ED` has
`device_id = NULL`.

### 3.3 A live delete/recreate loop on `device`

Two active schedules disagree about who owns the `device` table:

- [`Fonus\DevicesSyncJob`](app/Jobs/Fonus/DevicesSyncJob.php:123) — in `FonusCombinedSyncJob`,
  **every 2 hours** — upserts on `['code' => …]`, so a device new to Fonus is created with a
  **locally generated UUID**.
- [`DevicesSyncJob::checkAndDelete`](app/Jobs/DevicesSyncJob.php:207) — in `ShopifyCombinedSyncing`,
  **daily 12:30** — walks *every* local device and hard-deletes any whose `id` is absent from
  `easyesale_db.cea_device`:

  ```php
  if (DB::connection('easyesale_db')->table('cea_device')->where('id', $device->id)->doesntExist()) {
      DeviceInterface::where('device_id', $device->id)->delete();
      $device->delete();
  }
  ```

A device created by the Fonus job carries an id the legacy system has never heard of, so the next
day's run deletes it. When easyesale eventually catches up, the device returns **with a different
id**. In the local snapshot 5 of 3 125 devices are currently in that doomed state — all
newly-launched models (Galaxy S25 Edge, Galaxy S26 Plus, Moto G 2026, Galaxy XCover7 Pro,
iPad Air 11" 2025).

Whether this reaches `order_item` depends on one thing: **does production have an FK from
`listing_item.device_id` (or `store_product_id`) with `ON DELETE CASCADE`?** The local copy has
**zero** foreign keys on `listing_item`, so locally it would not cascade — but the local schema has
already been shown to drift from the migrations, so this must be read from production. Query 3 in
[`order-item-forensics.sql`](docs/analysis/order-item-forensics.sql) does exactly that.

Note also that in this snapshot iPhone 14 (`4316`) is **not** currently a deletion candidate, so if
the FK does not exist in production this path is out and §4 becomes the way to settle it.

---

## 4. The definitive method: read the MySQL binary log

This is the one technique that gives a certain answer instead of an inference. If binlogs are
retained for 2026-09-01 onward and `binlog_format = ROW` (Laravel/MySQL 8 default), **cascade
deletes are recorded as row events on `order_item`**, inside the same transaction as the parent
statement that caused them.

```bash
mysql -e "SELECT @@binlog_format, @@binlog_expire_logs_seconds; SHOW BINARY LOGS;"
```

```bash
mysqlbinlog --base64-output=DECODE-ROWS -v \
  --start-datetime='2026-09-01 13:30:00' \
  /var/log/mysql/mysql-bin.0* \
  | grep -n -B 60 'e87fb727-caac-4f75-b2c0-30ce8717f1da'
```

The 60 lines of context before the `order_item` row event will name the parent table and the exact
statement — `DELETE FROM listing_item …`, `DELETE FROM products …`, `DELETE FROM manufacturer …`, or
something ad hoc — together with its timestamp, thread id and server id. That tells you *when* and
*from where*, which is what you actually need.

If binlogs are already rotated away, §5 lists the only candidates and §6 finds the full blast radius.

---

## 5. Who can delete each candidate parent

### 5.1 `listing_item`

**No application code deletes a `listing_item` row.** I checked every branch and every delete call
site. `listing_item` also has **zero incoming foreign keys**, so nothing cascades into it either.

If Q2 comes back empty, the deletion came from **outside the application** — manual SQL, a cleanup
script, or the legacy source system operating on the same schema.

There is one suggestive data point, offered as a lead rather than a finding. In the local database
snapshot (data through ~Feb–Apr 2026), SKU `B41627ED` for OdeMobile is listing item
`b5dd58ed-6696-63d0-459e-3026ae0043b3`, created 2023-12-22. In production on 2026-09-01 the same SKU
in the same store resolved to listing item `8db0525c-28c1-4a06-88eb-7778938fe711` — **a different
id**, and one that does not exist in the snapshot. Either listing items are being replaced (old row
destroyed, new row inserted with a new UUID), or duplicates now coexist. Worth checking:

```sql
SELECT id, sku, status, store_product_id, created_at, updated_at
FROM   listing_item
WHERE  sku IN ('B41627ED','BD3EE35D')
ORDER  BY created_at;
```

If listing items are genuinely being replaced in production, then **`order_item.listing_item_id
ON DELETE CASCADE` silently destroys order history every time it happens** — and only the 963-in-
10 753 order items that have a `listing_item_id` are affected, which matches the pattern here
exactly (the `NULL`-listing item #3 survived).

Relevant: `ListingItemSyncJob:80` and `OdemobileListingItemSyncJob:161` both do
`ListingItem::updateOrCreate(['id' => $source->id], …)` — listing-item primary keys are dictated by
the **legacy source database**, not generated locally. If the upstream system re-issues an id, we
insert a new row; if anything then removes the stale one, every historical order item pointing at it
dies with it.

### 5.2 `products`

Exactly two paths delete a `products` row:

**(a) The admin UI.** [`CatalogController::destroy`](app/Http/Controllers/CatalogController.php:789)
— password-confirmed, so it is a deliberate human action. Check who deleted a catalog product around
that date.

**(b) A cascade from `manufacturer` — automated, every two hours.**

```
Fonus\ManufacturerSyncJob::checkAnddelete()
  Manufacturer::where('code', $code)->delete()        app/Jobs/Fonus/ManufacturerSyncJob.php:119
    └─ products.manufacturer_id       ON DELETE CASCADE
         └─ order_item.product_id     ON DELETE CASCADE
              ├─ order_operation_item.order_item_id   CASCADE
              └─ order_item_attribute.order_item_id   CASCADE
```

Confirmed in the live schema:

```
REFERENCING manufacturer:  products.manufacturer_id       ON DELETE CASCADE
                           store_product.manufacturer_id  ON DELETE CASCADE
REFERENCING products:      order_item.product_id          ON DELETE CASCADE
```

Neither `Manufacturer` nor `Catalog` uses `SoftDeletes`, so `->delete()` is a hard delete and the
cascade fires. The job is chained from
[`FonusCombinedSyncJob`](app/Jobs/Sync/FonusCombinedSyncJob.php:71), scheduled **active** in
`scheduled_cron_jobs` as **"Shopify Only Sync"**, cron `10 */2 * * *`.

`checkAnddelete()` deletes any local manufacturer whose `code` is absent from
`fonus_db.tbl_manufacturer`:

```php
$existingCodes = DB::connection('fonus_db')->table('tbl_manufacturer')
                   ->pluck('manufacturer_id')->toArray();

foreach ($manufacturers as $manufacturer) {
    if (isset($manufacturer->code) && !in_array($manufacturer->code, $existingCodes)) {
        Manufacturer::where('code', $manufacturer->code)->delete();
    }
}
```

**10 730 of 10 753 order items (99.8 %) carry a `product_id`**, so essentially all order history is
exposed to this path. Two aggravating details:

- If the `fonus_db` query ever returns empty or partial (connection blip, renamed table, permissions),
  `$existingCodes` is empty and **every** manufacturer is deleted — which would take `products`,
  `store_product` and the entire `order_item` history with it.
- `Manufacturer::orderBy('code')->chunk(100, …)` deletes *while* chunking, which shifts the offset
  window and skips rows — the classic chunk-while-deleting bug. It under-deletes, so it masks the
  blast radius rather than enlarging it.

The only trace it leaves in our logs is one line per manufacturer:

```bash
grep -n "Deleting Manufacturer ID" storage/logs/laravel*.log storage/logs/archived/*
```

Nothing logs the cascaded products or order items. `delete_queries.log` shows only
`delete from manufacturer where code = ?`.

---

## 6. Find every affected order, not just this one

Whichever parent it was, the cascade would have hit **every** order in history that referenced it.
This query enumerates the damage by comparing each order's stored total against the sum of its
surviving items:

```sql
SELECT o.order_id,
       o.purchase_order_id,
       s.name                                   AS store,
       o.order_date,
       o.order_total,
       COUNT(oi.id)                             AS items,
       ROUND(SUM(oi.item_price * oi.quantity),2) AS items_total
FROM   `order` o
JOIN   store s     ON s.id = o.store_id
LEFT   JOIN order_item oi ON oi.order_id = o.id
WHERE  o.order_date >= '2026-06-01'
GROUP  BY o.id
HAVING items = 0
    OR items_total IS NULL
    OR items_total < (o.order_total * 0.75)
ORDER  BY o.order_date DESC;
```

For order #215034 this yields: `order_total` 52.97 (47.98 goods + 4.99 shipping), `items_total`
32.99 — a 14.99 hole, exactly the Magnetic Case.

Two supporting queries:

```sql
-- orders with no items at all
SELECT o.order_id, s.name, o.order_total, o.created_at
FROM   `order` o
JOIN   store s ON s.id = o.store_id
LEFT   JOIN order_item oi ON oi.order_id = o.id
WHERE  oi.id IS NULL
ORDER  BY o.created_at DESC;
```

```sql
-- shipments whose items all vanished (the order_operation survives; its items cascade away)
SELECT oo.id, oo.order_id, oo.status, oo.created_at
FROM   order_operation oo
LEFT   JOIN order_operation_item ooi ON ooi.order_operation_id = oo.id
WHERE  ooi.id IS NULL
  AND  oo.created_at >= '2026-06-01';
```

The **shape** of the result set is diagnostic: a handful of orders clustered around one SKU points at
a single `listing_item` or `products` deletion; hundreds of orders spread across one manufacturer's
products points at the `ManufacturerSyncJob` cascade.

---

## 7. Recommendations (not applied — for your review)

Ordered by impact.

1. **Order history must not be destructible by a catalogue sync.** Change
   `order_item.product_id` and `order_item.listing_item_id` from `ON DELETE CASCADE` to
   `ON DELETE SET NULL` (both columns are already nullable, and both are only used for enrichment —
   `title`, `sku`, `quantity` and `item_price` are denormalised onto `order_item` already). Same for
   `order_item.image_id` / `thumbnail_id`. This single change makes the entire class of incident
   impossible, whatever the trigger turns out to be.

2. **Guard `Fonus\ManufacturerSyncJob::checkAnddelete()`.** Return early when `$existingCodes` is
   empty; abort if the delete set exceeds a sane fraction of the table; log the dependent
   `products` / `order_item` counts before deleting; and replace `chunk()` with `chunkById()`.

3. **Make cascades visible.** The `DB::listen` delete-logger structurally cannot see them. Before any
   `->delete()` on a table that is a cascade parent (`manufacturer`, `products`, `listing_item`,
   `store`, `client`, `order`), log the row and the count of dependants that will go with it.

4. **Add an integrity check to the schedule.** A daily job running the §6 query and reporting through
   `CriticalErrorService` would have surfaced this on 2026-09-02 instead of days later.

5. **Confirm the production schema matches the migrations.** The local database is missing the
   `order_item.client_id` foreign key that
   [`2025_02_03_061043_create_order_item_table.php`](database/migrations/2025_02_03_061043_create_order_item_table.php)
   declares — so the schema has drifted somewhere. Run the FK query from §2.3 **on production** before
   relying on my elimination table; production may have cascade edges the local copy lacks (a
   `listing_item.store_product_id` or `listing_item.device_id` FK would open further paths, since
   `DevicesSyncJob.php:211` and `Fonus/DeviceTypeSyncJob.php:119` do delete devices and device types).

6. **Retain binlogs longer.** `binlog_expire_logs_seconds` set to at least 14 days turns "we cannot
   tell what deleted it" into a five-minute lookup.

---

## 8. What changed from v1, and why

v1 argued the row was never inserted, based on two orders from 2026-01-24 that have an `order` and an
`order_operation` but zero `order_item` rows, with `shipping_carrier_service_id` NULL while every
sibling in the batch had one — a signature of the job aborting inside the line-items loop.

Your log kills that explanation **for this order**: item #2's id was echoed after insertion, and
item #3 was created afterwards, so nothing threw.

Those January orders may still be a *separate* real defect (`FetchShopifyOrders` has no per-order
`try/catch` — the `catch` at line 474 wraps both loops, so one bad order silently skips every order
behind it, and line 137's `continue` means a later run never repairs it). But they are not this
incident, and I should not have generalised from them. Treat §1–§7 above as the analysis of the
reported problem.

---

## 9. Files referenced

- [app/Jobs/FetchShopifyOrders.php](app/Jobs/FetchShopifyOrders.php) — lines 277-376 (item loop), 318 (insert), 324, 364 (the log line), 474 (the catch)
- [app/Jobs/Fonus/ManufacturerSyncJob.php](app/Jobs/Fonus/ManufacturerSyncJob.php) — lines 45-54, 109-125
- [app/Jobs/Sync/FonusCombinedSyncJob.php](app/Jobs/Sync/FonusCombinedSyncJob.php) — line 71
- [app/Http/Controllers/CatalogController.php](app/Http/Controllers/CatalogController.php) — line 789
- [app/Jobs/ListingItemSyncJob.php](app/Jobs/ListingItemSyncJob.php) — line 80
- [app/Jobs/OdemobileListingItemSyncJob.php](app/Jobs/OdemobileListingItemSyncJob.php) — line 161
- [database/migrations/2025_02_03_061043_create_order_item_table.php](database/migrations/2025_02_03_061043_create_order_item_table.php) — the cascade definitions

## 10. How this was verified

Schema and data facts were read from the configured `mysql` connection with read-only queries
(`information_schema`, `SHOW INDEX`, `SHOW TRIGGERS`, `SELECT`). **That connection is a local
snapshot (`laravel_aselna`, orders through 2026-02-23) — not production.** Everything in §3, §4 and
§6 needs to be run against production. No data was modified and no source file was changed.
