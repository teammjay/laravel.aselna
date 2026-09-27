# Duplicate-page test on OdeMobile-C

Store `8a6f1c6c-1d84-11f1-aaa9-ac1f6b9eb7e3`, Shopify `ode-clone.myshopify.com`.

## What was set up (26 Sep 2026, local DB `aselna_18_sep`)

`01_setup.sql` removed the local `store_navigation_page` rows for the 8 examples below. Their
Shopify pages were left in place. This is the same condition as the 601 UZID pages: the page exists
on Shopify, but there is no local row for it.

| Type | Page | Existing OdeMobile-C page id | Same page on UZID (id) |
|---|---|---|---|
| 90 | apple-ipod-touch-7th-gen-accessories | 132092166362 | 174985707809 |
| 90 | samsung-galaxy-z-fold6-accessories | 132092297434 | 174985806113 |
| 90 | nokia-g310-5g-accessories | 132092199130 | 174985740577 |
| 50 | fujitsu | 132137451738 | 174983741729 |
| 50 | plantronics | 132135190746 | 174983512353 |
| 50 | coby | 132136894682 | 174983872801 |
| 20 | health-household | 132160454874 | 174983348513 |
| 20 | clothing-accessories | 132160422106 | 174983315745 |

A dry run after the setup predicts 9 duplicates with the old code: these 8, plus
`alcatel-pop-star-accessories` (132121067738). That ninth one was already there, because the
catalogue has two active "Alcatel Pop Star" device records.

## Running the test

1. Run `02_check.sql`. The first query returns nothing, and the second returns the 39 baseline rows.
2. Start a queue worker and press **Generate All** for OdeMobile-C. Or dispatch only the three page steps:

   ```php
   $s = '8a6f1c6c-1d84-11f1-aaa9-ac1f6b9eb7e3';
   App\Jobs\Shopify\ShopifyBrandPageSyncJob::withChain([
       new App\Jobs\ShopifyIndvPagesSyncJob($s, 90),
       new App\Jobs\Shopify\ShopifyBrandPageSyncJob($s, 20, '28c4ba6b-9e59-369e-2759-4b7c45ff9500'),
       new App\Jobs\ShopifyIndvPagesSyncJob($s, 20),
       new App\Jobs\Shopify\ShopifyBrandPageSyncJob($s, 50, 'a28d2727-050b-61e2-fde6-ad46d1bb93ae'),
       new App\Jobs\ShopifyIndvPagesSyncJob($s, 50),
   ])->dispatch($s, 90, '32e22081-e4b5-543d-1f51-53745d132128');
   ```

   Type 90 needs device collections (type 60) to exist already. OdeMobile-C has them.
3. Run `02_check.sql` again.

## Expected result

| | Old code | With the fix |
|---|---|---|
| The 8 rows | `url_path` ends in `-1`, and `page_id` is a new id | `url_path` is the original handle, and `page_id` is the original id from the table above |
| Shopify | 9 new `-1` pages | No new `-1` pages. The existing pages are updated (their `updatedAt` changes) |
| Error log | Nothing | Nothing. If Shopify returns a different handle, you get a "possible duplicate" error |

Eight type-90 rows for devices that have no page yet (e.g. Samsung Galaxy Tab 7 P1000) create new
pages either way. Those are real new pages, not duplicates.

## Undo

`99_restore.sql` puts the 8 original rows back. You only need it if you don't run the test. If the test
has already recreated the rows, delete those new rows before restoring. Otherwise the same brand,
device or product type ends up with two rows.
