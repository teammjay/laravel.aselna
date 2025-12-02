<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedStatus;
use App\Constants\FeedTypes;
use App\Constants\ListingStatus;
use App\Constants\ListingSyncStatus;
use App\Constants\OrderOperationStatus;
use App\Constants\OrderStatus;
use App\Constants\OrderSyncronisationType;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItem;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSynchronization;
use App\Models\ProductSpecification;
use App\Services\Auth\WalmartAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\FileUtility;
use App\Utils\order\OrderUtility;
use Illuminate\Support\Facades\Log;

class WalmartService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId, $feedId = null)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $feedInfo = ListingFeed::where('id', $feedId)->first();

        switch ($feedInfo->feed_type) {
            case FeedTypes::CREATE:
                $this->generateCreateFeed($listingInfo, $listingId);
                break;
            case FeedTypes::IMAGE_UPDATE:
                $this->generateImageUpdateFeed($listingInfo, $listingId);
                break;
            case FeedTypes::DELETE:
                $this->generateDeleteFeed($listingInfo, $listingId);
                break;
            case FeedTypes::INVENTORY:
                $this->generateInventoryFeed($listingInfo, $listingId, $feedInfo);
                break;
            case FeedTypes::SHIPMENT_UPDATE:
                $this->callShipmentUpdateFeed($feedId);
                break;
            case FeedTypes::ORDER_CANCEL:
                $this->callOrderCancelFeed($feedId);
                break;
            case FeedTypes::PRICE:
                $this->generatePriceFeed($listingInfo, $listingId);
                break;
            default:
                break;
        }

        return 'feeds/walmart/' . $listingId . '.json';
    }

    public function submitFeed($feedId, $listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $feedInfo = ListingFeed::where('id', $feedId)->first();
        $store = $listingInfo->store;

        $feedType = $this->getfeedType($feedInfo->feed_type);

        $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
        $file = public_path('feeds/walmart/' . $listingId . '.json');

        $response = $authService->submitFeed($file, $feedType);
        //Log::info('Response ::' . json_encode($response));

        $resultFeedId = $response['feedId'];

        $feedInfo = ListingFeed::where('id', $feedId)->first();

        if ($feedInfo) {
            $feedInfo->feed_id = $resultFeedId;
            $feedInfo->submit_ts = now();
            $feedInfo->number_of_attempts = $feedInfo->number_of_attempts ?? 0 + 1;
            $feedInfo->save();
            Log::info("Feed ID updated successfully.");

        } else {
            Log::error("No ListingFeed found for listing_id: " . $listingId);
        }
    }

    public function submitInventoryFeedAfterCreate($listingId, $listingInfo, $feedInfo)
    {
        Log::info('Submit Inventory Feed After Create');
        $created_by = auth()->user()->name ?? 'admin';
        Log::info('Created By: ' . $created_by);

        $feedData = array(
            'listing_id' => $listingId,
            'status' => 0,
            'feed_type' => FeedTypes::INVENTORY,
            'store_id' => $listingInfo->store_id,
            'number_of_items' => $feedInfo->number_of_items,
            'created_by' => $created_by
        );
        //Log::info('Feed Data: ' . json_encode($feedData));

        $newfeed = ListingFeed::updateOrCreate([
            'listing_id' => $listingId,
            'feed_type' => FeedTypes::INVENTORY
        ], $feedData);

        $this->generateInventoryFeed($listingInfo, $listingId, $newfeed);
        //Log::info('Inventory Feed Generated: ' . json_encode($newfeed));

        $this->submitFeed($newfeed->id, $listingId);
        //Log::info('Inventory Feed Submitted: ' . json_encode($newfeed));

        $newfeed->update([
            'status' => FeedStatus::PROCESSING,
        ]);
    }

    public function getResultFeed(mixed $listingId, $feedId = null)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;

        $authService = new WalmartAuthService($store->seller_id, $store->secret_key);

        $feedInfo = ListingFeed::where('id', $feedId)->first();

        $response = $authService->getFeedStatus($feedInfo->feed_id, $feedInfo->number_of_items);
        Log::info('Feed Status: ' . json_encode($response));

        if ($response['feedStatus'] != 'PROCESSED') return null;

        $feedInfo->update([
            'success_count' => $response['itemsSucceeded'],
            'fail_count' => $response['itemsFailed'],
            'processing_count' => $response['itemsProcessing'],
            'number_of_items' => $response['itemsReceived'],
        ]);

        if ($feedInfo->feed_type == FeedTypes::CREATE) {
            foreach ($response['itemDetails']['itemIngestionStatus'] as $item) {
                $listingItems = ListingItem::where('sku', $item['sku'])->get();
                foreach ($listingItems as $listingItem) {
                    if ($item['ingestionStatus'] && isset($item['itemid'])) {
                        $listingItem->update([
                            'store_sku' => $item['itemid']
                        ]);
                    } else {

                    }

                }
            }

        } else if ($feedInfo->feed_type == FeedTypes::DELETE) {
            foreach ($response['itemDetails']['itemIngestionStatus'] as $item) {
                $listingItems = ListingItem::where('sku', $item['sku'])->get();
                foreach ($listingItems as $listingItem) {
                    if (($item['ingestionStatus']) && $item['ingestionStatus'] == 'SUCCESS') {
                        $listingItem->update(['status' => ListingStatus::Deleted]);
                    }
                }
            }
        }

        if ($response['feedStatus'] == 'PROCESSED' && intval($response['itemsSucceeded']) > 0
            && $feedInfo->feed_type == FeedTypes::CREATE) {
            $this->submitInventoryFeedAfterCreate($listingId, $listingInfo, $feedInfo);
        }

        // save response into folder
        FileUtility::saveToFile(public_path('result-feeds/walmart/' . $feedInfo->id . '.json'), json_encode($response));

        $feedInfo->update([
            'status' => FeedStatus::PROCESSED
        ]);

        /* counting based on store sku*/
        $listing_items = ListingItemLink::select('item_id')->where('listing_id', $listingId)->get();
        if (count($listing_items ?? []) > 0) {
            $successCnt = $failCnt = 0;
            foreach ($listing_items as $item) {

                $listingItem = ListingItem::findOrFail($item->item_id);
                if ($listingItem->store_sku != '') {
                    $listingItem->update([
                        'sync_status' => ListingSyncStatus::Synchronized
                    ]);
//                    $successCnt++;
                } else {
                    $listingItem->update([
                        'sync_status' => ListingSyncStatus::Unsynchronize
                    ]);
//                    $failCnt++;
                }
            }
//            $feedInfo->update([
//                'success_count' => $successCnt,
//                'fail_count' => $failCnt
//            ]);
        }
        /* counting based on store sku*/

        return 'result-feeds/walmart/' . $feedInfo->id . '.json';
    }

    private function generateCreateFeed($listingInfo, $listingId)
    {
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();
        $fileSystem = $listingInfo->store->fileSystem;
        $store = $listingInfo->store;

        $jsonFeed = [
            'MPItemFeedHeader' => [
//                'subset' => 'EXTERNAL',
                'locale' => 'en',
//                'sellingChannel' => 'marketplace',
//                'processMode' => 'REPLACE',
                'version' => '5.0.20250828-17_32_59-api',
                'businessUnit' => 'WALMART_US',
//                'subCategory' => 'electronics_other',
//                'requestId' => $listingId,
//                'requestBatchId' => $listingId . '_1',
            ]
        ];

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


            $product = [
                'Orderable' => [
                    'sku' => $item->sku,
                    'ShippingWeight' => floatval($productObj->product_weight),
                    'fulfillmentLagTime' => 1,
                    'productIdentifiers' => [
                        'productId' => $item->upc,
                        'productIdType' => 'UPC'
                    ],
                    'price' => $price,
                    'endDate' => '2060-01-01T08:00:00Z',
                    'electronicsIndicator' => 'Yes',
                    'shipsInOriginalPackaging' => 'Yes',
                    'startDate' => '2019-01-01T08:00:00Z',
                    'MustShipAlone' => 'No',
                    'country_of_origin_substantial_transformation' => 'China'
                ]
            ];

            $compatibleDevices = [];
            $compatibleBrands = [];
            foreach ($item->devices as $device) {
                if (isset($device->device->brand) && (strtolower($device->device->brand->name) == 'apple' || strtolower($device->device->brand->name) == 'unlocked')) {
                    $compatibleDevices[] = $device->device->name;
                } else {
                    $compatibleDevices[] = $device->device->brand->name . ' ' . $device->device->name;
                }
                if (in_array($device->device->brand->name, $compatibleBrands) == false && strtolower($device->device->brand->name) == 'apple') {
                    $compatibleBrands[] = $listingInfo->store->name;
                } elseif (in_array($device->device->brand->name, $compatibleBrands) == false) {
                    $compatibleBrands[] = $device->device->brand->name;
                }
            }

            $keyFeatures = [];
            foreach ($productObj->productKeyFeatures as $feature) {
                if ($feature->value == '' || !isset($feature->value)) continue;
                $keyFeatures[] = $feature->value;
            }

            $images = ListingItemProductImage::where('listing_item_id', $item->id)->get();
            Log::info('Images: ' . json_encode($images));
            Log::info('File System: ' . json_encode($fileSystem));
            $imagesPath = [];
            $mainImagePath = [];
            foreach ($images as $image) {
                if ($image->productImage && $image->main_image == 0) {
                    $imagesPath[] = $fileSystem->url_base_path . $image->path;
                } else {
                    $mainImagePath = $fileSystem->url_base_path . $image->path;
                }
            }

            $requiredAttributes = $this->getVisibleAttributes($storeTypeCategory->name, $store);

            // skip only the first one for the secondary image
            $visibleAttributes = [
                'productName' => $item->title,
                'brand' => $listingInfo->store->name,
                'shortDescription' => $item->description,
                'keyFeatures' => $keyFeatures,
                'mainImageUrl' => $mainImagePath,
                'productSecondaryImageURL' => $imagesPath,
                'manufacturerPartNumber' => '' . $item->sku,
                'modelNumber' => $item->sku,
//                'msrp' => floatval($productObj->product_mrsp),
                'color' => $productColor->name ?? '',
            ];

            $specifications = $this->getProductSpecifications($listingItem);

            if (in_array('compatibleModels', $requiredAttributes)) $visibleAttributes['compatibleModels'] = $compatibleDevices;
            if (in_array('has_written_warranty', $requiredAttributes)) $visibleAttributes['has_written_warranty'] = $specifications['has_written_warranty'] ?? 'No';
            if (in_array('has_nrtl_listing_certification', $requiredAttributes)) $visibleAttributes['has_nrtl_listing_certification'] = $specifications['has_nrtl_listing_certification'] ?? 'No';
            if (in_array('condition', $requiredAttributes)) $visibleAttributes['condition'] = $specifications['condition'] ?? 'New';
            if (in_array('isProp65WarningRequired', $requiredAttributes)) $visibleAttributes['isProp65WarningRequired'] = $specifications['isProp65WarningRequired'] ?? 'No';
            if (in_array('smallPartsWarnings', $requiredAttributes))
                $visibleAttributes['smallPartsWarnings'] = [
                    $specifications['smallPartsWarnings'] ?? '0 - No warning applicable'
                ];
            if (in_array('netContent', $requiredAttributes))
                $visibleAttributes['netContent'] = [
                    'productNetContentUnit' => 'Each',
                    'productNetContentMeasure' => 1,
                ];
            if (in_array('assembledProductWidth', $requiredAttributes))
                $visibleAttributes['assembledProductWidth'] = [
                    'unit' => 'in',
                    'measure' => $productObj->package_dimensions_width ?? 0,
                ];
            if (in_array('assembledProductHeight', $requiredAttributes))
                $visibleAttributes['assembledProductHeight'] = [
                    'unit' => 'in',
                    'measure' => $productObj->package_dimensions_height ?? 0,
                ];
            if (in_array('assembledProductLength', $requiredAttributes))
                $visibleAttributes['assembledProductLength'] = [
                    'unit' => 'in',
                    'measure' => $productObj->package_dimensions_length ?? 0,
                ];
            if (in_array('compatibleBrands', $requiredAttributes)) $visibleAttributes['compatibleBrands'] = $compatibleBrands;
            if (in_array('cell_phone_case_type', $requiredAttributes))
                $visibleAttributes['cell_phone_case_type'] = $specifications['cell_phone_case_type'] ?? 'Holster';
            if (in_array('msrp', $requiredAttributes)) $visibleAttributes['msrp'] = floatval($productObj->product_mrsp);
            foreach ($requiredAttributes as $attribute) {
                if (!isset($visibleAttributes[$attribute]) && isset($specifications[$attribute])) {
                    $visibleAttributes[$attribute] = $specifications[$attribute] ?? '';
                }
            }

            $product['Visible'][$storeTypeCategory->name] = $visibleAttributes;

            $jsonFeed['MPItem'][] = $product;
        }

        FileUtility::saveToFile(public_path('feeds/walmart/' . $listingId . '.json'), json_encode($jsonFeed));
    }

    private function generateImageUpdateFeed($listingInfo, $listingId)
    {
    }

    private function generateDeleteFeed($listingInfo, $listingId)
    {
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $jsonFeed = [
            'RetireItemHeader' => [
                'version' => '1.0'
            ],
            'RetireItem' => []
        ];

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;

            $product = [
                'sku' => $item->sku
            ];
            $jsonFeed['RetireItem'][] = $product;
        }

        FileUtility::saveToFile(public_path('feeds/walmart/' . $listingId . '.json'), json_encode($jsonFeed));
    }

    private function generateInventoryFeed($listingInfo, $listingId, $feedInfo)
    {
        //create afeed like this {"InventoryHeader":{"version":"1.4"},"Inventory":[{"sku":"Z41-LARWJD","quantity":{"unit":"EACH","amount":500}}]}
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $jsonFeed = [
            'InventoryHeader' => [
                'version' => '1.4'
            ],
            'Inventory' => []
        ];

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;
            $product = [
                'sku' => $item->sku,
                'quantity' => [
                    'unit' => 'EACH',
                    'amount' => $item->quantity
                ]
            ];
            $jsonFeed['Inventory'][] = $product;
        }

        Log::info('Listing Feed Type: ' . $feedInfo->feed_type);
        if ($feedInfo->feed_type == FeedTypes::CREATE) {
            FileUtility::saveToFile(public_path('feeds/walmart/I_' . $listingId . '.json'), json_encode($jsonFeed));
        } else {
            FileUtility::saveToFile(public_path('feeds/walmart/' . $listingId . '.json'), json_encode($jsonFeed));
        }
    }

    private function generatePriceFeed($listingInfo, $listingId)
    {
        // create a feed like this {"PriceHeader":{"version":"1.7"},"Price":[{"sku":"D93-JNRVJY","pricing":[{"currentPrice":{"currency":"USD","amount":11.99},"currentPriceType":"BASE"}]}]}

        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $jsonFeed = [
            'PriceHeader' => [
                'version' => '1.7'
            ],
            'Price' => []
        ];

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;
            $product = [
                'sku' => $item->sku,
                'pricing' => [
                    [
                        'currentPrice' => [
                            'currency' => 'USD',
                            'amount' => $item->price
                        ],
                        'currentPriceType' => 'BASE'
                    ]
                ]
            ];
            $jsonFeed['Price'][] = $product;
        }

        FileUtility::saveToFile(public_path('feeds/walmart/' . $listingId . '.json'), json_encode($jsonFeed));
    }

    public function getOrders($store)
    {
        try {
            if (!isset($store->seller_id) || !isset($store->secret_key)) {
                Log::info("Error in Walmart Orders : No Secret Key Found");
                return null;
            }
            $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
            $last_synchronization = OrderSynchronization::where('store_id', $store->id)->where('synchronization_type', OrderSyncronisationType::ORDER)->orderBy('last_order_update_date', 'desc')->orderBy('updated_at', 'desc')->first();
            $filterStartDate = OrderUtility::getLastSyncDate($store->id, OrderSyncronisationType::ORDER);
            $filterStartDateFormatted = date('Y-m-d\TH:i:s\Z', strtotime($filterStartDate));

            Log::info("Walmart Orders Start Date : " . json_encode($last_synchronization));

            $response = $authService->getOrders($filterStartDateFormatted, $last_synchronization->cursor ?? null);

            if (!isset($response)) {
                Log::info("Error in Walmart Orders : ");
                return null;
            }

//            Log::info('Walmart Orders Response: ' . json_encode($response));
            if (isset($response['list']['meta']['totalCount']) && $response['list']['meta']['totalCount'] == 0) {
                Log::info("No Orders Found");
                return null;
            }

            $nextCursor = $response['list']['meta']['nextCursor'];

            $created_by = auth()->user()->name ?? 'admin';
            $synchronizeArr = [
                'store_id' => $store->id,
                'synchronization_type' => OrderSyncronisationType::ORDER,
                'filter_start_date' => $filterStartDate,
                'filter_end_date' => now(),
                'created_by' => $created_by,
                'cursor' => $nextCursor
            ];
            Log::info('Order Synchronization: ' . json_encode($synchronizeArr));
            $order_synchronization = OrderSynchronization::create($synchronizeArr);

            if (isset($response['list']['elements']['order'])) {
                $noOfOrder = 0;
                $last_order_update_date = $order_synchronization->last_order_update_date ?? null;
                foreach ($response['list']['elements']['order'] as $sorder) {
                    $order = Order::where('order_id', $sorder['customerOrderId'])->where('purchase_order_id', $sorder['purchaseOrderId'])->first();

                    $persist = false;
                    $statusChange = false;

                    if ($order == null) {
                        $order = Order::updateOrCreate([
                            'order_id' => $sorder['customerOrderId'],
                            'purchase_order_id' => $sorder['purchaseOrderId'],
                        ], [
                            'order_id' => $sorder['customerOrderId'],
                            'purchase_order_id' => $sorder['purchaseOrderId'],
                            'order_date' => date('Y-m-d H:i:s', $sorder['orderDate'] / 1000),
                            'order_status' => OrderStatus::Synchronizing,
                            'store_id' => $store->id,
                            'order_synchronization_id' => $order_synchronization->id
                        ]);
                        $persist = true;
                        $statusChange = true;
                    } else {
                        // TODO Need To implement status change
                        $statusChange = true;
                    }

                    if ($statusChange) {
                        OrderUtility::mapWalmartOrders($order, $sorder, $store);
                    }

                    if ($last_order_update_date === null || strtotime($sorder['orderDate'] / 1000) > strtotime($last_order_update_date)) {
                        $last_order_update_date = date('Y-m-d H:i:s', $sorder['orderDate'] / 1000);
                    }
                    $noOfOrder++;
                }

                if (isset($last_synchronization) && strtotime($last_synchronization->last_order_update_date) > strtotime($last_order_update_date)) {
                    $last_order_update_date = date('Y-m-d H:i:s', strtotime($last_synchronization->last_order_update_date));
                }
                $order_synchronization->update(['number_of_items' => $noOfOrder, 'last_order_update_date' => $last_order_update_date]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("Error in Walmart Orders: " . $e->getMessage());
            return null;
        }
    }

    public function getLatestOrder($store)
    {
        try {
            if (!isset($store->seller_id) || !isset($store->secret_key)) {
                Log::info("Error in Walmart Orders : No Secret Key Found");
                return null;
            }
            $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
            $filterStartDateFormatted = now()->subDays(2)->toIso8601String();

            $response = $authService->getOrders($filterStartDateFormatted, null, 1);

            if (!isset($response)) {
                Log::info("Error in Walmart Orders : ");
                return null;
            }

//            Log::info('Walmart Orders Response: ' . json_encode($response));
            if (isset($response['list']['meta']['totalCount']) && $response['list']['meta']['totalCount'] == 0) {
                Log::info("No Orders Found");
                return null;
            }

            $data = [];
            if (isset($response['list']['elements']['order'])) {
                foreach ($response['list']['elements']['order'] as $sorder) {
                    $data['order_id'] = $sorder['customerOrderId'];
                    $data['order_date'] = date('Y-m-d H:i:s', $sorder['orderDate'] / 1000);
                }
            }

            return $data;

        } catch (\Exception $e) {
            Log::error("Error in Walmart Orders: " . $e->getMessage());
            return null;
        }
    }

    public function syncAttributes($store)
    {
        // TODO: Implement syncAttributes() method.
    }

    public function getfeedType($feedType)
    {
        switch ($feedType) {
            case FeedTypes::CREATE:
                return 'MP_ITEM';
            // case FeedTypes::IMAGE_UPDATE:
            //     return 'MPItem';
            case FeedTypes::DELETE:
                return 'RETIRE_ITEM';
            case FeedTypes::SHIPMENT_UPDATE:
                return 'RETIRE_ITEM';
            case FeedTypes::INVENTORY:
                return 'inventory';
            case FeedTypes::PRICE:
                return 'price';
            default:
                return '';
        }
    }

    private function callShipmentUpdateFeed($feedId)
    {
        $listingInfo = ListingFeed::where('id', $feedId)->first();
        $store = $listingInfo->store;

        $orderItems = OrderItem::where('order_id', $listingInfo->listing_id)->get();

        foreach ($orderItems as $item) {
            $orderLine = [
                'lineNumber' => 1,
                'orderLineStatuses' => [
                    'orderLineStatus' => [
                        [
                            'cancellationReason' => 'CANCEL_REQUESTED_BY_CUSTOMER',
                            'statusCode' => 'CANCELLED',
                            'status' => 'CANCELLED',
                            'statusQuantity' => [
                                'amount' => 10
                            ]
                        ]
                    ]
                ]
            ];
        }

        $cancelOrder = [
            'orderCancellation'
        ];
    }

    private function callOrderCancelFeed(mixed $feedId)
    {

    }

    public function shipmentUpdate($mainOrderOperation, $orderOperation, $store)
    {
        $finalData = [];
        $count = 1;
        foreach ($orderOperation->order->orderItem as $item) {
            Log::info('Itema data for the operation :: ' . json_encode($item));
            $orderLine = [
                'lineNumber' => $count,
                'orderLineStatuses' => [
                    'orderLineStatus' => [
                        [
                            'status' => 'Shipped',
                            'statusQuantity' => [
                                'unitOfMeasurement' => 'EACH',
                                'amount' => $item->quantity
                            ],
                            'trackingInfo' => [
                                "shipDateTime" => date('Y-m-d\TH:i:sP'),
                                "carrierName" => [
                                    "carrier" => $mainOrderOperation->carrier->name,
                                ],
                                "methodCode" => 'Standard',
                                "trackingNumber" => $mainOrderOperation->tracking_number,
                            ]
                        ]
                    ]
                ]
            ];

            $finalData['orderShipment']['orderLines']['orderLine'][] = $orderLine;
            $count++;
        }

        Log::info('Final data for the operation change :: ' . json_encode($finalData));

        $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
        $response = $authService->shipOrder($orderOperation->order->purchase_order_id, $finalData);

        Log::info('Walmart ship order response: ' . json_encode($response));

        if (isset($response)) {
            $orderResp = $response['order'];
            Log::info('Walmart Ship order response: ' . json_encode($orderResp));
            if (isset($orderResp) && isset($orderResp['purchaseOrderId']) && $orderResp['purchaseOrderId'] == $orderOperation->order->purchase_order_id) {
                Log::info('Walmart ship order successful: ' . json_encode($orderResp));
                $orderOperation->update(['status' => OrderOperationStatus::MarkAsShipped, 'ship_date' => now()]);
                if (isset($orderOperation->order_id)) {
                    $order = Order::findOrFail($orderOperation->order_id);
                    $order->update(['order_status' => OrderStatus::Shipped]);
                }

                if (isset($orderOperation->order->orderItem)) {
                    foreach ($orderOperation->order->orderItem as $o_item) {
                        $item_qty = $o_item->quantity;
                        $orderItem = OrderItem::findOrFail($o_item->id);
                        $orderItem->update(['shipped_quantity' => $item_qty]);
                    }
                }
                $success = true;
            } else {
                Log::info('Walmart ship order error: ' . json_encode($response));
                $success = false;
            }
        } else {
            Log::info('Walmart ship order error: No response received');
            $success = false;
        }
        return $success;
    }

    private function getVisibleAttributes($categoryName, $store)
    {
        $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
        $specifications = $authService->getProductSpecifications('MP_ITEM', '5.0.20250828-17_32_59-api', $categoryName);
        if (isset($specifications)) {
            $specMap = $specifications['schema']['properties']['MPItem']['items']['properties']['Visible']['properties'] ?? [];
            foreach ($specMap as $key => $specification) {
                $allProperties = [];
                if (isset($specification['properties'])) {
                    foreach ($specification['properties'] as $propKey => $property) {
                        $allProperties[] = $propKey;
                    }
                    return $allProperties;
                }
            }
        }
        return [];
    }

    private function getProductSpecifications($listingItem)
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
}
