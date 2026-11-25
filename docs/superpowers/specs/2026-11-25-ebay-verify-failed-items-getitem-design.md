# eBay: check failed feed items with GetItem before counting them as failed

**Date:** 2026-11-25
**Status:** Implemented 2026-11-25 (uncommitted). Open questions were resolved with the defaults:
Q1 the full request as supplied is kept; Q2 an Ended listing stays failed; Q3 the Warning branch is not changed
directly, but warning SKUs go through the GetItem check; Q4 DELETE feeds are not covered; Q5 the limit is 50
(`EBAY_VERIFY_MAX_PER_RUN`). Switches: `EBAY_VERIFY_FAILED_ITEMS` (default on) and `EBAY_VERIFY_DRY_RUN` (default off).
**Scope:** `EbayService::getResultFeed()` for `CREATE` and `RESUBMIT` feeds, plus one new Trading API call in `EbayAuthService`.

---

## 1. Problem

Sometimes eBay's bulk result file (the `AddFixedPriceItemResponse` / `ReviseFixedPriceItemResponse` nodes) returns `Ack = Failure` for an item even though eBay **did create the listing**. Right now the result file is the only thing we trust, so in that case:

1. `listing_item.store_sku` is never set to the eBay `ItemID`.
2. The item is marked `ListingSyncStatus::Unsynchronize` (`EbayService.php:845-866`).
3. `listing_feeds.fail_count` goes up, `success_count` stays too low, and the feed can end up as `FeedStatus::ERROR`.
4. For a `CREATE` feed, the recount at `EbayService.php:892-919` sees an empty `store_sku`, so it counts the item as failed and calls `resubmitFailedItems()`. That resubmits an item that **already exists on eBay**, which can create a duplicate or another error, and wastes retries (`submit_count`).

The code already has one workaround: error code `21916271` is counted as a success (`EbayService.php:835`). But it still does not stamp `store_sku`, so the `CREATE` recount turns it back into a failure.

## 2. Proposed fix (summary)

After the result file is parsed, take every item in the feed that **still has no `store_sku`** and ask eBay directly with the Trading API **`GetItem` by `SKU`**:

- **The item exists and is active:** set `store_sku = ItemID` and `sync_status = Synchronized`, move it from the fail count to the success count, and record a note.
- **eBay says the item does not exist:** keep the current failure behaviour.
- **The GetItem call errors (network, token, rate limit):** keep the current failure behaviour. Never mark an item as a success unless eBay confirmed it.

Because this step runs **before** the `CREATE` recount and before `resubmitFailedItems()`, confirmed items are counted correctly and are not resubmitted.

## 3. The API call

The request you supplied:

```xml
<?xml version="1.0" encoding="utf-8"?>
<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
  <SKU>XW223-BZ2C5U</SKU>
  <DetailLevel>ReturnAll</DetailLevel>
  <IncludeItemSpecifics>true</IncludeItemSpecifics>
  <IncludeWatchCount>true</IncludeWatchCount>
  <ErrorLanguage>en_US</ErrorLanguage>
  <WarningLevel>High</WarningLevel>
</GetItemRequest>
```

- Endpoint: `POST ws/api.dll` on the existing `$this->client`, the same one `completeSaleTracking()` and `getMyeBaySellingActiveList()` use.
- Headers: `X-EBAY-API-CALL-NAME: GetItem`, `X-EBAY-API-COMPATIBILITY-LEVEL: 1349`, `X-EBAY-API-SITEID: 0`, `X-EBAY-API-IAF-TOKEN: <refreshToken()>`, `Content-Type: text/xml`.
- **Looking up by SKU works for our listings.** eBay only allows `GetItem` by `SKU` when the listing was created with `InventoryTrackingMethod = SKU`, and our create feed sets that (`EbayService.php:361`). The SKU we send in the feed is `listing_item.sku` (`EbayService.php:239`), so we look up that same value.
- **Reduce the payload (recommended):** we only need `ItemID`, `SKU` and `SellingStatus.ListingStatus`. `ReturnAll` + `IncludeItemSpecifics` + `IncludeWatchCount` returns the full description and item specifics for every call. We can drop those flags or add `<OutputSelector>` (`ItemID`, `SKU`, `ListingStatus`, `Ack`, `Errors`) so responses stay small. We can keep your exact request if you want the full item logged for debugging. See open question Q1.

### Reading the response

