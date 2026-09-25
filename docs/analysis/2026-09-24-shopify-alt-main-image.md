# Shopify alternate main image (`product_alt_image_number`): analysis and implementation plan

**Date:** 2026-09-24
**Scope:** How Oydera picks an alternate image as the Shopify main image for some stores, what
Aselna does today, the code needed to do the same in Aselna, and the risks of doing it on stores
whose images were copied from Oydera.
**No code was changed.** This is a read-only analysis. No live database was queried; the SQL in
§7 is there to confirm the assumptions before the build starts.

Old-system reference: `cuba-master/docs/shopify-image-sync.md` (Oydera `ImageLoader`, bugs B0–B10).

---

## 1. Summary

1. **Aselna ignores `product_alt_image_number`.** `SubmitShopifyProductsJob` always pushes the
   original `product_images` file. The column exists in the table, is copied from Oydera by
   `StoreSyncJob`, and is never read.
2. **Rows copied from Oydera already point at the alternate on Shopify.**
   `ListingItemProductImageSyncJob` copies Oydera's `listing_item_product_image.path` /
   `reference_id` onto the Aselna row for our *original* `product_images` id. For an alternate
   store, the Shopify media behind that `reference_id` is the **alternate** file that Oydera
   pushed. Nothing on the Aselna row records that (`alt_product_image_id` stays null).
3. **Aselna can already overwrite those alternates with the original.** The "updated images" pass
   in `SubmitShopifyProductsJob::updateProduct` (`:724-753`) re-sends the original file whenever
   `product_images.updated_at` is newer than the listing image row. On an alternate store this
   silently turns the main image back to the original. That is risk **R1**, and it applies today.
4. **The building blocks exist.** `listing_item_product_images.alt_product_image_id`,
   `generated_product_image.alt_product_image_id`, `product_image_alternatives.version_number` /
   `main_image_id`, and a working precedent (`ListingItemImageCreate`, which picks an alternate by
   `version_number` for the main image). The featured-image reorder in `updateProduct` already
   moves the `main_image = 1` media to position 0.
5. **Recommended approach:** match Oydera's *store rule* exactly (N-th alternate by position,
   ordered by `version_number`). In Aselna, alternates hang only off the main image, so this is
   main-image-only. Record the chosen alternate on the listing image row. Re-push only when that
   choice changes. Backfill the copied rows *without* re-pushing (§5).

---

## 2. What Oydera does (short version)

Source: `ImageLoader.assignImagesForMarketplaceProductEntity` (`ImageLoader.java:148-160`).

For each listing image, the file is chosen in this order:

| # | Condition | File pushed |
|---|---|---|
| 1 | image has alternates **and** `listing_item.image_version > 0` | `alternates.get(image_version)`: 0-based index, **buggy** (B1) |
| 2 | image has alternates **and** `store.product_alt_image_number = N > 0` **and** `N <= count` | `alternates.get(N - 1)`: N-th alternate **by position**, ordered by `version_number` |
| 3 | otherwise | the original `product_image` file |

It is then sent with the original image's `sort_order` and `main_image` flag. So if the main
image has alternates, the alternate becomes the Shopify featured image.

Known Oydera weaknesses that matter when copying it (numbering from the Oydera doc):

- **B2/B3**: applied to every image, by position rather than `version_number`.
- **B5**: changing `product_alt_image_number` does **not** re-push. Shopify keeps the old file
  until the image row is touched.
- No record of which alternate was pushed: the LIPI row has only `reference_id` / `path`.

---

## 3. What Aselna does today

### 3.1 Tables

