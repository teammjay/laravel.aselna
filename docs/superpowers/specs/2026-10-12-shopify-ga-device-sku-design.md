# Shopify product page → GA/GTM event with the product+device SKU

Status: analysis only, no code changed. Date: 2026-10-12.

## Requirement

Every product+device combination gets its own SKU in the feed, for example `A20-XXXXXX` for product `A20` on one device.
When a shopper opens a product page on the Shopify store, the theme fires a GA/GTM event, and that event has to carry
the same combination SKU. Two questions:

1. How do we get that many SKUs into Shopify?
2. How does the Shopify theme code find the right SKU at page-view time?

## How things work today (findings)

### The combination SKU is random, not derivable

- `ProcessFeedGenerationJob::insertListingItemDetail()` (`app/Jobs/ProcessFeedGenerationJob.php:357`) creates one
  `ListingItemDetail` row per (listing item, device) with
  `sku = StoreTypeListing::generateUniqueAlphabeticSku($product_code, 6)`.
- `generateUniqueAlphabeticSku()` (`app/Models/StoreTypeListing.php:94`) is `strtoupper($product_code . '-' . Str::random(6))`.
- **Consequence:** the suffix is random. Shopify can't compute `A20-XXXXXX` from the product and the device. The SKU has to
  be **sent to Shopify as data**, or looked up from Aselna at runtime.
- SKUs are scoped per store/listing: the same product+device has a different SKU in each store's feed.
- Data path: `ListingItem` (product level, `sku`, `store_sku` = Shopify product GID) → `devices()` / `itemDetails()` →
  `ListingItemDetail` (`device_id`, `sku`).

### Decision: push the `ListingItemDetail.sku` of the same listing item

**The SKU to push is `ListingItemDetail.sku`, taken from the detail rows of the same `ListingItem` that is the
Shopify product.** No other store is involved. The code supports this:

- `ProcessFeedGenerationJob` runs for one store (`store_product.store_id = $store_id`, line 164). It walks that
  store's `ListingItem`s (line 1016), the same rows `ShopifyService::SubmitCreateFeed()` pushed to Shopify
  (`store_sku` = product GID). It creates or reuses one `ListingItemDetail` per compatible device.
- The feed row's `id` is `$item_detail->sku` for both the Google CSV and the Meta feed (lines ~1188 and ~1212). That is
  exactly the SKU the GA event must carry, so GA4 `item_id` matches the Merchant Center / Meta catalog id.
- The feed row's `link` is built by `ListingUtility::generateItemUrl()` for Shopify as
  `<site>/collections/<device page url_path>/products/<handle>` (lines 1138-1149, `ListingUtility.php:167`). So a
  shopper coming from a Google/Meta ad lands on the product **inside the device collection**. The theme then knows
  the device without any extra work (see Q2).
- **If a device has several detail rows:** the feed uses the **first row per device, ordered by `id`**
  (`$itemDetailsByDevice[$detail->device_id] ??= $detail` over `orderBy('id')`, lines 1064-1068). The metafield map must
  use the same rule, otherwise GA and the feed would disagree.

So the map for a Shopify product is:
`ListingItemDetail::where('listing_item_id', <Shopify ListingItem id>)->orderBy('id')`, joined to `device`, first row
per device → `{device.code: sku}` (see "Why device.code, not the device id" below). The source store is the same as the target Shopify store, and no store mapping is needed.

### What Shopify receives today

- **Products:** `ShopifyService::SubmitCreateFeed()` (`app/Services/Marketplace/Service/ShopifyService.php:60`) sends
  **one Shopify product per `ListingItem` with a single variant** (`sku = $listingItem->sku`). It sends no per-device data.
  Device compatibility is expressed only through **tags**: `getAllTags()` adds every `ProductInterface.interface_id`.
- **Devices:** `ShopifyBrandPageSyncJob` (`app/Jobs/Shopify/ShopifyBrandPageSyncJob.php:218-330`) creates one
  **smart collection per device** (handle stored in `store_navigation_page.url_path`). Its rules are
  `TAG EQUALS <interface_id>` for the device's interfaces. It also writes collection metafields `global.device_id`,
  `global.device_code`, `global.device_name`, and others (`getDeviceMetafields()`, line 691).

So on the storefront a product is generic. The "device" part of the combination is known only from **which device
collection the shopper came through**, e.g. `/collections/samsung-galaxy-a20/products/<product-handle>`.

## Q1: How to pass that many SKUs to Shopify

### Recommendation: one JSON metafield per product, a `device_code → sku` map

Write a single product metafield that holds every combination SKU for that product, keyed by the device code that is
already on the device collection (`global.device_code`):

```
namespace: global            (same namespace the device collections already use)
key:       device_skus
type:      json
value:     {"4566":"F58-Z3CXRA","4587":"F58-QQYSJW", ...}
```

Why this fits:

- **Volume isn't a problem when it's per product.** The total number of combinations is large, but each Shopify
  product only needs its own devices. Each entry is about 20 bytes (`"4566":"F58-Z3CXRA",`, measured). Shopify's limit for a
  `json` metafield on current API versions is 128 KB (**verify against the API version in `ShopifyQueries` before building**),
  which is room for roughly 6,000 devices per product. There are 3,241 devices in total, so even a product that fits
  every device stays under the limit.

