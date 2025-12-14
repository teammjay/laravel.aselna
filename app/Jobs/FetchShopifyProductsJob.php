<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ListingItem;
use App\Models\ManageMarketplaceProduct;
use App\Services\Auth\ShopifyAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Store;
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

        $stores = Store::select('store.*','store_type.name as storeTypeName')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->get();

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
                            $this->manageProducts($allProducts, $store);
                            Log::info('End Cursor: ' . $afterCursor);
//                            $hasNextPage = false;
                        } else {
                            $hasNextPage = false;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error fetching products for store: ' . $store->name . ' - ' . $e->getMessage());
                        // Optionally, you can break the loop or continue based on your error handling strategy
                        $hasNextPage = false; // Stop the loop on error
                    }
                }
            }
        }
    }

    private function manageProducts($products, $store)
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
                    $listingItem = ListingItem::where('store_sku',$sku)->first();
                    if (isset($listingItem) && isset($listingItem->storeProduct) && isset($listingItem->storeProduct->product)) {
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
                        ]);

                        $listingItem->update([
                            'store_url' => $handle,
                            'store_sku' => $product['id'] . '-' . $variantId,
                        ]);
                    } else {
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
}
