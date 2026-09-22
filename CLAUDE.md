# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Aselna** — a Laravel 11 (PHP 8.2+) ERP / marketplace management system that centralizes listings, orders, shipments, and customer messaging across Amazon (SP-API), eBay, Shopify, Magento, Walmart, Temu, NewEgg, and Google Shopping. Server-rendered Blade + AdminLTE 3 admin UI, Tailwind/Vite assets, heavy Laravel queue usage.

## Commands

```bash
composer install && npm install
npm run dev            # Vite dev server
npm run build          # production assets
composer dev           # concurrently: serve + queue:listen + pail + vite

php artisan serve
php artisan queue:work            # or queue:listen
php artisan schedule:work         # scheduler; tasks come from the DB (see routes/console.php)
./restart.sh                      # migrate + queue:restart + db:seed --class=JobScheduler
./clean_archived_logs.sh          # prune storage/logs/archived older than 14 days
```

Tests:

```bash
./vendor/bin/phpunit tests/Utils/RmaNumberGeneratorTest.php          # single file
./vendor/bin/phpunit --filter testGenerate tests/Utils/...           # single method
```

**Gotcha:** `phpunit.xml` registers only `tests/Unit` and `tests/Feature` as suites, and those hold only the stub `ExampleTest`s. The real tests live in `tests/Utils`, `tests/Jobs`, `tests/Services` and are **not** picked up by `php artisan test` / bare `phpunit` — run them by path. Tests hit the configured MySQL database (the sqlite/`:memory:` lines in `phpunit.xml` are commented out), so they are not isolated.

Formatting: `./vendor/bin/pint` (Pint is installed, no config file — defaults apply). The existing codebase is not Pint-clean; prefer matching surrounding style over reformatting untouched files.

## Domain vocabulary

- **Store** — one marketplace account/storefront (`store` table, UUID string PK). Nearly everything is scoped by `store_id`. `marketplace_name` on a Store is what selects the marketplace service.
- **Listing / ListingItem / ListingItemDetail** — the catalog hierarchy pushed out to marketplaces. `ListingItemDetail` carries per-marketplace state.
- **Feed** (`marketplace_feeds` table, model `Feed`) + `FeedPart` — the generated payload submitted to a marketplace. Feeds are chunked into parts when large.
- **Order → OrderOperation → OrderOperationItem** — an `OrderOperation` is a shipment/return/cancel action on an order; it is the unit that produces labels and print jobs.
- **Device / DeviceSeries / DeviceTypes** — the compatibility taxonomy (phone models etc.) that drives listing generation and store pages.
- **HDMessage / HdTicket*** — the integrated helpdesk (marketplace + IMAP email).

## Architecture

### Marketplace integration (the core abstraction)

`MarketplaceServiceFactory::create($marketplaceName)` maps a marketplace string (`'amazon'`, `'walmart'`, `'ebay'`, `'google shopping ads'`, `'magento'`, `'shopify'`, `'temu'`) to a service in `app/Services/Marketplace/Service/`. Every one implements `MarketplaceServiceInterface`: `generateFeed`, `submitFeed`, `getResultFeed`, `syncAttributes`. Adding a marketplace means adding the service, registering it in the factory match arm, and adding a matching `app/Services/Auth/<X>AuthService.php` (token/credential handling per store). NewEgg has an auth service but no marketplace service — it is not routed through the factory.

Feed lifecycle: `FeedGenerationJob` / `ProcessFeedGenerationJob` build the file → `submitFeed` → `FetchResultFeedAsync` / `ResultFeedSync` command pulls results back. Status transitions use `App\Constants\FeedStatus`.

### Jobs and job tracking

`app/Jobs/` is the bulk of the business logic (~100 job classes; per-marketplace subfolders `Amazon/`, `Ebay/`, `Shopify/`, `Magento/`, `Walmart/`, plus `Sync/`, `Devices/`, `Fonus/`).

Jobs that need progress tracking `use App\Jobs\Concerns\TrackableJob`, which writes to the `job_statuses` table (`JobStatus` model) with `job_id` / `chain_id` / `parent_job_id` so chained jobs form a visible tree. When combining with `Dispatchable`, the conflict must be resolved explicitly — see `ProcessFeedGenerationJob`:

```php
use Dispatchable, TrackableJob {
    TrackableJob::withChain insteadof Dispatchable;
    Dispatchable::withChain as dispatchableWithChain;
}
```

`AppServiceProvider::boot()` hooks `Queue::createPayloadUsing` and creates the tracking record at dispatch time for any job using the trait (picking up `store_name` / `store_type` properties as tags). Jobs then call `$this->updateTrackingRecord([...])` themselves in `handle()`; the `Queue::before/after/failing` listeners are present but their status updates are commented out, so **status is only as accurate as the job's own calls**.

### Scheduling

