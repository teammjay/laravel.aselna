# Plan — Purging Shopify store data before an Oydera re-sync

**Date:** 2026-09-17
**Goal:** Empty `store_product`, `listing_item`, `listing_item_detail` and
`listing_item_product_images` for a Shopify store, so `OyderaMasterSwitch` can repopulate them
from a clean base.
**Status:** implemented. The SQL below is what `app/Console/Commands/Purge/` runs — see
`oydera:purge`, `oydera:purge-relink`, `oydera:purge-teardown`, and the operating summary in
`docs/shopify_switch.md`. The plan is kept as the reasoning behind those commands.

Scope: `UZID`, `OdeMobile`, `AccessoryChoice`. The plan is written so one store can be done first
and the other two later, with no change to the SQL other than the store id.

---

## 1. Stop before you run the draft SQL

The three statements in the request do the right thing for `UZID` and **quietly destroy order
history for the other two stores**.

`order_item.listing_item_id` is `ON DELETE CASCADE` (`order_item_listing_item_id_foreign`). So
`DELETE li FROM listing_item li JOIN store_product sp ...` does not only delete listing items — it
deletes the order lines sold under them, and their children cascade in turn. Measured today:

| Store | `order_item` deleted | `order_item_attribute` | `order_operation_item` | distinct orders affected |
|---|---:|---:|---:|---:|
| UZID | **0** | 0 | 0 | 0 |
| OdeMobile | **20,455** | 37,734 | 21,581 | 19,639 |
| AccessoryChoice | **1,882** | 3,222 | 1,963 | 1,832 |
| **Total** | **22,337** | **40,956** | **23,544** | **21,471** |

UZID is genuinely clean, which is why the draft SQL looks safe. It is not safe for the other two.
Section 4 defuses this.

There is a second, dormant cascade: `hd_ticket.related_listing_item_id` is also `ON DELETE CASCADE`,
and `order.hd_ticket_id` cascades from `hd_ticket` — so a populated ticket link would let a listing
item delete take whole **orders** with it. The column is NULL on all 41,021 tickets today, so it
cannot fire. Re-check it before each run rather than assuming.

Full cascade map: `docs/analysis/2026-09-17-deletion-impact-store-product-listing-item.md`.

## 2. What actually has to be deleted

Only two of the four tables have a foreign key, so most of the work must be explicit — nothing
cascades from `listing_item` into details, images, or links.

| # | Table | Linked by | Cascades? | Must delete explicitly? |
|---|---|---|---|---|
| 1 | `listing_item_product_images` | `listing_item_id` | no FK | **yes** |
| 2 | `listing_item_detail` | `listing_item_id` | no FK | **yes** |
| 3 | `listing_item_link` | `item_id` | no FK | yes (0 rows for all three stores — verify) |
| 4 | `listing_item_history` | `item_id`, `store_product_id` | no FK | yes (0 rows for all three stores — verify) |
| 5 | `listing_item` | `store_product_id` | → `order_item` (**cascade**) | yes |
| 6 | `store_product` | `store_id` | → `store_product_image` (empty) | yes |

Delete in that order: children first, parents last. Reversing it strands rows that can no longer
be found, because once `listing_item` is gone there is nothing left to join `listing_item_detail` to.

### Volume per store

| Store | `store_product` | `listing_item` | `listing_item_detail` | `listing_item_product_images` |
|---|---:|---:|---:|---:|
| UZID | 28,651 | 1,934 | 593,616 | 16,122 |
| OdeMobile | 29,207 | 7,289 | 766,113 | 10,009 |
| AccessoryChoice | 2,334 | 2,334 | 628,904 | 0 |

`listing_item_detail` is the only table large enough to need batching.

## 3. Staging tables

The draft SQL re-derives the same three-table join on every statement, against a 7.5M-row table.
Materialising the id sets once turns each delete into an indexed join against a small table, and
makes the work restartable.

### Use permanent tables, not `TEMPORARY`

