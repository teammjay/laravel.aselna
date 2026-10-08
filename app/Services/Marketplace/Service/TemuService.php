<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedStatus;
use App\Constants\FeedTypes;
use App\Constants\OrderStatus;
use App\Constants\OrderSyncronisationType;
use App\Constants\WeightUnit;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItem;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Models\Order;
use App\Models\OrderSynchronization;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationInfo;
use App\Models\ProductSpecificationValue;
use App\Services\Auth\TemuAuthService;
use App\Services\CriticalErrorService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\FileUtility;
use App\Utils\order\OrderUtility;
use App\Utils\order\ShippingUtility;
use Illuminate\Support\Facades\Log;

class TemuService implements MarketplaceServiceInterface
{
    // Packages already given the new tracking in this run. Merged orders can share one Temu package,
    // and it only needs updating once.
    private $updatedPackageSns = [];

    public function generateFeed($listingId, $feedId = null)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $feedInfo = ListingFeed::where('id', $feedId)->first();
        $identifier = $feedId ?: $listingId;

        switch ($feedInfo->feed_type) {
            case FeedTypes::CREATE:
                $this->generateCreateFeed($listingInfo, $identifier);
                break;
//            case FeedTypes::IMAGE_UPDATE:
//                $this->generateImageUpdateFeed($listingInfo, $identifier);
//                break;
//            case FeedTypes::DELETE:
//                $this->generateDeleteFeed($listingInfo, $identifier);
//                break;
//            case FeedTypes::INVENTORY:
//                $this->generateInventoryFeed($listingInfo, $identifier, $feedInfo);
//                break;
//            case FeedTypes::SHIPMENT_UPDATE:
//                $this->callShipmentUpdateFeed($feedId);
//                break;
//            case FeedTypes::ORDER_CANCEL:
//                $this->callOrderCancelFeed($feedId);
//                break;
//            case FeedTypes::PRICE:
//                $this->generatePriceFeed($listingInfo, $identifier);
//                break;
            default:
                break;
        }

