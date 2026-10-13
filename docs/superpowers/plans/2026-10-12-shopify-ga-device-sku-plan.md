# Plan — Combination SKU in the Shopify product-page GA/GTM event

**Date:** 2026-10-12
**Goal:** When a shopper opens a product page on the Shopify store, the GA/GTM event carries the product+device
combination SKU (`ListingItemDetail.sku`, e.g. `A20-K3JQZP`) that the feeds use.
**SKU source (decided 2026-10-12):** `ListingItemDetail.sku` of the **same** `ListingItem` that is the Shopify
product. This is the value `ProcessFeedGenerationJob` writes as the feed `id`. No other store and no store mapping
are involved.
**Design:** `docs/superpowers/specs/2026-10-12-shopify-ga-device-sku-design.md`. Read it first for the why.
**Status:** Steps 3-6 and 8 are implemented (uncommitted, on `update_tracking_implementation`). Migration not run yet.
Steps 0.2-0.4, 1, 4.3, 7 (theme) and 9 (rollout) are still open.

### Implementation notes (how the code differs from the plan below)

- **Feed trigger limited to NevaWireless (2026-10-13).** `ProcessFeedGenerationJob` only dispatches the job when
  `SyncShopifyDeviceSkuMetafieldsJob::shouldRunAfterFeed($store_id)` is true, i.e. the store is in
  `config('services.shopify.device_sku_feed_store_ids')` (env `SHOPIFY_DEVICE_SKU_FEED_STORE_IDS`, comma separated;
  default `c2c8131f-c8a2-e082-9f89-3e8bf96b1049`, NevaWireless shopify). This is so a feed of any other site never
  pushes by accident. Manual runs (Saved Jobs / Cron Jobs Run Now) are **not** limited: the picker still lists every
  Shopify store, and the job runs for whatever stores it is given. To enable another site's feed trigger, add its
  Shopify store id.
- `store_sku` holds the full GID (`gid://shopify/Product/…`) for products Aselna created, and the bare numeric id
  for products from `FetchShopifyProductsJob` / the oydera sync. `SyncShopifyDeviceSkuMetafieldsJob::productGid()`
  converts both to the GID.
- The job is store scoped: it implements `StoreScopedJob` and uses `ScopesToStores`, so Saved Jobs / Cron Jobs
  "Run Now" shows the store picker (Shopify stores only). The constructor takes `string|array|null $storeIds`:
  the feed trigger passes the one store id, the popup passes the chosen ids. With none given, it uses the cron store
  mapping, else every active Shopify store. `uniqueId()` is the sorted store ids, or `all`.
- The dispatch in `ProcessFeedGenerationJob` is guarded: it only queues the job when the feed's store type code is
  `shopify` (`dispatchDeviceSkuMetafieldSync()`), so other stores' feeds don't queue no-op jobs.
- If a product had a pushed map and now has no details, `{}` is pushed. Products that never had a map and have none
  now are skipped.
- `MAX_VALUE_BYTES` is set to 128 KB, and Step 1 should still confirm it. Over-limit products are reported through
  `CriticalErrorService::report()` and counted as `too_large`.
- The seeder adds a Saved Jobs row. The daily cron is still a `scheduled_cron_jobs` row to add via
  `jobs:sync-registry` and activate in the Cron Jobs screen.
- Tests in `tests/Jobs/SyncShopifyDeviceSkuMetafieldsJobTest.php` cover the pure helpers only (map building, encoding,
  GID, `userErrors` handling) and don't touch the DB. They extend `PHPUnit\Framework\TestCase`, not the app
  `TestCase`.

**Approach:**

- A background job, `SyncShopifyDeviceSkuMetafieldsJob`, is dispatched when `ProcessFeedGenerationJob` finishes.
- It writes one JSON metafield per Shopify product: `global.device_skus = {device_code: sku}`.
- It pushes only products whose map changed, detected by a hash.
- The theme reads the device from the device collection (`collection.metafields.global.device_code`) and looks up the SKU.

