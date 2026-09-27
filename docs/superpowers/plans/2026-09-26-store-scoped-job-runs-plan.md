# Plan — Running multi-store jobs for selected stores from the UI

**Date:** 2026-09-26
**Goal:** When a user clicks **Run Now** on a job that works across stores, show a popup where
they pick the stores (only the eligible ones). The job then runs for those stores only.
Scheduled (cron) runs keep working as they do today.
**Status:** implemented on `feed_dahboard_faceboook` (uncommitted, pending review). No migrations.

### Implementation notes (how the code differs from the plan below)

- **No `per_store` mode.** No dashboard job needs it (Group D jobs are only started by other jobs), so
  `StoreScopedJob` has no `storeScopeMode()`. Instead it declares `eligibleStoreTypes()`,
  `onlyActiveStores()` (mirrors whether the job's own query filters `store_status <> 0`) and
  `defaultStoreIds()` (the stores a run with no selection covers — the `STORE_NAMES` list for
  `OyderaMasterSwitch` / `OdemobileListingItemSyncJob`, else null = cron mapping or all stores).
- **Minimal job diffs.** Each job swaps `Common::getActiveStoreJobs('X', 'type')` for
  `$this->resolveStoreIds()` and keeps its existing `if (!empty($store_ids)) whereIn` block.
  `storeMappingClass()` keeps the `Existing*ListingItemSyncJob` jobs on their sibling's mapping.
- **Group B jobs now honour the cron store mapping too** (they ignored it before). No mapping rows
  exist for them today, so scheduled runs are unchanged.
- **Parameters:** with no selection, `build()` spreads `scheduled_cron_jobs.parameters` exactly as
  `routes/console.php` does; a selection replaces the first parameter (the store argument).
- **Legacy `storeId` property** kept on `FetchShopifyProductsJob`, `OyderaMasterSwitch` and
  `SyncShopifyMissingMediaPathsJob`, so jobs already queued with a single store don't widen to every
  store after deploy.
- **Popup:** "Default stores (same as the scheduled run)" lists what a plain run covers; "Only these
  stores" starts pre-filled with those defaults. Partials: `jobs/run_stores_modal`, `jobs/run_stores_js`.
- `SyncAllOrderStatusJob` (6.3) and the Cron edit-screen JSON rework (Step 7) are not done; the
  edit screen got the hint text and the stale-mapping fix only.
- Tests: `tests/Jobs/ScopesToStoresTest.php`, `tests/Services/StoreScopedJobRunnerTest.php` (read-only).

---

## 1. What exists today

### 1.1 Two job dashboards, two tables

| Menu | URL / controller | Table / model | What "Run Now" does |
|---|---|---|---|
| **Saved Jobs** | `/jobs` → `JobSchedulerController` | `scheduled_jobs` / `ScheduledJob` (int PK) + `schedule_jobs_parent` | Builds a `Bus::chain` of every parent job, then the job itself. Each one is created with `new $class()`, so **no arguments at all**. |
| **Cron Jobs** | `/scheduled_jobs` → `ScheduledjobsController` | `scheduled_cron_jobs` / `ScheduledJobs` (UUID PK) + `store_scheduled_cron_jobs` | `dispatch(new $class($params))`. This passes the whole parameters array as the **first** constructor argument (see bug 4.2). |

The scheduler (`routes/console.php`) only reads `scheduled_cron_jobs`, and creates each job as
`new $class(...array_values(json_decode($parameters)))`. Today `parameters` is `NULL` on every row,
so every scheduled job is created with no arguments.

### 1.2 Store selection that already exists (Cron Jobs)

The Cron Job edit screen can already tie stores to a job:

1. Pick store types, then `scheduled_jobs.getstore` returns HTML `<tr>` rows with a checkbox per store.
2. On save, `ScheduledjobsController::update()` rewrites `store_scheduled_cron_jobs`
   (`scheduled_job_id`, `store_id`, `store_type_id`).
3. At runtime the job calls `Common::getActiveStoreJobs('<ClassShortName>', '<store_type_code>')`,
   which finds the cron row with `class_name LIKE %name%`, then returns its mapped store ids.
4. The job then does:

   ```php
   $query = Store::...->where('store_type.code', 'amazon')->where('store.store_status', '<>', 0);
   if (!empty($store_ids)) {
       $query->whereIn('store.id', $store_ids);
   }
   ```

   So if **no stores are mapped, the job runs for all stores**. This is the fallback we have to keep.

**In practice this is almost unused.** Only one cron row has any mapping:
`Amazon Listing Item Sync` → 2 stores. All other rows run for every store of their type.

**This is a fixed, saved setting, not a choice made at run time.** Neither dashboard lets you pick
stores when you run a job. The setting also only works when the job's class name matches an
**active** cron row. So on the Saved Jobs dashboard it applies only by accident.

### 1.3 How the jobs choose their stores today

After checking the store-related jobs in `app/Jobs`, they fall into four groups:

**Group A — already read the cron mapping (`Common::getActiveStoreJobs`). No constructor arguments.**
Each of these needs the same small change.

| Job | Store type | On Saved Jobs | On Cron Jobs |
|---|---|:-:|:-:|
| `FetchAmazonOrdersJob` | amazon | | |
| `FetchShopifyOrders` | shopify | ✔ | ✔ |
| `FetchEbayOrders` | ebay | ✔ | ✔ |
| `FetchWalmartOrdersJob` | walmart | ✔ | ✔ |
| `FetchTemuOrders` | temu | ✔ | ✔ |
| `AmazonListingItemSyncJob` | amazon | ✔ | ✔ |
| `EbayListingItemSyncJob` | ebay | ✔ | ✔ |
| `WalmartListingItemSyncJob` | walmart | ✔ | ✔ |
| `Amazon\ExistingListingItemSyncJob` | amazon (reuses `AmazonListingItemSyncJob` mapping) | ✔ | ✔ |
| `Ebay\ExistingListingItemSyncJob` | ebay (reuses `EbayListingItemSyncJob` mapping) | ✔ | ✔ |
| `Walmart\ExistingListingItemSyncJob` | walmart (reuses `WalmartListingItemSyncJob` mapping) | ✔ | ✔ |
| `MagnetoAttributesSyncJob` | magento | | |
| `Magento\MagentoProductsSyncJob` | magento | ✔ | |
| `FetchEmailMessagesJob` | any type | ✔ | ✔ |
| `FetchShopifyProductsJob` | shopify (already has `?string $storeId`) | ✔ | |

**Group B — hard-coded store queries. No filter at all.** These need the same change, plus the
`whereIn` has to be added.

| Job | Store type | On Saved Jobs | On Cron Jobs |
|---|---|:-:|:-:|
| `SubmitShopifyProductsJob` | shopify | ✔ | ✔ |
| `ShopifyCollectionSyncJob` | shopify | ✔ | |
| `ShopifyPagesSyncJob` | shopify | ✔ | |
| `Sync\ShopifyOnlySyncJob` | shopify | ✔ | ✔ (inactive) |
| `Magento\FetchMagentoOrderJob` | magento | | |
| `OdemobileListingItemSyncJob` | shopify (hard-coded 3 names) | ✔ | |
| `SyncAllOrderStatusJob` | multi-type (per-operation) | ✔ | ✔ — *phase 2, see 6.3* |

**Group C — already take a store (`?string $storeId`), one store or all.**
`Sync\OyderaMasterSwitch` (default = the 3 `STORE_NAMES`), `Shopify\SyncShopifyMissingMediaPathsJob`.
Widen the argument to accept a list.

**Group D — take one required store id (child/worker jobs).**
`ListingItemProductImageSyncJob`, `Shopify\SyncShopifyItems`, `ShopifyIndvPagesSyncJob`,
`ShopifyCollectionDeviceSyncJob`, `StoreCategorySyncJob`, `Magento\Magento{Brand,Category,Device,Relation}SyncJob`,
`Shopify\ShopifyBrandPageSyncJob`. These are started by other jobs, not from the dashboards.
**No change is needed.** If you ever want one of them on a dashboard, the runner can dispatch one
instance per selected store (see 5.2, "per-store" mode).

**Not store-scoped:** all `Fonus\*`, `FetchResultFeedAsync`, `TransactionStatusCheckJob`,
`SystemPrinterSyncJob`, `AmazonProductTypeSyncJob`, `Product*ImagesSyncJob`, etc. For these,
**Run Now** works exactly as it does today, with no popup.

### 1.4 Size of the change

| Area | Files | Size |
|---|---|---|
| New contract + trait + runner service | 3 new | ~200 lines |
| Group A jobs | 15 | ~6–10 lines each, mechanical |
| Group B jobs | 6 (+1 in phase 2) | ~8–12 lines each |
| Group C jobs | 2 (+ `OyderaMasterSwitchCommand`) | small |
| Controllers | `JobSchedulerController`, `ScheduledjobsController` | ~80 lines |
| Routes | `routes/web.php` | 3 routes, plus moving `/jobs` into `auth` |
| Views | new `partials/run-store-modal.blade.php`; `jobs/index`, `scheduled_jobs/index` | ~150 lines |
| `Common::getActiveStoreJobs` | fix matching (4.3) | small |
| Migrations | **none needed** | — |
| Tests | new `tests/Services/StoreScopedJobRunnerTest.php`, `tests/Jobs/...` | — |

Roughly **25 PHP files touched, none with a structural rewrite**. The risky part is not the amount
of code. It is getting the meaning of "empty" right (§3) so that scheduled runs don't change.

---

## 2. The flow in the website (target)

```
Saved Jobs / Cron Jobs list
  └─ [Run Now] on a row
       ├─ Job (or any job in its parent chain) is NOT store-scoped
       │     → submit form as today → "Job queued"
       └─ Job IS store-scoped
             → modal opens, calls GET /jobs/run-stores?source=saved&job=<id>
             │     ← { types: ["shopify"], stores: [{id,name,type,preselected}] }
             │
             │   ┌──────────────── Run "Fetch Shopify Orders" ────────────────┐
             │   │ ( ) All eligible stores (5)                                 │
             │   │ (•) Only these stores                                       │
             │   │     [ Select2 multi-select, grouped by store type      ▾ ] │
             │   │     [Select all] [Clear]                                    │
             │   │   Eligible: Shopify stores with status ≠ 0                  │
             │   │                                   [Cancel]  [Run for 2]     │
             │   └─────────────────────────────────────────────────────────────┘
             │
             → POST /jobs/{job}/run  (or /scheduled_jobs/{job}/run)
                   scope=all | selected, store_ids[]=...
             → server re-checks every store id against the eligible set
             → dispatches job(s) with storeIds
             → flash "Fetch Shopify Orders queued for UZID, OdeMobile"
             → Jobs Queue / job_statuses row tagged with the store names
```

The scheduler is not affected: `routes/console.php` still creates jobs with no arguments.
`storeIds` is then `null`, the job falls back to `getActiveStoreJobs`, and that falls back to all
stores. This is the same as today.

---

## 3. Key decision: what "no stores" means

There are three possible states. They must stay separate:

| `storeIds` value | Meaning | Who produces it |
|---|---|---|
| `null` | **Default behaviour:** use the cron mapping; if it is empty, run for all stores of the type. | scheduler, CLI, legacy callers |
| `['id1','id2']` | **Only these stores.** Ignore the cron mapping. | UI "Only these stores", CLI with args |
| `[]` | Treated as `null`. | legacy `new $class([])` from `ScheduledjobsController::run` |

The UI must **never** send an empty "selected" list. Validation requires `min:1` when
`scope=selected`. "All eligible stores" is sent as `scope=all`, and the server turns it into
`null`. Treating `[]` as `null` keeps today's `ScheduledjobsController::run` call
(`new $class([])`) from quietly meaning "no stores".

---

## 4. Existing bugs to fix along the way

These sit directly in the code path we are changing:

1. **`/jobs*` routes are not authenticated.** `routes/web.php:779–784` is outside the
   `Route::middleware('auth')` group, so anyone can call `POST /jobs/{id}/run` without logging in.
   Move these routes inside the group. This matters more once `run` accepts store ids.
2. **`ScheduledjobsController::run` passes its arguments wrongly.** It calls `new $class($params)`,
   which passes one array argument. `parameters` is a `text` column with no cast, so any
   non-null value would make `array_values()` throw a `TypeError`. Use
   `new $class(...array_values(json_decode($job->parameters, true) ?? []))`, the same as the scheduler.
3. **`Common::getActiveStoreJobs` matches by `LIKE %ShortName%`.** It can match the wrong row
   (for example, `FetchShopifyProductsJob` would also match a future `FetchShopifyProductsJobV2`).
   Its store-type subquery also calls `->get()` inside the closure, which runs a pointless extra
   query. Match on the full class name instead: `where('class_name', static::class)`.
4. **Saved Jobs "Run Now" breaks on jobs with required constructor arguments.** `new $jobClass()`
   throws on any Group D job. The runner in §5.2 handles this.
5. **Cron edit leaves old store mappings behind.** `update()` only rewrites
   `store_scheduled_cron_jobs` when `store_type_id` is present. If you clear every store type,
   the old mappings stay. Always delete first, then insert whatever was submitted.
6. `Route::post('/jobs/add', ...)` points at a method that doesn't exist on `JobSchedulerController`,
   and `jobs.running` renders a view but is registered as `POST`. Tidy these up while moving the routes.

---

## 5. Implementation steps

### Step 1 — Contract: how a job says it is store-scoped

`app/Jobs/Contracts/StoreScopedJob.php`

```php
namespace App\Jobs\Contracts;

interface StoreScopedJob
{
    /**
     * store_type.code values this job can run for. [] = any store type.
     */
    public static function eligibleStoreTypes(): array;

    /**
     * 'multi'     – one instance handles a list of stores (constructor takes ?array $storeIds)
     * 'per_store' – one instance per store (constructor takes string $storeId)
     */
    public static function storeScopeMode(): string;
}
```

Both are static, so the controllers can ask a class about itself without creating it. Creating
some jobs runs DB queries in the constructor.

### Step 2 — Trait: store resolution in one place

`app/Jobs/Concerns/ScopesToStores.php`

```php
namespace App\Jobs\Concerns;

trait ScopesToStores
{
    /** null = default (cron mapping, else all). Non-empty = only these. */
    public ?array $storeIds = null;

    /** Read by the Queue::createPayloadUsing hook for job_statuses tags. */
    public $store_name;

    public static function storeScopeMode(): string
    {
        return 'multi';
    }

    protected function initStoreScope(string|array|null $storeIds): void
    {
        if (is_string($storeIds) && $storeIds !== '') {
            $storeIds = [$storeIds];
        }
        $this->storeIds = empty($storeIds) ? null : array_values(array_unique($storeIds));

        if ($this->storeIds) {
            $this->store_name = Store::whereIn('id', $this->storeIds)->pluck('name')->implode(', ');
        }
    }

    /**
     * Store ids to restrict to, or null for "no restriction".
     * $mappingClass lets the Existing*ListingItemSyncJob jobs keep reading their sibling's mapping.
     */
    protected function resolveStoreIds(?string $mappingClass = null, ?string $storeType = null): ?array
    {
        if ($this->storeIds) {
            return $this->storeIds;
        }
        $ids = Common::getActiveStoreJobs($mappingClass ?? static::class, $storeType);

        return empty($ids) ? null : $ids;
    }

    /** Apply to any Store query that already joins/filters on store type. */
    protected function scopeStoreQuery($query, string $column = 'store.id', ?string $mappingClass = null, ?string $storeType = null)
    {
        $ids = $this->resolveStoreIds($mappingClass, $storeType);

        return $ids ? $query->whereIn($column, $ids) : $query;
    }
}
```

Watch-outs:
- `OyderaMasterSwitch` and `FetchShopifyProductsJob` already declare `$store_name`, so remove
  their own declaration when they adopt the trait. Otherwise PHP reports a property conflict.
- The trait must not declare a constructor. Each job keeps its own and calls `initStoreScope()`,
  because many already have constructors (`chainId` setup in `FetchEmailMessagesJob`, etc.).

### Step 3 — Update the jobs

**Group A pattern** (example: `FetchShopifyOrders`):

```php
class FetchShopifyOrders implements ShouldQueue, StoreScopedJob
{
    use ..., ScopesToStores;

    public static function eligibleStoreTypes(): array { return ['shopify']; }

    public function __construct(string|array|null $storeIds = null)
    {
        $this->initStoreScope($storeIds);
    }

    public function handle(): void
    {
        $query = Store::select(...)->join(...)->where('store_type.code', 'shopify')->where('store.store_status', '<>', 0);
        $this->scopeStoreQuery($query, 'store.id', null, 'shopify');
        $stores = $query->get();
        ...
```

This replaces the `getActiveStoreJobs` call and the `if (!empty($store_ids))` block. Notes for
individual jobs:
- `Amazon|Ebay|Walmart\ExistingListingItemSyncJob`: pass the sibling class as
  `$mappingClass` (for example `\App\Jobs\AmazonListingItemSyncJob::class`) so their cron
  behaviour stays the same.
- `AmazonListingItemSyncJob` filters on the bare `id` column (it has no join), so pass `'id'`.
- `FetchEmailMessagesJob`: `eligibleStoreTypes()` returns `[]` (any type), and its column is `'id'`.
- `FetchShopifyProductsJob`: change `?string $storeId` to `string|array|null $storeIds`, and keep
  `$ignoreAutoGenerate` as the second argument. `OyderaMasterSwitch` calls it with
  `($store->id, true)`, and that still works because a string becomes `[id]`.
- If a job logs or tracks store names, use `$this->store_name` in its `updateTrackingRecord` tags.

**Group B pattern:** same as Group A. Add the `scopeStoreQuery(...)` call to the hard-coded query.
- `OdemobileListingItemSyncJob`: when `storeIds` is set, use `whereIn('store.id', ...)` instead of
  the hard-coded `whereIn('store.name', [...])`. Otherwise keep the name list as the default.
  Its `eligibleStoreTypes()` is `['shopify']`. The modal preselects the 3 names (Step 5).
- `Sync\ShopifyOnlySyncJob`: scope the outer store loop. The child `ShopifyBrandPageSyncJob` /
  `ShopifyIndvPagesSyncJob` jobs already take a store, so nothing else changes.

**Group C:** `OyderaMasterSwitch`: widen `?string $storeId` to `string|array|null $storeIds`.
Keep the `STORE_NAMES` default when it is `null`, and keep the order sort only for the default
case. Update `OyderaMasterSwitchCommand` to take `{store?*}` (several names or ids).

**Compatibility check for the scheduler:** `routes/console.php` calls `new $class()` when
`parameters` is null, so `storeIds` is `null` and behaviour is unchanged. Do **not** change
`console.php`.

### Step 4 — Runner service (shared by both dashboards)

`app/Services/Jobs/StoreScopedJobRunner.php` has one job: turn "a class name plus a store
selection" into job instances.

```php
class StoreScopedJobRunner
{
    public function isStoreScoped(string $class): bool;               // is_subclass_of(StoreScopedJob)

    /** Eligible stores for one or more classes (union of types; [] type list = all types). */
    public function eligibleStores(array $classes): Collection;       // store_status <> 0, ordered by type, name

    /** Stores to preselect: cron mapping for the class if any, else OyderaMasterSwitch::STORE_NAMES-style defaults. */
    public function preselectedStoreIds(string $class, ?string $cronJobId = null): array;

    /**
     * Build instances. $storeIds === null => default behaviour.
     * multi     => [new $class($storeIds)]
     * per_store => one new $class($id) per store (requires non-null $storeIds)
     * not scoped => [new $class()]
     */
    public function build(string $class, ?array $storeIds): array;

    /** Throws ValidationException if any id is outside eligibleStores($classes). */
    public function assertEligible(array $classes, array $storeIds): void;
}
```

For a Saved Job with parents, `eligibleStores()` takes the **union** over every store-scoped class
in the chain. When the jobs are built, each class only gets the ids that match its own store type.
For example, a chain with a Shopify job and an eBay job, run with a mixed selection, gives each
job only its own stores. If a class ends up with no stores after this filter, it is **skipped**
rather than being created with `null`, because `null` would mean "all stores".

### Step 5 — Routes and controllers

`routes/web.php`: move the `/jobs*` routes into the `auth` group, then add:

```php
Route::get('/jobs/run-stores', [JobSchedulerController::class, 'runStores'])->name('jobs.run_stores');
// existing, now accepting scope + store_ids[]
Route::post('/jobs/{job}/run', [JobSchedulerController::class, 'run'])->name('jobs.run');
Route::post('/scheduled_jobs/{job}/run', [ScheduledjobsController::class, 'run'])->name('scheduled_jobs.run');
```

`runStores` (JSON) takes `source=saved|cron` and `job=<id>`. It works out the list of classes
(for Saved Jobs this includes `getAllParents()`) and returns:

```json
{ "scoped": true, "types": ["shopify"], "stores": [{"id":"…","name":"UZID","type":"Shopify","preselected":true}] }
```

Put the store-scope validation for both `run` actions in a new `app/Http/Requests/RunJobRequest.php`:

```php
'scope'       => 'nullable|in:all,selected',
'store_ids'   => 'required_if:scope,selected|array|min:1',
'store_ids.*' => 'string|exists:store,id',
```

Then call `$runner->assertEligible(...)`.

`JobSchedulerController::run`: replace both `new $jobClass()` calls with
`$runner->build($class, $storeIds)` and flatten the results into the `Bus::chain`. The flash
message should list the store names.

`ScheduledjobsController::run`: same idea. Also fix the `parameters` spreading (bug 4.2). If a
cron row has fixed `parameters` **and** is store-scoped, the store ids go in as the first
argument. Document that the store ids are always the job's first constructor parameter.

### Step 6 — The modal (one partial, used by both lists)

`resources/views/partials/run-store-modal.blade.php` + `@section('plugins.Select2', true)` on
`jobs/index` and `scheduled_jobs/index` (Select2 is registered in `config/adminlte.php` but inactive).

- On the list page, give each store-scoped row's Run button `data-store-scoped="1"` and
  `data-stores-url`. The controller sets this using `$runner->isStoreScoped()` on the job class
  and its parents, so no extra request is needed to decide whether to show the modal.
- Non-scoped rows keep the existing `<form method="POST">`.
- Modal contents:
  - Radio buttons: **All eligible stores (N)** / **Only these stores**. The default is "only these"
    when something is preselected, otherwise "all".
  - A Select2 multi-select grouped by store type (`<optgroup>`), with search. Stores with
    `store_status = 0` are not listed.
  - **Select all / Clear** buttons, and a live count on the submit button ("Run for 3 stores").
  - A disabled submit button when "only these" is chosen and nothing is selected.
  - A line explaining what is eligible, e.g. *"Eligible: Shopify stores that are active"*.
- Submit as a normal form POST (keeps the existing flash-message pattern). Hidden inputs:
  `scope`, `store_ids[]`, `_token`.

### Step 7 — Cron Jobs edit screen (improve, don't replace)

Keep `store_scheduled_cron_jobs` as the **saved default for scheduled runs**. It is a different
thing from the one-off selection made at run time. Improvements:

- Limit the store-type list to `eligibleStoreTypes()` of the row's class, and preselect those types.
- Change `getStore` to return JSON and build the rows on the client, or reuse `jobs.run_stores`,
  instead of sending HTML strings from the controller.
- Fix bug 4.5 (always delete the old mappings, then insert).
- Add a hint: *"No stores selected = runs for all eligible stores."*
- In the Run modal, preselect from this mapping so "Run Now" matches what the cron would do.

### Step 8 — Tracking and visibility

- `$store_name` (the store names joined with commas) is picked up at dispatch time by the
  `Queue::createPayloadUsing` hook in `AppServiceProvider`, so the `job_statuses` tags show which
  stores a run covered. Check in `/queue_jobs` that the filter by tag works.
- Optional: add `triggered_by` (user name) to the tracking tags so manual scoped runs can be told
  apart from cron runs.

### Step 9 — Tests (run by path, see CLAUDE.md)

`tests/Services/StoreScopedJobRunnerTest.php`:
- `build()` with `null` gives one instance with `storeIds === null`.
- `build()` with ids in multi mode gives one instance with those ids.
- `build()` in per_store mode gives N instances.
- `build()` on a non-scoped class gives `new $class()`.
- `assertEligible()` rejects a Shopify job given an eBay store id, and rejects inactive stores.
- Chain filtering: a mixed selection is split correctly by type, and a class with no stores left is skipped.

`tests/Jobs/ScopesToStoresTest.php`:
- `[]` becomes `null`, and a string becomes `[string]`.
- `resolveStoreIds()` prefers explicit ids over the cron mapping.

Tests hit the real MySQL database, so create and remove your own `store` rows in them.

### Step 10 — Rollout order

1. Bugs 4.1–4.3 (auth on `/jobs`, `parameters` spreading, `getActiveStoreJobs` matching). Deploy on their own.
2. Contract + trait + runner + tests.
3. Convert **one** job end-to-end (suggested: `FetchShopifyOrders`), add the modal to Saved Jobs, and test it manually.
4. Convert the rest of Group A (mechanical), then Groups B and C.
5. Add the modal to Cron Jobs, plus the edit-screen improvements (Step 7).
6. `./restart.sh` so the workers pick up the new constructors. **Jobs already in the queue were
   serialized without `storeIds`.** They deserialize with the default `null`, which is safe.

---

## 6. Open questions / later

1. **Permissions:** should running a job for chosen stores be limited to certain users? There is
   no role check on either dashboard today.
2. **One queue job or one per store?** "Multi" mode keeps today's behaviour (one job loops over
   the stores). Splitting into one job per store would allow parallel runs and retrying a single
   store, but it would change the tracking tree. Recommendation: stay with multi for now.
3. **`SyncAllOrderStatusJob`** works per order operation across every marketplace. Scoping it
   means adding `whereHas('order', fn ($q) => $q->whereIn('store_id', ...))`. Its store type list
   is "any". Left for phase 2 because it is the heaviest job.
4. **Merge the two dashboards?** Saved Jobs (`scheduled_jobs`) and Cron Jobs
   (`scheduled_cron_jobs`) overlap heavily. The runner service works for both, so merging them
   later only touches the UI.
