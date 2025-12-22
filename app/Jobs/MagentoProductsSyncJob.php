<?php

namespace App\Jobs;

use App\Constants\MarketPlaceUploadStatus;
use App\Helpers\Common;
use App\Models\Catalog;
use App\Models\Device;
use App\Models\GenerateProductImage;
use App\Models\ManageMarketplaceProduct;
use App\Models\MarketplaceAttributeMapping;
use App\Models\MarketPlaceCategoryMappings;
use App\Models\ProductImages;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\StoreTypeListing;
use App\Services\Auth\MagentoAuthService;
use App\Services\CriticalErrorService;
use App\Services\Marketplace\Service\MagentoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class MagentoProductsSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000;

    private $limit = 100;

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

            $this->updateTrackingRecord(['tags' => 'Magento Products']);

            /* get active store jobs*/
            $store_ids = Common::getActiveStoreJobs('MagentoProductsSyncJob', 'magento');
            /* get active store jobs*/

            $query = Store::select('store.*')->with('fileSystem', 'storeType')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'magento')->where('store.store_status', '<>', 0);
            if (!empty($store_ids)) {
                $query->whereIn('store.id', $store_ids);
            }
            $stores = $query->get();

            $this->magentoService = new MagentoService();
            //$stores = Store::select('store.*')->with('fileSystem', 'storeType')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'magento')->where('store.store_status','<>',0)->whereIn('store.id',$store_ids)->get();

            Log::info('Magento Simple Products Sync Job Started');
            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {
                    Log::info('Store : ' . $store->name);

                    $AlreadyCreatedIds = ManageMarketplaceProduct::select('product_id')->where('upload_status', 'success')->where('store_id', $store->id)->get();
                    $productTypes = ProductType::select('id')->where('code', 'ceabundle')->get()->toArray();

                    $products = Catalog::select('*')->with('productType', 'productKeyFeatures', 'productColor')
                        ->whereNotIn('product_type', $productTypes)
                        ->whereNotIn('product_id', $AlreadyCreatedIds)
                        ->where('product_status', '!=', 'discontinued')
                        ->orderBy('created_at', 'desc')
                        ->whereNull('parent_id')
                        ->limit($this->limit)
                        ->get();

                    foreach ($products as $product) {
                        $this->submitFeed($product, $store);
                    }
                }
            }
        }catch (\Throwable $e) {

            CriticalErrorService::report('Magento Products Sync Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }

    }

    private function submitFeed(mixed $productData, mixed $store)
    {
        if ($this->isConfigurableProductCreated($productData->product_id, $store->id)) {
            Log::info('Creating Configurable Product for Product ID: ' . $productData->product_id . ' in Store ID: ' . $store->id);
            $this->createConfigurableProduct($productData, $store);
        } else {
            Log::info('Creating simple Products for Product ID: ' . $productData->product_id . ' in Store ID: ' . $store->id);
            $devices = Device::select('device.*')
                ->leftJoin('device_interface', 'device_interface.device_id', '=', 'device.id')
                ->leftJoin('product_interface', 'product_interface.interface_id', '=', 'device_interface.interface_id')
                ->where('product_interface.product_id', $productData->product_id)
                ->whereNotIn('device.id', function ($query) use ($productData, $store) {
                    $query->select('device_id')
                        ->from('manage_marketplace_products')
                        ->whereNotNull('parent_id')
                        ->where('product_id', $productData->product_id)
                        ->where('store_id', $store->id);
                })
                ->where('device.status', 'active')
                ->whereIn('device.order', [10, 2])
                ->limit(20)
                ->get();

            if (count($devices) === 0) {
                $configProduct = ManageMarketplaceProduct::where('product_id', $productData->product_id)
                    ->whereNull('parent_id')
                    ->where('store_id', $store->id)
                    ->first();

                if ($configProduct) {
                    $configProduct->update([
                        'upload_status' => MarketPlaceUploadStatus::SUCCESS,
                        'upload_attempts' => 1
                    ]);
                }
            } else {
                foreach ($devices as $device) {
                    $this->createSimpleProduct($productData, $store, $device);
                }
            }
        }
    }

    private function isConfigurableProductCreated($product_id, $id)
    {
        $product = ManageMarketplaceProduct::where('product_id', $product_id)
            ->where('store_id', $id)
            ->whereNull('parent_id')
            ->first();

        return !isset($product);
    }

    private function createConfigurableProduct(mixed $productData, mixed $store)
    {
        $magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key,$store->service_url);
        $bulletPoints = $productData->productKeyFeatures;
        $bulletPointHtml = '<ul>';
        foreach ($bulletPoints as $bulletPoint) {
            $bulletPointHtml .= '<li>' . $bulletPoint->value . '</li>';
        }
        $bulletPointHtml .= '</ul>';

        $keywords = ProductTitleKeyword::select('product_id', 'keyword')->where('product_id', $productData->product_id)->orderBy('priority', 'asc')->get()->toArray();
        $productTitle = $keywords[0]['keyword'] ?? '';
        $productKeywords = implode(' ', array_column(array_slice($keywords, 1), 'keyword'));

        $productName = $productTitle . ' - ' . trim($productKeywords) . ' - ' . $store->short_code . $productData->product_sku;

        $mediaGallerEntries = $this->getMediaGalleryEntries($productData, $store);
//        Log::info('Media Gallery Entries: ' . json_encode($mediaGallerEntries));
        $categoryIds = $this->getCategoryIds($store, $productData);

//        $categoryIds = implode(',', $categoryIds);
//        Log::info('Category IDs: ' . $categoryIds);

        $finalProductData = [
            "attribute_set_id" => 4,
            "status" => 1,
            "visibility" => 4,
            "type_id" => 'configurable',
            'name' => $productName,
            'sku' => $productData->product_sku,
            "custom_attributes" => [
//                [
//                    "attribute_code" => "meta_title",
//                    "value" => "{{google_title}}"
//                ],
//                [
//                    "attribute_code" => "meta_keyword",
//                    "value" => "{{search_keywords}}"
//                ],
//                [
//                    "attribute_code" => "meta_description",
//                    "value" => "{{long_description}}"
//                ],
                [
                    "attribute_code" => "bullet_points",
                    "value" => $bulletPointHtml
                ],
                [
                    "attribute_code" => "short_description",
                    "value" => $productData->product_description
                ],
                [
                    "attribute_code" => "category_ids",
                    "value" => $categoryIds
                ]
            ],
            "extension_attributes" => [
                "stock_item" => [
                    "qty" => 100,
                    "is_in_stock" => true
                ]
            ],
            "media_gallery_entries" => $mediaGallerEntries
        ];

        $response = $this->magentoService->createOrUpdateProduct($finalProductData, $store);
        Log::info('Response from Magento: ' . json_encode($response));

        ManageMarketplaceProduct::updateOrCreate(
            [
                'product_id' => $productData->product_id,
                'product_code' => $productData->product_sku,
                'store_id' => $store->id
            ],
            [
                'product_id' => $productData->product_id,
                'product_code' => $productData->product_sku,
                'store_id' => $store->id,
                'upload_status' => MarketPlaceUploadStatus::IN_PROGRESS,
                'marketplace_product_id' => $response['id'] ?? null,
                'upload_attempts' => 1
            ]
        );
    }

    private function createSimpleProduct(mixed $productData, mixed $store, $device)
    {
        Log::info('Creating Simple Product for Device ID: ' . $device->id . ' in Store ID: ' . $store->id);
        $magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key,$store->service_url);
        $keywords = ProductTitleKeyword::select('product_id', 'keyword')->where('product_id', $productData->product_id)->orderBy('priority', 'asc')->get()->toArray();
        $productTitle = $keywords[0]['keyword'] ?? '';
        $productKeywords = implode(' ', array_column(array_slice($keywords, 1), 'keyword'));

        $productName = $productTitle . ' for ' . $device->name . ' - ' . trim($productKeywords) . ' - ' . $store->short_code . $productData->product_sku;

        $marketPlaceDevice = MarketplaceAttributeMapping::where('store_id', $store->id)
            ->where('code_id', $device->id)
            ->where('code', 'devices')
            ->first();

        Log::info('Brand ID: ' . $device->brand_id);

        $marketPlaceBrand = MarketplaceAttributeMapping::where('store_id', $store->id)
            ->where('code_id', $device->brand_id)
            ->where('code', 'brands')
            ->first();

        $new_sku = StoreTypeListing::generateUniqueAlphabeticSku($productData->product_sku, 4);

        $finalProductData = [
            "attribute_set_id" => 4,
            "status" => 1,
            "visibility" => 1,
            "type_id" => 'simple',
            'name' => $productName,
            'sku' => $new_sku,
            "price" => $productData->product_price,
            "extension_attributes" => [
                "stock_item" => [
                    "qty" => 100,
                    "is_in_stock" => true
                ]
            ],
            "custom_attributes" => [
                [
                    "attribute_code" => "weight",
                    "value" => $productData->product_weight ?? 0
                ],
                [
                    "attribute_code" => "width",
                    "value" => $productData->dimensions_width ?? ''
                ],
                [
                    "attribute_code" => "height",
                    "value" => $productData->dimensions_height ?? ''
                ],
                [
                    "attribute_code" => "depth",
                    "value" => $productData->dimensions_length ?? ''
                ],
                [
                    "attribute_code" => "devices",
                    "value" => $marketPlaceDevice->marketplace_code ?? ''
                ],
                [
                    "attribute_code" => "brands",
                    "value" => $marketPlaceBrand->marketplace_code ?? ''
                ],
                [
                    "attribute_code" => "case_size",
                    "value" => $productData->size ? $productData->size->value : ''
                ]
            ]
        ];

        Log::info('Final Product Data: ' . json_encode($finalProductData));
        $response = $this->magentoService->createOrUpdateProduct($finalProductData, $store);
        Log::info('Response from Magento: ' . json_encode($response));

        if (isset($response['id'])) {
            $magentoDevices = $magentoAuthService->getAttribute('devices');
            $deviceOption = [
                "attribute_id" => $magentoDevices['attribute_id'],
                "label" => "Devices",
                "values" => [
                    [
                        "value_index" => $marketPlaceDevice->marketplace_code
                    ]
                ]
            ];
            $magentoAuthService->addOptionstoParent($productData->product_sku, $deviceOption);

            $magentoBrands = $magentoAuthService->getAttribute('brands');
            $brandOption = [
                "attribute_id" => $magentoBrands['attribute_id'],
                "label" => "Brands",
                "values" => [
                    [
                        "value_index" => $marketPlaceBrand->marketplace_code
                    ]
                ]
            ];
            $magentoAuthService->addOptionstoParent($productData->product_sku, $brandOption);

            $magentoAuthService->addChildProductToParent($productData->product_sku, $new_sku);
        }

        $configProduct = ManageMarketplaceProduct::where('product_id', $productData->product_id)
            ->whereNull('parent_id')
            ->where('store_id', $store->id)
            ->first();

        Log::info('Config Product: ' . json_encode($configProduct));

        ManageMarketplaceProduct::updateOrCreate(
            [
                'product_id' => $productData->product_id,
                'store_id' => $store->id,
                'sku' => $new_sku,
                'device_id' => $device->id,
                'marketplace_variant_id' => $response['id'] ?? null
            ],
            [
                'product_id' => $productData->product_id,
                'product_code' => $productData->product_sku,
                'sku' => $new_sku,
                'store_id' => $store->id,
                'device_id' => $device->id,
                'upload_status' => MarketPlaceUploadStatus::SUCCESS,
                'marketplace_product_id' => $configProduct->marketplace_product_id ?? null,
                'marketplace_variant_id' => $response['id'] ?? null,
                'parent_id' => $configProduct->id ?? null,
                'upload_attempts' => 1
            ]
        );
    }

    private function getMediaGalleryEntries($productData, $store)
    {
        if (env('APP_ENV') === 'local') {
            return [];
        }

        $mediaGalleryEntries = [];
        ProductImages::where('product_id', $productData->product_id)
            ->chunk(100, function ($productImages) use ($store, $productData, $mediaGalleryEntries) {
                foreach ($productImages as $productImage) {
                    $Image = $productImage->imageObj;
                    $localFilePath = storage_path('app/public/' . $Image->location . $Image->name);
                    $base64Image = base64_encode(file_get_contents($localFilePath));

                    $imageEntry = [
                        "media_type" => "image",
                        "label" => $Image->name,
                        "position" => 0,
                        "disabled" => false,
                        "content" => [
                            "base64_encoded_data" => $base64Image,
                            "type" => 'image/jpeg',
                            "name" => $Image->name
                        ]
                    ];
                    if ($productImage->main_image) {
                        $imageEntry['types'][] = 'image';
                        $imageEntry['types'][] = 'small_image';

                        $thumbnailImage = $productImage->thumbnailImageObj;
                        if ($thumbnailImage) {
                            $thumbnailLocalFilePath = storage_path('app/public/' . $thumbnailImage->location . $thumbnailImage->name);
                            $base64ThumbnailImage = base64_encode(file_get_contents($thumbnailLocalFilePath));
                            $thumbnailImageEntry = [
                                "media_type" => "image",
                                "label" => $thumbnailImage->name,
                                "position" => 0,
                                "disabled" => false,
                                'types' => ['thumbnail'],
                                "content" => [
                                    "base64_encoded_data" => $base64ThumbnailImage,
                                    "type" => 'image/jpeg',
                                    "name" => $thumbnailImage->name
                                ]
                            ];
                            $mediaGalleryEntries[] = $thumbnailImageEntry;
                        }
                    }
                    $mediaGalleryEntries[] = $imageEntry;
                }
            });
        return $mediaGalleryEntries;
    }

    /**
     * @param mixed $store
     * @param mixed $productData
     * @return mixed
     */
    public function getCategoryIds(mixed $store, mixed $productData)
    {
        $categoryIds = MarketPlaceCategoryMappings::where('store_id', $store->id)
            ->where('name', $productData->productType->name)
            ->pluck('marketplace_category_id')->toArray();

        $parentCategoryIds = MarketPlaceCategoryMappings::where('store_id', $store->id)
            ->where('name', $productData->productType->name)
            ->pluck('marketplace_parent_id')->toArray();

        foreach ($parentCategoryIds as $parentCategoryId) {
            if (!in_array($parentCategoryId, $categoryIds)) {
                $categoryIds[] = $parentCategoryId;
            }
        }

        return array_merge($categoryIds);
    }

}