#### Why device.code, not the device id

`device.id` is a UUID (36 characters), so a UUID-keyed entry is about 52 bytes, 2.5× larger. 3,241 devices × 52 bytes
is about 168 KB, which is over the limit. `device.code` was checked on 2026-10-13 (read-only):

- set on all 3,241 devices (none null or empty)
- unique (no duplicates, active or not)
- 4 characters on average, 6 at most (e.g. `3907`, `255`)

It is also already on every device collection as the metafield `global.device_code`
(`ShopifyBrandPageSyncJob::getDeviceMetafields()`), and it's the stable cross-system key that `SyncShopifyItems` uses
to match devices with oydera. If a device's code ever changes, its map changes too: the hash changes and the product
is pushed again. The collection metafield follows on the next `ShopifyBrandPageSyncJob` run.
- **No new Shopify objects.** It's 1 metafield per product instead of thousands of variants, metaobjects, or products.
- **Liquid reads it directly:** no API call, no latency, and the SKU is already in the HTML when GTM fires.
- **Written separately from the product push:** `metafieldsSet` writes metafields onto existing products, up to
  25 metafields (25 products) per call, without touching the product's other data.

### When and how the metafield is pushed: a separate background job after feed generation

The push happens in its own job, `SyncShopifyDeviceSkuMetafieldsJob`. It is **not** part of feed generation and
**not** part of the Shopify product push (`ShopifyService::SubmitCreateFeed()`). The job is dispatched when feed
generation finishes, because that is the only time new combination SKUs come into existence:
`ProcessFeedGenerationJob` creates the `ListingItemDetail` rows (`insertListingItemDetail()`).

Why a separate job is better than writing the metafield inside feed generation or `SubmitCreateFeed()`:

- **Feed generation stays as it is.** It is long, chunked, and fails on things like `UpcNotAvailableException`.
  A Shopify API error or rate limit must never fail or slow down a feed file, and a feed failure must not leave
  half-written metafields.
- **It covers SKUs created by any feed.** The SKUs we need may come from a non-Shopify store's feed (see open
  question 1). `SubmitCreateFeed()` only runs for Shopify listings and only when a product is created, so it would
  miss devices added later.
- **It can be retried and run again on its own.** It is `TrackableJob`, so it shows up in `job_statuses`. It can be
  re-run from the job dashboard without regenerating a feed.
- **Only changes are pushed.** The job compares against what it pushed last time, so a feed run with no new devices
  costs almost no API calls.

#### Trigger

- At the end of `ProcessFeedGenerationJob::handle()`, at both places that mark the feed `FeedStatus::PROCESSED` and
  the tracking record `completed` (the Meta branch, around line 239, and the main path, around line 293), dispatch
  `SyncShopifyDeviceSkuMetafieldsJob::dispatch($this->store_id)`. This is a single dispatch line: the job does all
  the work, and the feed job doesn't wait for it.
- Dispatch only when the source store is one that feeds a Shopify store's metafields (see "Store mapping" below).
  Otherwise, return early in the job.
- Make the job `ShouldBeUnique`, keyed by the target Shopify store. Several feeds finishing close together then
  collapse into one run instead of overlapping pushes.
- **Safety net:** also register the job as a daily `scheduled_cron_jobs` row (or via `jobs:sync-registry`). It picks
  up products that didn't exist in Shopify yet when the feed finished (no `store_sku`), and runs that failed.

#### What the job does

1. **Resolve the Shopify store(s)**: the store whose feed finished, or the stores picked in Run Now (see "Store mapping").
2. **Find the Shopify products**: the target store's `ListingItem` rows with a non-empty `store_sku` (the Shopify
   product GID saved by `SubmitCreateFeed()`). Skip products not yet in Shopify. The next run catches them.
3. **Build the map per product.** For each Shopify `ListingItem`, collect its own `ListingItemDetail` rows, joined to
   `device`, into `{device_code: sku}`, sorted by code so the JSON is stable.
4. **Detect changes:** `hash = sha1(json)`. Compare it with the hash saved for this Shopify product at the last
   successful push. If it's the same, skip the product. This is how "only push what changed" works without
   tracking individual detail rows.
5. **Push** the changed ones with `metafieldsSet`, 25 products per call, using the existing `ShopifyAuthService`
   GraphQL client. Respect Shopify's throttle (`extensions.cost.throttleStatus`), and back off when it runs low.
6. **Save the new hash** only for products whose write came back without `userErrors`. Failed products keep their
   old hash and are retried on the next run.
7. Report failures through `CriticalErrorService::handle()`. Write counts (checked / unchanged / pushed / failed) to
   the tracking record with `updateTrackingRecord()`.

#### Where the "last pushed" hash lives

There are two options. A small new table is preferred because `listing_item` is shared by every marketplace:

```
shopify_device_sku_syncs
  id                 bigint PK
  store_id           uuid    (target Shopify store)
  listing_item_id    uuid    (Shopify ListingItem)
  payload_hash       char(40)
  device_count       int
  synced_at          timestamp
  unique (store_id, listing_item_id)
```

`device_count` also gives a quick way to watch how close products get to the size limit.

#### Store mapping

Not needed. The SKUs are the `ListingItemDetail` rows of the Shopify store's own listing items, so the job runs for
the store whose feed just finished. It only proceeds if that store is a Shopify store (`store_type.code = 'shopify'`)
and has listing items with a `store_sku`.

#### Also needed

- Optionally create a metafield **definition** for `global.device_skus` (type `json`). It isn't required for Liquid
  access, but it makes the field visible and validated in Shopify admin.
- Removed devices: if a detail row is deleted (e.g. by `SyncShopifyItems`), the map changes, so the hash changes and
  the metafield is overwritten without that device. No separate delete step is needed.

If a product ever goes over the size limit, split it into `device_skus_1`, `device_skus_2`, … The Liquid then checks
each one in turn. That is unlikely given the per-product numbers.

### Alternatives considered (not recommended)

| Option | Why not |
|---|---|
| Make SKUs deterministic (`A20-<device_code>`) so Liquid can build them | SKUs that already exist are random and live on marketplaces/feeds. Changing them breaks order matching and history. It would only help new rows, so we'd still need the map. |
| One Shopify variant per device | Changes storefront UX (device dropdown), variant/option limits, inventory per variant, and the whole create/price/inventory feed logic. Far too large a change for analytics. |
| Metaobject per combination | Millions of entries, plus a lookup by handle per page view. Doesn't scale and is harder to sync. |
| Runtime lookup from Aselna (app proxy / public endpoint) | Adds an extra request on every product view, so the event fires late or is lost on bounce. Aselna would need public infrastructure, and `routes/api.php` is unauthenticated today. Keep it only as a fallback. |

## Q2: How the Shopify theme gets the SKU

On the product template, the device comes from the collection the shopper browsed through. When the URL is
`/collections/<device-handle>/products/<handle>`, Liquid exposes `collection`, and that device collection already
has `collection.metafields.global.device_code`.

```liquid
{%- liquid
  assign device_code = collection.metafields.global.device_code.value
  assign device_skus = product.metafields.global.device_skus.value
  assign combo_sku = blank
  if device_code != blank and device_skus != blank
    assign combo_sku = device_skus[device_code]
  endif
  if combo_sku == blank
    assign combo_sku = product.selected_or_first_available_variant.sku
  endif
-%}
<script>
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ ecommerce: null });
  window.dataLayer.push({
    event: 'view_item',
    ecommerce: {
      items: [{
        item_id: {{ combo_sku | json }},
        item_name: {{ product.title | json }},
        item_variant: {{ collection.metafields.global.device_name.value | default: '' | json }},
        price: {{ product.selected_or_first_available_variant.price | divided_by: 100.0 }}
      }]
    }
  });
</script>
```

Put this in a snippet (e.g. `snippets/ga-device-sku.liquid`) and render it from the product section/template.
Replace the push body with your existing event code. Only `item_id` needs to change.

Theme requirements and caveats:

- **Product links must keep the collection in the URL.** Collection grids should link with
  `{{ product.url | within: collection }}`. Most themes do this, but some link to the bare `/products/<handle>` URL.
  If yours does, `collection` is empty on the product page and the code falls back to the generic SKU.
- **Feed/ad traffic already has the device.** Google and Meta feed links point to
  `/collections/<device>/products/<handle>` (see the decision above), so `collection` is set for ad clicks.
- **Direct landings** (organic search, shared links to `/products/<handle>`) don't have a device in context. Options:
  - Fall back to the generic `ListingItem.sku`, which is the code above.
  - Use a device chosen earlier: if the theme has a "select your device" picker or remembers the last device
    collection, keep the device code in `localStorage`/a cookie and resolve the SKU in JS. In that case, output the
    whole map with `{{ product.metafields.global.device_skus.value | json }}` and look it up client-side.
  - If ads/feeds should deep-link with the device, add `?device=<id>` to the link and read it in JS from the same JSON map.
- The JSON map is visible in page source. It holds only SKUs, which are fine to expose.
- If GTM fires the event rather than inline code, push the SKU into the `dataLayer` (as above) or into a
  `data-combo-sku` attribute, and let the GTM tag read it as a variable.

## Open questions (need answers before implementation)

1. ~~**Whose SKU?**~~ **Resolved 2026-10-12:** push `ListingItemDetail.sku` of the Shopify `ListingItem` itself.
   It's the same value `ProcessFeedGenerationJob` writes as the feed `id` (see "Decision" above).
2. **Direct landings with no device context:** fall back to the generic SKU, use a remembered device, or skip the event?
3. Does the live theme link products with `within: collection`, and is there a device picker? This needs a look at the theme code.
4. How many devices does the largest product cover? This confirms the 128 KB limit isn't hit. A quick count from
   `listing_item_detail` grouped by `listing_item_id` for the Shopify store will answer it.