| GetItem result | Meaning | Action |
|---|---|---|
| `Ack` = `Success`/`Warning`, `Item.ItemID` present, `Item.SellingStatus.ListingStatus` = `Active` | The listing exists | **Mark as created** |
| `Ack` = `Success`, `ListingStatus` = `Ended` / `Completed` | The listing existed but has ended | Do not mark as created; leave it failed (see Q2) |
| `Ack` = `Failure` (for example error 17, "item cannot be accessed / not found") | The item was not created | Keep it failed |
| HTTP/Guzzle exception, or the token refresh fails | Unknown | Keep it failed and log the error. Do not change any data |

## 4. Changes by file

### 4.1 `app/Services/Auth/EbayAuthService.php`: new method

```php
/**
 * Look up one listing by seller SKU (Trading API GetItem).
 * https://developer.ebay.com/devzone/xml/docs/reference/ebay/GetItem.html
 * Returns the decoded response array, or null when the request itself failed.
 */
public function getItemBySku(string $sku): ?array
```

- Same pattern as `getMyeBaySellingActiveList()`: build the XML with `SimpleXMLElement`, wrap the SKU in `htmlspecialchars`, post it, and return `json_decode(json_encode(simplexml_load_string($body)), true)`.
- On `RequestException`, log and `return null`. Do **not** call `CriticalErrorService::report` for every SKU, because one bad run would send one notification per item. Report once per feed from the caller instead (see 4.2).

### 4.2 `app/Services/Marketplace/Service/EbayService.php`

**a) New private helper**

```php
/**
 * Ask eBay whether items the result file reported as failed were actually created.
 * Stamps store_sku / Synchronized on the ones that exist.
 *
 * @param  Collection<ListingItem> $items  items in this feed that still have no store_sku
 * @return array{confirmed: string[], checked: int, lookup_errors: int}
 */
private function verifyUnconfirmedItemsOnEbay(EbayAuthService $authService, $items): array
```

For each item, up to the per-run cap (see section 6):
1. Call `$authService->getItemBySku($item->sku)`.
2. If the response matches the "Mark as created" row in section 3:
   `$item->update(['store_sku' => $itemId, 'sync_status' => ListingSyncStatus::Synchronized]);`
   and add the SKU to `confirmed`.
3. Otherwise leave the item as it is.
4. Log one line per SKU: `eBay GetItem verify: SKU X -> ItemID Y / not found / lookup error`.

**b) Where it is called in `getResultFeed()`, in the `CREATE` / `RESUBMIT` case**

Insert it **after** the existing "mark the rest as Unsynchronize" block (lines 845-866) and **before** the first `$feedInfo->update([...counts...])` (line 873):

```
parse response loop                (existing, 814-843)
mark non-success → Unsynchronize   (existing, 845-866)
▶ NEW: verify with GetItem
    $candidates = items linked to this listing
                  whose sku is one of the SKUs in this feed's response (RESUBMIT only covers its own SKUs)
                  and store_sku is empty
    $result = $this->verifyUnconfirmedItemsOnEbay($authService, $candidates)
    $confirmedCnt = count($result['confirmed'])
    $successCnt  += $confirmedCnt
    $failCnt      = max(0, $failCnt - $confirmedCnt)
    if ($confirmedCnt) $errorMsg = "eBay returned errors but {$confirmedCnt} item(s) exist on eBay (verified via GetItem). " . $errorMsg
update feed counts / status        (existing, 873-890)  ← now uses the corrected counts
CREATE recount from store_sku      (existing, 892-919)  ← confirmed items now have store_sku, so
                                                          they count as success and are NOT resubmitted
```

Notes:
- The order matters. The block at 845-866 sets **every** SKU that is not in `$successSku` to `Unsynchronize`. If verification ran first, that block would undo it. Running verification afterwards means the `Synchronized` status we set is the final value.
- **Which SKUs to check:** use the SKUs from the response nodes (`$item->SKU`) that are not in `$successSku`, together with the feed's listing. This keeps a `RESUBMIT` feed from checking items that were not part of that resubmit. If a response node has no `<SKU>` (eBay sometimes leaves it out on a hard failure), fall back to all listing items that have no `store_sku`.
- For `CREATE` feeds, the existing recount (892-919) already recalculates `success_count`/`fail_count` from `store_sku`. The counter adjustment above matters most for `RESUBMIT`, which has no recount. It also keeps the first `update()` and the feed status correct.
- `$authService` is already created at the top of `getResultFeed()`, so no new auth setup is needed.

**c) Optional, related gap (Q3):** in the `Ack == Warning` branch (827-831), eBay **did** create the item and returns `ItemID`, but we do not stamp `store_sku`, so those items are counted as failures in the `CREATE` recount and get resubmitted. Stamping `ItemID` there (the same 3 lines as in the success branch) would fix this without an API call. Verification would also catch these items, but it would cost one call each.

### 4.3 Configuration

