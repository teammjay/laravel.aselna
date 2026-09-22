# Shopify Switch

Steps for moving the Oydera-managed Shopify stores onto Aselna.

## Orchestration: `OyderaMasterSwitch`

The three sync jobs used to be run one at a time, by hand, each one sweeping every store in a
single pass. That was slow and hard to restart. They are now split per store and per task, and
driven by one master job.

### The pieces

| Piece | Path | Purpose |
|---|---|---|
| Master job | `app/Jobs/Sync/OyderaMasterSwitch.php` | Loops the stores, builds the chain, dispatches it |
| Item sync | `app/Jobs/Shopify/SyncShopifyItems.php` | Takes `(storeId, taskType)` — runs **one** task for **one** store (store products, listing items, details, navigation pages) |
| Image sync | `app/Jobs/ListingItemProductImageSyncJob.php` | Takes `(storeId)` |
| Command | `app/Console/Commands/OyderaMasterSwitchCommand.php` | Run the whole thing for a single store |
| Scheduler row | `database/seeders/JobScheduler.php` | Registers the master job so it can be run from the UI |

### Stores

`OyderaMasterSwitch::STORE_NAMES` holds the three stores, and the run order follows this list
(the DB returns them in a different order, so the job re-sorts them):

```php
public const STORE_NAMES = ['UZID', 'OdeMobile', 'AccessoryChoice'];
```

Resolved against `store_type.code = 'shopify'`, these are:

| Name | Store id |
|---|---|
| `UZID` | `e4424bb0-87f7-ce9e-90c3-6d9f491bc726` |
| `OdeMobile` | `6c7009ce-eac1-a995-a986-27d5df0103ac` |
| `AccessoryChoice` | `99ede22c-fc39-7f9f-cd0d-34d4b9a70c21` |

Note there is also a Shopify store named `OdeMobile-C`, which is **not** matched — the name test
is exact.

### The chain

For each store, in order, five jobs are appended to a single `Bus::chain`:

| # | Job | Task | Writes to |
|---|---|---|---|
| 1 | `SyncShopifyItems` | `TASK_STORE_PRODUCT` (`'store_product'`) | `store_product` |
| 2 | `SyncShopifyItems` | `TASK_LISTING_ITEM` (`'listing_item'`) | `listing_item` |
| 3 | `SyncShopifyItems` | `TASK_LISTING_ITEM_DETAIL` (`'listing_item_detail'`) | `listing_item_detail` |
| 4 | `ListingItemProductImageSyncJob` | — | `listing_item_product_images` |
| 5 | `SyncShopifyItems` | `TASK_STORE_NAVIGATION_PAGE` (`'store_navigation_page'`) | `store_navigation_page` |

Three stores x five jobs = a 15-job chain, plus a trailing `ChainCompletionJob` that closes out the
parent record. Because it is one chain, the jobs run strictly in sequence — store by store, task by
task — and each one gets its own `job_statuses` row, tagged with the task type. A failure stops the
chain and marks the parent `failed` via the `catch` callback.

> The order still matters for the same reason it always did: each task reads rows the previous one
> wrote. Splitting them does not make them independent. Step 5 is the exception — navigation pages
> depend on nothing above them and sit last only so the listing data is in place first.

### Every task is a full reconciliation

Each task compares one store's rows with oydera (`easyesale_db`) and brings them level:

- a row only in oydera is **created**,
- a row in both is **updated**, and only when a value actually differs,
- a row only in Aselna is **deleted**, because it was removed upstream.

So after a clean run the per-store counts match oydera, and no separate purge is needed. Two
guards apply to the delete pass:

- It is skipped when the source read returns nothing for the store, so an empty or unreachable
  source cannot wipe a store.
- A listing item that an `order_item` or `hd_ticket` points at is **kept** and counted as
  `protected` (those foreign keys are `ON DELETE CASCADE` and would take order lines with them).
  A store product still holding such an item is kept as well.

Each run logs, and writes to `job_statuses.output`, a summary like
`created: 12, updated: 40, unchanged: 3100, deleted: 5, protected: 0, ...`.

