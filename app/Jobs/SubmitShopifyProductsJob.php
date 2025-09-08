<?php

namespace App\Jobs;

use App\Constants\ListingStatus;
use App\Constants\ListingSyncStatus;
use App\Models\Catalog;
use App\Models\GenerateProductImage;
use App\Models\ListingItem;
use App\Models\ProductImageAlternative;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\ManageMarketplaceProduct;
use App\Models\ProductImages;
use App\Models\ProductInterface;
use App\Models\ProductTags;
use App\Models\StoreProduct;
use App\Models\StoreTypeListing;
use App\Models\UPCCodes;
use App\Services\Auth\ShopifyAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use phpseclib3\File\ASN1\Element;

class SubmitShopifyProductsJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 30000;

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
        $stores = Store::select('store.*')->with('fileSystem', 'storeType')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->get();

        if (count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                Log::info('Store : ' . $store->name);
                if (env('APP_ENV') == 'local' && trim(strtolower($store->name)) != strtolower('GetAccessory')) {
                    continue;
                }
                if (($store->auto_generate_listings ?? 0) == 0) {
                    Log::info('Auto Generate Listings is disabled for store: ' . $store->name);
                    continue;
                }

                // Handle Created Products
                // get all te children and their children of cea products
                // Get product types with parent code 'cea' and all their descendants recursively
                $ceaProductTypes = [];
                $ceaParentTypes = ProductType::select('id')->where('code', 'cea')->get()->pluck('id')->toArray();

                // Get immediate children
                $ceaChildrenTypes = ProductType::select('id')->whereIn('parent_id', $ceaParentTypes)->get()->pluck('id')->toArray();
                $ceaProductTypes = array_merge($ceaProductTypes, $ceaParentTypes, $ceaChildrenTypes);

                // Get descendants recursively
                $nextLevelParents = $ceaChildrenTypes;
                foreach ($nextLevelParents as $parentId) {
                    $children = ProductType::select('id')->where('parent_id', $parentId)->get()->pluck('id')->toArray();
                    if (!empty($children)) {
                        $ceaProductTypes = array_merge($ceaProductTypes, $children);
                    }
                }

                Log::info('CEA Product Types and descendants: ' . count($ceaProductTypes));

                $AlreadyCreatedIds = ManageMarketplaceProduct::select('product_id')->where('upload_status', 'success')->where('store_id', $store->id)->get();
                $productTypes = ProductType::select('id')->where('code', 'ceabundle')->get()->toArray();
                $products = Catalog::select('*')->with('productType', 'productKeyFeatures', 'productColor')
                    ->whereNotIn('product_type', $productTypes)
                    ->whereIn('product_type', $ceaProductTypes)
                    ->whereNull('parent_id')
                    ->where('product_status', '!=', 'discontinued')
                    //->where('product_sku', 'E74')
                    ->whereNotIn('product_id', $AlreadyCreatedIds)
                    ->orderBy('created_at', 'desc')
                    ->limit(100)
                    ->get();
                foreach ($products as $product) {
                    $this->createProduct($product, $store);
                    if (env('APP_ENV') == 'local') {
                        break;
                    }
                }

                // Handle Updated Products
                $AlreadyCreatedIds = ManageMarketplaceProduct::select('product_id')
                    ->where('upload_status', 'success')->where('store_id', $store->id)
                    ->orderBy('updated_at', 'asc')
                    ->limit(100)
                    ->get();

                $products = Catalog::select('*')->with('productType', 'productKeyFeatures', 'productColor')
                    ->whereNotIn('product_type', $productTypes)
                    ->whereIn('product_type', $ceaProductTypes)
                    ->whereNull('parent_id')
                    ->where('product_status', '!=', 'discontinued')
                    //->where('product_sku', 'E74')
                    ->whereIn('product_id', $AlreadyCreatedIds)
                    ->orderBy('created_at', 'desc')
                    ->limit(100)
                    ->get();

                foreach ($products as $product) {
                    $this->updateProduct($product, $store);
                    if (env('APP_ENV') == 'local') {
                        break;
                    }
                }

                // Handle Discontinued Products
                $discontinuedProducts = Catalog::select('*')->with('productType', 'productKeyFeatures', 'productColor')
                    ->whereNotIn('product_type', $productTypes)
                    ->whereIn('product_type', $ceaProductTypes)
                    ->whereNull('parent_id')
                    ->where('product_status', 'discontinued')
                    ->orderBy('created_at', 'desc')
                    ->limit(100)
                    ->get();
                foreach ($discontinuedProducts as $product) {
                    $this->discontinueProduct($product, $store);
                    if (env('APP_ENV') == 'local') {
                        break;
                    }
                }
            }
        }
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

    public function generateProductImages($productData, $store)
    {
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
//            echo "File uploaded successfully to $remoteFilePath";
        } else {
//            echo "Error uploading file.";
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

    private function createProduct($productData, $store)
    {
        $fileSystem = $store->fileSystem;
        $shopifyAuthService = new ShopifyAuthService($store->secret_key, $store->service_url);
        $product_id = $sku = $upcCode = NULL;

        $listingItem = $this->addListingItem($productData, $store);

        if (!$listingItem) {
            Log::info('Listing Item not created for product SKU: ' . $productData->product_sku);
            return;
        }

        if (isset($listingItem->sku)) {
            $sku = $listingItem->sku;
        } else {
            $sku = StoreTypeListing::generateUniqueAlphabeticSku($productData->product_sku, 6);
        }

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
            'name' => 'Title',
            'values' => [
                ['name' => 'Default Title']
            ]
        ];

        $this->generateProductImages($productData, $store);

        $images = GenerateProductImage::where('product_id', $productData->product_id)->orderBy('main_image', 'desc')->get();
        $files = [];
        foreach ($images as $image) {
            $files[] = [
                'contentType' => 'IMAGE',
                'originalSource' => $fileSystem->url_base_path . $image->path,
            ];
        }

        $productTitle = $this->generateProductTitle($productData, $store);

        $productNewData = [
            'productSet' => array_filter([
                'title' => $productTitle,
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
//                        'barcode' => $upcCode,
                        'optionValues' => [
                            [
                                'optionName' => 'Title',
                                'name' => "Default Title",
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
            $productIdReplaced = str_replace('gid://shopify/Product/', '', $productId);
            $handle = $response['data']['productSet']['product']['handle'];
            $variantId = $response['data']['productSet']['product']['variants']['nodes'][0]['id'];
            $variantIdReplaced = str_replace('gid://shopify/ProductVariant/', '', $variantId);

            Log::info('Variant ID: ' . $variantId);

            $inventoryItemId = $shopifyAuthService->getInventoryItemId($variantId);
            $locationId = $shopifyAuthService->getLocationId();

            if (!$inventoryItemId || !$locationId) {
                Log::info('Invalid inventory item or location for variant ' . $variantId . '"]');
                return false;
            }

            $listingItem->update([
                'store_url' => $handle,
                'store_sku' => $productIdReplaced . '-' . $variantIdReplaced
            ]);

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

    private function updateProduct(mixed $productData, mixed $store)
    {
        $fileSystem = $store->fileSystem;

        $shopifyAuthService = new ShopifyAuthService($store->secret_key, $store->service_url);
        Log::info("Syncing Product SKU :: " . $productData->product_sku);

        $proDetail = ManageMarketplaceProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->where('upload_status', 'success')->first();

        $listingItem = $this->addListingItem($productData, $store);

        if (!isset($listingItem)) {
            Log::info('Listing Item not created for product SKU: ' . $productData->product_sku);
            return;
        }

        $productTitle = $this->generateProductTitle($productData, $store);

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

        $tags = $this->getAllTags($productData, []);
        $finalTagsString = implode(', ', $tags);

        $variantData = [
            'id' => $proDetail->marketplace_variant_id,
            'price' => $productData->product_price,
        ];

        $productUpdateData = [
            'product' => [
                'id' => $proDetail->marketplace_product_id,
                'tags' => $finalTagsString,
                'title' => $productTitle,
                'descriptionHtml' => $descriptionHtml,
            ],
        ];

        $priceUpdatesData = [
            'productId' => $proDetail->marketplace_product_id,
            'variant' => [$variantData],
        ];

//            $this->generateProductImages($productData, $store);

//            $images = GenerateProductImage::where('product_id', $productData->product_id)->get();
        $media = [];
//            foreach ($images as $image) {
//                $media[] = [
//                    'mediaContentType' => 'IMAGE',
//                    'originalSource' => $fileSystem->url_base_path . $image->path,
//                ];
//            }
        $productUpdateData['media'] = $media;

        $response = $shopifyAuthService->updateProduct($productUpdateData);
        $priceResponse = $shopifyAuthService->updatePrice($priceUpdatesData);

        if (isset($response['data']) && empty($response['data']['userErrors'])) {
            $productId = $response['data']['productUpdate']['product']['id'];
            $productIdReplaced = str_replace('gid://shopify/Product/', '', $productId);
            $handle = $response['data']['productUpdate']['product']['handle'];
            $variantId = $response['data']['productUpdate']['product']['variants']['nodes'][0]['id'];
            $variantIdReplaced = str_replace('gid://shopify/ProductVariant/', '', $variantId);

            $listingItem->update([
                'store_url' => $handle,
                'store_sku' => $productIdReplaced . '-' . $variantIdReplaced
            ]);

            $proDetail->update([
                'upload_status' => 'success',
                'updated_at' => now()
            ]);
        } else {
            $proDetail->update([
                'upload_status' => 'failed',
                'updated_at' => now(),
                'upload_attempts' => $proDetail->upload_attempts + 1
            ]);
        }
    }

    private function discontinueProduct(mixed $productData, mixed $store)
    {
        $proDetail = ManageMarketplaceProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->where('upload_status', 'success')->first();

        if (isset($proDetail)) {
            $shopifyAuthService = new ShopifyAuthService($store->secret_key, $store->service_url);
            Log::info("Discontinuing Product SKU :: " . $productData->product_sku);

            $productUpdateData = [
                'product' => [
                    'id' => $proDetail->marketplace_product_id,
                    'status' => 'DRAFT',
                ],
            ];

            $response = $shopifyAuthService->updateProduct($productUpdateData);

            if (isset($response['data']) && empty($response['data']['userErrors'])) {
                $proDetail->update([
                    'updated_at' => now()
                ]);
            }
        }
    }

    public function addListingItem($productData, $store)
    {
        $product_exist = StoreProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->first();
        if (!isset($product_exist)) {
            $sPr = [
                'product_id' => $productData->product_id,
                'title' => $productData->product_title,
                'store_type_category_id' => $productData->product_type,
                'price' => $productData->product_marketplace_price,
                'msrp' => $productData->product_msrp,
                'description' => $productData->product_description,
                'store_id' => $store->id,
                'created_by' => 'system',
                'updated_by' => 'system'
            ];
            $product_exist = StoreProduct::create($sPr);
        }

        $listingItem = ListingItem::where('store_product_id', $product_exist->id)
            ->first();
        if (!isset($listingItem)) {
            $listingItem = ListingItem::updateOrCreate(
                [
                    'store_product_id' => $product_exist->id,
                ],
                [
                    'store_product_id' => $product_exist->id,
                    'title' => $productData->product_title,
                    'description' => $productData->product_description ?? '',
                    'price' => $productData->product_marketplace_price,
                    'quantity' => ($store->default_listing_quantity) ?: 10,
                    'sync_status' => ListingSyncStatus::Synchronized,
                    'status' => ListingStatus::Active,
                ]);

            return $listingItem;
        }
        return $listingItem;
    }

    private function generateProductTitle($productData, $store)
    {
        $keywords = ProductTitleKeyword::select('product_id', 'keyword')
            ->where('product_id', $productData->product_id)
            ->orderBy('priority', 'asc')->limit(5)->get()->toArray();

        Log::info('Keywords: ' . json_encode($keywords));
        // take the first one and reverse the remaining array

        $title = '';
        if (count($keywords) > 0) {
            $firstKeyword = array_shift($keywords);
            $title .= $firstKeyword['keyword'] . ', ';
            $reversedKeywords = array_reverse($keywords);
            foreach ($reversedKeywords as $keyword) {
                $title .= trim($keyword['keyword']) . ' ';
            }
        } else {
            $title = $productData->product_title;
        }
        return trim($title) . ' - ' . $store->short_code  . $productData->product_sku;
    }
}