---

## Step 0 — Decisions to make before coding

These change what the code does, so settle them first.

| # | Question | Effect on the plan |
|---|---|---|
| 0.1 | ~~Whose SKUs go in the event?~~ **Decided:** `ListingItemDetail.sku` of the Shopify `ListingItem` itself. | Step 2 is dropped. The job's source store = target store. |
| 0.2 | Product-level fallback when there is no device in context (direct landing on `/products/<handle>`): generic `ListingItem.sku`, remembered device, or no event? Ad/feed clicks already land in the device collection, so this only affects organic/shared links. | Only Step 7 (Liquid) changes. |
| 0.3 | Does the live theme link products with `within: collection`? Is there a device picker? | Step 7 may need a theme fix to the product-card snippet. |
| 0.4 | Largest number of devices on one product, for the source store. | Confirms the size limit isn't hit (Step 1). |

Query for 0.4 (read-only, run for the Shopify store):

```sql
SELECT lid.listing_item_id, COUNT(DISTINCT lid.device_id) AS devices
FROM listing_item_detail lid
JOIN listing_item li ON li.id = lid.listing_item_id
JOIN store_product sp ON sp.id = li.store_product_id
WHERE sp.store_id = '<shopify store id>'
GROUP BY lid.listing_item_id
ORDER BY devices DESC
LIMIT 10;
```

---

## Step 1 — Check the Shopify limits for our API version

`ShopifyAuthService` uses API version `2025-01` (hard-coded in its constructor).

1. Confirm the maximum value size of a `json` metafield for `2025-01` (expected 128 KB) and the `metafieldsSet`
   limit (expected 25 metafields per call).
2. Estimate the size: about 20 bytes per device (measured, keyed by `device.code`). Even all 3,241 devices come to
   about 65 KB, so the full-SKU format is kept.

**Done when:** the limits and the value format (full SKU or suffix) are written into the design doc.

---

## Step 2 — (Dropped) Store mapping

Not needed. The SKUs come from the Shopify store's own `ListingItemDetail` rows (decision 0.1), so there's no
`ga_sku_source_store_id` column and no store-form change. The step number is kept so the later references stay valid.

---

## Step 3 — Migration for the "last pushed" hash table

```php
// database/migrations/2026_10_xx_120100_create_shopify_device_sku_syncs_table.php
Schema::create('shopify_device_sku_syncs', function (Blueprint $table) {
    $table->id();
    $table->string('store_id', 36);          // target Shopify store
    $table->string('listing_item_id', 36);   // Shopify ListingItem
    $table->char('payload_hash', 40);
    $table->unsignedInteger('device_count')->default(0);
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();
    $table->unique(['store_id', 'listing_item_id']);
});
```

Add the model `app/Models/ShopifyDeviceSkuSync.php`: explicit `$table`, `$fillable`. It uses an int PK, so no UUID
boot hook.

**Done when:** `php artisan migrate` runs locally, and `./restart.sh` would apply it on deploy.

---

## Step 4 — Shopify GraphQL plumbing

### 4.1 Query constant

In `app/Constants/ShopifyQueries.php`, next to the other `public static` heredocs:

```php
public static $metafieldsSet = <<<GQL
mutation metafieldsSet(\$metafields: [MetafieldsSetInput!]!) {
  metafieldsSet(metafields: \$metafields) {
    metafields { id ownerType key namespace }
    userErrors { field message code }
  }
}
GQL;
```

### 4.2 Client method

In `app/Services/Auth/ShopifyAuthService.php`:

```php
public function setMetafields(array $metafields)
{
    return $this->makeGraphQLRequest(ShopifyQueries::$metafieldsSet, ['metafields' => $metafields]);
}
```

Each item: `['ownerId' => <product GID>, 'namespace' => 'global', 'key' => 'device_skus', 'type' => 'json', 'value' => <json string>]`.

