<?php

namespace App\Jobs;

use App\Constants\MarketPlaceUploadStatus;
use App\Models\Catalog;
use App\Models\Device;
use App\Models\GenerateProductImage;
use App\Models\ManageMarketplaceProduct;
use App\Models\MarketplaceAttributeMapping;
use App\Models\ProductImages;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\Store;
use App\Services\Auth\MagentoAuthService;
use App\Services\Marketplace\Service\MagentoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MagentoProductsSyncJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 30000;

    public $magentoService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    public function handle(): void
    {
        $this->magentoService = new MagentoService();
        $stores = Store::select('store.*')->with('fileSystem', 'storeType')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'magento')->get();

        Log::info('Magento Simple Products Sync Job Started');
        if (count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                Log::info('Store : ' . $store->name);

                $pIds = ManageMarketplaceProduct::select('product_id')->whereIn('upload_status', ['success', 'failed'])->get();

                $productTypes = ProductType::select('id')->where('code', 'ceabundle')->get()->toArray();

                Catalog::select('*')->with('productType', 'productKeyFeatures', 'productColor')
                    ->whereNotIn('product_type', $productTypes)
                    ->whereNull('parent_id')
                    ->where('product_sku', 'V12')
                    ->whereNotIn('product_id', $pIds)
                    ->orderBy('created_at', 'desc')
                    ->chunk(100, function ($products) use ($store) {
                        foreach ($products as $product) {
                            $this->submitFeed($product, $store);
                        }
                    });

            }
        }

    }

    private function submitFeed(mixed $productData, mixed $store)
    {
//        if ($this->isSimpleProductRemaining($productData->product_id, $store->id)) {
//
//        } else {
//
//        }
        $this->createConfigurableProduct($productData, $store);

        // $query->leftJoin('device_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id');
        // $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'products.product_id');
        // get all the devices associated with the product
//        $devices = Device::select('device.*')
//            ->leftJoin('device_interface', 'device_interface.device_id', '=', 'device.id')
//            ->leftJoin('product_interface', 'product_interface.interface_id', '=', 'device_interface.interface_id')
//            ->where('product_interface.product_id',$productData->product_id )
//            ->limit(5)
//            ->get();
//
//        foreach ($devices as $device) {
//            $this->createSimpleProduct($productData, $store, $device);
//        }
    }

    private function isSimpleProductRemaining($product_id, $id)
    {

        return false;
    }

    private function createConfigurableProduct(mixed $productData, mixed $store)
    {
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
//                [
//                    "attribute_code" => "category_ids",
//                    "value" => "{{category_ids}}"
//                ]
            ],
            "extension_attributes" => [
                "stock_item" => [
                    "qty" => 100,
                    "is_in_stock" => true
                ]
            ]
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

        $keywords = ProductTitleKeyword::select('product_id', 'keyword')->where('product_id', $productData->product_id)->orderBy('priority', 'asc')->get()->toArray();
        $productTitle = $keywords[0]['keyword'] ?? '';
        $productKeywords = implode(' ', array_column(array_slice($keywords, 1), 'keyword'));

        $productName = $productTitle . ' for ' . $device->name . ' - ' . trim($productKeywords) . ' - ' . $store->short_code . $productData->product_sku;

        $marketPlaceDevice = MarketplaceAttributeMapping::where('store_id', $store->id)
            ->where('code_id', $device->id)
            ->where('code', 'devices')
            ->first();

        $new_sku = $productData->product_sku . '-' . $marketPlaceDevice->marketplace_code;

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
                    "attribute_code" => "devices",
                    "value" => ""
                ],
                [
                    "attribute_code" => "brands",
                    "value" => ""
                ],
            ]
        ];

        Log::info('Final Product Data: ' . json_encode($finalProductData));
        $response = $this->magentoService->createOrUpdateProduct($finalProductData, $store);
        Log::info('Response from Magento: ' . json_encode($response));
    }

}