| Table | Relevant columns | Notes |
|---|---|---|
| `store` | `product_alt_image_number`, `productaltimagenumber` (both `int`, nullable) | **Two columns.** `StoreSyncJob:152-153` copies both from `easyesale_db.store`. The store edit screen (`resources/views/store/update.blade.php:316-319`) edits **`productaltimagenumber`** through a free-text input with no validation (`StoreController::update` → `$store->update($request->all())`). Neither column is read anywhere. |
| `product_images` | `id`, `product_id`, `main_image`, `image` (→ `sys_file`), `sort_order`, `code`, `updated_at` | Built from Fonus (`ProductImagesSyncJob`). |
| `product_image_alternatives` | `id`, `product_id`, `main_image_id` (→ `product_images.id`), `image` (→ `sys_file`), `version_number`, `sort_order` | Built from Fonus `tbl_product_main_images` by `ProductAlternativeImagesSyncJob`. `version_number` = **Fonus `image_id`**. `main_image_id` is always the product's **main** image, so alternates exist only for the main image. |
| `generated_product_image` | `product_id`, `product_image_id`, `alt_product_image_id`, `main_image`, `image_file_system_id`, `path` | The FTP copy that Shopify pulls from (`url_base_path . path`). **No `store_id`.** Upserted on `product_image_id` alone. `alt_product_image_id` is always written as null today. The model's `$fillable` lists `version_number`, but the table has no such column. |
| `listing_item_product_images` (LIPI) | `listing_item_id`, `product_image_id`, `alt_product_image_id`, `main_image`, `image_file_system_id`, `path` (Shopify CDN url), `reference_id` (Shopify MediaImage id) | Tracks Shopify state per listing item. `alt_product_image_id` exists but the Shopify path never sets it. The model's `$fillable` has `version_number`, but the table has no such column. |
| `listing_item` | `image_version` | Copied from Oydera (`SyncShopifyItems:973`). Read only by `ListingItemImageCreate` (non-Shopify feed path). |

### 3.2 Push flow (`app/Jobs/SubmitShopifyProductsJob.php`)

```
handle()
  for each active Shopify store with auto_generate_listings
    createProduct()  – products with no successful manage_marketplace_products row (limit 100)
    updateProduct()  – store_product / products updated since last_synch_date_time (limit 200)
    discontinueProduct()
```

**`generateProductImages()` (`:273-309`)**: for every `product_images` row of the product, FTP
uploads the **original** file to `/store-images/{store_id}/{product_image_id}.jpg` and upserts
`generated_product_image` on `product_image_id`. The row is skipped when
`generated.updated_at >= product_image.updated_at`.

**`createProduct()` (`:366-544`)**: sends `productSet` with `files[]` built from
`generated_product_image`, ordered `main_image desc`, with `alt = product_image_id`. Then
`handleMediaImages()` (`:937-975`) maps the returned media back to LIPI rows by that `alt` text.

**`updateProduct()` (`:546-777`)**, in order:

1. `handleOldImages()`: **deletes LIPI rows with `product_image_id IS NULL`**, then re-maps the
   current Shopify media into LIPI by `alt`.
2. `generateProductImages()`.
3. Adds media for `product_images` that have no LIPI row yet.
4. `productUpdate` → `handleMediaImages()`.
5. Deletes LIPI rows (and their Shopify media) whose `product_image_id` is no longer on the
   product.
6. `deleteOldProductImages()`: deletes Shopify media whose url/`reference_id` is not in
   **any** LIPI row.
7. Featured image: finds the LIPI with `main_image = 1` and moves its media to position 0
   (`setFeaturedImage` → `productReorderMedia`).
8. **Updated images** (`:724-753`): every LIPI whose `updated_at < product_images.updated_at` gets
   `productUpdateMedia` with the **original** generated file, and `path` is set to null.
9. `dispatchMediaPathSync()` → `SyncShopifyMediaPathsJob` fills `path` once Shopify has
   processed the media. `SyncShopifyMissingMediaPathsJob` is the scheduled safety net.

### 3.3 How copied Oydera rows look

`ListingItemProductImageSyncJob::mapImageLink()` (`:462-472`) writes, for every Oydera LIPI:

| Column | Value |
|---|---|
| `product_image_id` | our `product_images.id`, resolved by `code` (always the original image) |
| `main_image` | our `product_images.main_image` |
| `path`, `reference_id` | Oydera's values, i.e. the Shopify media that Oydera pushed, which is the **alternate** on an alternate store |
| `alt_product_image_id` | not written, so it stays null |

Because a LIPI row exists for the main image, step 3 of `updateProduct` does not add it again.
From then on, the only thing that decides whether the alternate survives is step 8.

---

## 4. Target behaviour

For a Shopify store with `N = product_alt_image_number > 0`:

- **Main image only** (`product_images.main_image = 1`). This matches Oydera in practice, because
  Aselna alternates only attach to the main image.
- Alternates of that main image, ordered by `version_number` (then `sort_order`, then `id`, so the
  order is stable); pick position `N` (1-based).
- If there are fewer than `N` alternates, or the alternate has no file, **fall back to the
  original** and log it.
- The pushed media keeps `main_image = 1` and is moved to Shopify position 0 (existing step 7).
- The LIPI row for the main image records **which** file is live: `alt_product_image_id` = the
  chosen alternate, or null for the original.