Two reasons `CREATE TEMPORARY TABLE` will not do:

- A temp table dies with the connection. Tinker, a queue worker and a mysql shell are three
  different sessions, and a batched delete that runs for an hour will not survive a dropped one.
- MySQL cannot open the same `TEMPORARY` table twice in one statement, which rules out the
  `... WHERE id IN (SELECT ... FROM tmp)` shape used throughout.

Create real tables prefixed `purge_`, and drop them in step 6.

### One pair of tables, not one pair per store

The request suggested `uzid_store_ids`, `uzid_listing_ids`. Carrying a `store_id` column instead
means the same tables and the same SQL cover one store today and all three later — the only thing
that changes is the `WHERE store_id = ...` filter on the populate step.

```sql
CREATE TABLE purge_store_product_ids (
    store_product_id CHAR(36)    NOT NULL,
    store_id         CHAR(36)    NOT NULL,
    PRIMARY KEY (store_product_id),
    KEY idx_store (store_id)
) ENGINE=InnoDB;

CREATE TABLE purge_listing_item_ids (
    listing_item_id  CHAR(36)    NOT NULL,
    store_id         CHAR(36)    NOT NULL,
    batch_no         INT         NOT NULL DEFAULT 0,
    PRIMARY KEY (listing_item_id),
    KEY idx_store_batch (store_id, batch_no)
) ENGINE=InnoDB;
```

### Populate (run once per store)

```sql
SET @store_id = 'e4424bb0-87f7-ce9e-90c3-6d9f491bc726';   -- UZID

INSERT INTO purge_store_product_ids (store_product_id, store_id)
SELECT id, store_id FROM store_product WHERE store_id = @store_id;

INSERT INTO purge_listing_item_ids (listing_item_id, store_id)
SELECT li.id, @store_id
FROM listing_item li
JOIN purge_store_product_ids p ON p.store_product_id = li.store_product_id
WHERE p.store_id = @store_id;

-- ~500 listing items per batch; details are the only table that needs this
SET @row = 0;
UPDATE purge_listing_item_ids
SET batch_no = (@row := @row + 1) DIV 500
WHERE store_id = @store_id;
```

Store ids:

| Store | id |
|---|---|
| UZID | `e4424bb0-87f7-ce9e-90c3-6d9f491bc726` |
| OdeMobile | `6c7009ce-eac1-a995-a986-27d5df0103ac` |
| AccessoryChoice | `99ede22c-fc39-7f9f-cd0d-34d4b9a70c21` |

`OdeMobile-C` (`8a6f1c6c-...`) is a different store. Do not include it by accident.

### Record the counts

Before deleting anything, snapshot what you expect to remove, so step 5 has something to check
against:

```sql
SELECT
  (SELECT COUNT(*) FROM purge_store_product_ids WHERE store_id = @store_id) AS store_products,
  (SELECT COUNT(*) FROM purge_listing_item_ids  WHERE store_id = @store_id) AS listing_items,
  (SELECT COUNT(*) FROM listing_item_detail d
     JOIN purge_listing_item_ids p ON p.listing_item_id = d.listing_item_id
    WHERE p.store_id = @store_id) AS details,
  (SELECT COUNT(*) FROM listing_item_product_images i
     JOIN purge_listing_item_ids p ON p.listing_item_id = i.listing_item_id
    WHERE p.store_id = @store_id) AS images;
```

## 4. Protecting the order lines

Do this **before** deleting `listing_item`, for every store. It is a no-op on UZID and the whole
ballgame on the other two.

The key fact that makes this cheap: **the sync preserves ids.** `SyncShopifyItems` upserts with
`ListingItem::updateOrCreate(['id' => $item->id], ...)` and `StoreProduct::updateOrCreate(['id' =>
$storeProduct->id], ...)`, both using Oydera's own ids. A listing item deleted and re-synced comes
back with the **same primary key**. So the link can be parked and restored, rather than lost.

