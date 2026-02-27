<?php

namespace App\Jobs\Magento;

use App\Constants\ListingStatus;
use App\Constants\MarketPlaceUploadStatus;
use App\Helpers\Common;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\ListingItem;
use App\Models\ManageMarketplaceProduct;
use App\Models\MarketPlaceCategoryMappings;
use App\Models\ProductImages;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\StoreTypeListing;
use App\Models\ListingItemProductImage;
use App\Services\CriticalErrorService;
use App\Services\Marketplace\Service\MagentoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MagentoProductsSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000;

    private $limit = 5;

    public MagentoService $magentoService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    public function handle(): void
    {
        try {
            if (env('APP_ENV') === 'local') {
                $this->limit = 10;
            }

            /* get active store jobs*/
            $store_ids = Common::getActiveStoreJobs('MagentoProductsSyncJob', 'magento');

            $query = Store::select('store.*')->with('fileSystem', 'storeType')
                ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
                ->where('store_type.code', 'magento')
                ->where('store.store_status', '<>', 0);

            if (!empty($store_ids)) {
                $query->whereIn('store.id', $store_ids);
            }
            $stores = $query->get();

            $this->magentoService = new MagentoService();

            Log::info('Magento Simple Products Sync Job Started');

            if (count($stores ?? []) > 0) {
                $tags = [];
                foreach ($stores as $store) {
                    Log::info('Store : ' . $store->name);
                    $tags[] = $store->name;

                    if (($store->auto_generate_listings ?? 0) == 0) {
                        Log::info('Auto Generate Listings is disabled for store: ' . $store->name);
                        continue;
                    }

                    $productTypes = ProductType::select('id')->where('code', 'ceabundle')->get()->toArray();

                    // Handle Created Products
                    $AlreadyCreatedIds = ManageMarketplaceProduct::select('product_id')
                        ->where('upload_status', 'success')
                        ->where('store_id', $store->id)
                        ->get();

                    $newProducts = Catalog::select('products.*')
                        ->with('productType', 'productKeyFeatures', 'productColor')
                        ->whereNotIn('products.product_type', $productTypes)
                        ->where('products.product_status', '=', 'active')
                        ->whereNull('products.parent_id')
                        ->whereNotIn('products.product_id', $AlreadyCreatedIds)
                        ->orderBy('products.created_at', 'desc')
                        ->limit($this->limit)
                        ->get();

                    foreach ($newProducts as $product) {
                        $this->createProduct($product, $store);
                    }

                    // Handle Updated Products
                    $updatedProducts = Catalog::select('products.*')
                        ->with('productType', 'productKeyFeatures', 'productColor')
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
                        ->whereNull('products.parent_id')
                        ->where('products.product_status', '=', 'active')
                        ->where(function ($query) {
                            $query->whereColumn('store_product.last_synch_date_time', '<', 'store_product.updated_at')
                                ->orWhereColumn('store_product.last_synch_date_time', '<', 'products.updated_at');
                        })
                        ->orderBy('manage_marketplace_products.updated_at', 'asc')
                        ->limit($this->limit)
                        ->get();

                    foreach ($updatedProducts as $product) {
                        $this->updateProduct($product, $store);
                    }

                    // Handle Discontinued Products
                    $discontinuedProducts = Catalog::select('products.*')
                        ->join('store_product', function($join) use ($store) {
                            $join->on('products.product_id', '=', 'store_product.product_id')
                                ->where('store_product.store_id', $store->id);
                        })
                        ->join('manage_marketplace_products', function($join) use ($store) {
                            $join->on('products.product_id', '=', 'manage_marketplace_products.product_id')
                                ->where('manage_marketplace_products.upload_status', 'success')
                                ->where('manage_marketplace_products.store_id', $store->id);
                        })
                        ->whereNotIn('products.product_type', $productTypes)
                        ->whereNull('products.parent_id')
                        ->whereIn('products.product_status', ['discontinued', 'temporaryDisabled', 'draft'])
                        ->whereColumn('store_product.last_synch_date_time', '<', 'products.updated_at')
                        ->orderBy('products.updated_at', 'desc')
                        ->get();

                    foreach ($discontinuedProducts as $product) {
                        $this->discontinueProduct($product, $store);
                    }
                }
                $this->updateTrackingRecord(['tags' => implode(' ', $tags) . ' Magento Products']);
            }
        }catch (\Throwable $e) {
            CriticalErrorService::report('Magento Products Sync Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createProduct(mixed $productData, mixed $store)
    {
        Log::info('Creating Magento Product for Product ID: ' . $productData->product_id . ' in Store ID: ' . $store->id);

        $listingItem = $this->addListingItem($productData, $store);
        if (!$listingItem) return;

        if (isset($listingItem->sku) && !empty($listingItem->sku)) {
            $sku = $listingItem->sku;
        } else {
            $sku = StoreTypeListing::generateUniqueAlphabeticSku($productData->product_sku, 6);
            $listingItem->update(['sku' => $sku]);
        }

        $manageProduct = ManageMarketplaceProduct::updateOrCreate([
            'product_id' => $productData->product_id,
            'store_id' => $store->id,
        ], [
            'product_code' => $productData->product_sku,
            'sku' => $sku,
            'upload_status' => MarketPlaceUploadStatus::PENDING,
            'upload_attempts' => 1
        ]);

        $finalProductData = $this->prepareProductData($productData, $store, $sku, 1); // 1 = Enabled

        $response = $this->magentoService->createOrUpdateProduct($finalProductData, $store);
        Log::info('Response from Magento: ' . json_encode($response));

        if (isset($response['id'])) {
            $manageProduct->update([
                'marketplace_product_id' => $response['id'],
                'marketplace_variant_id' => $response['id'],
                'upload_status' => MarketPlaceUploadStatus::SUCCESS,
            ]);

            $urlKey = $this->getCustomAttribute($response, 'url_key');

            $listingItem->update([
                'store_url' => $urlKey ?: ($response['sku'] ?? $sku),
                'store_sku' => $response['id'],
                'sku' => $sku
            ]);

            $this->handleMediaImages($response, $listingItem, $productData);

            $storeProduct = StoreProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->first();
            if ($storeProduct) {
                $storeProduct->update(['last_synch_date_time' => now()]);
            }
        }
    }

    private function updateProduct(mixed $productData, mixed $store)
    {
        Log::info('Updating Magento Product for Product ID: ' . $productData->product_id . ' in Store ID: ' . $store->id);

        $manageProduct = ManageMarketplaceProduct::where('product_id', $productData->product_id)
            ->where('store_id', $store->id)
            ->where('upload_status', 'success')
            ->first();

        if (!$manageProduct) return;

        $listingItem = $this->addListingItem($productData, $store);

        $finalProductData = $this->prepareProductData($productData, $store, $manageProduct->sku, 1);

        $response = $this->magentoService->createOrUpdateProduct($finalProductData, $store);

        if (isset($response['id'])) {
            $manageProduct->update(['updated_at' => now()]);

            $urlKey = $this->getCustomAttribute($response, 'url_key');
            $listingItem->update([
                'store_url' => $urlKey ?: ($response['sku'] ?? $manageProduct->sku),
                'store_sku' => $response['id']
            ]);

            $this->handleMediaImages($response, $listingItem, $productData);

            $storeProduct = StoreProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->first();
            if ($storeProduct) {
                $storeProduct->update(['last_synch_date_time' => now()]);
            }
        }
    }

    private function discontinueProduct(mixed $productData, mixed $store)
    {
        Log::info('Discontinuing Magento Product for Product ID: ' . $productData->product_id . ' in Store ID: ' . $store->id);

        $manageProduct = ManageMarketplaceProduct::where('product_id', $productData->product_id)
            ->where('store_id', $store->id)
            ->where('upload_status', 'success')
            ->first();

        if (!$manageProduct) return;

        $finalProductData = [
            'sku' => $manageProduct->sku,
            'status' => 2, // 2 = Disabled in Magento
        ];

        $response = $this->magentoService->createOrUpdateProduct($finalProductData, $store);

        if (isset($response['id'])) {
            $manageProduct->update(['updated_at' => now()]);
            $storeProduct = StoreProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->first();
            if ($storeProduct) {
                $storeProduct->update(['last_synch_date_time' => now()]);
            }
            $listingItem = ListingItem::where('store_product_id', $storeProduct->id)->first();
            if ($listingItem) {
                $listingItem->update(['status' => ListingStatus::Suspended]);
            }
        }
    }

    private function prepareProductData($productData, $store, $sku, $status)
    {
        $bulletPoints = $productData->productKeyFeatures;
        $bulletPointHtml = '<ul>';
        foreach ($bulletPoints as $bulletPoint) {
            $bulletPointHtml .= '<li>' . htmlspecialchars($bulletPoint->value, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</li>';
        }
        $bulletPointHtml .= '</ul>';

        $productTitle = $this->generateProductTitle($productData, $store);
        $mediaGalleryEntries = $this->getMediaGalleryEntries($productData, $store);
        $categoryIds = $this->getCategoryIds($store, $productData);

        return [
            "attribute_set_id" => 4,
            "status" => $status,
            "visibility" => 4, // Catalog, Search
            "type_id" => 'simple',
            'name' => $productTitle,
            'sku' => $sku,
            "price" => $productData->product_price,
            "extension_attributes" => [
                "stock_item" => [
                    "qty" => 100,
                    "is_in_stock" => true
                ]
            ],
            "custom_attributes" => [
                ["attribute_code" => "weight", "value" => $productData->product_weight ?? 0],
                ["attribute_code" => "short_description", "value" => $productData->product_description],
                ["attribute_code" => "description", "value" => $productData->product_description],
                ["attribute_code" => "bullet_points", "value" => $bulletPointHtml],
                ["attribute_code" => "category_ids", "value" => implode(',', $categoryIds)]
            ],
            "media_gallery_entries" => $mediaGalleryEntries
        ];
    }

    private function generateProductTitle($productData, $store)
    {
        $keywords = ProductTitleKeyword::select('product_id', 'keyword')
            ->where('product_id', $productData->product_id)
            ->orderBy('priority', 'asc')->get()->toArray();

        $productTitle = $keywords[0]['keyword'] ?? $productData->product_title;
        $productKeywords = implode(' ', array_column(array_slice($keywords, 1), 'keyword'));

        return trim($productTitle . ' ' . $productKeywords) . ' - ' . $store->short_code . $productData->product_sku;
    }

    private function addListingItem($productData, $store)
    {
        $storeProduct = StoreProduct::where('product_id', $productData->product_id)->where('store_id', $store->id)->first();
        if (!$storeProduct) {
            $storeProduct = StoreProduct::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'product_id' => $productData->product_id,
                'store_id' => $store->id,
                'store_type_category_id' => $productData->product_type,
                'active' => 1,
                'price' => $productData->product_marketplace_price,
                'description' => $productData->product_description,
                'created_by' => 'system',
                'updated_by' => 'system'
            ]);
        }

        return ListingItem::updateOrCreate(
            ['store_product_id' => $storeProduct->id],
            [
                'description' => $productData->product_description ?? '',
                'price' => $productData->product_marketplace_price,
                'quantity' => $store->default_listing_quantity ?: 10,
                'sync_status' => \App\Constants\ListingSyncStatus::Synchronized,
                'status' => ListingStatus::Active,
            ]
        );
    }

    private function handleMediaImages($magentoProduct, $listingItem, $productData)
    {
        if (!isset($magentoProduct['media_gallery_entries'])) return;

        foreach ($magentoProduct['media_gallery_entries'] as $mediaEntry) {
            if ($mediaEntry['media_type'] == 'image') {
                $imageLabel = $mediaEntry['label'] ?? '';

                // Try to find the local product image by label/name
                $productImage = ProductImages::where('product_id', $productData->product_id)
                    ->whereHas('imageObj', function($q) use ($imageLabel) {
                        $q->where('name', $imageLabel);
                    })->first();

                if ($productImage) {
                    ListingItemProductImage::updateOrCreate([
                        'listing_item_id' => $listingItem->id,
                        'product_image_id' => $productImage->id,
                    ], [
                        'main_image' => in_array('image', $mediaEntry['types'] ?? []),
                        'reference_id' => $mediaEntry['id'],
                        'path' => $mediaEntry['file'] ?? '',
                    ]);
                }
            }
        }
    }

    private function getMediaGalleryEntries($productData, $store)
    {
        if (env('APP_ENV') === 'local') return [];

        $mediaGalleryEntries = [];
        $productImages = ProductImages::where('product_id', $productData->product_id)->get();

        foreach ($productImages as $productImage) {
            $imageObj = $productImage->imageObj;
            if (!$imageObj) continue;

            $localFilePath = storage_path('app/public/' . $imageObj->location . $imageObj->name);
            if (!file_exists($localFilePath)) continue;

            $base64Image = base64_encode(file_get_contents($localFilePath));

            $imageEntry = [
                "media_type" => "image",
                "label" => $imageObj->name,
                "position" => $productImage->main_image ? 0 : 1,
                "disabled" => false,
                "content" => [
                    "base64_encoded_data" => $base64Image,
                    "type" => 'image/jpeg',
                    "name" => $imageObj->name
                ]
            ];

            if ($productImage->main_image) {
                $imageEntry['types'] = ['image', 'small_image', 'thumbnail'];
            }

            $mediaGalleryEntries[] = $imageEntry;
        }
        return $mediaGalleryEntries;
    }

    private function getCustomAttribute($product, $attributeCode)
    {
        if (isset($product['custom_attributes'])) {
            foreach ($product['custom_attributes'] as $attr) {
                if ($attr['attribute_code'] == $attributeCode) {
                    return $attr['value'];
                }
            }
        }
        return null;
    }

    public function getCategoryIds(mixed $store, mixed $productData)
    {
        $categoryIds = MarketPlaceCategoryMappings::where('store_id', $store->id)
            ->where('name', $productData->productType->name)
            ->pluck('marketplace_category_id')->toArray();

        $parentCategoryIds = MarketPlaceCategoryMappings::where('store_id', $store->id)
            ->where('name', $productData->productType->name)
            ->pluck('marketplace_parent_id')->toArray();

        foreach ($parentCategoryIds as $parentCategoryId) {
            if ($parentCategoryId && !in_array($parentCategoryId, $categoryIds)) {
                $categoryIds[] = $parentCategoryId;
            }
        }

        return $categoryIds;
    }
}