#### Store navigation pages (step 5)

`TASK_STORE_NAVIGATION_PAGE` does for the three stores what `App\Jobs\StorePagesJobSync` does for
every store at once: it copies oydera `store_navigation_page` into `store_navigation_page`, keeping
the same id and the same columns. Unlike `StorePagesJobSync` (which only upserts) it also deletes
the pages oydera no longer has for that store, so the counts end up equal. Device ids are matched
on `device.code` where the id differs, as for listing items. `StorePagesJobSync` itself is
unchanged.

```sql
-- Aselna side; compare with the same query on easyesale_db
SELECT store_id, COUNT(*) FROM store_navigation_page
WHERE store_id IN ('e4424bb0-87f7-ce9e-90c3-6d9f491bc726',
                   '6c7009ce-eac1-a995-a986-27d5df0103ac',
                   '99ede22c-fc39-7f9f-cd0d-34d4b9a70c21')
GROUP BY store_id;
```

### How to run it

**Whole thing, all three stores** — from the Scheduled Jobs screen ("Oydera Master Switch"), or:

```bash
php artisan oydera:master-switch
```

**One store only** — pass a store name or a store id:

```bash
php artisan oydera:master-switch UZID
php artisan oydera:master-switch e4424bb0-87f7-ce9e-90c3-6d9f491bc726
```

The argument is constrained to Shopify stores; anything else exits 1 with
`No Shopify store found matching: ...`. A single-store run is not limited to `STORE_NAMES` — any
Shopify store id works.

**From tinker**, if you want one task for one store without the rest of the chain:

```php
use App\Jobs\Shopify\SyncShopifyItems;

SyncShopifyItems::dispatch($storeId, SyncShopifyItems::TASK_STORE_PRODUCT);
SyncShopifyItems::dispatch($storeId, SyncShopifyItems::TASK_LISTING_ITEM);
SyncShopifyItems::dispatch($storeId, SyncShopifyItems::TASK_LISTING_ITEM_DETAIL);
App\Jobs\ListingItemProductImageSyncJob::dispatch($storeId);
SyncShopifyItems::dispatch($storeId, SyncShopifyItems::TASK_STORE_NAVIGATION_PAGE);
```

An unknown `taskType` throws `InvalidArgumentException`, which the job's own `catch` turns into an
error-log report.

### Seeding caution

`ScheduledJob::$fillable` is only `name` / `last_run` / `description` / `class_name`, so a row the
seeder **creates** takes the table defaults — and `scheduled_cron_jobs.is_active` defaults to `1`
with `cron_expression` defaulting to `* * * * *`. The first `db:seed --class=JobScheduler` (which
`./restart.sh` runs) therefore creates "Oydera Master Switch" **active, on an every-minute cron**.

Set the cron and toggle it off from the UI straight after that first seed. For comparison, the
existing "Shopify Only Sync" row sits at `is_active = 0`, cron `10 */2 * * *`. Later re-seeds are
safe: `updateOrCreate` matches on `class_name` and does not touch those columns again.

Also note `routes/console.php` instantiates scheduled jobs as `new $class(...array_values($params))`.
`OyderaMasterSwitch` has an all-optional constructor so a row with empty `parameters` is fine.
`SyncShopifyItems` and `ListingItemProductImageSyncJob` do **not** — do not add those to the
schedule table without filling in `parameters`, or the scheduler will throw `ArgumentCountError`
at boot and abort loading the remaining rows.

### Relationship to `OdemobileListingItemSyncJob`

`OdemobileListingItemSyncJob` covers the same three stores and also writes `store_product` and
`listing_item`. It overlaps with chain steps 1 and 2, so the two are **alternatives, not
complements**. The step-by-step runbook below is written around `OdemobileListingItemSyncJob`;
the master switch is the newer path. Do not run both for the same store in one session without
re-checking the duplicate query in step 1.

---

## Shopify verification

After switching, run these three jobs **in this order** to copy listing data from Oydera
(`easyesale_db`) into Aselna. Then check the results with the queries below. The order matters.
Each job reads rows that the previous job wrote.