`order_item.listing_item_id` is nullable (40,805 of 95,694 rows are already NULL), so nulling it
is valid and defuses the cascade completely.

```sql
-- 4a. park the links
CREATE TABLE purge_order_item_links (
    order_item_id   CHAR(36) NOT NULL,
    listing_item_id CHAR(36) NOT NULL,
    store_id        CHAR(36) NOT NULL,
    PRIMARY KEY (order_item_id),
    KEY idx_listing (listing_item_id)
) ENGINE=InnoDB;

INSERT INTO purge_order_item_links (order_item_id, listing_item_id, store_id)
SELECT oi.id, oi.listing_item_id, @store_id
FROM order_item oi
JOIN purge_listing_item_ids p ON p.listing_item_id = oi.listing_item_id
WHERE p.store_id = @store_id;

-- 4b. break the link so the cascade has nothing to follow
UPDATE order_item oi
JOIN purge_order_item_links l ON l.order_item_id = oi.id
SET oi.listing_item_id = NULL
WHERE l.store_id = @store_id;
```

After the purge **and** the re-sync (section 7), put them back:

```sql
UPDATE order_item oi
JOIN purge_order_item_links l ON l.order_item_id = oi.id
JOIN listing_item li         ON li.id = l.listing_item_id   -- only where it came back
SET oi.listing_item_id = l.listing_item_id
WHERE l.store_id = @store_id;

-- anything left unrestored is a listing item Oydera no longer has
SELECT COUNT(*) FROM purge_order_item_links l
LEFT JOIN listing_item li ON li.id = l.listing_item_id
WHERE l.store_id = @store_id AND li.id IS NULL;
```

Nothing is lost if that last count is non-zero — the order line keeps a NULL link, exactly as the
40,805 rows that already have one.

> If you would rather not carry the relink step, the alternative is to accept the loss and take a
> `mysqldump` of `order_item`, `order_item_attribute` and `order_operation_item` first. That is a
> worse trade: restoring from a dump is all-or-nothing and will clash with orders imported in the
> meantime.

## 5. The deletes

**Do not** disable `foreign_key_checks` — the cascade is the thing being managed, and switching
checks off would leave dangling `order_item` rows instead of protecting them.

> **Implementation note.** `oydera:purge` does *not* wrap this in a transaction. The detail delete
> reaches ~766k rows for OdeMobile, which is too large for one unit of work, so it autocommits batch
> by batch. Restartability comes from the staging tables and `batch_no` instead — re-running the
> command for the same store re-stages and carries on.

```sql
-- 1. images  (16,122 rows worst case — single statement is fine)
DELETE i FROM listing_item_product_images i
JOIN purge_listing_item_ids p ON p.listing_item_id = i.listing_item_id
WHERE p.store_id = @store_id;

-- 2. details — batched, ~766k rows worst case.
--    Repeat for batch_no 0..N until it stops matching rows.
DELETE d FROM listing_item_detail d
JOIN purge_listing_item_ids p ON p.listing_item_id = d.listing_item_id
WHERE p.store_id = @store_id AND p.batch_no = @batch;

-- 3. links and history (expected 0 — confirm first, see §2)
DELETE l FROM listing_item_link l
JOIN purge_listing_item_ids p ON p.listing_item_id = l.item_id
WHERE p.store_id = @store_id;

DELETE h FROM listing_item_history h
JOIN purge_listing_item_ids p ON p.listing_item_id = h.item_id
WHERE p.store_id = @store_id;

-- 4. listing items  (cascade is now inert — §4 must have run)
DELETE li FROM listing_item li
JOIN purge_listing_item_ids p ON p.listing_item_id = li.id
WHERE p.store_id = @store_id;

-- 5. store products
DELETE sp FROM store_product sp
JOIN purge_store_product_ids p ON p.store_product_id = sp.id
WHERE p.store_id = @store_id;
```