Note: `makeGraphQLRequest()` logs the full response body. With 25 products per call that is noisy but acceptable,
and it matches the other calls.

### 4.3 (Optional) Metafield definition

One-off: create a `global.device_skus` definition (owner `PRODUCT`, type `json`, with storefront access) with
`metafieldDefinitionCreate`. It isn't required for Liquid to read the value, but it makes the field visible and
validated in Shopify admin. Do it as a one-time artisan command or by hand in admin. Don't put it in the job.

**Done when:** a manual `tinker` call on a test product writes and reads back the metafield.

---

## Step 5 — The job: `app/Jobs/Shopify/SyncShopifyDeviceSkuMetafieldsJob.php`

### 5.1 Shape

```php
class SyncShopifyDeviceSkuMetafieldsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TrackableJob {
        TrackableJob::withChain insteadof Dispatchable;
        Dispatchable::withChain as dispatchableWithChain;
    }

    public $timeout = 3600;
    public $tries = 1;              // the daily run is the retry
    public $uniqueFor = 3600;

    public string $store_name = '';
    public string $store_type = 'shopify';

    public function __construct(public ?string $storeId = null) {}

    public function uniqueId(): string
    {
        return $this->storeId ?? 'all';
    }
}
```

- `ShouldBeUnique`: several feeds finishing at once collapse into one queued run. This needs a cache driver that
  supports locks (redis/database). Check `CACHE_STORE` in the environment. No job in `app/Jobs` uses
  `ShouldBeUnique` yet, so this is the first one.
- `storeId` is the store whose feed just finished. `null` means "all Shopify stores", which is what the daily
  scheduled run uses.

### 5.2 `handle()` step by step

1. `updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => ...])`.
2. **Resolve the stores:** `storeId` if given, otherwise all active stores, filtered to
   `store_type.code = 'shopify'`. If the store whose feed just finished is not a Shopify store, mark the job
   `completed` with "not a Shopify store" and return. That is the normal case for Amazon/eBay/etc. feeds.
3. For each target store, create `ShopifyAuthService($store->secret_key, $store->service_url)` (same as `ShopifyService`).
4. **Walk the Shopify products in chunks** (`chunkById(200)`): the target store's `ListingItem` rows with
   `store_sku` not empty. Products not yet in Shopify are skipped, and the next run picks them up.
5. **Build the maps for the chunk in one query** (no N+1). These are the details of the same listing items, with
   no join to other stores:

   ```php
   ListingItemDetail::join('device', 'device.id', '=', 'listing_item_detail.device_id')
       ->whereIn('listing_item_detail.listing_item_id', $chunk->pluck('id'))
       ->whereNotNull('listing_item_detail.sku')->where('listing_item_detail.sku', '<>', '')
       ->orderBy('listing_item_detail.id')
       ->toBase()
       ->get(['listing_item_detail.listing_item_id', 'device.code as device_code', 'listing_item_detail.sku']);
   ```

   - Key by `device.code`, not the device UUID: it is unique, 4-6 characters, and already on the device collections
     as `global.device_code`, so the value is about 60% smaller (see the design doc).
   - Group them in PHP into `[listing_item_id => [device_code => sku]]`, keeping the **first row per device**
     (`??=`). This is the exact rule `ProcessFeedGenerationJob` uses (lines 1064-1068: `orderBy('id')` +
     `$itemDetailsByDevice[$detail->device_id] ??= $detail`), so the metafield always matches the feed `id`.
     Leave a comment that points to that code, and change both together if one changes.
   - `ksort` each map by device code, so the same data always gives the same JSON.
6. **Change detection:** load the `shopify_device_sku_syncs` rows for the chunk in one query. For each product:
   `json = json_encode($map)`, `hash = sha1($json)`. Skip the product if the hash equals the stored `payload_hash`.
   A product with an empty map whose stored row also says 0 devices is unchanged. A product whose map became empty
   gets `{}`.
