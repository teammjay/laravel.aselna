<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ManageMarketplaceProduct;
use App\Services\Auth\ShopifyAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Store;
use Illuminate\Support\Facades\Log;

class FetchShopifyProductsJob implements ShouldQueue
{
    use Queueable;

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
        $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->get();

        if (count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                if (env('APP_ENV') === 'local' && $store->name != 'AccessoryWiz') {
                    continue;
                }

                Log::info('Store Name: ' . $store->name);

                Log::info('Secret Key: ' . $store->secret_key);

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
                $variants = $product['variants']['edges'];
                if ($variants) {
                    foreach ($variants as $edge) {
                        $variant = $edge['node'];
                        $sku = $variant['sku'];
                    }
                }

                if ($sku) {
                    $skuPart = explode('-', $sku);
                    $product_code = $skuPart[0];
                    if ($product_code) {
                        Log::info('Processing Product Code: ' . $product_code . ' for Store: ' . $store->name);
                        $productDetail = Catalog::whereLike('product_sku', $product_code)->first();
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
                            'upload_status' => 'success',
                            'upload_attempts' => 1,
                        ]);
                    }
                }
            }
        }

    }
}
