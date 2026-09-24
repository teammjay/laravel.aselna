<?php

namespace App\Jobs;

use App\Constants\ListingStatus;
use App\Constants\ListingSyncStatus;
use App\Models\Catalog;
use App\Models\GenerateProductImage;
use App\Models\ListingItem;
use App\Models\ListingItemProductImage;
use App\Models\ProductImageAlternative;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\ManageMarketplaceProduct;
use App\Models\ProductImages;
use App\Models\ProductInterface;
use App\Models\ProductSpecification;
use App\Models\ProductTags;
use App\Models\StoreProduct;
use App\Models\StoreTypeListing;
use App\Models\StoreType;
use App\Models\UPCCodes;
use App\Models\JobStatus;
use App\Jobs\Sync\FonusCombinedSyncJob;
use App\Jobs\Shopify\SyncShopifyMediaPathsJob;
use App\Services\Auth\ShopifyAuthService;
use App\Services\CriticalErrorService;
use App\Utils\ShopifyImageUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use phpseclib3\File\ASN1\Element;
use App\Jobs\Concerns\TrackableJob;

class SubmitShopifyProductsJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000;
    private array $publications;

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
        if (JobStatus::isJobRunning(FonusCombinedSyncJob::class)) {
            Log::info('Skipping SubmitShopifyProductsJob: FonusCombinedSyncJob is currently running.');
            $this->updateTrackingRecord([
                'status' => 'failed', 'output' => 'Skipping SubmitShopifyProductsJob: FonusCombinedSyncJob is currently running.','started_at' => now(), 'finished_at' => now()
            ]);
            return;
        }

        try {
            $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now()]);
            // The worker is long-lived; alternates may have changed since the last run.
            ShopifyImageUtility::flushCache();
            /* shopify stores*/
            $stores = Store::select('store.*')->with('fileSystem', 'storeType')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->where('store.store_status', '<>', 0)->get();

            if (count($stores ?? []) > 0) {
                $tags = [];
                foreach ($stores as $store) {
                    Log::info('Store : ' . $store->name);
                    if (env('APP_ENV') == 'local' && trim(strtolower($store->name)) != strtolower('GetAccessory')) {
                        continue;
                    }
                    if (($store->auto_generate_listings ?? 0) == 0) {
                        Log::info('Auto Generate Listings is disabled for store: ' . $store->name);
                        continue;
                    }

                    $tags[] = $store->name;

                    $this->getPublications($store);

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
                        ->where('product_status', '=', 'active')
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
//                    $AlreadyCreatedIds = ManageMarketplaceProduct::select('product_id')
//                        ->where('upload_status', 'success')->where('store_id', $store->id)
//                        ->orderBy('updated_at', 'asc')
//                        ->get();

                    $products = Catalog::select('*')->with('productType', 'productKeyFeatures', 'productColor')
                        ->join('manage_marketplace_products', function($join) use ($store) {
                            $join->on('products.product_id', '=', 'manage_marketplace_products.product_id')
                                ->where('manage_marketplace_products.upload_status', 'success')
                                ->where('manage_marketplace_products.store_id', $store->id);
                        })
                        ->join('store_product', function($join) use ($store) {
                            $join->on('products.product_id', '=', 'store_product.product_id')
                                ->where('store_product.store_id', $store->id);
                        })
                        ->whereNotIn('products.product_type', $productTypes)
                        ->whereIn('products.product_type', $ceaProductTypes)
                        ->whereNull('products.parent_id')
                        ->where('products.product_status', '=', 'active')
                        ->where(function ($query) {
                            $query->whereColumn(
                                'store_product.last_synch_date_time',
                                '<',
                                'store_product.updated_at'
                            )
                                ->orWhereColumn(
                                    'store_product.last_synch_date_time',
                                    '<',
                                    'products.updated_at'
                                )->orWhereNull('store_product.last_synch_date_time');
                        })
                        ->orderBy('manage_marketplace_products.updated_at', 'asc')
                        ->limit(200)
                        ->get();

                    foreach ($products as $product) {
                        Log::info('Update Products SKU :: ' . $product->product_sku . ' | Product ID :: ' . $product->product_id);
                        $this->updateProduct($product, $store);
                        if (env('APP_ENV') == 'local') {
                            break;
                        }
                    }

                    // Handle Discontinued Products
                    $discontinuedProducts = Catalog::select('*')->with('productType', 'productKeyFeatures', 'productColor')
                        ->join('store_product', function($join) use ($store) {
                            $join->on('products.product_id', '=', 'store_product.product_id')
                                ->where('store_product.store_id', $store->id);
                        })
                        ->whereNotIn('product_type', $productTypes)
                        ->whereIn('product_type', $ceaProductTypes)
                        ->whereNull('parent_id')
                        ->whereIn('product_status', ['discontinued', 'temporaryDisabled', 'draft'])
                        ->where(function ($query) {
                            $query->whereColumn(
                                'store_product.last_synch_date_time',
                                '<',
                                DB::raw('GREATEST(store_product.updated_at, products.updated_at)')
                            )->orWhereNull('store_product.last_synch_date_time');
                        })
                        ->orderBy('products.created_at', 'desc')
                        ->get();

                    foreach ($discontinuedProducts as $product) {
                        $this->discontinueProduct($product, $store);
                        if (env('APP_ENV') == 'local') {
                            break;
                        }
                    }
                }
                $this->updateTrackingRecord(['tags' => implode(' ', $tags) . ' Submit Shopify Products']);
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Submit Shopify Products Sync Job Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
    }

    public function getAllTags($productData, array $tags)
    {
        $removeTags = [];
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
            $removeTags[] = $productData->productType->name;
            $tags[] = strtolower(str_replace(' ', '-', $productData->productType->name));
        }

        if (isset($productData->productType) && !empty($productData->productType->parent) && !empty($productData->productType->parent->id)) {
            $tags[] = $productData->productType->parent->id;
        }

        if (isset($productData->productType) && !empty($productData->productType->parent) && !empty($productData->productType->parent->name)) {
            $removeTags[] = $productData->productType->parent->name;
            $tags[] = strtolower(str_replace(' ', '-', $productData->productType->parent->name));
        }

        $tags[] = 'upsell-enable';
        return [
            'addTags' => $tags,
            'removeTags' => $removeTags
        ];
    }

    public function generateProductImages($productData, $store)
    {
        //Log::info('Filesystem : ' . json_encode($store->fileSystem));
        ProductImages::where('product_id', $productData->product_id)
            ->chunk(100, function ($productImages) use ($store, $productData) {
                if (!isset($store->fileSystem)) return;
                foreach ($productImages as $productImage) {
                    // Stores with product_alt_image_number push an alternate of the image instead (oydera's rule).
                    $resolved = ShopifyImageUtility::resolveImage($productImage, $store);
                    $Image = $resolved['file'];
                    $altImageId = $resolved['alt_product_image_id'];

                    if (!isset($Image)) {
                        Log::warning('Product image ' . $productImage->id . ' has no file, not generated for store ' . $store->name);
                        continue;
                    }

                    // One row per product image and alternate, shared by every store: the file is the same whichever store pushes it.
                    $generatedImage = GenerateProductImage::where('product_image_id', $productImage->id)
                        ->where('alt_product_image_id', $altImageId)
                        ->first();
                    if (isset($generatedImage)
                        && $generatedImage->updated_at >= $productImage->updated_at
                        && (!isset($resolved['alternate']) || $generatedImage->updated_at >= $resolved['alternate']->updated_at)) {
                        continue;
                    }

                    // A different name per alternate, so the original and its alternates never overwrite each other.
                    $path = '/store-images/' . $store->id . '/' . $productImage->id . (isset($altImageId) ? '_' . $altImageId : '') . '.jpg';

                    $imageData = [
                        'product_id' => $productData->product_id,
                        'product_image_id' => $productImage->id,
                        'alt_product_image_id' => $altImageId,
                        'image_file_system_id' => $Image->id,
                        'main_image' => $productImage->main_image == 1 ? 1 : 0,
                        'path' => $path,
                        'reference_id' => '',
                    ];

                    Log::info("Main Image :: " . json_encode($productImage));

                    $this->uploadImage($store, $Image, $path);

                    $generatedImage = GenerateProductImage::updateOrCreate([
                        'product_image_id' => $productImage->id,
                        'alt_product_image_id' => $altImageId,
                    ], $imageData);
                    // updateOrCreate leaves updated_at alone when nothing changed; the file was uploaded again all the same.
                    $generatedImage->touch();
                }
            });
    }

    /**
     * The generated image rows a store pushes for a product: per product image, the original or the
     * alternate the store's product_alt_image_number selects. Main image first.
     */
    private function generatedImagesFor($store, $productId)
    {
        $generatedImages = collect();

        foreach (ProductImages::where('product_id', $productId)->get() as $productImage) {
            $generatedImage = GenerateProductImage::where('product_image_id', $productImage->id)
                ->where('alt_product_image_id', ShopifyImageUtility::resolveAltImageId($productImage, $store))
                ->first();

            if (isset($generatedImage)) {
                $generatedImages->push($generatedImage);
            }
        }

        return $generatedImages->sortByDesc('main_image')->values();
    }

    /**
     * The generated image row a store pushes for one product image.
     */
    private function generatedImageFor($store, $productImageId): ?GenerateProductImage
    {
        if (empty($productImageId)) {
            return null;
        }

        $productImage = ProductImages::find($productImageId);
        if (!isset($productImage)) {
            return null;
        }

        return GenerateProductImage::where('product_image_id', $productImageId)
            ->where('alt_product_image_id', ShopifyImageUtility::resolveAltImageId($productImage, $store))
            ->first();
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

    public function createProduct($productData, $store)
    {
        $fileSystem = $store->fileSystem;
        $shopifyAuthService = new ShopifyAuthService($store->secret_key, $store->service_url);
        $product_id = $sku = $upcCode = NULL;
        $createdHandle = NULL;

        $listingItem = $this->addListingItem($productData, $store);
        $storeProduct = StoreProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->first();

        if (!$listingItem) {
            Log::info('Listing Item not created for product SKU: ' . $productData->product_sku);
            return;
        }

        if (isset($listingItem->sku)) {
            $sku = $listingItem->sku;
            $createdHandle = $listingItem->store_url;
        } else {
            $sku = StoreTypeListing::generateUniqueAlphabeticSku($productData->product_sku, 6);
            $listingItem->update(['sku' => $sku]);
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

        $descriptionHtml = $this->getDescriptionHtml($productData,$store);
//        if (isset($productData->productKeyFeatures)) {
//            $descriptionHtml .= '<ul>';
//            foreach ($productData->productKeyFeatures as $feature) {
//                $safeText = htmlspecialchars($feature->value, ENT_QUOTES | ENT_XML1, 'UTF-8'); // Escapes special characters
//                if (!empty(trim($safeText))) {
//                    $descriptionHtml .= '<li>' . $safeText . '</li>';
//                }
//            }
//
//            $descriptionHtml .= '</ul>';
//        }
//        $descriptionHtml .= '<h2>Product Description</h2>';
//        $descriptionHtml .= '<p>' . $productData->product_description . '</p>';

        $tags = [];
        $tags = $this->getAllTags($productData, $tags);
        Log::info('Tags: ' . json_encode($tags));
        $tagsString = implode(',', $tags['addTags']);

        $productPrice = $productData->product_price;

        if (isset($storeProduct->price) && isset($storeProduct->overwrite_price) && $storeProduct->overwrite_price == 1) {
            $productPrice = $storeProduct->price;
        }

        $productOptions[] = [
            'name' => 'Title',
            'values' => [
                ['name' => 'Default Title']
            ]
        ];

        $this->generateProductImages($productData, $store);

        $images = $this->generatedImagesFor($store, $productData->product_id);
        $files = [];
        foreach ($images as $image) {
            $files[] = [
                'alt' => $image->product_image_id,
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
                'handle' => $createdHandle,
                'productOptions' => $productOptions,
                'files' => $files,
                'variants' => [
                    [
                        'price' => $productPrice,
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
                            'tracked' => false,
                        ]
                    ]
                ],
            ], function ($value) {
                return !is_null($value);
            }),
        ];

        $metafields = $this->getMetafieldsData($productData,$store);
        if (count($metafields) > 0) {
            $productNewData['productSet']['metafields'] = $metafields;
        }

        Log::info('Product Data: ' . json_encode($productNewData));

        $shopifyAuthService = new ShopifyAuthService($store->secret_key, $store->service_url);
        $response = $shopifyAuthService->saveProduct($productNewData);
        Log::info('Product: ' . json_encode($response));

        if (isset($response['data']) && empty($response['data']['userErrors']) && isset($response['data']['productSet']['product'])) {

            $productId = $response['data']['productSet']['product']['id'];
            $productIdReplaced = str_replace('gid://shopify/Product/', '', $productId);
            $handle = $response['data']['productSet']['product']['handle'];
            $variantId = $response['data']['productSet']['product']['variants']['nodes'][0]['id'];
            $variantIdReplaced = str_replace('gid://shopify/ProductVariant/', '', $variantId);

            Log::info('Variant ID: ' . $variantId);

            $inventoryItemId = $shopifyAuthService->getInventoryItemId($variantId);
            $locationId = $shopifyAuthService->getLocationId();

            $this->handleMediaImages($response['data']['productSet']['product'], $listingItem, $store);
//            $this->dispatchMediaPathSync($listingItem, $store, $productId);

            $storeProduct->update([
                'last_synch_date_time' => date('Y-m-d H:i:s')
            ]);

            $listingItem->update([
                'store_url' => $handle,
                'store_sku' => $productIdReplaced . '-' . $variantIdReplaced,
                'sku' => $sku
            ]);

            if (isset($inventoryItemId) && isset($locationId)) {
                // Prepare inventory update array
                $inventoryUpdates['locationId'] = $locationId;
                $inventoryUpdates['changes'] = [
                    'inventoryItemId' => $inventoryItemId,
                    'locationId' => $locationId,
                    'delta' => 20
                ];
                $shopifyAuthService->updateInventory($inventoryUpdates);
            }
            $shopifyAuthService->productPublish($productId, $this->publications);

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

    public function updateProduct(mixed $productData, mixed $store)
    {
        $fileSystem = $store->fileSystem;
        $storeProduct = StoreProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->first();

        $shopifyAuthService = new ShopifyAuthService($store->secret_key, $store->service_url);
        Log::info("Syncing Product SKU :: " . $productData->product_sku);

        $proDetail = ManageMarketplaceProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->where('upload_status', 'success')->first();

        $listingItem = $this->addListingItem($productData, $store);

        if (!isset($listingItem)) {
            Log::info('Listing Item not created for product SKU: ' . $productData->product_sku);
            return;
        }

        $productTitle = $this->generateProductTitle($productData, $store);

        if (isset($storeProduct->title) && isset($storeProduct->overwrite_title) && $storeProduct->overwrite_title == 1) {
            $productTitle = $storeProduct->title;
        }

        $descriptionHtml = $this->getDescriptionHtml($productData,$store);

        $tags = $this->getAllTags($productData, []);
        $finalTagsString = implode(', ', $tags['addTags']);

        $productPrice = $productData->product_price;

        if (isset($storeProduct->price) && isset($storeProduct->overwrite_price) && $storeProduct->overwrite_price == 1) {
            $productPrice = $storeProduct->price;
        }

        $variantData = [
            'id' => $proDetail->marketplace_variant_id,
            'price' => $productPrice,
        ];

        $productUpdateData = [
            'product' => [
                'id' => $proDetail->marketplace_product_id,
                'tags' => $tags['addTags'],
                'status' => 'ACTIVE',
            ],
        ];

        if (isset($storeProduct->overwrite_description) && $storeProduct->overwrite_description == 1) {
            $productUpdateData['product']['descriptionHtml'] = $descriptionHtml;
        }else if (strtolower($store->name) != 'odemobile') {
            $productUpdateData['product']['descriptionHtml'] = $descriptionHtml;
        }

        if (isset($storeProduct->overwrite_title) && $storeProduct->overwrite_title == 1) {
            $productUpdateData['product']['title'] = $productTitle;
        }else if (strtolower($store->name) != 'odemobile') {
            $productUpdateData['product']['title'] = $productTitle;
        }

        $priceUpdatesData = [
            'productId' => $proDetail->marketplace_product_id,
            'variant' => [$variantData],
        ];

        $this->handleOldImages($shopifyAuthService, $proDetail->marketplace_product_id, $listingItem, $store);

        $this->generateProductImages($productData, $store);

        $images = $this->generatedImagesFor($store, $productData->product_id);
        // Add Additional Images.
        $media = [];
        foreach ($images as $key => $image) {
            // check if image exists in shopify already
            $listingItemProductImage = ListingItemProductImage::where('listing_item_id', $listingItem->id)
                ->where('product_image_id', $image->product_image_id)
                ->whereIn('product_image_id', function ($subQuery) use ($productData) {
                    $subQuery->select('id')
                        ->from('product_images')
                        ->where('product_id', $productData->product_id);
                })
                ->first();
            if (isset($listingItemProductImage)) {
                continue;
            }

            $media[] = [
                'alt' => $image->product_image_id,
                'mediaContentType' => 'IMAGE',
                'originalSource' => $fileSystem->url_base_path . $image->path,
            ];
        }

        if (count($media) > 0) {
            $productUpdateData['media'] = $media;
        }

        $metafields = $this->getMetafieldsData($productData,$store);
        if (count($metafields) > 0) {
            $productUpdateData['product']['metafields'] = $metafields;
        }

        Log::info('Product Update Data: ' . json_encode($productUpdateData));

        $response = $shopifyAuthService->updateProduct($productUpdateData);

        $priceResponse = $shopifyAuthService->updatePrice($priceUpdatesData);
        $shopifyAuthService->removeTags($proDetail->marketplace_product_id, $tags['removeTags']);
        $shopifyAuthService->addTags($proDetail->marketplace_product_id, $tags['addTags']);

        $shopifyAuthService->productPublish($proDetail->marketplace_product_id, $this->publications);


        if (isset($response['data']) && empty($response['data']['userErrors']) && isset($response['data']['productUpdate']['product'])) {
            Log::info('Product Update Data: Response : ');
            $productId = $response['data']['productUpdate']['product']['id'];
            $productIdReplaced = str_replace('gid://shopify/Product/', '', $productId);
            $handle = $response['data']['productUpdate']['product']['handle'];
            $variantId = $response['data']['productUpdate']['product']['variants']['nodes'][0]['id'];
            $featuredImageId = $response['data']['productUpdate']['product']['featuredMedia']['id'] ?? null;
            $variantIdReplaced = str_replace('gid://shopify/ProductVariant/', '', $variantId);

            // Deleted Image from Product Image Table. From 659 - 687
            $deletedImages = ListingItemProductImage::where('listing_item_id', $listingItem->id)
                ->where(function ($query) use ($productData, $listingItem) {
                    $query->whereNotIn('product_image_id', function ($subQuery) use ($productData) {
                        $subQuery->select('id')
                            ->from('product_images')
                            ->where('product_id', $productData->product_id);
                    });
                })
                ->get();

            $mediaIdsToDelete = [];
            foreach ($deletedImages as $image) {
                if (!empty($image->reference_id)) {
                    $mediaIdsToDelete[] = 'gid://shopify/MediaImage/' .$image->reference_id;
                }
                $image->delete();
            }
            if (count($mediaIdsToDelete) > 0) {
                $shopifyAuthService->deleteMediaImages($proDetail->marketplace_product_id, $mediaIdsToDelete);
            }

            // Sync the Images Images. From 690
            $this->handleMediaImages($response['data']['productUpdate']['product'], $listingItem, $store);
            // Delete Any Image Whose entry is not there in Listing_product_image table
            $this->deleteOldProductImages($shopifyAuthService, $proDetail->marketplace_product_id, []);
            //Change the Main Image Order
            $firstImage = ListingItemProductImage::where('listing_item_id', $listingItem->id)->where('main_image', 1)->first();
            if (!isset($firstImage)) {
                $productImage = ProductImages::select('id')->where('product_id', $productData->product_id)->where('main_image', 1)->first();
                $firstImage = ListingItemProductImage::where('listing_item_id', $listingItem->id)->where('product_image_id', $productImage->id)->first();
                if ($firstImage) {
                    $firstImage->update([
                        'main_image' => 1
                    ]);
                }
            }
            if (isset($firstImage) && isset($firstImage->reference_id) && !empty($firstImage->reference_id)) {
                if (isset($featuredImageId)) {
                    Log::info('Featured Image Id: ' . $featuredImageId);
                    $featuredImageIdReplaced = str_replace('gid://shopify/MediaImage/', '', $featuredImageId);
                    if ($featuredImageIdReplaced != $firstImage->reference_id) {
                        Log::info('Updating Featured Image to Id: ' . $firstImage->reference_id);
                        $shopifyAuthService->setFeaturedImage($proDetail->marketplace_product_id, $firstImage->reference_id);
                    }
                } else {
                    $shopifyAuthService->setFeaturedImage($proDetail->marketplace_product_id, $firstImage->reference_id);
                }
            }

            // handle The updated Image: re-push images updated since they were pushed, with the file the
            // store selects (its product_alt_image_number alternate or the original).
            $this->updateChangedImages($shopifyAuthService, $proDetail->marketplace_product_id, $productData, $listingItem, $store);

//            $this->dispatchMediaPathSync($listingItem, $store, $proDetail->marketplace_product_id);


            $storeProduct->update([
                'last_synch_date_time' => date('Y-m-d H:i:s')
            ]);

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
                'updated_at' => now(),
                'upload_attempts' => $proDetail->upload_attempts + 1
            ]);
        }
    }

    private function discontinueProduct(mixed $productData, mixed $store)
    {
        $proDetail = ManageMarketplaceProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->where('upload_status', 'success')->first();
        $storeProduct = StoreProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->first();

        $listingItem = $this->addListingItem($productData, $store);

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
                if ($storeProduct) {
                    $storeProduct->update([
                        'last_synch_date_time' => date('Y-m-d H:i:s')
                    ]);
                }
                if ($listingItem) {
                    $listingItem->update([
                        'status' => ListingStatus::Suspended
                    ]);
                }
            }
        }
    }

    public function addListingItem($productData, $store)
    {
        $product_exist = StoreProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->first();
        if (!isset($product_exist)) {
            $sPr = [
                'product_id' => $productData->product_id,
                'store_type_category_id' => $productData->product_type,
                'price' => $productData->product_marketplace_price,
                'msrp' => $productData->product_msrp,
                'active' => 1,
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

    public static function generateProductTitle($productData, $store, $is_require_for_metatitle = 0)
    {
        $titleTemplate = $store->shopify_title_template;
        if($titleTemplate != ''){

            if (str_contains($titleTemplate, "#device_name") && $is_require_for_metatitle == 0) {
                $titleTemplate = str_replace('#device_name','',$titleTemplate);
            }

            Log::info('Shopify Store Product Title Template ::'.$titleTemplate);

            $keywords = ProductTitleKeyword::select('product_id', 'keyword')
                ->where('product_id', $productData->product_id)
                ->orderBy('priority', 'asc')->get()->toArray();

            $replacements = [];

            for ($i = 1; $i <= 10; $i++) {
                $replacements["#title_keyword{$i}"] = $keywords[$i - 1]['keyword'] ?? '';
            }

            $replacements['#store_short_code'] = $store->short_code;
            $replacements['#product_code'] = $productData->product_sku;
            $replacements['#product_title'] = $productData->product_title;

            $title = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $titleTemplate
            );

            $title = preg_replace('/\s+/', ' ', trim($title));

            Log::info('Final Title Template ::'.$title);

            return trim($title);
        }else {
            $limit = $store->random_title_keyword_level;
            $reverse = true;
            if (!isset($limit)) {
                $limit = 6;
                $reverse = false;
            } else {
                $limit++;
            }

            $keywords = ProductTitleKeyword::select('product_id', 'keyword')
                ->where('product_id', $productData->product_id)
                ->orderBy('priority', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    $item->keyword = trim(preg_replace('/\s+/', ' ', str_replace(',', ' ', $item->keyword ?? '')));
                    return $item;
                })
                ->toArray();

            Log::info('Keywords: ' . json_encode($keywords));
            // take the first one and reverse the remaining array

            $title = '';
            if (count($keywords) > 0) {
                $firstKeyword = array_shift($keywords);
                $title .= $firstKeyword['keyword'];
                if ($reverse) {
                    $reversedKeywords = array_reverse($keywords);
                    $title .= ', ';
                } else {
                    $title .= ' ';
                    $reversedKeywords = $keywords;
                }
                foreach ($reversedKeywords as $keyword) {
                    $title .= trim($keyword['keyword']) . ' ';
                }
            } else {
                $title = $productData->product_title;
            }
            return trim($title) . ' - ' . $store->short_code . $productData->product_sku;
        }
    }

    /**
     * Replace the Shopify media of every listing image whose image changed after it was pushed.
     *
     * Same test as oydera's ImageLoader: the product image, or the alternate the store selects,
     * was updated after the listing image. The file sent is the one the store selects now, so an
     * update never puts the original back over the alternate. Changing product_alt_image_number
     * alone does not re-push anything; it applies to images pushed from then on.
     */
    private function updateChangedImages(ShopifyAuthService $shopifyAuthService, $marketplaceProductId, $productData, $listingItem, $store): void
    {
        $fileSystem = $store->fileSystem;
        if (!isset($fileSystem)) {
            return;
        }

        $productImages = ProductImages::where('product_id', $productData->product_id)->get()->keyBy('id');
        $generatedImages = $this->generatedImagesFor($store, $productData->product_id)->keyBy('product_image_id');

        $listingImages = ListingItemProductImage::where('listing_item_id', $listingItem->id)
            ->whereIn('product_image_id', $productImages->keys()->all())
            ->whereNotNull('reference_id')
            ->where('reference_id', '<>', '')
            ->get();

        foreach ($listingImages as $image) {
            $productImage = $productImages->get($image->product_image_id);
            $generatedImage = $generatedImages->get($image->product_image_id);
            if (!isset($productImage) || !isset($generatedImage)) {
                continue;
            }

            $alternate = ShopifyImageUtility::resolveImage($productImage, $store)['alternate'];
            $imageChanged = $image->updated_at < $productImage->updated_at
                || (isset($alternate) && $image->updated_at < $alternate->updated_at);

            if (!$imageChanged) {
                continue;
            }

            Log::info('Replacing Shopify media ' . $image->reference_id . ' of image ' . $image->product_image_id
                . ' (alternate ' . ($image->alt_product_image_id ?? 'none') . ' -> ' . ($generatedImage->alt_product_image_id ?? 'none') . ')');

            $response = $shopifyAuthService->updateMediaImage($marketplaceProductId, $image->reference_id, [
                'alt' => $image->product_image_id,
                'mediaContentType' => 'IMAGE',
                'originalSource' => $fileSystem->url_base_path . $generatedImage->path,
            ]);

            $errors = $response['data']['productUpdateMedia']['mediaUserErrors'] ?? null;
            if (!isset($response['data']['productUpdateMedia']) || !empty($errors)) {
                // Left as it is, so the next run tries again.
                Log::warning('Shopify media ' . $image->reference_id . ' not replaced: ' . json_encode($errors ?? $response));
                continue;
            }

            // The replaced media gets a new URL once Shopify finishes processing it.
            $image->update([
                'alt_product_image_id' => $generatedImage->alt_product_image_id,
                'image_file_system_id' => $generatedImage->image_file_system_id,
                'path' => null,
                'updated_at' => now()
            ]);
        }
    }

    private function handleMediaImages($product, $listingItem, $store, $AlreadyImages = null)
    {
        if (!isset($product['media']['nodes'])) return;
        $media = $product['media']['nodes'];
        Log::info('No of Media Items: ' . json_encode($media));
        foreach ($media as $mediaItem) {
            if ($mediaItem['mediaContentType'] == 'IMAGE') {
                if (!isset($mediaItem['id'])) continue;

                $ImageId = $mediaItem['id'];
                $imageIdReplaced = str_replace('gid://shopify/MediaImage/', '', $ImageId);
                Log::info('Media Image: ' . json_encode($mediaItem));
                $imageUrl = $mediaItem['preview']['image']['url'] ?? null;

                if(isset($AlreadyImages) && array_key_exists($imageUrl,$AlreadyImages)){
                    // skip
                } else {
                    $altText = $mediaItem['alt'] ?? '';
                    Log::info('Image URL: ' . $imageUrl);
                    $generatedImage = $this->generatedImageFor($store, $altText);
                    if (isset($generatedImage) && !ListingItemProductImage::where('listing_item_id', $listingItem->id)->where('product_image_id', $generatedImage->product_image_id)->whereNotNull('path')->exists()) {
                        ListingItemProductImage::updateOrCreate([
                            'listing_item_id' => $listingItem->id,
                            'product_image_id' => $generatedImage->product_image_id,
                            'reference_id' => $imageIdReplaced,
                        ], [
                            'listing_item_id' => $listingItem->id,
                            'product_image_id' => $generatedImage->product_image_id,
                            'main_image' => $generatedImage->main_image,
                            'alt_product_image_id' => $generatedImage->alt_product_image_id,
                            'image_file_system_id' => $generatedImage->image_file_system_id,
                            'reference_id' => $imageIdReplaced,
                            'path' => $imageUrl,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Shopify processes media asynchronously, so images pushed in this run usually have no URL yet.
     * Queue a delayed job to fill in the paths once Shopify is done.
     */
    private function dispatchMediaPathSync($listingItem, $store, $marketplaceProductId): void
    {
        $hasMissingPaths = ListingItemProductImage::where('listing_item_id', $listingItem->id)
            ->whereNull('path')
            ->whereNotNull('reference_id')
            ->exists();

        if ($hasMissingPaths) {
            SyncShopifyMediaPathsJob::dispatch($listingItem->id, $store->id, $marketplaceProductId)->delay(now()->addSeconds(30));
        }
    }

    private function deleteOldProductImages(ShopifyAuthService $shopifyAuthService, $marketplace_product_id,$AlreadyImages)
    {
        $oldImages = $shopifyAuthService->getProductImages($marketplace_product_id);
        Log::info('Old Images: ' . json_encode($oldImages));
        $Images = $oldImages['data']['product']['media']['nodes'] ?? [];
        $mediaIds = [];
        foreach ($Images as $image) {
            $imageUrl = $image['preview']['image']['url'] ?? '';
            Log::info('Image :: ' . json_encode($image));
            if (isset($AlreadyImages) && array_key_exists($imageUrl, $AlreadyImages)) {
                continue;
            }
            $mediaId = $image['id'];
            $imageIdReplaced = str_replace('gid://shopify/MediaImage/', '', $mediaId);
            if (ListingItemProductImage::where('path', $imageUrl)->exists()) {
                continue;
            }
            if (!ListingItemProductImage::where('reference_id',$imageIdReplaced)->exists()) {
                $mediaIds[] = $mediaId;
            }
        }
        if (count($mediaIds) == 0) {
            return;
        }
        $shopifyAuthService->deleteProductImage($mediaIds, $marketplace_product_id);
    }

    private function handleOldImages(ShopifyAuthService $shopifyAuthService, $marketplace_product_id, $listingItem, $store)
    {
        ListingItemProductImage::where('listing_item_id', $listingItem->id)->whereNull('product_image_id')->delete();

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
        $oldImages = $shopifyAuthService->getProductImages($marketplace_product_id);
        if (!isset($oldImages['data']['product'])) return;
        $this->handleMediaImages($oldImages['data']['product'], $listingItem, $store, $AlreadyImages);

        //$this->deleteOldProductImages($shopifyAuthService, $marketplace_product_id,$AlreadyImages);
    }

    private function getMetafieldsData(mixed $productData, $store)
    {
        $product_type = ProductType::where('id', $productData->product_type)->first();
        $parent_product_type = ProductType::where('id', $product_type->parent_id)->first();
        $productInterface = ProductInterface::where('product_id', $productData->product_id)->first();

        $metafields = [];
        $metafields[] = [
            'namespace' => 'global',
            'key' => 'category_name',
            'value' => $product_type->name,
            'type' => 'single_line_text_field'
        ];

        //change the name from Wired Earphones to wired-earphones
        $metafields[] = [
            'namespace' => 'global',
            'key' => 'category',
            'value' => strtolower(str_replace(' ', '-', $product_type->name)),
            'type' => 'single_line_text_field'
        ];

        if (isset($parent_product_type)) {
            $metafields[] = [
                'namespace' => 'global',
                'key' => 'sub_category_name',
                'value' => $parent_product_type->name,
                'type' => 'single_line_text_field'
            ];

            //change the name from Wired Earphones to wired-earphones
            $metafields[] = [
                'namespace' => 'global',
                'key' => 'sub_category',
                'value' => strtolower(str_replace(' ', '-', $parent_product_type->name)),
                'type' => 'single_line_text_field'
            ];
        }

        if (isset($productInterface)) {
            $metafields[] = [
                'namespace' => 'global',
                'key' => 'interface',
                'value' => $productInterface->interface_id,
                'type' => 'single_line_text_field'
            ];
        }

        $walmartStoreType = StoreType::where('code', 'walmart')->first();
        $storeTypeIds = [$store->store_type_id];
        if ($walmartStoreType) {
            $storeTypeIds[] = $walmartStoreType->id;
        }

        $productSpecifications = ProductSpecification::where('product_id', $productData->product_id)
            ->whereIn('store_type_id', $storeTypeIds)
            ->get();

        if ($productSpecifications->count() > 0) {
            $specs = [];
            // First pass for Walmart specs (lower precedence)
            if ($walmartStoreType) {
                foreach ($productSpecifications as $specification) {
                    if ($specification->store_type_id == $walmartStoreType->id) {
                        $specs[$specification->name] = $specification->calculated_value();
                    }
                }
            }
            // Second pass for current store specs (higher precedence)
            foreach ($productSpecifications as $specification) {
                if ($specification->store_type_id == $store->store_type_id) {
                    $specs[$specification->name] = $specification->calculated_value();
                }
            }

            $metafields[] = [
                'namespace' => 'global',
                'key' => 'product_specification',
                'value' => json_encode($specs),
                'type' => 'json'
            ];
        }


        if(isset($store->shopify_title_template) && $store->shopify_title_template != '') {
            Log::info('Shopify Store Title Template ::'.$store->shopify_title_template);
            if (str_contains($store->shopify_title_template, "#device_name")) {
                $productTitle = $this->generateProductTitle($productData, $store, 1);
                $metafields[] = [
                    'namespace' => 'device_name',
                    'key' => 'device_title',
                    'value' => $productTitle,
                    'type' => 'single_line_text_field'
                ];
            }
        }

        Log::info('Metafields ::'.json_encode($metafields));
        return $metafields;
    }

    public static function getDescriptionHtml( $productData,$store)
    {
        $descriptionHtml = '<div class="product-key-features">';
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
        $descriptionHtml .= '</div>';

        if (isset($productData->product_description)) {
            $descriptionHtml .= '<div class="product-desc">';
            $descriptionHtml .= '<h2>Product Description</h2>';
            $descriptionHtml .= '<p>' . $productData->product_description . '</p>';
            $descriptionHtml .= '</div>';
        }

        if (strtolower($store->name) == 'odemobile') {

        }

        return $descriptionHtml;
    }

    public function getPublications(mixed $store): void
    {
        $shopifyAuthService = new ShopifyAuthService($store->secret_key, $store->service_url);
        $publications = $shopifyAuthService->getPublications();

        Log::info('Publications: ' . json_encode($publications));
        $publicationIds = [];
        foreach ($publications['data']['publications']['edges'] as $publication) {
            $publicationIds[]['publicationId'] = $publication['node']['id'];
        }
        $this->publications = $publicationIds;
    }
}
