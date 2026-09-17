# Shopify Switch

Steps for moving the Oydera-managed Shopify stores onto Aselna.

## Shopify verification

After switching, run these three jobs **in this order** to copy listing data from Oydera
(`easyesale_db`) into Aselna. Then check the results with the queries below. The order matters.
Each job reads rows that the previous job wrote.

| # | Job | Writes to (Aselna) | Stores covered |
|---|-----|--------------------|----------------|
| 1 | `App\Jobs\OdemobileListingItemSyncJob` | `store_product`, `listing_item` | Shopify stores named `UZID`, `AccessoryChoice`, `OdeMobile` |
| 2 | `App\Jobs\Shopify\SyncShopifyItems` | `listing_item_detail` | Same three stores |
| 3 | `App\Jobs\ListingItemProductImageSyncJob` | `listing_item_product_images` | **One store only**, hardcoded as `c2c8131f-c8a2-e082-9f89-3e8bf96b1049` |

### Before you start

1. **Check that the stores exist.** Jobs 1 and 2 find their stores by `store.name` joined to
   `store_type.code = 'shopify'`. If a store's name doesn't match exactly, it is skipped with no error.

   ```sql
   SELECT s.id, s.name
   FROM store s
   JOIN store_type st ON st.id = s.store_type_id
   WHERE st.code = 'shopify'
     AND s.name IN ('UZID', 'AccessoryChoice', 'OdeMobile');
   ```

2. **Check the catalog.** Job 1 only links an Oydera listing item when its product's
   `reference_id1` matches a `products.product_code` in Aselna. Job 3 needs the `product_images`
   rows. Run the Fonus product and image syncs first.

3. **Keep Shopify pushes off.** Leave `auto_generate_listings = 0` on these stores until verification
   is finished. This keeps `SubmitShopifyProductsJob` from pushing half-synced data. For the reasons,
   see `docs/analysis/2026-09-10-oydera-to-aselna-listing-reconciliation.md` §5.3.

4. **Run one queue worker.** The queue is `database` with `retry_after = 6000` seconds.
   Jobs 2 and 3 set timeouts far above that (300000s and 600000s). If either runs longer than
   100 minutes, a second worker can pick up the same job and run it twice. Use a single worker,
   or run the jobs synchronously in tinker.

### How to run a job

From tinker (`php artisan tinker`):

```php
App\Jobs\OdemobileListingItemSyncJob::dispatch();      // step 1
App\Jobs\Shopify\SyncShopifyItems::dispatch();         // step 2
App\Jobs\ListingItemProductImageSyncJob::dispatch();   // step 3
```

Keep `php artisan queue:work` running. Wait for each job to finish before you dispatch the next.
Check progress in `job_statuses`:

```sql
SELECT type, status, started_at, finished_at, output, tags
FROM job_statuses
ORDER BY created_at DESC
LIMIT 10;
```

Job 1 also has a row in `scheduled_cron_jobs` ("Odemobile Items Sync Job"), so you can queue it
from the Scheduled Jobs screen. Jobs 2 and 3 have no row, so use tinker for those.

---

### Step 1: Run `OdemobileListingItemSyncJob`

**What it does.** For each of the three stores, it reads Oydera `listing_item` rows, joined through
`store_product` to `product`. It then:

- Finds the Aselna product with `products.product_code = product.reference_id1`. Items with no
  matching product are skipped silently.
- Finds the Aselna `store_product` by `(product_id, store_id)`. If there isn't one, it creates it
  from the Oydera `store_product`.
- Upserts `listing_item` using **Oydera's listing item id**. It sets Aselna's `store_product_id`
  and `product_id`, and maps `status` through `ListingStatus`.

> **Caution: duplicates.** The job has no "already exists" check. The check at `:85-88` is
> commented out. `listing_item` also has no unique index on `store_product_id`. If Aselna already
> created its own listing item for a store product, this job adds a **second** one. Run the duplicate
> query below **before** the job to get a baseline, then run it again afterwards.

**Verify:**

```sql
-- Listing items per store after the sync
SELECT sp.store_id, COUNT(*) AS listing_items
FROM listing_item li
JOIN store_product sp ON sp.id = li.store_product_id
WHERE sp.store_id IN (/* ids from "Before you start" step 1 */)
GROUP BY sp.store_id;

-- Store products that now have more than one listing item (should not grow)
SELECT li.store_product_id, COUNT(*) AS cnt, GROUP_CONCAT(li.sku) AS skus
FROM listing_item li
JOIN store_product sp ON sp.id = li.store_product_id
WHERE sp.store_id IN (/* store ids */)
GROUP BY li.store_product_id
HAVING cnt > 1;

-- Listing items with no store product (product_id is still set, so they can be traced back)
SELECT id, sku, product_id
FROM listing_item
WHERE store_product_id IS NULL
  AND updated_by = 'system'
  AND updated_at >= /* job start time */;

-- Store products the job created
SELECT id, product_id, store_id, created_at
FROM store_product
WHERE store_id IN (/* store ids */)
  AND created_by = 'system'
  AND created_at >= /* job start time */;
```

