# Status Update — Rebuilding the Shopify store data from Oydera

**Date:** 2026-09-17
**Stores:** UZID, OdeMobile, AccessoryChoice

## The short version

We are clearing out the old listing data for the three Shopify stores and re-importing it from
Oydera. The work splits into three parts: **delete**, **protect what must not be deleted**, and
**re-sync**. Parts 1 and 3 are built and tested locally. Part 2 is built too, but it is the piece
that needs care before we touch production.

---

## 1. Deleting the old records

**The problem.** Deleting `store_product`, `listing_item`, `listing_item_detail` and
`listing_item_product_images` with plain SQL was running far too long. The tables are big
(`listing_item_detail` alone is ~7.5M rows overall, ~600-770k per store) and every delete statement
was re-running the same three-table join to work out which rows to touch.

**What we did.** We work out the list of IDs once, store it in temporary staging tables, and then
delete against that list. Each delete becomes a simple indexed lookup instead of a large join, and
the big table is deleted in batches so it can be stopped and resumed.

We wrapped this in commands rather than loose SQL, so it runs the same way every time:

```
php artisan oydera:purge UZID --dry-run     # report only, deletes nothing
php artisan oydera:purge UZID               # the real thing
```

It runs per store. The dry run always comes first — it shows exactly what would be removed before
anything happens.

**One thing worth knowing:** most of these tables have **no foreign keys** linking them together.
Deleting a listing item does *not* clean up its details, images or links — those have to be deleted
explicitly, in the right order. Getting that order wrong leaves orphaned rows that are then very
hard to find. The command handles the ordering.

---

## 2. The cascade problem (the one to watch)

**The problem.** Some tables *are* linked, with "delete the parent, delete the child" rules
attached. `order_item` is linked to `listing_item` that way. So deleting a listing item silently
deletes the **order lines** sold under it — and their attributes and shipment lines in turn.

Here is the actual exposure we measured:

| Store | Order lines that would be deleted | Orders affected |
|---|---:|---:|
| UZID | **0** | 0 |
| OdeMobile | **20,455** | 19,639 |
| AccessoryChoice | **1,882** | 1,832 |

UZID is genuinely clean — which is exactly why the original SQL looked safe when we tested it. It
is not safe for the other two.

**Our solution.** We do not delete the order lines and we do not restore them from a backup.
Instead, because the re-sync re-creates listing items with the *same IDs* as before, we can:

1. save the order line → listing item pairs to a side table,
2. blank the link (which switches the cascade off),
3. delete and re-sync as normal,
4. put the links back.

No order row is ever deleted. There is a `oydera:purge-relink` command for step 4.

**Also found:** a second link of the same kind, from helpdesk tickets to listing items, which chains
all the way to the `order` table itself — meaning it could delete whole orders, not just order
lines. It is currently inactive (the field is empty on every ticket), so it cannot fire today. The
command refuses to run if it ever becomes active, rather than trusting that it stays empty.

**Status:** the mechanism is written and tested locally. It has **not** been run against production
yet. That is the remaining gate.

---

## 3. Syncing the data back

**What we did.** There is now one master job that handles all three stores:

```
php artisan oydera:master-switch          # all three stores
php artisan oydera:master-switch UZID     # one store
```

It can also be started from the Scheduled Jobs screen in the admin UI.

For each store it runs four steps in order — store products, listing items, listing item details,
then images — and waits for each to finish before starting the next, because each step reads what
the previous one wrote.

**Why this is different from before.** The old sync was a single job that swept every store and
every table in one pass. It took a long time, and if it failed anywhere you had to start again from
the beginning. We split it so each step is one store and one table. That means:

- a failure only costs that one step, not the whole run,
- you can re-run a single store or a single step,
- the progress dashboard now shows which store and which step is running (e.g. `UZID listing_item`)
  instead of one long opaque job.

We also fixed the image sync, which had a single store ID hardcoded in the code — it only ever
synced one store, and changing stores meant editing the file. It now takes the store as a
parameter like the others.

---

## What is left

1. **Run the purge on UZID in production first.** It is the only store with zero order-line
   exposure, so a mistake there cannot cost order history. It is also the smallest.
2. **Then AccessoryChoice (1,882 order lines), then OdeMobile (20,455)** — smallest risk first.
3. **Take a database backup before the first production run.**
4. Full cycle per store: purge → re-sync → restore order links → verify.

The purge command checks its own work: after deleting, it confirms the four tables are empty for
that store *and* that the order tables have exactly the same row count as before it started. If
either check fails it stops and tells you not to run the sync.

## One open question

UZID has 28,651 store products but only 1,934 listing items — about 93% of them carry nothing.
The re-sync updates store products in place anyway, so we may not need to delete them at all.
Skipping that would make the whole operation noticeably smaller and lower risk. Worth a decision
before the production run. The command already supports it (`--keep-store-product`).

## Reference

| Document | Covers |
|---|---|
| `docs/shopify_switch.md` | How to run everything, step by step |
| `docs/superpowers/plans/2026-09-17-pre-sync-purge-plan.md` | The delete plan and the reasoning |
| `docs/analysis/2026-09-17-deletion-impact-store-product-listing-item.md` | Full map of what deletes what |