- A re-push happens when the chosen alternate differs from the recorded one (fixing Oydera B5), or
  when the chosen file itself is newer than the LIPI row.

`listing_item.image_version` should be **ignored for Shopify** unless §7 query Q4 shows Shopify
listing items with `image_version > 0`. If it shows any, see risk R6 before deciding.

---

## 5. Implementation, step by step

### Step 0: Confirm the data (before writing code)

Run the §7 queries. Q1 decides which store column is the source of truth. Q2/Q3 confirm the
position rule works on our `version_number` values. Q4 decides whether `image_version` matters.

### Step 1: Pick one store column

Use `store.product_alt_image_number` (it is Oydera's real column, `PRODUCT_ALT_IMAGE_NUMBER`).

- `resources/views/store/update.blade.php:316-319`: point the input at `product_alt_image_number`,
  give it a proper label, and make it `type="number" min="0"`.
- Validate it in `StoreController::update` (`nullable|integer|min:0`).
- If Q1 shows `productaltimagenumber` holds values that `product_alt_image_number` does not, copy
  them across once in a migration or tinker script. Leave the old column alone for now (the Oydera
  sync still writes it).
- Add a helper on `App\Models\Store`:

```php
public function altImageNumber(): int
{
    return max(0, (int) $this->product_alt_image_number);
}
```

### Step 2: Make `generated_product_image` store-aware

Today one row per `product_image_id` is shared by every Shopify store, and its `path` embeds
whichever store generated it last (see R3). Alternates make this worse, because two stores can
need two different files for the same image.

Migration (new file, `update_generated_product_image_table`):

```php
Schema::table('generated_product_image', function (Blueprint $table) {
    $table->uuid('store_id')->nullable()->after('product_id');
    $table->index(['store_id', 'product_image_id']);
});
```

Upsert key becomes `(store_id, product_image_id)`. `alt_product_image_id` is stored on the row, so
a change of alternate is visible.

### Step 3: A single resolver for "which file does this image push"

New static utility, matching the `app/Utils/` convention: `app/Utils/ShopifyImageUtility.php`.

```php
namespace App\Utils;

use App\Models\ProductImageAlternative;
use App\Models\ProductImages;
use App\Models\Store;
use Illuminate\Support\Facades\Log;

class ShopifyImageUtility
{
    /**
     * The file to push for one product image on one store.
     *
     * @return array{file: \App\Models\SysFile|null, alt_product_image_id: string|null}
     */
    public static function resolveImage(ProductImages $productImage, Store $store): array
    {
        $original = ['file' => $productImage->imageObj, 'alt_product_image_id' => null];

        $n = $store->altImageNumber();
        if ($n <= 0 || !$productImage->main_image) {
            return $original;
        }

        $alternates = ProductImageAlternative::with('imageObj')
            ->where('main_image_id', $productImage->id)
            ->orderBy('version_number')->orderBy('sort_order')->orderBy('id')
            ->get();

        $alternate = $alternates->get($n - 1);
        if (!$alternate || !$alternate->imageObj) {
            Log::info('ShopifyImageUtility: store ' . $store->name . ' wants alternate ' . $n
                . ' of image ' . $productImage->id . ', only ' . $alternates->count() . ' available; using original.');
            return $original;
        }

        return ['file' => $alternate->imageObj, 'alt_product_image_id' => $alternate->id];
    }
}
```

The position rule (`get($n - 1)`) matches Oydera rule 2 byte for byte. That matters because the
copied Shopify images were produced by it. Do not "fix" it to match on `version_number`, or stores
whose images are already live will change.

### Step 4: `generateProductImages()` uses the resolver

In `SubmitShopifyProductsJob::generateProductImages()`:

```php
$resolved = ShopifyImageUtility::resolveImage($productImage, $store);
$file = $resolved['file'];
$altId = $resolved['alt_product_image_id'];

$generated = GenerateProductImage::where('store_id', $store->id)
    ->where('product_image_id', $productImage->id)->first();

$sourceUpdatedAt = $altId ? $file->updated_at : $productImage->updated_at;
if ($generated
    && $generated->alt_product_image_id === $altId
    && $generated->updated_at >= max($productImage->updated_at, $sourceUpdatedAt)) {
    continue;
}

// Different file name per alternate, so Shopify never serves a cached copy of the old file.
$path = '/store-images/' . $store->id . '/' . $productImage->id . ($altId ? '_' . $altId : '') . '.jpg';
$this->uploadImage($store, $file, $path);

GenerateProductImage::updateOrCreate(
    ['store_id' => $store->id, 'product_image_id' => $productImage->id],
    [
        'product_id' => $productData->product_id,
        'alt_product_image_id' => $altId,
        'image_file_system_id' => $file->id,
        'main_image' => $productImage->main_image ? 1 : 0,
        'path' => $path,
        'reference_id' => '',
    ]
);
```

Then change every `GenerateProductImage::where('product_id', …)` / `where('product_image_id', …)`
read in this job (`:440`, `:614`, `:739`, `:956`) to add `->where('store_id', $store->id)`.
`handleMediaImages()` needs `$store` passed in for that.

**Keep `product_image_id` set on the alternate's rows.** The Shopify media `alt` stays
`product_image_id`, so `handleMediaImages()` keeps mapping media back to LIPI rows. Do not copy the
`ListingItemImageCreate` pattern of nulling `product_image_id` (see R2).

### Step 5: Record the alternate on the LIPI row

In `handleMediaImages()`, add `'alt_product_image_id' => $generatedImage->alt_product_image_id` to
the LIPI `updateOrCreate` attributes. This covers `createProduct` and newly added media in
`updateProduct`.

### Step 6: Re-push when the selection changes (replace step 8 of `updateProduct`)

Replace the timestamp-only query at `:724-736` with a comparison against the generated row:

```php
$generatedByImage = GenerateProductImage::where('store_id', $store->id)
    ->where('product_id', $productData->product_id)
    ->get()->keyBy('product_image_id');

$lipis = ListingItemProductImage::where('listing_item_id', $listingItem->id)
    ->whereNotNull('product_image_id')->whereNotNull('reference_id')
    ->get();

foreach ($lipis as $lipi) {
    $generated = $generatedByImage->get($lipi->product_image_id);
    if (!$generated) {
        continue;
    }

    $selectionChanged = $lipi->alt_product_image_id !== $generated->alt_product_image_id;
    $fileChanged = $lipi->updated_at < $generated->updated_at;
    if (!$selectionChanged && !$fileChanged) {
        continue;
    }

    $shopifyAuthService->updateMediaImage($proDetail->marketplace_product_id, $lipi->reference_id, [
        'alt' => $lipi->product_image_id,
        'mediaContentType' => 'IMAGE',
        'originalSource' => $fileSystem->url_base_path . $generated->path,
    ]);

    $lipi->update([
        'alt_product_image_id' => $generated->alt_product_image_id,
        'image_file_system_id' => $generated->image_file_system_id,
        'path' => null,
        'updated_at' => now(),
    ]);
}
```

This is what stops R1. The comparison is against `generated_product_image.updated_at` (bumped only
when a file is actually re-uploaded), not `product_images.updated_at` (bumped by every Fonus sync
that changes any column).

Check the `updateMediaImage` response for `userErrors`. Only clear `path` / update the row on
success, so that a failed call is retried on the next run (see R10).

### Step 7: Backfill copied Oydera rows (no push)

Before Step 6 goes live on an Oydera-copied store, the copied LIPI rows must say which alternate
Shopify already has. Otherwise `selectionChanged` is true for every main image, and the first run
re-pushes the whole store.

Better: do it inside `ListingItemProductImageSyncJob::mapImageLink()`, so every sync run keeps it
right while Oydera is still the writer:

```php
'alt_product_image_id' => $productImage->main_image
    ? ShopifyImageUtility::resolveImage($productImage, $this->store)['alt_product_image_id']
    : null,
```

(Load the `Store` once in `handle()`; cache the alternates per product, because the job runs in
1000-row chunks.)

Also seed `generated_product_image` for these stores with the same `alt_product_image_id`, so the
generated row and the LIPI agree and Step 6 sees no change. A one-off artisan command
(`shopify:backfill-alt-images {store}`) that runs `generateProductImages()` without pushing
anything is the simplest way. It FTP-uploads the files, but Shopify is not touched.

After this, run §7 Q5 for the store. It should return zero rows.

### Step 8: Featured image

No new code. Step 7 of `updateProduct` already moves the `main_image = 1` LIPI to position 0.
In `createProduct`, `files[]` is ordered `main_image desc`, so the alternate is first. Verify
once on a test store that `productSet` keeps that order (see R10).

### Step 9: Tests and rollout

- Unit test for `ShopifyImageUtility::resolveImage` in `tests/Utils/` (N = 0, N within range,
  N out of range, non-main image, alternate with no file). Run it by path; `tests/Utils` is not
  in the phpunit suites, and tests hit the configured MySQL.
- Roll out one store at a time: backfill (Step 7) → Q5 = 0 → enable the Aselna push for that store
  → spot-check the featured image of ~10 products in Shopify admin against Q3.
- Keep the Oydera push off for a store once Aselna is pushing it (R8).

### Files touched

| File | Change |
|---|---|
| `database/migrations/<new>_update_generated_product_image_table.php` | `store_id` + index |
| `app/Models/GenerateProductImage.php` | `store_id` in `$fillable`; drop the phantom `version_number` |
| `app/Models/Store.php` | `altImageNumber()` |
| `app/Utils/ShopifyImageUtility.php` | new resolver |
| `app/Jobs/SubmitShopifyProductsJob.php` | Steps 4–6 |
| `app/Jobs/ListingItemProductImageSyncJob.php` | Step 7 (`mapImageLink`) |
| `app/Console/Commands/…BackfillShopifyAltImages.php` | Step 7 one-off |
| `resources/views/store/update.blade.php`, `app/Http/Controllers/StoreController.php` | Step 1 |
| `tests/Utils/ShopifyImageUtilityTest.php` | Step 9 |

---

## 6. Risks and mitigations

| # | Risk | Where | Effect | Mitigation |
|---|---|---|---|---|
| **R1** | The "updated images" pass re-sends the **original** file whenever `product_images.updated_at > lipi.updated_at`. | `SubmitShopifyProductsJob.php:724-753` | On an alternate store, the Shopify main image turns back into the original after any Fonus image sync touches the row. **This can already happen today.** | Step 6. Until then, run Q6 to see which copied main images are exposed. |
| **R2** | `handleOldImages()` deletes LIPI rows with `product_image_id IS NULL`, and `deleteOldProductImages()` then deletes Shopify media with no LIPI row. | `:1022`, `:993-1018` | If alternates are stored the `ListingItemImageCreate` way (`product_image_id = null`), their LIPI rows are deleted and the **live alternate is deleted from Shopify**. | Keep `product_image_id` on alternate rows; the alternate goes in `alt_product_image_id` (Steps 4–5). |
| **R3** | `generated_product_image` has no `store_id` and is upserted on `product_image_id`. | `:280-306` | The second Shopify store to process a product finds a fresh row and skips the upload, then pushes `its url_base_path + another store's path`. If stores do not share a file system, that URL is broken. With alternates, stores would also overwrite each other's choice. | Step 2. Q7 shows whether the Shopify stores share a file system (which decides whether this is broken today or only after the change). |
| **R4** | Two store columns; the UI edits `productaltimagenumber` (free text, unvalidated), Oydera's value lands in `product_alt_image_number`. | `update.blade.php:316-319`, `StoreSyncJob:152-153` | The feature reads one column while staff edit the other. Non-numeric input gets saved as 0/garbage. Every `StoreSyncJob` run overwrites local edits with Oydera's values. | Step 1. While Oydera is still the source, make the column read-only in the UI or stop `StoreSyncJob` from overwriting it after the switch. |
| **R5** | Aselna `version_number` = Fonus `image_id`; Oydera alternates came from its own editor/transfer. The **order** or **count** of alternates per main image can differ between the two systems. | `ProductAlternativeImagesSyncJob:103`, `:139-142` | The same `N` picks a different file in Aselna than the one live on Shopify, and the next push changes the image. | Q2/Q3: compare per product the N-th alternate's file name in Aselna vs Oydera (via `sys_file.name`). Resolve mismatches before enabling Step 6 for the store. Backfill must use the Aselna rule, so Q5 then shows real differences. |
| **R6** | `listing_item.image_version` is copied from Oydera; Oydera gives it priority but uses it as a 0-based index (B1), so it pushed the wrong alternate or threw an error. | Oydera `ImageLoader.java:153` | If Shopify listing items have `image_version > 0`, what's live is Oydera's buggy pick. Ignoring `image_version` in Aselna changes those images. | Q4. If it returns zero Shopify rows, ignore `image_version`. If not, list the rows, check them in Shopify admin, and decide per store whether to accept the change. |
| **R7** | The first run after enabling Step 6 re-pushes every main image whose recorded alternate is null. | Step 6 | Mass re-upload, lots of Shopify API calls, a brief image flicker, CDN URLs change (`path` cleared). | Step 7 backfill first; Q5 = 0 before enabling. |
| **R8** | Oydera and Aselna both write media to the same Shopify product during the transition. `ListingItemProductImageSyncJob` also overwrites `path`/`reference_id` from Oydera each run. | `OyderaMasterSwitch` chain | Media added by one side is deleted by the other (`deleteOldProductImages` deletes anything not in LIPI). Duplicated or missing images. | One writer per store: turn off Oydera's Shopify image push for a store before Aselna pushes it (or keep `auto_generate_listings = 0` in Aselna until then). |
| **R9** | `deleteOldProductImages()` checks `reference_id` / `path` against **all** LIPI rows, not the listing item's, and runs right after the update. | `:993-1018` | Media that Shopify is still processing (null `path`) and that was never written to LIPI, e.g. a failed `handleMediaImages` match, is deleted. With alternates, a mismatch between `alt` text and LIPI loses the main image. | Keep `alt = product_image_id` for alternates (Step 4). Scope the check to the listing item (`where('listing_item_id', …)`) when touching this code. |
| **R10** | `updateMediaImage` sends `previewImageSource` through `productUpdateMedia`. Whether Shopify replaces the MediaImage file in place (same id) is not verified in this repo; `productSet` media ordering is also not verified. | `ShopifyAuthService.php:646` | If the file is not replaced, the alternate never reaches Shopify, and the LIPI row still claims it did. | Verify on a test store. If it does not replace, switch to delete + `productCreateMedia` for a changed selection, then update `reference_id`. Only update the LIPI after a response with no `userErrors`. |
| **R11** | `ProductAlternativeImagesSyncJob::checkAndDeleteImage` deletes alternates that disappear from Fonus. `alt_product_image_id` has no FK. | `:230-258` | The resolver picks a different alternate (positions shift), so the main image changes. That is correct, but it happens without notice. | Log in the resolver when the chosen alternate differs from the LIPI's (Step 3/6). Optionally report through `CriticalErrorService::report` for the alternate stores. |
| **R12** | `N` larger than the alternate count falls back to the original silently (same as Oydera). | Step 3 | Products without enough alternates show the same main image as other stores, which may defeat the purpose (duplicate-image detection across stores). | Logged in Step 3; Q2 gives the count per store up front. |
| **R13** | Shopify processes media asynchronously; `path` is null right after a push. | Step 6 | Anything that reads `lipi.path` (e.g. `deleteOldProductImages`, other channels) sees no URL for a while. | Already handled by `SyncShopifyMediaPathsJob` / `SyncShopifyMissingMediaPathsJob`; make sure the latter is an active `scheduled_cron_jobs` row. |
| **R14** | `ListingItemImageCreate` upserts on `(listing_item_id, product_image_id)` but writes `product_image_id = null` for an alternate. | `ListingItemImageCreate.php:113-146` | A new duplicate LIPI row on every run. Not on the Shopify path, but it is the same table, and those rows are what R2 deletes. | Out of scope; fix separately (keep `product_image_id`, set `alt_product_image_id`). |

---

## 7. Verification queries (Aselna MySQL, read-only)

Replace `:store_id` as needed.

```sql
-- Q1: which store column carries the value
SELECT s.id, s.name, s.product_alt_image_number, s.productaltimagenumber, s.file_system_id
FROM store s JOIN store_type st ON st.id = s.store_type_id
WHERE st.code = 'shopify' AND s.store_status <> 0;

-- Q2: per store with N > 0, how many active products get an alternate vs fall back
SELECT s.name, s.product_alt_image_number AS n,
       SUM(alt.cnt >= s.product_alt_image_number) AS gets_alternate,
       SUM(COALESCE(alt.cnt, 0) < s.product_alt_image_number) AS falls_back
FROM store s
JOIN store_product sp ON sp.store_id = s.id
JOIN product_images pi ON pi.product_id = sp.product_id AND pi.main_image = 1
LEFT JOIN (SELECT main_image_id, COUNT(*) cnt FROM product_image_alternatives GROUP BY main_image_id) alt
       ON alt.main_image_id = pi.id
WHERE s.product_alt_image_number > 0
GROUP BY s.name, s.product_alt_image_number;

-- Q3: the alternate Aselna would pick for each main image of one store (compare file names with Oydera)
SELECT p.product_sku, pi.id AS product_image_id, x.id AS alt_id, x.version_number, f.name AS alt_file
FROM store s
JOIN store_product sp ON sp.store_id = s.id
JOIN products p ON p.product_id = sp.product_id
JOIN product_images pi ON pi.product_id = p.product_id AND pi.main_image = 1
JOIN (SELECT a.*, ROW_NUMBER() OVER (PARTITION BY main_image_id ORDER BY version_number, sort_order, id) pos
      FROM product_image_alternatives a) x ON x.main_image_id = pi.id
LEFT JOIN sys_file f ON f.id = x.image
WHERE s.id = :store_id AND x.pos = s.product_alt_image_number;

-- Q4: Shopify listing items where Oydera's image_version would have taken priority
SELECT s.name, COUNT(*) AS items_with_image_version
FROM listing_item li
JOIN store_product sp ON sp.id = li.store_product_id
JOIN store s ON s.id = sp.store_id
JOIN store_type st ON st.id = s.store_type_id
WHERE st.code = 'shopify' AND li.image_version > 0
GROUP BY s.name;

-- Q5: after backfill, main-image LIPI rows whose recorded alternate differs from the generated one (expect 0)
SELECT lipi.id, lipi.listing_item_id, lipi.alt_product_image_id, g.alt_product_image_id AS generated_alt
FROM listing_item_product_images lipi
JOIN listing_item li ON li.id = lipi.listing_item_id
JOIN store_product sp ON sp.id = li.store_product_id
JOIN generated_product_image g ON g.product_image_id = lipi.product_image_id AND g.store_id = sp.store_id
WHERE sp.store_id = :store_id AND lipi.main_image = 1
  AND NOT (lipi.alt_product_image_id <=> g.alt_product_image_id);

-- Q6 (R1 exposure today): copied main images on alternate stores that the current code would overwrite
SELECT s.name, COUNT(*) AS exposed
FROM listing_item_product_images lipi
JOIN product_images pi ON pi.id = lipi.product_image_id AND pi.main_image = 1
JOIN listing_item li ON li.id = lipi.listing_item_id
JOIN store_product sp ON sp.id = li.store_product_id
JOIN store s ON s.id = sp.store_id
WHERE s.product_alt_image_number > 0
  AND EXISTS (SELECT 1 FROM product_image_alternatives a WHERE a.main_image_id = pi.id)
  AND lipi.updated_at < pi.updated_at
GROUP BY s.name;

-- Q7 (R3): do the Shopify stores share a file system?
SELECT s.file_system_id, GROUP_CONCAT(s.name) AS stores
FROM store s JOIN store_type st ON st.id = s.store_type_id
WHERE st.code = 'shopify' AND s.store_status <> 0
GROUP BY s.file_system_id;
```

---

## 8. File reference

| File | What to look at |
|---|---|
| `app/Jobs/SubmitShopifyProductsJob.php` | `generateProductImages` :273, `createProduct` :366, `updateProduct` :546 (featured :700-721, updated images :724-753), `handleMediaImages` :937, `deleteOldProductImages` :993, `handleOldImages` :1020 |
| `app/Jobs/ListingItemProductImageSyncJob.php` | `mapImageLink` :462 (copies Oydera LIPI, no alternate recorded) |
| `app/Jobs/ProductAlternativeImagesSyncJob.php` | `createProductImageFonus` :94 (`version_number` = Fonus `image_id`), `checkAndDeleteImage` :230 |
| `app/Jobs/ListingItemImageCreate.php` | Precedent: alternate by `version_number` for main image :113-129; `getProductMainImage` :235 |
| `app/Jobs/Shopify/SyncShopifyMediaPathsJob.php`, `SyncShopifyMissingMediaPathsJob.php` | Filling `path` after async Shopify processing |
| `app/Jobs/Oydera/StoreSyncJob.php` | :152-153, both store columns copied from Oydera |
| `app/Services/Auth/ShopifyAuthService.php` | `getProductImages` :509, `setFeaturedImage` :616, `deleteMediaImages` :634, `updateMediaImage` :646 |
| `app/Models/GenerateProductImage.php`, `ListingItemProductImage.php`, `ProductImageAlternative.php`, `ProductImages.php` | Models |
| `resources/views/store/update.blade.php` | :316-319, store setting input |
| Oydera `ImageLoader.java` | :148-160, selection rules being copied |
