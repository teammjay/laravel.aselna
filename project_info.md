# Aselna — Project Info

## 1. Project Name
**Aselna**

## 2. Client / Industry
E-commerce / multi-channel retail. The client is an online seller of mobile and electronics accessories. They run many storefronts across the major US marketplaces and their own Shopify and Magento sites.

## 3. One-line summary
Aselna is an ERP that lets one team run every marketplace store from a single dashboard. It covers product listings, orders, shipping labels, printing, and customer messages across Amazon, eBay, Walmart, Shopify, Magento, Temu, NewEgg, and Google Shopping.

## 4. Our role
**Built full-stack from scratch.** A two-developer team designed and built the whole system: data model, marketplace integrations, background job infrastructure, shipping and printing pipeline, helpdesk, and the admin UI. We also migrated the client's data off their legacy platform and onto Aselna.

## 5. Tech stack
- **Backend:** PHP 8.2, Laravel 11, Laravel Queues and Scheduler
- **Database:** MySQL (primary database plus three connected legacy, supplier, and print-spooler databases)
- **Frontend:** Blade, AdminLTE 3, Tailwind CSS, Vite, server-side DataTables
- **Marketplace APIs:** Amazon SP-API, eBay, Walmart, Shopify, Magento, Temu, NewEgg, Google Shopping
- **Shipping:** Endicia (SOAP/XML) for label purchase, cancellation, and postage credit
- **Other:** IMAP email ingestion, XML feed generation and validation, a custom job tracking system, and a Log Viewer

## 6. Problem statement
The client sold on many marketplaces with several store accounts on each. Every channel had its own seller portal, its own listing format, and its own order and shipping flow. Staff had to jump between dashboards to publish products, process orders, buy labels, and answer buyers. This caused duplicated work, inventory and listing mismatches, and slow fulfilment. They also depended on an ageing legacy system that couldn't keep up with new channels.

**The goal:** one piece of software to manage every marketplace store the client has.

## 7. Key features built
- **Unified marketplace integration layer.** One common interface (generate feed → submit → fetch results → sync attributes) covers Amazon, eBay, Walmart, Shopify, Magento, Temu, and Google Shopping, with per-store authentication. Adding a new channel means writing one service class, not rewriting the app.
- **Centralized catalog and listing engine.** One product catalog (listings, items, variants, images, specifications) is pushed to every channel. Listings are generated automatically from templates, SEO title generation (spintax), and a device-compatibility taxonomy (brand → series → device model). SKUs and UPCs are generated too.
- **Order management across all channels.** Orders from every marketplace are fetched automatically into one queue. Shipments, returns (with RMA numbers), and cancellations are handled as order operations, and tracking numbers are synced back to each marketplace.
- **Shipping and label printing pipeline.** Endicia integration handles label purchase, cancellation, and postage credit. Rules decide which carrier service and package to use. Shipping labels and packing slips are sent automatically to warehouse printers through a print-spooler queue.
- **Integrated helpdesk.** Buyer messages from the marketplaces and from email (IMAP) land in one ticketing inbox, with labels, message templates, and per-store messaging accounts.
- **Background job orchestration and monitoring.** About 140 queued jobs handle syncing, feeds, and order fetching. Schedules are managed from the database and can be changed without a deploy. Job tracking shows parent and child job chains, and an error dashboard deduplicates errors and sends alerts.

## 8. Challenges faced

**Challenge 1: Every marketplace works differently.**
Each channel has its own authentication (OAuth, SP-API tokens, API keys), its own data format (XML feeds, JSON, CSV), and its own asynchronous feed and result cycle.
*How we solved it:* We defined a single `MarketplaceServiceInterface` and a factory that picks the right service for each store. Authentication is handled by a separate service per marketplace. As a result, the rest of the app (listings, feeds, orders) never needs to know which marketplace it is talking to. Large feeds are split into parts and their results are fetched back asynchronously.

**Challenge 2: Migrating off a live legacy system without downtime.**
The client's business ran on an older platform and a separate supplier database while Aselna was being built.
*How we solved it:* We built a set of sync jobs (products, devices, listings, stores, orders, images, shipping settings, and more) that read from the legacy and supplier databases and backfill Aselna's schema. They run in dependency-ordered job chains. That let both systems run side by side and let the client switch over gradually, not all at once.

**Challenge 3: Keeping a heavy background workload visible.**
With more than a hundred scheduled and chained jobs, it was hard to tell what had run, what failed, and why.
*How we solved it:* We built a trackable-job layer that records every dispatched job together with its chain and parent, so a whole sync shows up as a tree. Scheduling moved into the database so admins can turn tasks on and off and change timings from the UI. We also added a central error service that deduplicates errors, logs them, and sends notifications, plus an in-app error dashboard.

## 9. Results / impact
- **One dashboard for 8 marketplaces** and many store accounts. Staff no longer switch between separate seller portals.
- **End-to-end fulfilment in one place:** order import → label purchase → automatic printing → tracking sync back to the marketplace.
- **About 140 automated background jobs** replaced manual syncing, listing, and order-download work.
- **Built for scale:** about 140 data models, 300 database migrations, and more than 4,400 commits over the project.
- *[Add business metrics if available, e.g. orders processed per day, number of active stores/listings, or time saved per order.]*

## 10. Timeline
**About 18 months of active development** (October 2024 to March 2026), followed by ongoing maintenance and feature work.

## 11. Screenshots / links
*[Add screenshots of the dashboard, order screen, listing generator, and helpdesk. No public link, since this is an internal client system.]*

## 12. Client testimonial
*[Optional: add a quote from the client if available.]*
