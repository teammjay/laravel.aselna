<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Services\Auth\ShopifyAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Store;

class FetchShopifyProductsJob implements ShouldQueue
{
    use Queueable;

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
                if (env('APP_ENV') === 'local' && $store->secret_key == '' && $store->service_url == '') {
                    continue;
                }

                $allProducts = [];
                $hasNextPage = true;
                $afterCursor = null;

                while ($hasNextPage) {
                    $after = $afterCursor ? $afterCursor : NULL;
                    try {
                        $shopifyAuth = new ShopifyAuthService($store->secret_key, $store->service_url);
                        $response = $shopifyAuth->getProducts($after);
                        if(isset($response['data']['products'])){
                            $productsData = $response['data']['products'];
                            if(isset($productsData['edges'])){
                                foreach ($productsData['edges'] as $edge) {
                                    $allProducts[] = $edge['node'];
                                }
                            }
                            // Prepare for next page
                            $hasNextPage = $productsData['pageInfo']['hasNextPage'];
                            $afterCursor = end($productsData['pageInfo'])['endCursor'] ?? null;
                        }
                    } catch (\Exception $e) {
                        return ['error' => $e->getMessage()];
                    }
                }
                $this->manageProducts($allProducts, $store);
            }
        }
    }

    private function manageProducts($products, $store){
        if(isset($products)){
            foreach($products as $product) {
                $sku = '';
                $variants = $product['variants']['edges'];
                if($variants){
                    foreach($variants as $edge){
                        $variant = $edge['node'];
                        $sku = $variant['sku'];
                    }
                }

                if($sku) {
                    $skuPart = explode('-',$sku);
                    $product_code = $skuPart[0];
                    if($product_code){
                        $productDetail = Catalog::whereLike('product_sku',$product_code)->first();
                        ManageMarketplaceProduct::updateOrCreate([
                            'product_id' => $productDetail->product_id,
                            'product_code' => $product_code,
                            'store_id' => $store->id,
                            'sku' => $sku,
                        ], [
                            'product_id' => $productDetail->product_id,
                            'product_code' => $product_code->product_sku,
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
