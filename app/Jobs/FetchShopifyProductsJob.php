<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Models\Catalog;
use App\Models\GenerateProductImage;
use App\Models\ListingItem;
use App\Models\ListingItemProductImage;
use App\Models\ManageMarketplaceProduct;
use App\Services\Auth\ShopifyAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class FetchShopifyProductsJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 1800;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $this->updateTrackingRecord(['tags' => 'Shopify Products']);

        /* get active store jobs*/
        $store_ids = Common::getActiveStoreJobs('FetchShopifyProductsJob','shopify');
        /* get active store jobs*/

        $query = Store::select('store.*','store_type.name as storeTypeName')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify');
        if(!empty($store_ids)){
            $query->whereIn('store.id',$store_ids);
        }
        $stores = $query->get();

        //$stores = Store::select('store.*','store_type.name as storeTypeName')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->whereIn('store.id',$store_ids)->get();

        if (count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                if (env('APP_ENV') === 'local' && $store->name != 'GetAccessory') {
                    continue;
                }

                if (($store->auto_generate_listings ?? 0) == 0) {
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
                            'reason' => $e->getMessage() ?? 'Unknown',
                            'method' => __METHOD__
                        ],$extraInfo);

                        Log::error('Error fetching products for store: ' . $store->name . ' - ' . $e->getMessage());
                        // Optionally, you can break the loop or continue based on your error handling strategy
                        $hasNextPage = false; // Stop the loop on error
                    }
                }
            }
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
                    $listingItems = ListingItem::where('sku',$sku)->get();
                    if (count($listingItems) > 0) {
                        foreach ($listingItems as $listingItem) {
                            if (isset($listingItem->storeProduct->product)) {
                                $productData = $listingItem->storeProduct->product;
                                $handle = $product['handle'];
                                $product_code = $productData->product_sku;
                                ManageMarketplaceProduct::updateOrCreate([
                                    'product_id' => $listingItem->storeProduct->product_id,
                                    'product_code' => $product_code,
                                    'store_id' => $store->id,
                                    'sku' => $sku,
                                ], [
                                    'product_id' => $listingItem->storeProduct->product_id,
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
                            }

                            $oldImages = $shopifyAuthService->getProductImages($product['id']);
                            $this->handleMediaImages($oldImages['data']['product'], $listingItem);
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
//        $query = DB::connection('easyesale_db')->table('listing_item_product_image')
//            ->join('product_image', 'product_image.id', '=', 'listing_item_product_image.product_image_id')
//            ->where('product_id', 'd75692a5-d38c-4599-9cc0-25d526a63686')->whereNotNull('reference_id');


//        $listingImages = $query->get();
        $AlreadyImages = [];
//        foreach ($listingImages as $listingImage) {
//            $AlreadyImages[$listingImage->path] = [
//                'path' => $listingImage->path,
//                'main_image' => $listingImage->main_image,
//                'listing_item_id' => $listingImage->listing_item_id,
//                'sort_order' => $listingImage->sort_order,
//                'code' => $listingImage->code
//            ];
//        }

        $media = $product['media']['nodes'];
        Log::info('No of Media Items: ' . json_encode($media));
        foreach ($media as $mediaItem) {
            if ($mediaItem['mediaContentType'] == 'IMAGE') {
                if (!isset($mediaItem['preview']['image']['url'])) {
                    continue;
                }
                Log::info('Media Image: ' . json_encode($mediaItem));
                $imageUrl = $mediaItem['preview']['image']['url'];
                if (ListingItemProductImage::where('listing_item_id', $listingItem->id)->where('path', $imageUrl)->exists()) {
                    continue;
                }
                if(isset($AlreadyImages) && array_key_exists($imageUrl,$AlreadyImages)){
                } else {
                    $altText = $mediaItem['alt'] ?? '';
                    Log::info('Image URL: ' . $imageUrl);
                    $generatedImage = GenerateProductImage::where('product_image_id', $altText)
                        ->first();
                    if (isset($generatedImage)) {
                        ListingItemProductImage::updateOrCreate([
                            'listing_item_id' => $listingItem->id,
                            'path' => $imageUrl,
                        ], [
                            'listing_item_id' => $listingItem->id,
                            'product_image_id' => $generatedImage->product_image_id,
                            'main_image' => $generatedImage->main_image,
                            'image_file_system_id' => $generatedImage->image_file_system_id,
                            'reference_id' => $generatedImage->reference_id,
                            'path' => $imageUrl,
                        ]);
                    }
                }
            }
        }
    }

}
