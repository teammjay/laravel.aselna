<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\Store;
use App\Models\ManageShopifyProduct;
use App\Models\ProductImages;
use App\Models\ProductInterface;
use App\Models\ProductTags;
use App\Models\StoreTypeListing;
use App\Models\UPCCodes;
use App\Services\Auth\ShopifyAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SubmitShopifyProductsJob implements ShouldQueue
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
        /* shopify stores*/
        $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','shopify')->get();

        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                Log::info('Store : ' . $store->name);
                if (trim(strtolower($store->name)) != strtolower('AccessoryWiz')) {
                    continue;
                }

                Log::info('Store IN: ' . $store->name);
                $pIds = ManageShopifyProduct::select('product_id')->where('upload_status','success')->get();
                $products = Catalog::select('*')->with('productType','productKeyFeatures','productColor')
                    ->whereNotIn('product_id', $pIds)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                    /*->chunk(25, function ($products) use ($store) {
                        foreach ($products as $product) {
                            $this->createFeed($product,$store);
                        }
                    });*/
                Log::info('Products: ' . json_encode($products));
                foreach ($products as $product) {
                    $this->submitFeed($product, $store);
                }
            }
        }
    }

    public function submitFeed($productData,$store)
    {
        $fileSystem = $store->fileSystem;

        $is_uploaded = ManageShopifyProduct::where('product_id',$productData->product_id)->where('store_id',$store->id)->where('upload_status','success')->count();

        Log::info('Count Info: ' . $is_uploaded);
        Log::info('Product Info: ' . json_encode($productData));

        if($is_uploaded > 0){
            return true;
        }

        $ManageShopifyProduct = ManageShopifyProduct::create([
            'product_id' => $productData->product_id,
            'product_code' => $productData->product_sku,
            'store_id' => $store->id,
            'upload_status' => 'pending',
            'upload_attempts' => 1,
        ]);

        $productOptions = [];
        Log::info('Product Info: ' . json_encode($productData));

        $descriptionHtml = '';
        if(isset($productData->productKeyFeatures)) {
            $descriptionHtml .= '<ul>';
            foreach ($productData->productKeyFeatures as $feature) {
                $safeText = htmlspecialchars($feature->value, ENT_QUOTES | ENT_XML1, 'UTF-8'); // Escapes special characters
                if (!empty(trim($safeText))) {
                    $descriptionHtml .= '<li>' . $safeText . '</li>';
                }
            }

            $descriptionHtml .= '</ul>';
        }
        $descriptionHtml .= '<h2>Product Description</h2>';
        $descriptionHtml .= '<p>' . $productData->product_description . '</p>';

        $tags = [];
        $tags = $this->getAllTags($productData, $tags);
        Log::info('Tags: ' . json_encode($tags));
        $tagsString = implode(',', $tags);

        $productOptions[] = [
            'name' => 'Color',
            'values' => [
                ['name' => (isset($productData->productColor->name)) ? $productData->productColor->name : 'Black']
            ]
        ];

        $productImages = ProductImages::where('product_id', $productData->product_id)->orderBy('sort_order','asc')->get();
        $files = [];
        /*foreach ($productImages as $image) {
            $files[] = [
                'contentType' => 'IMAGE',
                'originalSource' => $fileSystem->url_base_path . $image->path,
            ];
        }*/

        $sku = StoreTypeListing::generateUniqueAlphabeticSku($productData->product_sku, 6);
        $upcCodes = UPCCodes::where('store', $store->id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit(1)->first();

        $productNewData = [
            'productSet' => array_filter([
                'title' => $productData->product_title,
                'descriptionHtml' => $descriptionHtml,
                'vendor' => $store->name,
                'productType' => $productData->productType->name,
                'tags' => $tagsString,
                'productOptions' => $productOptions,
                'files' => $files,
                'variants' => [
                    [
                        'price' => $productData->product_price,
                        'sku' => $sku,
                        'barcode' => isset($upcCodes->upc_code) ? $upcCodes->upc_code : '',
                        'optionValues' => [
                            [
                                'optionName' => 'Color',
                                'name' => (isset($productData->productColor->name)) ? $productData->productColor->name : 'Black',
                            ]
                        ],
                        'inventoryItem' => [
                            'sku' => $sku,
                            'tracked' => true,
                        ]
                    ]
                ],
            ], function ($value) {
                return !is_null($value);
            }),
        ];

        Log::info('Product Data: ' . json_encode($productNewData));

        $shopifyAuthService = new ShopifyAuthService($store->secret_key);
        $response = $shopifyAuthService->saveProduct($productNewData);
        Log::info('Product: ' . json_encode($response));

        if(isset($response['data']) && empty($response['data']['userErrors'])) {

            $productId = $response['data']['productSet']['product']['id'];
            $variantId = $response['data']['productSet']['product']['variants']['nodes'][0]['id'];

            Log::info('Variant ID: ' . $variantId);

            $inventoryItemId = $shopifyAuthService->getInventoryItemId($variantId);
            $locationId = $shopifyAuthService->getLocationId();

            if (!$inventoryItemId || !$locationId) {
                Log::info('Invalid inventory item or location for variant '.$variantId.'"]');
                return false;
            }

            // Prepare inventory update array
            $inventoryUpdates['locationId'] = $locationId;
            $inventoryUpdates['changes'] = [
                'inventoryItemId' => $inventoryItemId,
                'locationId' => $locationId,
                'delta' => 20
            ];

            $response = $shopifyAuthService->updateInventory($inventoryUpdates);

            if(isset($response['data']) && empty($response['data']['userErrors'])) {
                Log::info('SKU : ' . $sku);
                $ManageShopifyProduct->update([
                    'sku' => $sku,
                    'shopify_product_id' => $productId,
                    'shopify_variant_id' => $variantId,
                    'upload_status' => 'success'
                ]);
            }
        }

        return true;
    }

    public function getAllTags($productData, array $tags): array
    {
        ProductTags::where('product_id', $productData['product_id'])->get()->each(function ($tag) use (&$tags) {
            $tags[] = $tag->name;
        });

        ProductInterface::where('product_id', $productData['product_id'])->get()->each(function ($interface) use (&$tags) {
            $tags[] = $interface->interface_id;
        });

        if (isset($productData->productColor) && !empty($productData->productColor->name)) {
            $tags[] = 'color_' . $productData->productColor->name;
            $tags[] = $productData->productColor->name;
        }

        if (isset($productData->material) && !empty($productData->material->name)) {
            $tags[] = 'material_' . $productData->material->name;
        }

        if (isset($productData->size) && !empty($productData->size->value)) {
            $tags[] = 'size_' . $productData->size->value;
        }

        if (isset($productData->productType) && !empty($productData->productType->id)) {
            $tags[] = $productData->productType->id;
        }

        if (isset($productData->productType) && !empty($productData->productType->name)) {
            $tags[] = $productData->productType->name;
        }

        if (isset($productData->productType) && !empty($productData->productType->parent) && !empty($productData->productType->parent->id)) {
            $tags[] = $productData->productType->parent->id;
        }

        if (isset($productData->productType) && !empty($productData->productType->parent) && !empty($productData->productType->parent->name)) {
            $tags[] = $productData->productType->parent->name;
        }

        $tags[] = 'upsell-enable';
        return $tags;
    }
}
