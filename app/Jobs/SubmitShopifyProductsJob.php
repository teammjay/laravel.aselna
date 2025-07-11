<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\GenerateProductImage;
use App\Models\ProductImageAlternative;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\ManageMarketplaceProduct;
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
        $stores = Store::select('store.*')->with('fileSystem','storeType')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','shopify')->get();

        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                Log::info('Store : ' . $store->name);
                if (trim(strtolower($store->name)) != strtolower('AccessoryWiz')) {
                    continue;
                }

                Log::info('Store IN: ' . json_encode($store));
                $pIds = ManageMarketplaceProduct::select('product_id')->where('upload_status','success')->get();
                $productTypes = ProductType::select('id')->where('code', 'ceabundle')->get()->toArray();
                $products = Catalog::select('*')->with('productType','productKeyFeatures','productColor')
                    ->whereNotIn('product_type', $productTypes)
                    ->whereNull('parent_id')
                    ->where('product_status','!=','discontinued')
                    //->where('product_sku', 'E74')
                    ->whereNotIn('product_id', $pIds)
                    ->orderBy('created_at', 'desc')
                    /*->limit(5)
                    ->get();*/
                    ->chunk(100, function ($products) use ($store) {
                        foreach ($products as $product) {
                            $this->submitFeed($product,$store);
                        }
                    });
                //Log::info('Products: ' . json_encode($products));
                /*foreach ($products as $product) {
                    $this->submitFeed($product, $store);
                }*/
            }
        }
    }

    public function submitFeed($productData,$store)
    {
        $fileSystem = $store->fileSystem;

        $is_uploaded = ManageMarketplaceProduct::where('product_id',$productData->product_id)->where('store_id',$store->id)->where('upload_status','success')->count();

        Log::info('Count Info: ' . $is_uploaded);
        Log::info('Product Info: ' . json_encode($productData));
        $product_id = $sku = $upcCode = NULL;
        if($is_uploaded > 0){
            $product_id = $productData->product_id;
            $proDetail = ManageMarketplaceProduct::where('product_id',$productData->product_id)->where('store_id',$store->id)->where('upload_status','success')->first();
            $sku = $proDetail->sku;
            $upcCode = $proDetail->upc_code;

            $this->generateProductImages($productData, $store);

            $images = GenerateProductImage::where('product_id', $productData->product_id)->get();
            $media = [];
            foreach ($images as $image) {
                $media[] = [
                    'mediaContentType' => 'IMAGE',
                    'originalSource' => $fileSystem->url_base_path . $image->path,
                ];
            }
            $productUpdateData = [
                'product' => [
                    'id' => $product_id
                ],
                'media' => $media
            ];

            Log::info('Product Update Data: ' . json_encode($productUpdateData));

            $shopifyAuthService = new ShopifyAuthService($store->secret_key, $store->service_url);
            $response = $shopifyAuthService->updateProduct($productUpdateData);
            Log::info('Product Update : ' . json_encode($response));

            if (isset($response['data']) && empty($response['data']['userErrors'])) {

                $proDetail->update([
                    'upload_status' => 'success'
                ]);

                return true;
            }else{
                return false;
            }

        }else {

            $sku = StoreTypeListing::generateUniqueAlphabeticSku($productData->product_sku, 6);

            $ManageMarketplaceProduct = ManageMarketplaceProduct::updateOrCreate([
                'product_id' => $productData->product_id,
                'product_code' => $productData->product_sku,
                'store_id' => $store->id,
                'sku' => $sku,
            ], [
                'product_id' => $productData->product_id,
                'product_code' => $productData->product_sku,
                'sku' => $sku,
                'store_id' => $store->id,
                'upload_status' => 'pending',
                'upload_attempts' => 1,
            ]);

            $productOptions = [];

            $descriptionHtml = '';
            if (isset($productData->productKeyFeatures)) {
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

            $this->generateProductImages($productData, $store);

            $images = GenerateProductImage::where('product_id', $productData->product_id)->get();
            $files = [];
            foreach ($images as $image) {
                $files[] = [
                    'contentType' => 'IMAGE',
                    'originalSource' => $fileSystem->url_base_path . $image->path,
                ];
            }

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
                            'barcode' => $upcCode,
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

            $shopifyAuthService = new ShopifyAuthService($store->secret_key, $store->service_url);
            $response = $shopifyAuthService->saveProduct($productNewData);
            Log::info('Product: ' . json_encode($response));

            if (isset($response['data']) && empty($response['data']['userErrors'])) {

                $productId = $response['data']['productSet']['product']['id'];
                $variantId = $response['data']['productSet']['product']['variants']['nodes'][0]['id'];

                Log::info('Variant ID: ' . $variantId);

                $inventoryItemId = $shopifyAuthService->getInventoryItemId($variantId);
                $locationId = $shopifyAuthService->getLocationId();

                if (!$inventoryItemId || !$locationId) {
                    Log::info('Invalid inventory item or location for variant ' . $variantId . '"]');
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

                if (isset($response['data']) && empty($response['data']['userErrors'])) {
                    Log::info('SKU : ' . $sku);
                    $ManageMarketplaceProduct->update([
                        'sku' => $sku,
//                        'upc_code' => isset($upcCode) ? $upcCode : '',
                        'marketplace_product_id' => $productId,
                        'marketplace_variant_id' => $variantId,
                        'upload_status' => 'success'
                    ]);
                }
            }
        }
        return true;
    }

    public function getAllTags($productData, array $tags)
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

    public function generateProductImages($productData, $store){
        //Log::info('Filesystem : ' . json_encode($store->fileSystem));
        ProductImages::where('product_id', $productData->product_id)
            ->chunk(100, function ($productImages) use ($store, $productData) {
                if (!isset($store->fileSystem)) return;
                foreach ($productImages as $productImage) {
                    $Image = $productImage->imageObj;
                    $path = '/store-images/' . $store->id . '/' . $productImage->id . '.jpg';

                    $imageData = [
                        'product_id' => $productData->product_id,
                        'product_image_id' => $productImage->id,
                        'alt_product_image_id' => null,
                        'image_file_system_id' => $Image->id,
                        'path' => $path,
                        'reference_id' => '',
                    ];

                    Log::info("Main Image :: " . json_encode($productImage));

                    if ($productImage->main_image == 1) {
                        $imageData['main_image'] = 1;
                    }
                    $this->uploadImage($store, $Image, $path);

                    GenerateProductImage::updateOrCreate([
                        'product_image_id' => $productImage->id,
                    ], $imageData);
                }
            });
    }

    public function uploadImage($store, $Image, $path)
    {
        $storeFileSystem = $store->fileSystem;
        Log::info("File system :: " . json_encode($storeFileSystem));
        $host = $storeFileSystem->host;
        $port = $storeFileSystem->port ?? 21;
        $loginId = $storeFileSystem->login_id;
        $password = $storeFileSystem->password;
        $rootPath = $storeFileSystem->root_path;
        $localFilePath = storage_path('app/public/' . $Image->location . $Image->name);

        $ftpConn = \ftp_connect($host, $port);
        if (!$ftpConn) {
            Log::info("Could not connect to FTP server: $host");
        }

        $login = \ftp_login($ftpConn, $loginId, $password);
        if (!$login) {
            ftp_close($ftpConn);
            Log::info("Could not login to FTP server with provided credentials.");
        }

        \ftp_pasv($ftpConn, true);

        $remoteFilePath = $rootPath . $path;
        Log::info("Remote Path :: " . $remoteFilePath);

        $this->createFtpDirectory($ftpConn, dirname($remoteFilePath));

        //Upload the file
        if (ftp_put($ftpConn, $remoteFilePath, $localFilePath, FTP_BINARY)) {
            echo "File uploaded successfully to $remoteFilePath";
        } else {
            echo "Error uploading file.";
        }

        // Close FTP connection
        ftp_close($ftpConn);
    }

    public function createFtpDirectory($ftpConn, $dirPath)
    {
        $parts = explode('/', $dirPath);
        $currentDir = '';

        foreach ($parts as $part) {
            if (empty($part)) continue;
            $currentDir .= "/$part";

            if (!@ftp_chdir($ftpConn, $currentDir)) { // Check if directory exists
                ftp_mkdir($ftpConn, $currentDir); // Create directory if it does not exist
            }
        }
    }
}
