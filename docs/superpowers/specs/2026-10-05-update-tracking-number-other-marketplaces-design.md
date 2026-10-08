# Update Tracking Number — Amazon, Walmart, eBay, Temu

**Status:** Implemented (uncommitted) — see §5 for testing
**Date:** 2026-10-05
**Branch:** `Multi_store_functionality`

## 1. Where we are today

The "Update Tracking Number" popup (orders list + order detail) posts to
`OrdersController::updateTrackingNumber()` (`app/Http/Controllers/OrdersController.php:287`). It:

1. Finds the shipped operation (`getShippedShipment`) and the operations merged into it.
2. Copies them (`copyOperationWithTracking`) with the new carrier / service / tracking number and
   `label_creation_type = MarkAsShipped`.
3. Pushes the copy to the marketplace:
   - **Shopify** → `ShopifyService::updateTrackingNumber()` — loads the order's fulfillments and calls
     `fulfillmentTrackingInfoUpdateV2` on the latest one. **Works.**
   - **Everything else** (`amazon`, `ebay`, `walmart`, `temu` are allowed at line 320) →
     `OrderOperationUtility::createShipment()`, i.e. the *first-time ship* call.
4. Deletes the copies if the marketplace rejected them.

The problem is step 3 for non-Shopify stores: the first-time ship call is not an "edit" call on most
marketplaces. What actually happens today:

| Marketplace | Call made today | What really happens on an already-shipped order |
|---|---|---|
| Amazon | `POST /orders/v0/orders/{id}/shipmentConfirmation` with `packageReferenceId: "123"` | Amazon treats a repeat with the **same** `packageReferenceId` + same items/qty as an **edit**, so this may already work by accident — but carrier mapping is wrong (see §3.1) and the success check accepts any response. |
| Walmart | `POST /v3/orders/{po}/shipping` without `processMode` | Lines are already `Shipped`; Walmart rejects it (or ignores it) unless `processMode: PARTIAL_UPDATE` is sent. |
| eBay | `POST /sell/fulfillment/v1/order/{id}/shipping_fulfillment` | Creates an **additional** fulfillment (the old tracking stays visible to the buyer) or fails because the line items are already fully fulfilled. The Fulfillment REST API has **no update/replace** call. |
| Temu | `bg.logistics.shipment.v2.confirm` | Confirm is for unshipped orders; on a shipped order it fails. **`TemuService::createShipment` returns `true` without checking the response**, so the UI says "updated" even when Temu rejected it. |

Also: `createShipment()` sets order status / `shipped_quantity` and may dispatch
`SyncFailedMarketplaceShipmentJob` on failure — side effects we don't want for a tracking edit.

## 2. Proposed design

Mirror what was done for Shopify: one dedicated **edit** method per marketplace service, never the
first-time ship path.

### 2.1 Common contract

Add `updateTrackingNumber($orderOperation): array` to each of:

- `app/Services/Marketplace/Service/AmazonService.php`
- `app/Services/Marketplace/Service/WalmartService.php`
- `app/Services/Marketplace/Service/EbayService.php` (exists, but eBay
  shipping currently lives inline in `OrderOperationUtility::createShipment`)
- `app/Services/Marketplace/Service/TemuService.php`

Same shape as `ShopifyService::updateTrackingNumber()`:

```php
// returns ['success' => bool, 'message' => string]
// - takes the NEW (copied) operation, which already carries tracking_number / carrier / carrierService
// - only talks to the marketplace; does NOT change operation/order status (the controller does that)
```

Optional: a small interface `App\Services\Marketplace\Contracts\UpdatesTrackingNumber` so the controller
can check `instanceof` instead of a hardcoded list of store types.

### 2.2 Controller change (`OrdersController::updateTrackingNumber`)

Replace the `if ($storeType == 'shopify') { ... } else { createShipment(...) }` block with one generic
branch:

```php
$service = match ($storeType) {
    'shopify' => new ShopifyService(),
    'amazon'  => new AmazonService(),
    'walmart' => new WalmartService(),
    'ebay'    => new EbayService(),
    'temu'    => new TemuService(),
};
$successRes = $service->updateTrackingNumber($newOperation);
// ...then the existing merged-operations loop, unchanged, but calling $service->updateTrackingNumber()
```

- The `in_array($storeType, [...])` guard (line 320) stays as the list of supported marketplaces.
- The merged-order loop already written for Shopify becomes generic. **Exception — Temu:** a combined
  shipment is one Temu package covering several parent orders, so one update call covers all of them;
  `TemuService::updateTrackingNumber` should be called once and the merged copies just marked shipped
  (see §3.4).
- `OrderOperationUtility::createShipment()` is no longer called from this endpoint.

### 2.3 Carrier mapping (needed by all four)

Each marketplace validates the carrier against its own list. Today we send
`ShippingCarrier->name` as-is (e.g. `USPS`, `UPS`, `FedEx`, `DHL`). Proposed helper, e.g.
`ShippingUtility::marketplaceCarrier($storeType, $carrier)`, returning the marketplace's code and a
fallback ("Other" + free-text name + tracking URL) when there's no match. Exact codes per marketplace are
listed in §3.

## 3. Per-marketplace detail

### 3.1 Amazon (SP-API)

**API:** Orders API v0 — `confirmShipment`, re-sent with the **same `packageReferenceId`** →
Amazon edits the existing confirmation (carrier, shipping method, tracking number, ship date).

```
POST /orders/v0/orders/{orderId}/shipmentConfirmation
{
  "marketplaceId": "<from getOrder>",
  "packageDetail": {
    "packageReferenceId": "123",            // MUST equal the one used at first ship
    "carrierCode": "USPS",                  // Amazon carrier code, or "Other"
    "carrierName": "",                      // required when carrierCode = "Other"
    "shippingMethod": "<carrier service>",
    "trackingNumber": "<new>",
    "shipDate": "<original ship date, ISO-8601>",
    "orderItems": [{ "orderItemId": "<id1>", "quantity": n }]   // same items + qty as original
  }
}
→ 204 No Content on success
```

Rules from the docs: `orderId`, `packageReferenceId`, `orderItemId` and `quantity` must be identical to
the original confirmation; the other fields are overwritten. Ship+ orders don't support
`confirmShipment` (400).

**Code changes**
- `AmazonService::updateTrackingNumber($op)` — builds the payload above, reusing the logic in
  `createShipment()` (`AmazonService.php:567`) but without status updates.
- `packageReferenceId` is hardcoded to `"123"` today (`AmazonService.php:594`). Keep `"123"` as the value
  for edits (it matches every shipment we've sent so far). Recommend moving it to a constant
  (e.g. `AmazonService::PACKAGE_REFERENCE_ID`) so first-ship and edit can't drift apart.
- Carrier: today `carrierCode = carrier->name` and `carrierName = ''`. If the name isn't one of Amazon's
  `CarrierCode` values the call fails. Map to Amazon code; otherwise `carrierCode: "Other"` +
  `carrierName: <name>`.
- `shipDate`: send the **original** operation's `ship_date` (an edit shouldn't move the ship date), falling
  back to now.
- `AmazonAuthService::shipOrder()` returns `204` on success and a string / array on error; the current
  check `$response === 204 || isset($response)` is always true. The new method must treat **only 204** as
  success and surface the error body as `message`.

**Heads-up:** Orders API v0 *read* operations are deprecated (removal **2027-03-27**) in favour of
Orders API v2026-01-01. `confirmShipment` is **not** on the deprecation list yet and has no v2026
equivalent, so it's safe to use. v2026 `getOrder` can return FBM package/tracking info — useful later to
verify the edit, not required now.