| # | Job | Writes to (Aselna) | Stores covered |
|---|-----|--------------------|----------------|
| 1 | `App\Jobs\OdemobileListingItemSyncJob` | `store_product`, `listing_item` | Shopify stores named `UZID`, `AccessoryChoice`, `OdeMobile` |
| 2 | `App\Jobs\Shopify\SyncShopifyItems` | `store_product`, `listing_item` or `listing_item_detail`, one per `taskType` | One store per dispatch |
| 3 | `App\Jobs\ListingItemProductImageSyncJob` | `listing_item_product_images` | One store per dispatch |

### Before you start

0. **No purge is needed.** The `SyncShopifyItems` tasks and `ListingItemProductImageSyncJob`
   delete what oydera no longer has themselves — see **Every task is a full reconciliation** above.

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
use App\Jobs\Shopify\SyncShopifyItems;

App\Jobs\OdemobileListingItemSyncJob::dispatch();                                  // step 1
SyncShopifyItems::dispatch($storeId, SyncShopifyItems::TASK_LISTING_ITEM_DETAIL);  // step 2
App\Jobs\ListingItemProductImageSyncJob::dispatch($storeId);                       // step 3
```

Steps 2 and 3 take a store id now, so repeat them per store. Or run all of it in one chain with
`php artisan oydera:master-switch` (see **Orchestration** above).

Keep `php artisan queue:work` running. Wait for each job to finish before you dispatch the next.
Check progress in `job_statuses`:

```sql
SELECT type, status, started_at, finished_at, output, tags
FROM job_statuses
ORDER BY created_at DESC
LIMIT 10;
```

Job 1 has a row in `scheduled_cron_jobs` ("Odemobile Items Sync Job"), and the master job has one
("Oydera Master Switch"), so both can be queued from the Scheduled Jobs screen. Jobs 2 and 3 have
no row of their own — run them through the master switch, the artisan command, or tinker.

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

**What it does.** The job now takes `(storeId, taskType)` and runs exactly one of its three tasks
per dispatch; this step is `TASK_LISTING_ITEM_DETAIL`. (The other two, `TASK_STORE_PRODUCT` and
`TASK_LISTING_ITEM`, cover the same ground as step 1 — see **Relationship to
`OdemobileListingItemSyncJob`** above.) For the given store it selects the Aselna `listing_item`
rows whose `store_product_id` belongs to that store, and copies Oydera `cea_listing_item_detail`
into `listing_item_detail`, keeping the same id.

- It **reconciles**: missing detail rows are created, changed ones updated, and detail rows oydera
  no longer has for these listing items are deleted.
- It depends on step 1. Listing items that step 1 left without a `store_product_id` are not picked up.
- It is **one store per dispatch**. Repeat it for each store, or let the master switch do that.

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
listing items in the store passed to its constructor. For each row, it finds the
Aselna `product_images` row by `(product_id, code)`. It then upserts `listing_item_product_images`
using the Oydera id, and copies `path` and `reference_id`. Rows with no matching Aselna product
image are skipped silently.

> **One store per dispatch.** The store id used to be hardcoded in `handle()`; it is now the
> constructor argument. To cover the other stores, dispatch it once per store
> (`ListingItemProductImageSyncJob::dispatch($storeId)`), or let the master switch do it.

**Verify:**

```sql
-- Image links for the store
SELECT COUNT(*) AS images, SUM(lipi.main_image = 1) AS main_images
FROM listing_item_product_images lipi
JOIN listing_item li  ON li.id = lipi.listing_item_id
JOIN store_product sp ON sp.id = li.store_product_id
WHERE sp.store_id = /* the store id you dispatched the job with */;

-- Listing items with no image at all
SELECT li.id, li.sku
FROM listing_item li
JOIN store_product sp ON sp.id = li.store_product_id
LEFT JOIN listing_item_product_images lipi ON lipi.listing_item_id = li.id
WHERE sp.store_id = /* the store id you dispatched the job with */
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