        return 'feeds/temu/' . $identifier . '.json';
    }

    public function submitFeed($feedId, $listingId)
    {
        $feedInfo = ListingFeed::findOrFail($feedId);
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;

        $temuAuthService = new TemuAuthService();

        // Backward compatibility
        $file = public_path('feeds/temu/' . $listingId . '.json');
        if (!file_exists($file) && $feedId) {
            $file = public_path('feeds/temu/' . $feedId . '.json');
        }

        $resultFile = public_path('result-feeds/temu/' . ($feedId ?: $listingId) . '.json');
        // json content
        if (!file_exists($file)) {
             Log::error("Temu feed file not found: $file");
             return;
        }
        $allfeedContent = file_get_contents($file);

        $successCount = 0;
        $failedCount = 0;
        $errorMessages = [];

        $allresults = [];

        foreach (json_decode($allfeedContent, true) as $individualFeed) {
            try {
                Log::info('Individual Feed: ' . json_encode($individualFeed));
                $response = $temuAuthService->submitFeed($individualFeed, $store);
                Log::info('Temu Feed Submission Response: ' . json_encode($response));

                $allresults[] = $response;

                if (isset($response) && $response['success'] == true) {
                    $successCount++;
                    $goodsId = $response['result']['goodsId'] ?? null;
                    if ($goodsId) {
                        $sku = $individualFeed['skuList'][0]['outSkuSn'] ?? null;
                        $listingItem = ListingItem::where('sku', $sku)->first();

                        if ($listingItem) {
                            $listingItem->update(['store_sku' => $goodsId]);
                        }
                    }
                } else {
                    $failedCount++;
                    $errorMsg = $response['errorMessage'] ?? $response['error_msg'] ?? 'Unknown Temu error';
                    $sku = $individualFeed['skuList'][0]['outSkuSn'] ?? 'unknown';
                    $errorMessages[] = "[$sku] $errorMsg";
                }
            } catch (\Exception $e) {
                Log::info('Temu Feed Submission Error: ' . $e->getMessage());
                $allresults[] = ['error' => $e->getMessage()];
                $failedCount++;
                $errorMessages[] = $e->getMessage();
            }
        }

        FileUtility::saveToFile($resultFile, json_encode($allresults));

        // Determine proper status based on results
        $status = FeedStatus::PROCESSED;
        $errorText = null;
        if ($successCount === 0 && $failedCount > 0) {
            $status = FeedStatus::ERROR;
            $errorText = implode("\n", array_slice($errorMessages, 0, 50));
        }
        elseif ($failedCount > 0 && $successCount > 0) {
            // Mixed results — still PROCESSED but with errors noted
            $status = FeedStatus::PROCESSED;
            $errorText = implode("\n", array_slice($errorMessages, 0, 50));
        }

        $feedInfo->update([
            'success_count' => $successCount,
            'fail_count' => $failedCount,
            'submit_ts' => now(),
            'number_of_attempts' => ($feedInfo->number_of_attempts ?? 0) + 1,
            'status' => $status,
            'error_text' => $errorText,
        ]);
    }

    public function getResultFeed(mixed $listingId, $feedId = null)
    {
//        $temuAuthService = new TemuAuthService();
//        $listingItems = ListingItemLink::where('listing_id', $listingId)->get();
//        $outSkunList = [];
//        $feedInfo = ListingFeed::findOrFail($feedId);
//
//        foreach ($listingItems as $listingItem) {
//            $item = $listingItem->item;
//            $outSkuN = $item->sku;
//            $outSkunList[] = $outSkuN;
//        }
//
//        if (isset($outSkunList)) {
//            $response = $temuAuthService->getFeedResult($outSkunList);
//            Log::info('Temu Feed Result Response for SKU ' . $outSkuN . ': ' . json_encode($response));
//
//            if (isset($response) && $response['success'] == true) {
//                $successCount = 0;
//                $errorCount = 0;
//                foreach ($response['result']['resultList'] as $goodsInfo) {
//                    $sku = $goodsInfo['outSkuSn'] ?? null;
//                    $goodsId = $goodsInfo['goodsId'] ?? null;
//
//                    $listingItem = ListingItem::where('sku', $sku)->first();
//                    if ($listingItem && $goodsId) {
//                        $listingItem->update(['store_sku' => $goodsId]);
//                        $successCount++;
//                    } else {
//                        $errorCount++;
//                    }
//                }
//                $feedInfo->update([
//                    'success_count' => $successCount,
//                    'failed_count' => $errorCount,
//                    'status' => FeedStatus::PROCESSED
//                ]);
//            }
//        }
    }

    public function syncAttributes($store)
    {
        // TODO: Implement syncAttributes() method.
    }

    private function generateCreateFeed($listingInfo, $identifier)
    {
        try {
            $listingId = $listingInfo->id;
            $temuAuthService = new TemuAuthService();
            $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();
            $fileSystem = $listingInfo->store->fileSystem;

            $jsonFeed = [];

            foreach ($listingItems as $listingItem) {
                $item = $listingItem->item;
                $storeTypeCategory = $item->storeProduct->storeTypeCategory;
                $productObj = $item->storeProduct->product;
                $productColor = $productObj->productColor;

                $price = $item->price;
                if (!isset($price)) {
                    $price = $item->storeProduct->price;
                }
                if (!isset($price)) {
                    $price = $productObj->product_marketplace_price;
                }

                $keyFeatures = [];
                foreach ($productObj->productKeyFeatures as $feature) {
                    if ($feature->value == '' || !isset($feature->value)) continue;
                    $keyFeatures[] = $feature->value;
                }

                $template = $temuAuthService->getTemplate($storeTypeCategory->code);
                Log::info('Template for Category ' . $storeTypeCategory->code . ': ' . json_encode($template));

                $productData = $this->getProductData($listingItem);
                $properties = [];

                Log::info('Product data :: ' . json_encode($productData));

                $TemplateProperties = $template['result']['templateInfo']['goodsProperties'] ?? [];
                $specProperties = $this->buildGoodsPropertiesPayload($TemplateProperties, $productData);
                $properties = array_merge($properties, $specProperties['properties']);
                Log::info('Properties completed');

                $TemplateSpecProperties = $template['result']['templateInfo']['goodsSpecProperties'] ?? [];
                $specDetails = $this->getSpecDetails($TemplateSpecProperties, $productData);
                $properties = array_merge($properties, $specDetails['properties']);

                $specIdList = array_merge($specDetails['specIdList'], $specProperties['specIdList']);
                Log::info('Properties :: ' . json_encode($properties));

                if (empty($specIdList)) {
                    $userInputParentSpecList = $template['result']['userInputParentSpecList'];
                    foreach ($userInputParentSpecList as $spec) {
                        switch ($spec['parentSpecName']) {
                            case 'Color':
                                if (isset($productColor)) {
                                    $specOption = $temuAuthService->createSpecOption($storeTypeCategory->code, $spec['parentSpecId'], $productColor->name);
                                    if (isset($specOption) && isset($specOption['result']['specId'])) {
                                        $specIdList[] = $specOption['result']['specId'];
                                    }
                                }
                                break;
                            case 'Material':
                                $material = $productObj->material ?? null;
                                if (isset($material)) {
                                    $specOption = $temuAuthService->createSpecOption($storeTypeCategory->code, $spec['parentSpecId'], $material->name);
                                    if (isset($specOption) && isset($specOption['result']['specId'])) {
                                        $specIdList[] = $specOption['result']['specId'];
                                    }
                                }
                                break;
                            default:
                                break;
                        }
                        if (count($specIdList) >= 1) {
                            break;
                        }
                    }
                }
                Log::info('Spec ID List :: ' . json_encode($specIdList));


                $ShippingTemplates = $temuAuthService->geShippingTemplate();
                Log::info('Shipping Templates: ' . json_encode($ShippingTemplates));

                $templateID = $ShippingTemplates['result']['templateList'][0]['templateId'] ?? 'LFT-15741247532810352765';

                $images = ListingItemProductImage::leftJoin('product_images', 'product_images.id', '=', 'listing_item_product_images.product_image_id')
                    ->where('listing_item_product_images.listing_item_id', $item->id)->orderBy('listing_item_product_images.main_image', 'desc')
                    ->get();

                Log::info('Images: ' . json_encode($images));
                Log::info('File System: ' . json_encode($fileSystem));
                $imagesPath = [];
                foreach ($images as $image) {
                    $imagesUrl = $fileSystem->url_base_path . $image->path;
                    [$width, $height] = getimagesize($imagesUrl);

                    // Skip if aspect ratio is not 1:1
                    if ($width !== $height || $width < 800 || $height < 800) {
                        Log::info('Skipping image due to aspect ratio: ' . $imagesUrl);
                        continue;
                    }

                    $ImageResponse = $temuAuthService->uploadImage($imagesUrl);
                    Log::info('Response Image :: ' . json_encode($ImageResponse));
                    if (isset($ImageResponse['result']['url'])) {
                        $imagesPath[] = $ImageResponse['result']['url'];
                    }
                }

                $feedItem = [
                    'goodsBasic' => [
                        'goodsName' => $item->title,
                        'catId' => (int)$storeTypeCategory->store_type_code,
                        'importDesignation' => 'Imported',
                    ],
                    'goodsDesc' => $item->description,
                    'goodsServicePromise' => [
                        'costTemplateId' => $templateID,
                        'fulfillmentType' => 1,
                        'shipmentLimitDay' => 2
                    ],
                    'goodsOriginInfo' => [
                        'originRegion1' => 'Mainland China',
                        'originRegion2' => 'Guangdong'
                    ],
                    'goodsProperty' => [
                        'goodsProperties' => $properties
                    ],
                    'bulletPoints' => $keyFeatures,
                ];

                $feedItem['skuList'] = [
                    [
                        'height' => '' . $productObj->package_dimensions_height,
                        'images' => $imagesPath,
                        'length' => '' . $productObj->package_dimensions_length,
                        'price' => [
                            'basePrice' => [
                                'amount' => '' . floatval($price),
                                'currency' => 'USD'
                            ],
                            'listPrice' => [
                                'amount' => '' . floatval($price),
                                'currency' => 'USD'
                            ],
                            'listPriceType' => 0
                        ],
                        'quantity' => $item->quantity,
                        'specIdList' => $specIdList,
                        'outSkuSn' => $item->sku,
                        'volumeUnit' => 'in',
                        'weight' => '' . number_format(WeightUnit::convert($productObj->product_weight_unit ?? WeightUnit::OZ, WeightUnit::LB, $productObj->product_weight), 2, '.', ''),
                        'weightUnit' => 'lb',
                        'width' => '' . $productObj->package_dimensions_width,
                    ]
                ];


                $jsonFeed[] = $feedItem;
            }

            Log::info('Json Feed :: ' . json_encode($jsonFeed));

            FileUtility::saveToFile(public_path('feeds/temu/' . $identifier . '.json'), json_encode($jsonFeed));

        } catch (\Throwable $e) {

            CriticalErrorService::report('Temu Generate Create Feed Error', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['listingId'=> $listingId]);

            Log::error('Temu Generate Create Feed Error : ' . $e->getMessage());
            throw $e; // propagate to FeedGenerationJob to update feed status
        }
    }

    public function buildGoodsPropertiesPayload(array $templateAttributes, array $productData): array
    {
        $finalPayload = [
            'properties' => [],
            'specIdList' => []
        ];

        // Create a simple map of your product data for easy lookup.
        // This assumes keys in $productData match the 'name' from the template.
        $productValueMap = $productData;

        foreach ($templateAttributes as $attribute) {
            $attributeName = $attribute['name'];
            Log::info('Attribute Name :: ' . $attributeName);

            // Skip if your product data doesn't have a value for this attribute.
            if (!isset($productValueMap[$attributeName])) {
                continue;
            }

            $valueFromDb = $productValueMap[$attributeName];
            Log::info('Attribute :: ' . json_encode($attribute));

            switch ($attribute['controlType']) {
                /**
                 * Case 1: Select type property (Dropdowns, Checkboxes)
                 * We need to find the matching 'vid' for the given value string.
                 */
                case 1:
                case 3:
                    // Normalize DB value to an array to handle multi-select gracefully.
                    $selectedValues = is_array($valueFromDb) ? $valueFromDb : [$valueFromDb];

                    // Create a lookup map for faster searching: ['Cotton' => ['vid' => 49, ...]]
                    $optionsMap = array_change_key_case(array_column($attribute['values'], null, 'value'), CASE_LOWER);
                    Log::info('Options Map :: ' . json_encode($optionsMap));

                    foreach ($selectedValues as $selectedValue) {
                        $searchingValue = strtolower($selectedValue);
                        Log::info('Searching :: ' . $searchingValue);
                        if (isset($optionsMap[$searchingValue])) {
                            $chosenOption = $optionsMap[$searchingValue];
                            $finalPayload['properties'][] = [
                                'templatePid' => $attribute['templatePid'],
                                'refPid' => $attribute['refPid'],
                                'pid' => $attribute['pid'],
                                'vid' => $chosenOption['vid'],
                                'value' => $chosenOption['value'],
                            ];
                            if ($chosenOption['specId']) {
                                $finalPayload['specIdList'][] = $chosenOption['specId'];
                            }
                        }
                    }
                    break;

                case 0:
                    $payloadObject = [
                        'templatePid' => $attribute['templatePid'],
                        'refPid' => $attribute['refPid'],
                        'pid' => $attribute['pid'],
                        'vid' => 0, // 'vid' is 0 for input types as per docs.
                        'value' => (string)$valueFromDb,
                    ];

                    // Check if a unit is defined and add it to the payload.
                    if (!empty($attribute['valueUnitList'])) {
                        // Using the first available unit by default.
                        // You might need more logic if multiple units are possible.
                        $payloadObject['valueUnit'] = $attribute['valueUnitList'][0]['valueUnit'];
                    }

                    $finalPayload['properties'][] = $payloadObject;
                    break;
            }
        }

        return $finalPayload;
    }

    private function getSpecDetails(array $templateAttributes, array $productData): array
    {
        $finalPayload = [
            'properties' => [],
            'specIdList' => []
        ];

        // Create a simple map of your product data for easy lookup.
        // This assumes keys in $productData match the 'name' from the template.
        $productValueMap = $productData;

        foreach ($templateAttributes as $attribute) {
            $attributeName = $attribute['name'];
            Log::info('Attribute Name :: ' . $attributeName);

            // Skip if your product data doesn't have a value for this attribute.
            if (!isset($productValueMap[$attributeName])) {
                continue;
            }

            $valueFromDb = $productValueMap[$attributeName];
            Log::info('Attribute :: ' . json_encode($attribute));

            switch ($attribute['controlType']) {
                /**
                 * Case 1: Select type property (Dropdowns, Checkboxes)
                 * We need to find the matching 'vid' for the given value string.
                 */
                case 1:
                case 3:
                    // Normalize DB value to an array to handle multi-select gracefully.
                    $selectedValues = is_array($valueFromDb) ? $valueFromDb : [$valueFromDb];

                    // Create a lookup map for faster searching: ['Cotton' => ['vid' => 49, ...]]
                    $optionsMap = array_change_key_case(array_column($attribute['values'], null, 'value'), CASE_LOWER);

                    foreach ($selectedValues as $selectedValue) {
                        $searchingValue = strtolower($selectedValue);
                        Log::info('Searching :: ' . $searchingValue);
                        if (isset($optionsMap[$searchingValue])) {
                            $chosenOption = $optionsMap[$searchingValue];
                            $finalPayload['properties'][] = [
                                'templatePid' => $attribute['templatePid'],
                                'refPid' => $attribute['refPid'],
                                'pid' => $attribute['pid'],
                                'vid' => $chosenOption['vid'],
                                'specId' => $chosenOption['specId'],
                            ];
                            if ($chosenOption['specId']) {
                                $finalPayload['specIdList'][] = $chosenOption['specId'];
                            }
                        }
                    }
                    break;
            }
        }

        return $finalPayload;
    }

    private function getProductData($listingItem)
    {
        $itemDetail = $listingItem->item;
        $storeProduct = $itemDetail->storeProduct;
        $product = $storeProduct->product;

        $productDataA = ProductSpecification::where('product_id', $product->product_id)
            ->where('store_type_id', $storeProduct->store->store_type_id)->get();
        $productData = [];

        foreach ($productDataA as $data) {
            $productData[$data->name] = $data->calculated_value();
        }

        $compatibleModels = [];
        foreach ($listingItem->listingItem->devices as $k => $device) {
            Log::info('Device Name ' . $device->device->name);
            $compatibleModels[] = strtolower($device->device->name);
        }
        $productData['Compatible Model'] = $compatibleModels;

        return $productData;
    }

    public function getOrders(mixed $store)
    {
        try {
            $temuAuthService = new TemuAuthService();

            $last_synchronization = OrderSynchronization::where('store_id', $store->id)->where('synchronization_type', OrderSyncronisationType::ORDER)->orderBy('last_order_update_date', 'desc')->orderBy('updated_at', 'desc')->first();
            $filterStartDate = OrderUtility::getLastSyncDate($store->id, OrderSyncronisationType::ORDER);
            Log::info('Temu Order Last Sync Date: ' . $filterStartDate);

            $filterStartDateFormatted = strtotime($filterStartDate);

            $orders = $temuAuthService->getOrders($filterStartDateFormatted, $last_synchronization->cursor ?? null);

            $last_order_update_date = null;
            $noOfOrder = 0;
            $nextCursor = $orders['next'] ?? null;

            $created_by = auth()->user()->name ?? 'admin';
            $synchronizeArr = [
                'store_id' => $store->id,
                'synchronization_type' => OrderSyncronisationType::ORDER,
                'filter_start_date' => isset($nextCursor) && isset($last_synchronization) ? $last_synchronization->filter_start_date : $filterStartDate,
                'filter_end_date' => isset($nextCursor) && isset($last_synchronization) ? $last_synchronization->filter_end_date : date('Y-m-d H:i:s'),
                'created_by' => $created_by,
                'cursor' => $nextCursor
            ];
            //Log::info('Order Synchronization: ' . json_encode($synchronizeArr));
            $order_synchronization = OrderSynchronization::create($synchronizeArr);

            Log::info('Temu Orders Response: ' . json_encode($orders));
            if (isset($orders['result']) && isset($orders['result']['totalItemNum']) && $orders['result']['totalItemNum'] > 0) {
                $last_order_update_date = $order_synchronization->last_order_update_date ?? null;
                foreach ($orders['result']['pageItems'] as $porder) {
                    $parentOrder = $porder['parentOrderMap'];
                    $presentOrder = Order::where('order_id', $parentOrder['parentOrderSn'])->first();
                    $shippingInfo = $temuAuthService->getShippingInfo($parentOrder['parentOrderSn']);
                    Log::info('Shipping Info for Order ' . $parentOrder['parentOrderSn'] . ': ' . json_encode($shippingInfo));
                    if (isset($shippingInfo) && isset($shippingInfo['result'])) {
                        $porder['shippingInfo'] = $shippingInfo['result'];
                    }

                    $persist = false;
                    $statusChange = false;

                    if ($presentOrder == null) {
                        $presentOrder = Order::updateOrCreate([
                            'order_id' => $parentOrder['parentOrderSn'],
                        ], [
                            'order_id' => $parentOrder['parentOrderSn'],
                            'order_date' => date('Y-m-d H:i:s', $parentOrder['parentOrderTime']),
                            'order_status' => OrderStatus::Synchronizing,
                            'store_id' => $store->id,
                            'order_synchronization_id' => $order_synchronization->id
                        ]);
                        $persist = true;
                        $statusChange = true;
                    } else {
                        $presentOrder = Order::updateOrCreate([
                            'order_id' => $parentOrder['parentOrderSn'],
                        ], [
                            'order_date' => date('Y-m-d H:i:s', $parentOrder['parentOrderTime']),
                        ]);
                        // TODO Need To implement status change
                        $statusChange = true;
                    }

                    if ($statusChange) {
                        OrderUtility::mapTemuOrders($presentOrder, $porder, $store);
                    }

                    if ($last_order_update_date === null || strtotime($parentOrder['parentOrderTime']) > strtotime($last_order_update_date)) {
                        $last_order_update_date = date('Y-m-d H:i:s', $parentOrder['parentOrderTime']);
                    }
                }
            } else {
                Log::error('Orders Not found');
                return null;
            }

            if (isset($last_synchronization) && strtotime($last_synchronization->last_order_update_date) > strtotime($last_order_update_date)) {
                Log::info('Using last order update date from last synchronization :: ' . $last_synchronization->last_order_update_date);
                $last_order_update_date = date('Y-m-d H:i:s', strtotime($last_synchronization->last_order_update_date));
            }
            $order_synchronization->update(['number_of_items' => $noOfOrder, 'last_order_update_date' => $last_order_update_date]);
        } catch (\Throwable $e) {

            CriticalErrorService::report('Temu Get Order Error', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['store_name'=>$store->name]);

            Log::info('Temu order error : ' . $e->getMessage());
        }
    }

    public function createShipment($mainOrderOperation, $orderOperation, $store)
    {
        try {
            $temuAuthService = new TemuAuthService();
            $warehouseDetails = $temuAuthService->getWarehouseInfo();
            $orderId = $orderOperation->order->order_id;
            $trackingNumber = $mainOrderOperation->tracking_number;
            Log::info('WareHouse List' . json_encode($warehouseDetails));
            if (isset($warehouseDetails) && count($warehouseDetails['result']['warehouseList']) > 0) {
                $warehouseDetail = $warehouseDetails['result']['warehouseList'][0];
                $carrierDetails = $temuAuthService->getLogisticsInfo($warehouseDetail['regionId1']);
                Log::info('Selected Carrier' . json_encode($carrierDetails));

                if (isset($carrierDetails['result'])) {
                    $result = $carrierDetails['result'];
                    $logisticsServiceProviderId = null;
                    foreach ($result as $logistic) {
                        if ($logistic['logisticsBrandName'] == 'USPS') {
                            $logisticsServiceProviderId = $logistic['logisticsServiceProviderId'];
                        }
                    }
                    if ($logisticsServiceProviderId == null) {
                        return false;
                    }

                    $data = [
                        'sendType' => 0,
                        'sendRequestList' => [
                            'carrierId' => $logisticsServiceProviderId,
                            'trackingNumber' => $trackingNumber,
                            'selfShippingWarehouseId' => $warehouseDetail['warehouseId'],
                            'orderSendInfoList' => []
                        ]
                    ];

                    $orderItems = [];
                    foreach ($mainOrderOperation->order->orderItem as $item) {
                        $orderItems[] = [
                            'parentOrderSn' => $orderId,
                            'orderSn' => $item->store_sku,
                            'quantity' => $item->quantity
                        ];
                    }
                    $data['sendRequestList']['orderSendInfoList'] = $orderItems;

                    Log::info("Request For Shipment" . json_encode($data));

                    $response = $temuAuthService->callOrderShipment($data);
                    Log::info('Temu Shipment Response : ' . json_encode($response));
                    return true;
                }
            }

            return  false;

        } catch (\Throwable $e) {

            CriticalErrorService::report('Temu Create Shipment Error', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['order_id'=>$orderOperation->order->order_id,'tracking_number'=>$mainOrderOperation->tracking_number]);

            Log::info('Temu shipment error : ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Replace the tracking number on the packages Temu holds for an order already shipped. The packages
     * are looked up per order line (bg.logistics.shipment.v2.get) and edited in one call.
     */
    public function updateTrackingNumber($orderOperation): array
    {
        $carrierCode = ShippingUtility::marketplaceCarrierCode($orderOperation);
        if (!$carrierCode) {
            return ['success' => false, 'message' => 'Temu does not accept ' . ($orderOperation->carrier->name ?? 'this carrier') . ' as a carrier. Please choose USPS, UPS, FedEx or DHL.'];
        }

        $temuAuthService = new TemuAuthService();
        $parentOrderSn = $orderOperation->order->order_id;

        $warehouseDetails = $temuAuthService->getWarehouseInfo();
        $warehouseDetail = $warehouseDetails['result']['warehouseList'][0] ?? null;
        if (!$warehouseDetail) {
            return ['success' => false, 'message' => 'Could not load the Temu warehouse.'];
        }

        $carrierDetails = $temuAuthService->getLogisticsInfo($warehouseDetail['regionId1']);
        $shipCompanyId = null;
        foreach ($carrierDetails['result'] ?? [] as $logistic) {
            if (strcasecmp($logistic['logisticsBrandName'] ?? '', $carrierCode) === 0) {
                $shipCompanyId = $logistic['logisticsServiceProviderId'];
            }
        }
        if (!$shipCompanyId) {
            return ['success' => false, 'message' => 'Temu does not offer ' . $carrierCode . ' for this warehouse.'];
        }

        // The order lines are shipped as Temu sub orders (orderSn), stored as store_sku.
        $packageSns = [];
        foreach ($orderOperation->order->orderItem as $item) {
            $shipmentInfo = $temuAuthService->getShipmentInfo($parentOrderSn, $item->store_sku);
            foreach ($shipmentInfo['result']['shipmentInfoDTO'] ?? [] as $shipment) {
                if (!empty($shipment['packageSn'])) {
                    $packageSns[$shipment['packageSn']] = true;
                }
            }
        }
        if (empty($packageSns)) {
            return ['success' => false, 'message' => 'No shipped package found in Temu for order ' . $parentOrderSn . '.'];
        }

        $packageSns = array_diff(array_keys($packageSns), $this->updatedPackageSns);
        if (empty($packageSns)) {
            // A merged order sharing a package that was already updated.
            return ['success' => true, 'message' => ''];
        }

        $editPackageRequestList = [];
        foreach ($packageSns as $packageSn) {
            $editPackageRequestList[] = [
                'packageSn' => $packageSn,
                'trackingNumber' => $orderOperation->tracking_number,
                'shipCompanyId' => $shipCompanyId,
            ];
        }


        $response = $temuAuthService->updateShipmentTracking($editPackageRequestList);
        Log::info('Temu tracking number update response for order ' . $parentOrderSn . ': ' . json_encode($response));

        if (empty($response['success'])) {
            return ['success' => false, 'message' => $response['errorMsg'] ?? $response['errorMessage'] ?? $response['error_msg'] ?? 'Temu did not accept the new tracking number.'];
        }

        $this->updatedPackageSns = array_merge($this->updatedPackageSns, $packageSns);

        return ['success' => true, 'message' => ''];
    }

    public function refundOrder($parentOrderSn){
        try{

            $temuAuthService = new TemuAuthService();
            $afterSalesList = $temuAuthService->getAfterSalesList($parentOrderSn);
            Log::info('Temu getAfterSalesList response : ' . json_encode($afterSalesList));

            if (isset($afterSalesList['result']['data']) && count($afterSalesList['result']['data']) > 0) {
                $orderDetail = $afterSalesList['result']['data'][0];
                $parentAfterSalesSn = $orderDetail['parentAfterSalesSn'];
                $refundOrderDetails = $temuAuthService->issueRefund($parentAfterSalesSn, $parentOrderSn);

                Log::info('Temu issueRefund response : ' . json_encode($refundOrderDetails));

                return $refundOrderDetails;
                //return true;
            }else{
                if(isset($afterSalesList['success']) && !$afterSalesList['success']){
                    Log::info('Temu refund error : ' . json_encode($afterSalesList));
                    return false;
                }
            }

            return false;
        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Refund Order Error', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['parentOrderSn'=>$parentOrderSn]);

            Log::info('Temu refund error : ' . $e->getMessage());
            return false;
        }
    }
}