**Docs**
- confirmShipment reference — https://developer-docs.amazon.com/sp-api/reference/confirmshipment
- Confirm the shipment status (editing via same packageReferenceId) — https://developer-docs.amazon.com/sp-api/docs/confirm-the-shipment-status
- Orders API v0 reference (CarrierCode values) — https://developer-docs.amazon.com/sp-api/docs/orders-api-v0-reference
- Orders API v2026-01-01 announcement — https://developer-docs.amazon.com/sp-api/changelog/new-introducing-the-orders-api-v2026-01-01
- Deprecation schedule — https://developer-docs.amazon.com/sp-api/docs/sp-api-deprecations

### 3.2 Walmart (Marketplace US)

**API:** the same Ship Order Lines endpoint, with **`processMode: "PARTIAL_UPDATE"`**, which Walmart
documents as the way to update tracking details after shipment.

```
POST /v3/orders/{purchaseOrderId}/shipping
{
  "orderShipment": {
    "processMode": "PARTIAL_UPDATE",
    "orderLines": { "orderLine": [{
      "lineNumber": "<id1>",
      "sellerOrderId": "<our order id>",
      "orderLineStatuses": { "orderLineStatus": [{
        "status": "Shipped",
        "statusQuantity": { "unitOfMeasurement": "EACH", "amount": "<qty>" },
        "trackingInfo": {
          "shipDateTime": <original ship date>,
          "carrierName": { "carrier": "USPS" }            // or { "otherCarrier": "<name>" }
          "methodCode": "Standard",
          "trackingNumber": "<new>",
          "trackingURL": "<required when otherCarrier>"
        }
      }]}
    }]}
  }
}
```

**Code changes**
- `WalmartService::updateTrackingNumber($op)` — same payload builder as `shipmentUpdate()`
  (`WalmartService.php:862`) plus `processMode`, without status updates. Extract the line builder into a
  private method shared by both.
- Success check: response `order.purchaseOrderId` equals ours **and** the line's tracking number in the
  response equals the new one (today we only check the PO id).
- `shipmentUpdate()` catches `Throwable` and returns `null`; the new method must return
  `['success' => false, 'message' => ...]` instead.
- Carrier: `carrierName.carrier` must be a Walmart-supported value; otherwise use `otherCarrier` +
  `trackingURL`. `methodCode` is hardcoded `Standard`; map from carrier service if possible
  (`Standard / Express / OneDay / Freight / WhiteGlove / Value`).
- Verify in sandbox whether `sellerOrderId` is required on PARTIAL_UPDATE (current first-ship call omits it
  and works).

**Docs**
- Ship order lines (reference, `processMode`) — https://developer.walmart.com/us-marketplace/reference/shippingupdates
- Ship an order (guide) — https://developer.walmart.com/us-marketplace/docs/ship-an-order

### 3.3 eBay

The Fulfillment REST API (what we use to ship) **cannot edit or delete** a shipping fulfillment;
calling `createShippingFulfillment` again only adds a second package. The documented way to
**replace** tracking is the Trading API **`CompleteSale`**: "the old number and carrier are deleted and
the new ones are added".

```
POST https://api.ebay.com/ws/api.dll
X-EBAY-API-CALL-NAME: CompleteSale
X-EBAY-API-COMPATIBILITY-LEVEL: 1349
X-EBAY-API-SITEID: 0
X-EBAY-API-IAF-TOKEN: <OAuth user token>

<?xml version="1.0" encoding="utf-8"?>
<CompleteSaleRequest xmlns="urn:ebay:apis:eBLBaseComponents">
  <OrderID>{order_id}</OrderID>
  <Shipment>
    <ShipmentTrackingDetails>
      <ShipmentTrackingNumber>{new}</ShipmentTrackingNumber>
      <ShippingCarrierUsed>{carrier}</ShippingCarrierUsed>
    </ShipmentTrackingDetails>
  </Shipment>
  <Shipped>true</Shipped>
</CompleteSaleRequest>
```

Good news: `EbayAuthService` already calls the Trading API with the OAuth token via
`X-EBAY-API-IAF-TOKEN` (`uploadImageToEPS`, `getListingItems`), so no new auth is needed.