Also check that `store_sku` and `store_url` are filled in on a sample of listing items and match
the live Shopify product.

In the logs, look for `Store Product ID: ... not found in Oydera`. On failure the job sets
`job_statuses.status = 'failed'` and reports "Odemobile Listing Item Sync Job Failed" to the error log.

---

### Step 2: Run `SyncShopifyItems` (listing item details)

**What it does.** Only the listing-item-detail part of this job is active. The store product and
listing item parts are commented out, and step 1 covers them. It selects the Aselna `listing_item`
rows whose `store_product_id` belongs to the three stores. For each one, it copies Oydera
`cea_listing_item_detail` into `listing_item_detail`, keeping the same id.

- It **only inserts**. If a `listing_item_detail` id already exists, that row is skipped and not
  updated, so re-running it won't refresh stale rows.
- It depends on step 1. Listing items that step 1 left without a `store_product_id` are not picked up.

**Verify:**

```sql
-- Detail rows per store
SELECT sp.store_id, COUNT(lid.id) AS details
FROM listing_item_detail lid
JOIN listing_item li  ON li.id = lid.listing_item_id
JOIN store_product sp ON sp.id = li.store_product_id
WHERE sp.store_id IN (/* store ids */)
GROUP BY sp.store_id;

-- Listing items that still have no detail rows
SELECT li.id, li.sku
FROM listing_item li
JOIN store_product sp ON sp.id = li.store_product_id
LEFT JOIN listing_item_detail lid ON lid.listing_item_id = li.id
WHERE sp.store_id IN (/* store ids */)
  AND lid.id IS NULL;
```

Compare the counts with Oydera's `cea_listing_item_detail` for the same listing item ids.

> **Failure is not recorded in `job_statuses`.** The `catch` block reports "Store Products Sync Job
> Failed" to the error log, but it never updates the tracking record. A failed run stays at
> `processing`. Check the Error Log dashboard as well as `job_statuses`.

---

### Step 3: Run `ListingItemProductImageSyncJob` (images)

**What it does.** It reads Oydera `listing_item_product_image` rows joined to `product_image`, for
listing items in store `c2c8131f-c8a2-e082-9f89-3e8bf96b1049` only. For each row, it finds the
Aselna `product_images` row by `(product_id, code)`. It then upserts `listing_item_product_images`
using the Oydera id, and copies `path` and `reference_id`. Rows with no matching Aselna product
image are skipped silently.

> **One store only.** To sync images for the other stores, change the store id at
> `app/Jobs/ListingItemProductImageSyncJob.php:43` and run the job again for each store.

**Verify:**

```sql
-- Image links for the store
SELECT COUNT(*) AS images, SUM(lipi.main_image = 1) AS main_images
FROM listing_item_product_images lipi
JOIN listing_item li  ON li.id = lipi.listing_item_id
JOIN store_product sp ON sp.id = li.store_product_id
WHERE sp.store_id = 'c2c8131f-c8a2-e082-9f89-3e8bf96b1049';

-- Listing items with no image at all
SELECT li.id, li.sku
FROM listing_item li
JOIN store_product sp ON sp.id = li.store_product_id
LEFT JOIN listing_item_product_images lipi ON lipi.listing_item_id = li.id
WHERE sp.store_id = 'c2c8131f-c8a2-e082-9f89-3e8bf96b1049'
  AND lipi.id IS NULL;

-- Image links whose listing item does not exist in Aselna (the job does not check this)
SELECT lipi.id, lipi.listing_item_id
FROM listing_item_product_images lipi
LEFT JOIN listing_item li ON li.id = lipi.listing_item_id
WHERE li.id IS NULL;

-- Images already on Shopify (reference_id set, so they won't be uploaded again)
SELECT COUNT(*) FROM listing_item_product_images
WHERE reference_id IS NOT NULL;
```

Any image with no `reference_id` is uploaded to Shopify again the next time `SubmitShopifyProductsJob`
runs. See `SHOPIFY_SYNC_VERIFICATION.md` §2.

---

### Sign-off checklist

- [ ] Duplicate-listing-item count did not grow after step 1
- [ ] Every Shopify-live Oydera listing item has an Aselna `listing_item` with matching `sku`, `store_sku` and `store_url`
- [ ] `listing_item_detail` counts match Oydera for the synced listing items
- [ ] Image links exist for each store (step 3 run once per store), and no image link points at a missing listing item
- [ ] No new rows in the Error Log for the three jobs
- [ ] Only after all of the above: turn on `auto_generate_listings` for **one** store and watch a single `SubmitShopifyProductsJob` run