7. **Size guard:** if `strlen($json)` is over the limit from Step 1, don't push. Report it with
   `CriticalErrorService::report(...)` and count it as failed.
8. **Push** the changed products in batches of 25 with `setMetafields()`.
   - Read `extensions.cost.throttleStatus.currentlyAvailable`. If it's low, `usleep` until it has restored enough.
   - Map `userErrors` back to products (the `field` index points at the input position).
9. **Save:** for each product written without errors, `updateOrCreate` its sync row with `payload_hash`,
   `device_count`, and `synced_at = now()`. Failed products keep their old row and are retried on the next run.
10. Count `checked / unchanged / pushed / failed / too_large`. At the end:
    `updateTrackingRecord(['status' => 'completed', 'finished_at' => now(), 'output' => <counts>])`.
11. Wrap the whole method in `try/catch (\Throwable $e)`: `CriticalErrorService::handle($e, ...)`, then
    `updateTrackingRecord(['status' => 'failed', ...])`.

### 5.3 What the job must not do

- It must not modify any product data other than this one metafield (`metafieldsSet` only touches that key).
- It must not regenerate or edit any `ListingItemDetail`. It only reads.
- It must not read another store's details. The map comes only from the Shopify `ListingItem`'s own rows.

**Done when:** running the job by hand for a test Shopify store writes the metafields, and a second run reports
everything `unchanged`.

---

## Step 6 — Wire up the triggers

### 6.1 After feed generation

In `app/Jobs/ProcessFeedGenerationJob.php` (which runs for one store, `$this->store_id`), there are two success exits. Each sets `FeedStatus::PROCESSED` and then
`updateTrackingRecord(['status' => 'completed', ...])`:

- the Meta branch (around line 239, before its `return;`)
- the main path (around line 293)

After each of those `updateTrackingRecord` calls, add:

```php
SyncShopifyDeviceSkuMetafieldsJob::dispatch($this->store_id);
```

- This is the only change to the feed job: a single dispatch line, and the feed doesn't wait for it.
- Keep it **after** the feed is marked done, so a dispatch problem can never mark a finished feed as failed. To be
  safe, wrap it in its own `try/catch` that only logs.
- Don't dispatch from the failure paths (`catch` / `failed()`).
- The dispatch can be unconditional. The job itself returns early for non-Shopify stores (5.2 step 2). Alternatively,
  guard it with `strtolower($this->storeDetail->storeType->name ?? '') === 'shopify'` to avoid queueing no-op jobs.
  Either is fine; the guard keeps `job_statuses` cleaner.

### 6.2 Daily safety net

Add a `scheduled_cron_jobs` row through `php artisan jobs:sync-registry` (inserts it inactive), then activate it with a
daily cron, e.g. `30 3 * * *`, and no parameters, meaning all stores. Also add it to `JobScheduler` seeder so
`./restart.sh` keeps it.

This run picks up:

- products created in Shopify after their feed finished
- runs that failed
- detail changes made outside feed generation (e.g. `SyncShopifyItems` deletes)

### 6.3 Manual run

Because it's a `TrackableJob`, it can be started from the job dashboard. If it should accept a store selection
there, add `ScopesToStores` (`app/Jobs/Concerns/ScopesToStores.php`) the same way the other multi-store jobs did in
the 2026-09-26 store-scoped job runs plan.

**Done when:** generating a feed for the source store produces a `SyncShopifyDeviceSkuMetafieldsJob` row in
`job_statuses` with sensible counts.

---

## Step 7 — Shopify theme (in the Shopify theme repo, not Aselna)

