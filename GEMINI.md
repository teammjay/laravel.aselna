# GEMINI.md - Project Context

## Project Overview
**Aselna** is a comprehensive e-commerce and ERP marketplace management system built on **Laravel 11**. It serves as a central hub for managing listings, orders, and shipments across multiple online marketplaces.

### Core Technologies
- **Backend:** PHP 8.2+, Laravel 11
- **Frontend:** Tailwind CSS v3, Vite, AdminLTE v3
- **Database:** MySQL/SQLite (supports Eloquent ORM)
- **Queues:** Redis (via Laravel Queues)
- **Integrations:**
    - Amazon (SP-API)
    - eBay
    - Shopify
    - Magento
    - Walmart
    - Temu
    - NewEgg
    - Google Shopping

### Key Features
- **Multi-Channel Synchronization:** Centralized management of products and listings across various platforms.
- **Order Management:** Automated fetching and processing of orders from integrated marketplaces.
- **Shipment Tracking:** Integration with carriers for document generation and tracking.
- **Helpdesk / Messaging:** Integrated system for managing customer communications (HDMessage, Tickets).
- **Background Processing:** Extensive use of Laravel Jobs for asynchronous tasks, featuring a custom tracking system for job status and chains.
- **Asset Management:** Image processing and PDF generation (FDPF).

## Building and Running

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL or SQLite

### Installation
1. **Clone and Install PHP Dependencies:**
   ```bash
   composer install
   ```
2. **Install Frontend Dependencies:**
   ```bash
   npm install
   ```
3. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Database Migration & Seeding:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
5. **Compile Assets:**
   ```bash
   npm run dev # or npm run build
   ```

### Execution
- **Web Server:** `php artisan serve`
- **Queue Worker:** `php artisan queue:work` or `php artisan queue:listen`
- **Helper Script:** `./restart.sh` (runs migrations, restarts queues, and seeds job schedules).

## Development Conventions

### Architecture & Patterns
- **Service Layer:** Marketplace logic is encapsulated in `app/Services/Marketplace/Service`. All marketplace services should implement `MarketplaceServiceInterface`.
- **Jobs & Traits:** Asynchronous jobs use the `TrackableJob` trait for monitoring progress and status in the `job_statuses` table.
- **Constants:** Enumerations for statuses (Order, Listing, Feed, etc.) are centralized in `app/Constants`.
- **Helpers:** Global utility functions are located in `app/Helpers/Common.php`.

### Coding Standards
- **Naming:** PSR-4 for class loading. Models follow standard Laravel conventions.
- **Error Handling:** Critical errors in background jobs should be reported via `CriticalErrorService`.
- **Logging:**
    - Standard Laravel logs.
    - Custom SQL logging in `AppServiceProvider` (specific logs for `DELETE` queries).
    - Integrated `LogViewer` for easier debugging.

### Testing
- Tests are located in the `tests/` directory.
- Use `php artisan test` or `./vendor/bin/phpunit` to run tests.