Batching note: MySQL multi-table `DELETE` does not accept `LIMIT`, which is why the detail delete
is driven by `batch_no` rather than `LIMIT 5000`. Batching by a pre-assigned column is also
restartable — a batch that fails can be re-run without re-deriving anything.

`listing_item_link` has **no index on `item_id`** (only a PRIMARY KEY on `id`), so statement 3 is a
full scan of ~88k rows. Harmless at that size; do not extend the pattern to a larger table without
adding an index.

### Expected safety check after each store

```sql
SELECT
  (SELECT COUNT(*) FROM store_product WHERE store_id = @store_id)                          AS sp_left,
  (SELECT COUNT(*) FROM listing_item li JOIN purge_listing_item_ids p
      ON p.listing_item_id = li.id WHERE p.store_id = @store_id)                           AS li_left,
  (SELECT COUNT(*) FROM listing_item_detail d JOIN purge_listing_item_ids p
      ON p.listing_item_id = d.listing_item_id WHERE p.store_id = @store_id)               AS lid_left,
  (SELECT COUNT(*) FROM listing_item_product_images i JOIN purge_listing_item_ids p
      ON p.listing_item_id = i.listing_item_id WHERE p.store_id = @store_id)               AS img_left;
-- all four must be 0
```

And the one that matters most — no order line was harmed:

```sql
SELECT COUNT(*) FROM order_item;                 -- compare against the pre-purge number
SELECT COUNT(*) FROM order_item_attribute;
SELECT COUNT(*) FROM order_operation_item;
```

## 6. Teardown

Keep `purge_order_item_links` until the re-sync has run and the relink in §4 is done. The other two
can go as soon as the store's verification passes.

```sql
DROP TABLE purge_store_product_ids;
DROP TABLE purge_listing_item_ids;
DROP TABLE purge_order_item_links;   -- only after §4 relink
```

## 7. Sequence per store

1. Confirm `hd_ticket.related_listing_item_id` is still all NULL (§1).
2. Turn `auto_generate_listings = 0` on the store, so nothing pushes to Shopify mid-purge.
3. Stop the queue workers, or at least make sure no `OyderaMasterSwitch` chain is in flight.
4. Build the staging tables and record the counts (§3).
5. Park the order links (§4a, §4b).
6. Run the deletes in order (§5).
7. Run the safety checks (§5).
8. Re-sync: `php artisan oydera:master-switch <store>`.
9. Restore the order links (§4).
10. Verify against `docs/shopify_switch.md`.
11. Drop the staging tables (§6).

## 8. One store or all three

**Do UZID first, alone.** It is the only store with zero order-line exposure, so a mistake there
cannot cost order history. It also has the smallest `listing_item` count (1,934), so a full
purge-and-resync cycle is the quickest to observe end to end.

Once UZID has been through the whole sequence and `docs/shopify_switch.md` verification passes,
repeat for `AccessoryChoice` (1,882 order lines) and then `OdeMobile` (20,455) — smaller exposure
first. The staging tables carry `store_id`, so this is the same SQL with a different `@store_id`;
nothing needs rewriting between runs.

Do **not** run all three in one transaction. Three stores at once means ~2M detail rows and ~22k
parked order links in a single unit of work, and a failure at the end rolls back a purge that took
hours.

## 9. Open questions

- **Is `store_product` meant to be purged at all?** UZID has 28,651 store products but only 1,934
  listing items, so ~93% of them carry nothing. If the goal is only to rebuild listings, deleting
  `listing_item` and below and leaving `store_product` alone is a much smaller operation, and the
  sync's `updateOrCreate` refreshes the store product rows in place anyway.
- **`OdemobileListingItemSyncJob` overlaps with the sync.** It writes the same `store_product` and
  `listing_item` rows for the same three stores. Confirm which of the two paths repopulates after
  the purge before running either — see `docs/shopify_switch.md` §"Relationship to
  OdemobileListingItemSyncJob".
- **Take a backup first.** At minimum `mysqldump` the six tables in §2 plus `order_item` for the
  target store before the first run.