1. **Snippet** `snippets/ga-device-sku.liquid`:

   ```liquid
   {%- liquid
     assign device_code = collection.metafields.global.device_code.value
     assign device_skus = product.metafields.global.device_skus.value
     assign combo_sku = blank
     if device_code != blank and device_skus != blank
       assign combo_sku = device_skus[device_code]
     endif
     if combo_sku == blank
       assign combo_sku = product.selected_or_first_available_variant.sku
     endif
   -%}
   <script>
     window.dataLayer = window.dataLayer || [];
     window.dataLayer.push({ ecommerce: null });
     window.dataLayer.push({
       event: 'view_item',
       ecommerce: {
         items: [{
           item_id: {{ combo_sku | json }},
           item_name: {{ product.title | json }},
           item_variant: {{ collection.metafields.global.device_name.value | default: '' | json }},
           price: {{ product.selected_or_first_available_variant.price | divided_by: 100.0 }}
         }]
       }
     });
   </script>
   ```

   If the suffix-only format was chosen in Step 1, build the SKU instead:
   `assign combo_sku = product.selected_or_first_available_variant.sku | append: '-' | append: suffix`.
   Adjust this to however the product code is exposed.

2. Render it from the product template/section: `{% render 'ga-device-sku', product: product, collection: collection %}`.
   Merge it with the existing GA event code rather than firing a second event.
3. **Product links:** in the product-card snippet used by collection grids, make sure links use
   `{{ card_product.url | within: collection }}`. Without this, `collection` is empty on the product page.
4. Apply the fallback chosen in 0.2 for direct landings.
5. **GTM:** the `view_item` tag reads `ecommerce.items` from the dataLayer. Check in GTM Preview that `item_id` is the
   combination SKU.

**Done when:** opening a product from a device collection page shows the correct combination SKU in GTM Preview.

---

## Step 8 — Tests

Real tests live outside the phpunit suites. Run them by path. They hit the configured MySQL database.

- `tests/Jobs/SyncShopifyDeviceSkuMetafieldsJobTest.php`:
  - Map building: ordered by device code, stable JSON, and the first detail row (by `id`) per device wins, the same as
    the feed. Rows with empty `sku` are skipped.
  - Change detection: the same map gives no push, a changed map gives a push, and an empty map gives `{}`.
  - Batching: 60 changed products give 3 calls of at most 25.
  - `userErrors` on one product: only that product's hash is not saved.
  - Too-large map: not pushed and reported.
  - Mock `ShopifyAuthService::setMetafields()`. **Never** call a real store from tests.
- Run with `./vendor/bin/phpunit tests/Jobs/SyncShopifyDeviceSkuMetafieldsJobTest.php`.

---

## Step 9 — Rollout

1. Deploy the migrations and code (`./restart.sh`).
2. Run the job once by hand for a single Shopify store, and check `job_statuses` counts.
3. In Shopify admin, open 2–3 products and check that the `global.device_skus` value matches the
   `listing_item_detail` rows.
4. Deploy the theme snippet. Check in GTM Preview from a device collection, and from a direct product link to see the fallback.
5. Activate the daily cron row.
6. Watch `ErrorlogDashboardController` for `CriticalErrorService` reports from the job for a few days.

## Rollback

- Remove or comment out the theme render line. GA goes back to the old event immediately.
- Deactivate the cron row and remove the two dispatch lines. The metafields left on products are harmless.
- To fully clean up, delete the metafields with a one-off `metafieldsDelete` script, then drop the
  `shopify_device_sku_syncs` table.

## Files touched (summary)

| File | Change |
|---|---|
| `database/migrations/…_create_shopify_device_sku_syncs_table.php` | new |
| `app/Models/ShopifyDeviceSkuSync.php` | new |
| `app/Constants/ShopifyQueries.php` | add `$metafieldsSet` |
| `app/Services/Auth/ShopifyAuthService.php` | add `setMetafields()` |
| `app/Jobs/Shopify/SyncShopifyDeviceSkuMetafieldsJob.php` | new |
| `app/Jobs/ProcessFeedGenerationJob.php` | 2 dispatch lines |
| `database/seeders/JobScheduler.php` | daily row |
| `tests/Jobs/SyncShopifyDeviceSkuMetafieldsJobTest.php` | new |
| Shopify theme: `snippets/ga-device-sku.liquid`, product template, product-card snippet | new / edit |