Add an on/off switch so this can be turned off without a deploy:

- `.env`: `EBAY_VERIFY_FAILED_ITEMS=true`, `EBAY_VERIFY_MAX_PER_RUN=50`
- Read through `config('services.ebay.verify_failed_items')` (add it to `config/services.php`). Do not read it directly with `env()` in the service. Existing code uses `env()` directly, but that returns null when the config is cached.

No migration. The fields already exist: `listing_feeds.success_count` / `fail_count` / `warning_count` / `error_text`, and `listing_item.store_sku` / `sync_status`.

## 5. What triggers it

Both ways of refreshing a feed already go through `EbayService::getResultFeed()`, so both get the new check without other changes:

- **Scheduled:** `FetchResultFeedAsync::fetchFeed()` → `getResultFeed($listingId, $feed->id)`.
- **Manual refresh from the UI:** `HomeController.php:1229-1231` → `getResultFeed($listingId, $feedID)`. The cached-file check above it looks for `*.xml` / `*.json` files directly under `result-feeds/ebay/`. eBay results are unzipped into a **sub-directory** (`result-feeds/ebay/{id}/`), so that check does not match them and the refresh always runs `getResultFeed()` again.

Because the check only runs on items that still have no `store_sku`, refreshing the same feed again is safe. Items that are already confirmed are skipped, so they are never looked up twice.

## 6. Rate limits and run time

- Trading API calls have a daily limit per application (5,000/day by default). `GetItem` is only called for **failed** items, never for successful ones, so normal volume is small.
- Cap the number of lookups per feed per run at `EBAY_VERIFY_MAX_PER_RUN` (default 50). Any items over the cap stay failed for this run and are checked on the next refresh. This is safe because the check only covers items with no `store_sku`.
- `FetchResultFeedAsync` handles up to 20 feeds per run with `$timeout = 3000`s. At roughly 0.5-1 s per GetItem, 50 lookups adds at most about 1 minute per feed. That is acceptable, but it is one reason for the cap.
- **Alternative, if volume grows:** `EbayAuthService::getListingItems()` already calls `GetSellerList` with a `SKUArray`, which looks up many SKUs in one call. It needs a `StartTime` window of 120 days or less, which is fine for listings that were just created. We start with `GetItem` because you asked for it and it is easier to read per SKU.

## 7. Failure handling

- A lookup error for one SKU does not stop the loop. That item stays failed.
- If **any** lookup fails, call `CriticalErrorService::report('eBay GetItem verification failed', [...], ['feed_id' => ..., 'failed_skus' => [...]])` **once per feed**.
- The whole verification step is wrapped in `try/catch (\Throwable)`. If it throws, we fall back to the current behaviour. A problem in verification must never stop the feed's counts and status from being saved, and must never make `FetchResultFeedAsync` mark the feed `FAILED`.

## 8. Testing plan

You test against real data, so dry-run first:

1. **Dry-run flag** (`EBAY_VERIFY_DRY_RUN=true`): call GetItem and log the result (`would mark SKU X as created with ItemID Y`) without updating `listing_item` or the feed counts.
2. Take a recent feed where eBay returned an error but the item is live. Run the scheduled job or click refresh. Check the log line and compare the `ItemID` with Seller Hub.
3. Turn off dry-run and refresh the same feed. Expected result: `store_sku` is set, `sync_status = Synchronized`, `success_count` goes up, `fail_count` goes down, `error_text` starts with the "verified via GetItem" note, and **no new RESUBMIT feed** is created for that SKU.
4. Negative check: a SKU that really failed should stay failed, with no `store_sku` and with the resubmit still triggered.
5. Optional: add `tests/Services/EbayVerifyFailedItemsTest.php` with a mocked `EbayAuthService` (run it by path; see CLAUDE.md about phpunit suites).

## 9. Open questions

- **Q1.** Should we keep your full `ReturnAll` + item specifics + watch count request, or trim it to the fields we use with `OutputSelector`? The full request gives more debugging detail but much larger responses.
- **Q2.** If GetItem finds the SKU but the listing is **Ended**, should we treat it as "not created" (the current plan, so it gets resubmitted), or stamp the `ItemID` so the next feed uses `ReviseFixedPriceItem` / relist?
- **Q3.** Should we also fix the `Ack == Warning` branch (4.2c) so warnings stamp `ItemID` directly? This is a small change that removes the need for many lookups.
- **Q4.** Should we also verify `DELETE` feeds, using GetItem to confirm `ListingStatus = Ended` when `EndFixedPriceItem` returns an error? This is out of scope for now.
- **Q5.** Is 50 lookups per feed per run a good cap?