`routes/console.php` does not hardcode a schedule — it reads the `scheduled_cron_jobs` table (`ScheduledJobs` model) at boot and registers each active row as a `job`, `command`, or `callback` with its cron expression, writing `last_run_at`/`last_status` back. Two entry points matter: `php artisan jobs:sync-registry` scans `app/Jobs/` and inserts any unregistered job class as an inactive row; `db:seed --class=JobScheduler` seeds the intended schedule. Adding a scheduled task = a DB row, not a code change.

### Multiple databases

Beyond the primary `mysql` connection there are legacy/companion databases used via explicit `DB::connection(...)`, never via model `$connection`:

- `easyesale_db` / `easyesale_db_wt_prefix` — legacy source system; almost every `*SyncJob` reads from it to backfill the Aselna schema.
- `fonus_db` — supplier/product source (`app/Jobs/Fonus/`).
- `aselna_printer_db` — external print spooler; work is queued by inserting into its `PrintJobs` table (see `EndicialabelService`, `OrderOperationUtility`, `OrdersController`).

### Shipping & printing

`app/Utils/order/OrderOperationUtility` orchestrates shipment creation, label purchase, credit checks, cancellation, and address mapping. `app/Utils/EndicialabelService` talks SOAP/XML to Endicia for label purchase, cancel, credit purchase, and account status, then stamps the label image and inserts a row into `aselna_printer_db.PrintJobs`. `app/Utils/shipment/ShipmentDocumentsCreator` decides which documents (label, packing slip) an `OrderOperation` produces and with what printer/format/rotation. Related constants: `PrintStatus`, `ShippingDocumentType`, `ShippingLabelFormat`, `ShippingLabelCreationType`, `ImageRotate`.

### Conventions

- **Constants over enums.** `app/Constants/` holds plain classes of `const` ints plus `names`/`badgeClasses` lookup maps and static helpers (`FeedStatus::getStatusName()`, `PrintStatus::getId()`). Statuses are stored as integers — always go through these classes rather than literals.
- **Static utility classes** in `app/Utils/` (`OrderUtility`, `ListingUtility`, `ShippingUtility`, `AmazonUtility`, `FileUtility`, `XmlUtility`/`XmlValidator`, `SkuGenerator`, `RmaNumberGenerator`, `Spintax`) and `app/Helpers/Common.php`. These are classes with static methods, **not** autoloaded global functions — call them as `Common::method()` with a `use App\Helpers\Common;`.
- **UUID string PKs** on the marketplace-facing models (`Store`, `Feed`, …): `$keyType = 'string'`, `$incrementing = false`, and a `boot()` `creating` hook assigning `Str::uuid()`. Table names are usually set explicitly via `$table` and rarely match Laravel's pluralization.
- **DataTables.** List screens are server-side DataTables: the controller builds an Eloquent `Builder`, wraps it in `App\Services\DataTable\DataTableService`, optionally calls `setCustomSearch()` / `setCustomSorting()` / `disableSearch()`, and returns `handle()`. Column-level filter UI goes through `App\Helpers\QueryFilters::applyFilter()` (operators: `contains`, `starts_with`, `in`, `is_set`, …).
- **Errors.** In background jobs and integrations report through `CriticalErrorService::handle($e, $context, $extra)` / `::report($message, ...)`, which persists to the `ErrorLog` model (via `App\Helpers\ErrorLogger`, deduplicated by a hash of message+trace) and notifies. `ErrorlogDashboardController` surfaces these in-app.
- **Logging.** `AppServiceProvider` installs a `DB::listen` that logs every `DELETE` statement with a class-history backtrace to `storage/logs/delete_queries.log`, and (only when `APP_ENV=local`) every query to `storage/logs/queries.log` — expect log volume locally. `opcodesio/log-viewer` is mounted for browsing; its `LogViewer::auth()` allowlist in `AppServiceProvider` currently contains only `admin@example.com`.
- **Menus.** The AdminLTE sidebar is configured in `config/adminlte.php`; the Tags submenu is injected dynamically from `HdTicketLabel` rows via a `BuildingMenu` listener in `AppServiceProvider`.
- Validation lives in `app/Http/Requests/`; controllers are thin CRUD (`index` renders a Blade list, `data` feeds the DataTable) and set `created_by`/`updated_by` from `auth()->user()->name`.

## Notes

- `.env` is committed in this working tree and holds live credentials for several marketplaces and databases — don't echo its values into output, commits, or external requests.
- `routes/web.php` is a single ~785-line file of resource-ish routes; `routes/api.php` is small and unauthenticated (DataTable/data endpoints and a Walmart CSV export).
- 299 migrations exist and many are `update_*_table` patches; check the latest migration for a table's real shape rather than trusting the original create migration.
- `docs/superpowers/` holds design/plan documents for in-flight work.
