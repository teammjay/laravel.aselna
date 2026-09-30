<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Models\Catalog;
use App\Models\GenerateProductImage;
use App\Models\ListingItem;
use App\Models\ListingItemProductImage;
use App\Models\ManageMarketplaceProduct;
use App\Models\StoreProduct;
use App\Services\Auth\ShopifyAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;
use App\Jobs\Concerns\ScopesToStores;
use App\Jobs\Contracts\StoreScopedJob;

class FetchShopifyProductsJob implements ShouldQueue, StoreScopedJob
{
    use Queueable, ScopesToStores, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 18000;

    /**
     * Legacy single-store id. Only set on jobs queued before $storeIds existed; read as a fallback.
     */
    public ?string $storeId = null;

    public static function eligibleStoreTypes(): array
    {
        return ['shopify'];
    }

    /**
     * The store query below does not filter on store_status.
     */
    public static function onlyActiveStores(): bool
    {
        return false;
    }

    /**
     * Create a new job instance.
     *
     * Pass a store id (or a list of store ids) to fetch only those stores,
     * e.g. scheduled_cron_jobs.parameters = {"store_id": "<uuid>"}.
     *
     * @param  bool  $ignoreAutoGenerate  Fetch the store even when auto_generate_listings is off. OyderaMasterSwitch
     *                                    uses it to record the products oydera created, before Aselna takes the store over.
     */
    public function __construct(
        string|array|null $storeIds = null,
        public bool $ignoreAutoGenerate = false
    ) {
        $this->initStoreScope($storeIds);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Shopify Products']);

            /* get active store jobs*/
            if (empty($this->storeIds) && !empty($this->storeId)) {
                $this->storeIds = [$this->storeId];
            }
            $store_ids = $this->resolveStoreIds();
            /* get active store jobs*/

            $query = Store::select('store.*','store_type.name as storeTypeName')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify');
            if(!empty($store_ids)){
                $query->whereIn('store.id',$store_ids);
            }
            $stores = $query->get();

            //$stores = Store::select('store.*','store_type.name as storeTypeName')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->whereIn('store.id',$store_ids)->get();

            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {
                    $this->trackCurrentStore($store, count($stores));
                    // Locally only GetAccessory, unless a store was asked for.
                    if (env('APP_ENV') === 'local' && empty($this->storeIds) && $store->name != 'GetAccessory') {
                        continue;
                    }

                    if (!$this->ignoreAutoGenerate && ($store->auto_generate_listings ?? 0) == 0) {
                        Log::info('Auto Generate Listings is disabled for store: ' . $store->name);
                        continue;
                    }

                    Log::info('Store Name: ' . $store->name);

                    Log::info('Secret Key: ' . $store->secret_key);

                    $this->updateTrackingRecord(['tags' => $store->storeTypeName]);

                    $hasNextPage = true;
                    $afterCursor = null;

                    while ($hasNextPage) {
                        $after = $afterCursor ?? NULL;
                        $allProducts = [];
                        try {
                            $shopifyAuth = new ShopifyAuthService($store->secret_key, $store->service_url);
                            $response = $shopifyAuth->getProducts($after);
                            if (isset($response['data']['products'])) {
                                $productsData = $response['data']['products'];
                                if (isset($productsData['nodes'])) {
                                    foreach ($productsData['nodes'] as $node) {
                                        $allProducts[] = $node;
                                    }
                                }

                                Log::info(('Page Info: ' . json_encode($productsData['pageInfo'])));
                                // Prepare for next page
                                $hasNextPage = $productsData['pageInfo']['hasNextPage'];
                                $afterCursor = $productsData['pageInfo']['endCursor'] ?? null;
                                $this->manageProducts($allProducts, $store, $shopifyAuth);
                                Log::info('End Cursor: ' . $afterCursor);
//                            $hasNextPage = false;
                            } else {
                                $hasNextPage = false;
                            }
                        } catch (\Throwable $e) {

                            $extraInfo = [
                                'store_id' => $store->id
                            ];

                            \App\Helpers\ErrorLogger::log($e, __METHOD__, $extraInfo);

                            CriticalErrorService::report('Fetch Shopify Product Job Failed', [
                                'marketplace' => 'Shopify',
                                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                                'method' => __METHOD__
                            ],$extraInfo);

                            Log::error('Error fetching products for store: ' . $store->name . ' - ' . $e->getMessage());
                            // Optionally, you can break the loop or continue based on your error handling strategy
                            $hasNextPage = false; // Stop the loop on error
                        }
                    }
                }
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);
        }
    }

    private function manageProducts($products, $store, $shopifyAuthService)
    {
        if (isset($products)) {
            foreach ($products as $product) {
                $sku = '';
                $variantId = '';
                $variants = $product['variants']['edges'];
                $productTitle = $product['title'] ?? '';
                if ($variants) {
                    foreach ($variants as $edge) {
                        $variant = $edge['node'];
                        $sku = $variant['sku'];
                        $variantId = $variant['id'];
                    }
                }


                if ($sku) {
                    // Only this store's listing items: stores share skus (OdeMobile / OdeMobile-C), and the
                    // Shopify ids written below belong to this store alone.
                    $listingItems = ListingItem::select('listing_item.*')
                        ->leftJoin('store_product', 'store_product.id', '=', 'listing_item.store_product_id')
                        ->where('listing_item.sku', $sku)
                        ->where(function ($query) use ($store) {
                            $query->where('store_product.store_id', $store->id)
                                ->orWhereNull('listing_item.store_product_id');
                        })
                        ->get();
                    if (count($listingItems) > 0) {
                        foreach ($listingItems as $listingItem) {
                            $productData = $listingItem->storeProduct->product ?? $listingItem->product;
                            $storeProduct = StoreProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->first();
                            if (isset($productData)) {
                                $handle = $product['handle'];
                                $product_code = $productData->product_sku;
                                ManageMarketplaceProduct::updateOrCreate([
                                    'product_id' => $productData->product_id,
                                    'product_code' => $product_code,
                                    'store_id' => $store->id,
                                    'sku' => $sku,
                                ], [
                                    'product_id' => $productData->product_id,
                                    'product_code' => $product_code,
                                    'sku' => $sku,
                                    'store_id' => $store->id,
                                    'marketplace_product_id' => $product['id'],
                                    'marketplace_variant_id' => $variantId,
                                    'upload_status' => 'success',
                                    'upload_attempts' => 1,
                                    'upc_code' => $listingItem->upc,
                                ]);

                                $replacedProductId = str_replace('gid://shopify/Product/', '', $product['id']);
                                $replacedVariantId = str_replace('gid://shopify/ProductVariant/', '', $variantId);

                                $listingItem->update([
                                    'store_url' => $handle,
                                    'store_sku' => $replacedProductId . '-' . $replacedVariantId,
                                ]);

                                $storeProduct?->update([
                                    'last_synch_date_time' => date('Y-m-d H:i:s')
                                ]);
                            } else {
                                Log::warning('No product resolved for Listing Item: ' . $listingItem->id . ' with SKU: ' . $sku . ' in Store: ' . $store->name);
                            }

                            $oldImages = $shopifyAuthService->getProductImages($product['id']);
                            if (isset($oldImages['data']['product'])) {
                                $this->handleMediaImages($oldImages['data']['product'], $listingItem);
                            }
                        }
                    }
                     else {
                        $skuPart = explode('-', $sku);
                        $product_code = $skuPart[0];
                        if ($product_code) {
                            Log::info('Processing Product Code: ' . $product_code . ' for Store: ' . $store->name);
                            $productDetails = Catalog::whereLike('product_sku', $product_code)->get();
                            if( count($productDetails) > 1) {
                                Log::warning('Multiple products found for SKU: ' . $product_code . ' in Store: ' . $store->name);
                                foreach ($productDetails as $detail) {
                                    Log::info('Product ID: ' . $detail->product_id . ' - SKU: ' . $detail->product_sku);
                                    //match title by starting keyword.
                                    $firstWord = explode(' ', $productTitle)[0];
                                    Log::info('First Word from Title: ' . $firstWord);
                                    if (str_starts_with($detail->product_title, $firstWord)) {
                                        Log::info('Matched Product: ' . $detail->product_sku . ' with Title: ' . $productTitle);
                                        $productDetail = $detail;
                                        break; // Use the first matching product
                                    }
                                }
                            } else {
                                $productDetail = $productDetails->first();
                            }
                            if (!$productDetail) {
                                Log::warning('Product not found for SKU: ' . $product_code . ' in Store: ' . $store->name);
                                continue; // Skip to the next product if not found
                            }
                            ManageMarketplaceProduct::updateOrCreate([
                                'product_id' => $productDetail->product_id,
                                'product_code' => $product_code,
                                'store_id' => $store->id,
                                'sku' => $sku,
                            ], [
                                'product_id' => $productDetail->product_id,
                                'product_code' => $product_code,
                                'sku' => $sku,
                                'store_id' => $store->id,
                                'marketplace_product_id' => $product['id'],
                                'marketplace_variant_id' => $variantId,
                                'upload_status' => 'success',
                                'upload_attempts' => 1,
                            ]);

                        }
                    }
                }
            }
        }

    }

    private function handleMediaImages($product, $listingItem)
    {
        $media = $product['media']['nodes'];
        Log::info('No of Media Items: ' . json_encode($media));

        $shopifyMediaIds = [];
        foreach ($media as $node) {
            if ($node['mediaContentType'] == 'IMAGE') {
                $mediaId = str_replace('gid://shopify/MediaImage/', '', $node['id']);
                $shopifyMediaIds[] = $mediaId;

                // Rows copied from oydera hold the REST product image id, which is not the media id.
                // The file is the same, so correct the id by it; otherwise the delete below removes them
                // and productUpdateMedia is sent an id Shopify does not know. The ?v= part of the url
                // changes when Shopify re-saves the image, so only the part before "?" is compared.
                // Query builder, so updated_at (when the image was pushed) is left alone.
                $imageUrl = $node['preview']['image']['url'] ?? null;
                if (!empty($imageUrl)) {
                    DB::table('listing_item_product_images')
                        ->where('listing_item_id', $listingItem->id)
                        ->whereRaw("SUBSTRING_INDEX(path, '?', 1) = ?", [strtok($imageUrl, '?')])
                        ->where(function ($query) use ($mediaId) {
                            $query->whereNull('reference_id')->orWhere('reference_id', '<>', $mediaId);
                        })
                        ->update(['reference_id' => $mediaId]);
                    Log::info("updating image reference: " . $imageUrl);
                }
            }
        }

        // Delete local images that have a reference_id but are NOT in Shopify anymore
        ListingItemProductImage::where('listing_item_id', $listingItem->id)
            ->whereNotNull('reference_id')
            ->whereNotIn('reference_id', $shopifyMediaIds)
            ->delete();

        foreach ($media as $mediaItem) {
            if ($mediaItem['mediaContentType'] == 'IMAGE') {
                if (!isset($mediaItem['preview']['image']['url'])) {
                    continue;
                }
                Log::info('Media Image: ' . json_encode($mediaItem));
                $imageUrl = $mediaItem['preview']['image']['url'];
                $ImageId = $mediaItem['id'];
                $imageIdReplaced = str_replace('gid://shopify/MediaImage/', '', $ImageId);
                if (ListingItemProductImage::where('listing_item_id', $listingItem->id)->where('path', $imageUrl)->exists()) {
                    continue;
                }
                $existingImage = ListingItemProductImage::where('listing_item_id', $listingItem->id)->where('reference_id', $imageIdReplaced)->first();
                if (isset($existingImage)) {
                    // Image was pushed before Shopify finished processing it, so the URL was never stored.
                    if (empty($existingImage->path)) {
                        Log::info('Filling missing image path for Listing Item: ' . $listingItem->id . ' Media: ' . $imageIdReplaced);
                        $existingImage->update(['path' => $imageUrl]);
                    }
                    continue;
                }
                if(isset($AlreadyImages) && array_key_exists($imageUrl,$AlreadyImages)){
                } else {
                    $altText = $mediaItem['alt'] ?? '';
                    Log::info('Image URL: ' . $imageUrl);
                    // An image can have a row per alternate; the media already on Shopify is recorded against the original's.
                    $generatedImage = GenerateProductImage::where('product_image_id', $altText)
                        ->orderByRaw('alt_product_image_id IS NULL DESC')
                        ->first();
                    if (isset($generatedImage)) {
                        ListingItemProductImage::updateOrCreate([
                            'listing_item_id' => $listingItem->id,
                            'product_image_id' => $generatedImage->product_image_id,
                            'reference_id' => $imageIdReplaced,
                        ], [
                            'listing_item_id' => $listingItem->id,
                            'product_image_id' => $generatedImage->product_image_id,
                            'main_image' => $generatedImage->main_image,
                            'image_file_system_id' => $generatedImage->image_file_system_id,
                            'reference_id' => $imageIdReplaced,
                            'path' => $imageUrl,
                        ]);
                    }
                }
            }
        }
    }

}
