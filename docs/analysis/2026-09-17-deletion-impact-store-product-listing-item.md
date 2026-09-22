# Deletion Impact Analysis — `store_product`, `listing_item`, `listing_item_detail`

**Date:** 2026-09-17
**Scope:** What data is destroyed, and what is silently left behind, when rows are deleted from
`store_product`, `listing_item`, or `listing_item_detail`.
**No code was changed.** This is a read-only analysis.

## Method

Two independent sources were used, because the migrations are not trustworthy on their own
(many FK definitions in `database/migrations/` are commented out or dropped by later patches):

1. **Live schema** — the actual InnoDB foreign-key graph of the primary `mysql` connection,
   read from `information_schema.KEY_COLUMN_USAGE` + `REFERENTIAL_CONSTRAINTS`, then walked
   recursively to follow `ON DELETE CASCADE` chains.
2. **Code** — a repo-wide grep of `app/`, `routes/`, `database/` for `delete()` / `destroy()` /
   `DELETE FROM` / `truncate()` touching these tables or their models.

All row counts below were measured against the live database on 2026-09-17.

## Headline findings

1. **No application code deletes from these three tables.** There is no controller action, job,
   command, or seeder that removes a `store_product`, `listing_item`, or `listing_item_detail`
   row. Any deletion is manual (SQL / tinker), so the behaviour is decided entirely by InnoDB
   foreign keys — not by application logic.
2. **Deleting a `listing_item` deletes order data.** `order_item.listing_item_id` is
   `ON DELETE CASCADE`, and 54,889 of 95,694 `order_item` rows carry a `listing_item_id`.
   Deleting listing items destroys order lines and, through them, shipment/return operation
   items and manufacturing order items.
3. **A dormant but far worse path exists.** `hd_ticket.related_listing_item_id` is also
   `ON DELETE CASCADE`, and `order.hd_ticket_id` cascades from `hd_ticket`. If that column were
   ever populated, deleting one listing item could delete whole **orders**, their operations and
   the entire helpdesk thread. It is currently safe only because the column is 100% NULL.
4. **The big child tables are NOT protected by FKs and will be orphaned, not deleted.**
   `listing_item_detail` (7.49M rows), `listing_item_product_images` (267,724 rows),
   `listing_item_link` (88,836 rows) and `listing_item.store_product_id` (500,555 rows) have no
   foreign key at all. Deleting a parent leaves these rows pointing at ids that no longer exist.

## Reference row counts (live, 2026-09-17)

| Table | Rows |
|---|---|
| `store_product` | 183,165 |
| `listing_item` | 500,555 |
| `listing_item_detail` | ~7,491,000 |
| `listing_item_product_images` | 267,724 |
| `listing_item_link` | 88,836 |
| `listing_item_history` | 85 |
| `store_product_image` | 0 |
| `order_item` | 95,694 (54,889 with a `listing_item_id`) |
| `order` | 81,232 (0 with an `hd_ticket_id`) |
| `hd_ticket` | 41,021 (0 with a `related_listing_item_id`) |

---

## 1. `DELETE FROM store_product`

### Tables whose data IS deleted (cascade)

| Table | Column | Rule | Practical impact today |
|---|---|---|---|
| `store_product_image` | `store_product_id` | `CASCADE` | **None** — the table is empty (0 rows) |

That is the only foreign key in the database that points at `store_product`. In the current
data, deleting store products deletes **nothing else**.

### Tables that are orphaned (NOT deleted, silently broken)

| Table | Column | Rows affected |
|---|---|---|
| `listing_item` | `store_product_id` | 500,555 non-null (every listing item) |
| `listing_item_history` | `store_product_id` | 85 |

The `listing_item.store_product_id` FK **was written and then commented out** —
`database/migrations/2025_01_07_043724_add_foreign_key_listing_item_table.php:16`. This is
confirmed against the live schema: no such constraint exists.

### Downstream consequences of the orphans

- `ListingItem::storeProduct()` (`app/Models/ListingItem.php:58`) resolves to `null`.
- Order ingestion reads through that relation to fill `order_item.product_id`:
  - `app/Utils/order/OrderUtility.php:177` — guarded with `?? null`.
  - `app/Utils/order/OrderUtility.php:505` and `:761` — **not guarded**. On PHP 8.2 these emit
    `Attempt to read property on null` warnings and store `product_id = NULL` on the order item,
    rather than failing loudly. Orders will keep importing, but with missing product linkage.
- Feed generation and listing screens that inner-join `store_product` will silently drop the
  orphaned listing items from their result sets.

---

## 2. `DELETE FROM listing_item`