**Code changes**
- `EbayAuthService::completeSaleTracking($orderId, $carrier, $trackingNumber)` — XML request as above,
  parse `<Ack>` (`Success`/`Warning` = ok, `Failure` = error, return `Errors.LongMessage`).
- `updateTrackingNumber($op)` wrapper (in a new/existing eBay service class) returning the common shape.
- Carrier: `ShippingCarrierUsed` must be an eBay `ShippingCarrierCodeType` value (`USPS`, `UPS`,
  `FedEx`, `DHL`, …); map or fall back to `Other`.
- Unrelated bug spotted while reading: `EbayAuthService::createShippingFulfillment()` reads
  `$response->getBody()->getContents()` twice (once for the log), so the returned value is always empty.
  Not blocking this work, but worth fixing at the same time.
- Alternative considered: `createShippingFulfillment` with the new tracking — rejected because the buyer
  would see both the old and new tracking numbers.

**Docs**
- CompleteSale — https://developer.ebay.com/devzone/xml/docs/reference/ebay/CompleteSale.html
- Using OAuth with the Trading API (IAF token) — https://developer.ebay.com/api-docs/static/oauth-trad-apis.html
- ShippingCarrierCodeType values — https://developer.ebay.com/devzone/xml/docs/reference/ebay/types/ShippingCarrierCodeType.html
- (for reference) createShippingFulfillment — https://developer.ebay.com/api-docs/sell/fulfillment/resources/order/shipping_fulfillment/methods/createShippingFulfillment
- (for reference) getShippingFulfillments — https://developer.ebay.com/api-docs/sell/fulfillment/resources/order/shipping_fulfillment/methods/getShippingFulfillments

### 3.4 Temu

**API:** `bg.logistics.shipment.shippingtype.update` (edit carrier + tracking of shipped packages), keyed by
`packageSn`. The `packageSn` is found with `bg.logistics.shipment.v2.get` (`parentOrderSn` + `orderSn`,
response `result.shipmentInfoDTO[].packageSn`).

> Correction made during implementation: `bg.logistics.shipment.update` is Temu's "re-buy an online label"
> call (`retrySendPackageRequestList`), **not** a tracking edit. The edit call with
> `editPackageRequestList` is `bg.logistics.shipment.shippingtype.update`.

```
type: bg.logistics.shipment.v2.get      → { parentOrderSn, orderSn }   → result.shipmentInfoDTO[].packageSn
type: bg.logistics.shipment.shippingtype.update
{
  "editPackageRequestList": [{
    "packageSn": "<from v2.get>",
    "trackingNumber": "<new>",
    "shipCompanyId": <logisticsServiceProviderId from bg.logistics.companies.get>
  }]
}
```

> Temu's docs are behind the partner-portal login, so the method and field names come from the public SDK
> (`temu-go`, `semi.online.order.logistics.shipment.go`). The SDK describes this call under "online
> shipping"; whether Temu accepts it for packages confirmed with our own tracking
> (`bg.logistics.shipment.v2.confirm`) must be confirmed in the portal or with one real order.

**Code changes**
- Reuse the existing `TemuAuthService::getShipmentInfo()` (`bg.logistics.shipment.v2.get`) and add
  `TemuAuthService::updateShipmentTracking(array $packages)` (`bg.logistics.shipment.shippingtype.update`),
  same signing as `callOrderShipment()`.
- `TemuService::updateTrackingNumber($op)` — get packageSn → resolve carrier id → update → check
  `success` in the response.
- Carrier: first-ship hardcodes `USPS` (`TemuService.php:654`). Edit must look up the chosen carrier's
  `logisticsServiceProviderId` from `getLogisticsInfo()` by `logisticsBrandName`, and fail clearly if Temu
  doesn't support it.
- Merged orders: combined shipments share one `packageSn`, so update once and mark merged copies shipped
  without extra calls (dedupe packageSn).
- Pre-existing issues that affect this:
  - `TemuService::createShipment()` returns `true` without checking Temu's response (`TemuService.php:686`),
    so failures look like success. Fix as part of this work.
  - `TemuAuthService` uses a hardcoded access token and env app keys (`TemuAuthService.php:18-24`), not
    per-store credentials — fine for one Temu store, wrong for multi-store. Flag for the multi-store branch.

**Docs**
- Temu Partner Platform (login required) — https://partner.temu.com/documentation (search
  `bg.logistics.shipment.update`, `bg.logistics.shipment.v2.get`, `bg.logistics.companies.get`)
- EU portal — https://partner-eu.temu.com/documentation
- Public SDK used to cross-check field names — https://pkg.go.dev/github.com/hiscaler/temu-go

### 3.5 Shopify (done — one follow-up)

Current implementation uses `fulfillmentTrackingInfoUpdateV2` on API version `2025-01` (`ShopifyAuthService.php:28`). Newer Admin API versions replace it with
`fulfillmentTrackingInfoUpdate` (same arguments). Switch when we bump the pinned version
— nothing to do now.

- https://shopify.dev/docs/api/admin-graphql/latest/mutations/fulfillmentTrackingInfoUpdate

### 3.6 Out of scope

Magento, NewEgg, Google Shopping have no order-shipping integration in the code
(`createShipment` has a `// TODO NewEgg` stub), so they stay blocked by the guard at
`OrdersController.php:320`.

## 4. Files to change (summary)

| File | Change |
|---|---|
| `app/Http/Controllers/OrdersController.php` | Generic per-marketplace `updateTrackingNumber` dispatch; drop `createShipment` call; Temu merged handling |
| `app/Services/Marketplace/Service/AmazonService.php` | New `updateTrackingNumber()`; `packageReferenceId` constant; carrier mapping |
| `app/Services/Auth/AmazonAuthService.php` | `shipOrder()` — return a clear success/error result (204 only) |
| `app/Services/Marketplace/Service/WalmartService.php` | New `updateTrackingNumber()` with `PARTIAL_UPDATE`; shared line builder |
| `app/Services/Auth/EbayAuthService.php` | New `completeSaleTracking()` (Trading API); fix double `getContents()` |
| `app/Services/Marketplace/Service/EbayService.php` | New `updateTrackingNumber()` wrapper |
| `app/Services/Marketplace/Service/TemuService.php` | New `updateTrackingNumber()`; carrier lookup; fix unchecked response in `createShipment()` |
| `app/Services/Auth/TemuAuthService.php` | New `getShipmentPackages()`, `updateShipment()` |
| `app/Utils/order/ShippingUtility.php` (or new constant class) | Carrier → marketplace code mapping |

No migrations and no Blade/route changes needed — the popup and endpoint stay the same.

## 5. Testing plan

**Start with one real order per marketplace, re-sending the tracking number it already has**, and check in
the seller portal that the order still shows a single tracking number (edited, not added).

- One real (or sandbox) shipped order per marketplace; update tracking → verify on the marketplace seller
  portal that the **old tracking is replaced, not duplicated**.
- Merged order case on each marketplace.
- Failure case: invalid carrier / bad tracking → error shown in the popup, copies deleted, original
  operation untouched.
- Amazon: confirm a 204 with the same `packageReferenceId` edits rather than adds a package.

## 6. Open questions for review

1. Amazon: are there old shipments confirmed with a `packageReferenceId` other than `"123"` (e.g. from
   the legacy easyesale system)? If so the edit would add a package instead — we may need to read it back
   via Orders v2026 `getOrder` first.
2. Should the buyer be notified on the edit? (Shopify sends `notifyCustomer: true`; eBay/Walmart/Amazon
   notify automatically; Temu unknown.)
3. Do we want a per-marketplace time limit in the UI (e.g. hide the button after delivery), or just show
   the marketplace's error?