This is the destructive one. Two cascade branches exist.

### Branch A — via `order_item` (ACTIVE today)

```
listing_item
└─ order_item.listing_item_id                    [CASCADE]   54,889 rows carry a value
   ├─ order_item_attribute.order_item_id         [CASCADE]
   ├─ order_operation_item.order_item_id         [CASCADE]
   └─ manufacturing_order_item.order_item_id     [CASCADE]
```

Deleting a listing item **deletes the order lines that were sold under it**, plus their
attributes, the per-operation shipment/return line items, and any manufacturing order items.
The parent `order` and `order_operation` rows survive, so this leaves orders with missing or
zero line items — orders that appear to have been placed for nothing.

`order_item.listing_item_id` is populated on every marketplace order import
(`app/Utils/order/OrderUtility.php:181`, `:507`, `:764` — the lookup is
`ListingItem::where('sku', ...)`, so the value genuinely references `listing_item.id`).

### Branch B — via `hd_ticket` (DORMANT, but wired)

```
listing_item
└─ hd_ticket.related_listing_item_id                  [CASCADE]
   ├─ hd_message.ticket_id                            [CASCADE]
   │  ├─ hd_message_attachment.message_id             [CASCADE]
   │  └─ hd_message_part.message_id                   [CASCADE]
   ├─ hd_ticket_hd_ticket_label_link.hd_ticket_id     [CASCADE]
   ├─ order.hd_ticket_id                              [CASCADE]   <-- deletes whole ORDERS
   │  ├─ order_item.order_id                          [CASCADE]
   │  │  ├─ order_item_attribute.order_item_id        [CASCADE]
   │  │  ├─ order_operation_item.order_item_id        [CASCADE]
   │  │  └─ manufacturing_order_item.order_item_id    [CASCADE]
   │  ├─ order_operation.order_id                     [CASCADE]
   │  │  └─ order_operation_item.order_operation_id   [CASCADE]
   │  └─ manufacturing_order.order_id                 [CASCADE]
   │     └─ manufacturing_order_item.manufacturing_order_id [CASCADE]
   └─ order_operation.hd_ticket_id                    [CASCADE]
      └─ order_operation_item.order_operation_id      [CASCADE]
```

**This branch does not fire today**, because `hd_ticket.related_listing_item_id` is NULL on all
41,021 tickets, and the only two places in the codebase that write the column write `NULL`
(`app/Http/Controllers/HomeController.php:797`,
`app/Jobs/ProcessStoreMessageAccountJob.php:296`). `order.hd_ticket_id` is likewise NULL on all
81,232 orders.

It is a latent landmine: the moment ticket-to-listing linking is switched on, deleting a listing
item starts deleting orders and helpdesk history.

### Full set of tables reachable by cascade from `listing_item` (12)

`hd_ticket`, `hd_message`, `hd_message_attachment`, `hd_message_part`,
`hd_ticket_hd_ticket_label_link`, `order`, `order_operation`, `order_item`,
`order_item_attribute`, `order_operation_item`, `manufacturing_order`,
`manufacturing_order_item`.

### Tables that are orphaned (NOT deleted)

| Table | Column | Rows in table | Note |
|---|---|---|---|
| `listing_item_detail` | `listing_item_id` | ~7,491,000 | no FK — the per-device variant rows survive their parent |
| `listing_item_product_images` | `listing_item_id` | 267,724 | no FK |
| `listing_item_link` | `item_id` | 88,836 | no FK; all 88,836 resolve to `listing_item.id`, 0 to `listing_item_detail.id` |
| `listing_item_history` | `item_id`, `store_product_id` | 85 | no FK (`ListingItemHistory::createBackup()` sets `item_id`, `app/Models/ListingItemHistory.php:76`) |
| `listing_item` (self) | `parent_id`, `replaced_id`, `linked_item_id` | — | no self-FK; child/variant rows keep dangling parent pointers |

Note the asymmetry: **the small, cheap-to-rebuild tables are the ones protected by cascade, and
the 7.5M-row table that actually matters is not.**

---

## 3. `DELETE FROM listing_item_detail`

### Tables whose data IS deleted (cascade)

**None.** No foreign key anywhere in the schema references `listing_item_detail`. Deleting from
it removes exactly those rows and nothing else.

Per-marketplace state lives on these rows, so from a data-integrity standpoint this is the
safest of the three to delete — but from a business standpoint it is the row that carries the
live marketplace listing state.

### Logical (non-FK) breakage to expect

Consumers find these rows by `sku`, not by id, so nothing errors — results just come back empty:

- `app/Utils/order/OrderUtility.php:487` — `ListingItemDetail::where('sku', $item['sku'])->first()`
  during order import; a missing row means the device/variant detail is not resolved.
- `app/Models/StoreTypeListing.php:52` and `:75` — SKU-existence checks used during listing
  generation; deleted rows make previously-used SKUs look free and eligible for reuse.
- `app/Services/Marketplace/Service/EbayService.php:355` and `:544` — eBay variation payloads are
  built from `ListingItemDetail::where('listing_item_id', ...)`. Deleting details silently
  shrinks or empties the variation set in the next generated feed, which the marketplace will
  read as "these variants were removed".

---

## 4. Cross-cutting notes

- **Engine.** Every table involved is InnoDB, so the FK cascades are genuinely enforced.
- **FK checks are never disabled.** No `SET FOREIGN_KEY_CHECKS=0`,
  `Schema::disableForeignKeyConstraints()`, or `withoutForeignKeyConstraints()` anywhere in
  `app/`, `database/`, or `config/`.
- **No model-level cleanup exists.** `StoreProduct`, `ListingItem` and `ListingItemDetail` each
  define only a `creating` hook that assigns a UUID (`app/Models/StoreProduct.php:20`,
  `app/Models/ListingItem.php:18`, `app/Models/ListingItemDetail.php:18`). There are no
  `deleting` / `deleted` observers, so Eloquent `->delete()` and raw SQL behave identically —
  neither performs any application-side tidy-up.
- **Bulk deletes behave the same.** `Model::where(...)->delete()` issues one SQL `DELETE`, which
  triggers the same cascades and skips model events entirely.
- **Inbound direction (for awareness).** Deleting a row from `products` cascades into
  `store_product` via `store_product.product_id` — see
  `database/migrations/2024_11_18_112756_create_store_product_table.php:17` and
  `CatalogController.php:789` (`$product->delete()`), which is a live, UI-reachable path into
  this same blast radius.

## 5. Read-only impact queries to run before any delete

These estimate the blast radius without changing anything. Substitute the real target ids.

```sql
-- store_product: what gets deleted, what gets orphaned
SELECT COUNT(*) FROM store_product_image WHERE store_product_id IN (:ids);   -- deleted
SELECT COUNT(*) FROM listing_item        WHERE store_product_id IN (:ids);   -- ORPHANED
SELECT COUNT(*) FROM listing_item_history WHERE store_product_id IN (:ids);  -- ORPHANED

-- listing_item: what gets deleted
SELECT COUNT(*) FROM order_item WHERE listing_item_id IN (:ids);             -- DELETED
SELECT COUNT(*) FROM order_operation_item oi
  JOIN order_item i ON i.id = oi.order_item_id
 WHERE i.listing_item_id IN (:ids);                                          -- DELETED
SELECT COUNT(*) FROM order_item_attribute a
  JOIN order_item i ON i.id = a.order_item_id
 WHERE i.listing_item_id IN (:ids);                                          -- DELETED
SELECT COUNT(*) FROM hd_ticket WHERE related_listing_item_id IN (:ids);      -- DELETED (0 today)

-- listing_item: what gets orphaned
SELECT COUNT(*) FROM listing_item_detail        WHERE listing_item_id IN (:ids);
SELECT COUNT(*) FROM listing_item_product_images WHERE listing_item_id IN (:ids);
SELECT COUNT(*) FROM listing_item_link          WHERE item_id IN (:ids);
SELECT COUNT(*) FROM listing_item_history       WHERE item_id IN (:ids);
SELECT COUNT(*) FROM listing_item               WHERE parent_id IN (:ids)
                                                   OR replaced_id IN (:ids)
                                                   OR linked_item_id IN (:ids);

-- listing_item_detail: nothing cascades; only the rows themselves go
SELECT COUNT(*) FROM listing_item_detail WHERE id IN (:ids);
```

## 6. Summary table

| Deleting from | Cascade-deleted tables | Orphaned tables (no FK) |
|---|---|---|
| `store_product` | `store_product_image` (empty today) | `listing_item`, `listing_item_history` |
| `listing_item` | `order_item`, `order_item_attribute`, `order_operation_item`, `manufacturing_order_item` — plus, if `related_listing_item_id` is ever populated: `hd_ticket`, `hd_message`, `hd_message_attachment`, `hd_message_part`, `hd_ticket_hd_ticket_label_link`, `order`, `order_operation`, `manufacturing_order` | `listing_item_detail`, `listing_item_product_images`, `listing_item_link`, `listing_item_history`, `listing_item` (self-refs) |
| `listing_item_detail` | *(none)* | *(none — SKU-keyed consumers break silently)* |
