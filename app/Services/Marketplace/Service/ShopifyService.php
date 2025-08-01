<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedStatus;
use App\Constants\FeedTypes;
use App\Constants\ListingSyncStatus;
use App\Jobs\ProductInterfaces;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItem;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use App\Models\ProductInterface;
use App\Models\ProductTags;
use App\Models\ProductTypeKeyword;
use App\Services\Auth\ShopifyAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use Illuminate\Support\Facades\Log;

class ShopifyService implements MarketplaceServiceInterface
{
    public function generateFeed($listingId,$feedId = null)
    {

    }

    public function submitFeed($feedId, $listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();

        $feedInfo = ListingFeed::where('id', $feedId)->first();

        $fileSystem = $listingInfo->store->fileSystem;
        $store = $listingInfo->store;
        switch ($feedInfo->feed_type) {
            case FeedTypes::CREATE:
                $this->SubmitCreateFeed($listingInfo,$feedInfo, $listingId);
                break;
            case FeedTypes::SHIPMENT_UPDATE:
                $this->submitShipmentUpdateFeed($listingInfo, $listingId);
                break;
            case FeedTypes::DELETE:
                $this->submitDeleteFeed($listingInfo, $listingId);
                break;
            case FeedTypes::INVENTORY:
                $this->submitInventoryFeed($listingInfo, $feedInfo, $listingId);
                break;
            case FeedTypes::PRICE:
                $this->submitPriceFeed($listingInfo, $feedInfo, $listingId);
                break;
            default:
                break;
        }
    }

    public function SubmitCreateFeed($listingInfo,$feedInfo, $listingId)
    {
        $fileSystem = $listingInfo->store->fileSystem;
        $store = $listingInfo->store;

        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();
        foreach ($listingItems as $litem) {
            $colorOption = [];
            $productOptions = [];
            $item = $litem->item;
            $listingItem = $litem->listingItem;
            $storeProduct = $listingItem->storeProduct;
            $productData = $listingItem->storeProduct->product;
            //Log::info('Listing Item: ' . json_encode($listingItem));
//
//            $collectionIds = $this->getCollectionIds($listingItem->devices);
//            continue;

            $descriptionHtml = '';
            $descriptionHtml .= '<ul>';
            foreach ($storeProduct->features as $feature) {
                $safeText = htmlspecialchars($feature->value, ENT_QUOTES | ENT_XML1, 'UTF-8'); // Escapes special characters
                if (!empty(trim($safeText))) {
                    $descriptionHtml .= '<li>' . $safeText . '</li>';
                }
            }

            $descriptionHtml .= '</ul>';
            $descriptionHtml .= '<h2>Product Description</h2>';
            $descriptionHtml .= '<p>' . $listingItem->description . '</p>';

            $tags = [];
            $tags = $this->getAllTags($productData, $tags);
            //Log::info('Tags: ' . json_encode($tags));
            $tagsString = implode(',', $tags);

            $productOptions[] = [
                'name' => 'Color',
                'values' => [
                    //['name' => $productData->productColor->name,]
                    ['name' => 'White',]
                ]
            ];

            $images = ListingItemProductImage::where('listing_item_id', $item->id)->get();
            $files = [];
            foreach ($images as $image) {
                $files[] = [
                    'contentType' => 'IMAGE',
                    'originalSource' => $fileSystem->url_base_path . $image->path,
                ];
            }

            if (env('APP_ENV') == 'local') {
                $files = [
                    [
                        'contentType' => 'IMAGE',
                        'originalSource' => 'http://uzve.com/ftp_images/store-images/f5ebde6c-2bcc-03f5-4d62-ef4692f87314/70cfd4a9-0163-4d8e-b248-5b5b19298583_cdf4e35c-b0f0-48b6-b3a3-9bfd6ddfca9a.jpg',
                    ],[
                        'contentType' => 'IMAGE',
                        'originalSource' => 'http://uzve.com/ftp_images/store-images/f5ebde6c-2bcc-03f5-4d62-ef4692f87314/70cfd4a9-0163-4d8e-b248-5b5b19298583_f637dfb1-0716-433a-9708-a55b8c109a56.jpg',
                    ],[
                        'contentType' => 'IMAGE',
                        'originalSource' => 'http://uzve.com/ftp_images/store-images/f5ebde6c-2bcc-03f5-4d62-ef4692f87314/70cfd4a9-0163-4d8e-b248-5b5b19298583_e06629ca-3b9e-4855-a169-ab2d2daf604d.jpg',
                    ],[
                        'contentType' => 'IMAGE',
                        'originalSource' => 'http://uzve.com/ftp_images/store-images/f5ebde6c-2bcc-03f5-4d62-ef4692f87314/70cfd4a9-0163-4d8e-b248-5b5b19298583_e032abc4-e121-4048-8004-3fafd6e4d0f7.jpg',
                    ],[
                        'contentType' => 'IMAGE',
                        'originalSource' => 'http://uzve.com/ftp_images/store-images/f5ebde6c-2bcc-03f5-4d62-ef4692f87314/70cfd4a9-0163-4d8e-b248-5b5b19298583_6974c6a4-1631-420c-9788-83b3c9bbde05.jpg',
                    ],
                ];
            }

            $productNewData = [
                'productSet' => array_filter([
                    'title' => $listingItem->title,
                    'descriptionHtml' => $descriptionHtml,
                    'vendor' => $store->name,
                    'productType' => $productData->productType->name,
                    'tags' => $tagsString,
                    'productOptions' => $productOptions,
                    'files' => $files,
                    'variants' => [
                        [
                            'price' => $listingItem->price,
                            'sku' => $listingItem->sku,
                            'barcode' => $listingItem->upc,
                            'optionValues' => [
                                [
                                    'optionName' => 'Color',
                                    //'name' => $productData->productColor->name,
                                    'name' => 'White',
                                ]
                            ],
                            'inventoryItem' => [
                                'sku' => $listingItem->sku,
                                'tracked' => true,
                            ]
                        ]
                    ],
                ], function ($value) {
                    return !is_null($value);
                }),
            ];

            //Log::info('Product Data: ' . json_encode($productNewData));

            $shopifyAuthService = new ShopifyAuthService($store->secret_key, $store->service_url);
            $response = $shopifyAuthService->saveProduct($productNewData);
//             get handle and id from response and save in listing_item table
            //Log::info('Product: ' . json_encode($response));
//            $productSetId = $response['productSet']['productSetOperation']['id'];
//
//            $feedInfo->update([
//                'feed_id' => $produc $feedInfo->update([
////                'feed_id' => $productSetId
////            ]);
//            ]);
            if(isset($response['data']) && empty($response['data']['userErrors'])) {

                $productId = $response['data']['productSet']['product']['id'];
                $variantId = $response['data']['productSet']['product']['variants']['nodes'][0]['id'];
                Log::info('Variant ID: ' . $variantId);
                $listingItem->update([
                    'store_sku' => $productId,
                    'variant_id' => $variantId
                ]);

                $this->submitInventoryFeed($listingInfo, $feedInfo, $listingId);

                /*$listing_items = ListingItemLink::select('item_id')->where('listing_id', $listingId)->get();
                if (count($listing_items ?? []) > 0) {
                    $successCnt = $failCnt = 0;
                    foreach ($listing_items as $item) {

                        $listingItem = ListingItem::findOrFail($item->item_id);
                        if($listingItem->store_sku != ''){
                            $listingItem->update([
                                'sync_status' => ListingSyncStatus::Synchronized
                            ]);
                            $successCnt++;
                        }else{
                            $listingItem->update([
                                'sync_status' => ListingSyncStatus::Unsynchronize
                            ]);
                            $failCnt++;
                        }
                    }
                    $feedInfo->update([
                        'status' => FeedStatus::PROCESSED,
                        'success_count' => $successCnt,
                        'fail_count' => $failCnt,
                        'number_of_attempts' => $feedInfo->number_of_attempts ?? 0 + 1,
                        'warning_count' => 0,
                        'procesing_count' => 0
                    ]);
                }*/
            }
        }
    }

    public function getResultFeed(mixed $listingId,$feedId = null)
    {
//        $feedInfo = ListingFeed::where('id',$feedId)->first();
//        $productSetID = $feedInfo['feed_id'];

    }

    public function syncAttributes($store)
    {

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

    public function getOrders($store, $sync_start_date)
    {
        if (!isset($store->secret_key)) {
            Log::info("Error in Shopify Orders : No Secret Key Found");
            return null;
        }
        $this->authService = new ShopifyAuthService($store->secret_key, $store->service_url);
        $response = $this->authService->getOrders($sync_start_date);

        return $response;
    }

    private function getCollectionIds($devices)
    {
        foreach ($devices as $device) {
            $deviceData = $device->device;
            //Log::info('Device: ' . json_encode($device->device));
        }
    }

    private function submitShipmentUpdateFeed($listingInfo, $listingId)
    {
    }

    private function submitDeleteFeed($listingInfo, $listingId)
    {
    }


    public function submitInventoryFeed($listingInfo, $feedInfo, $listingId)
    {

        $store = $listingInfo->store;
        $this->authService = new ShopifyAuthService($store->secret_key, $store->service_url);

        $listingItems = ListingItemLink::with('listingItem')->where('listing_id', $listingId)->cursor();
        $cnt = 0;
        $inventoryUpdates = [];
        foreach ($listingItems as $listingItem) {

            $itemDetail = $listingItem->item;
            $variantId = $itemDetail->variant_id;
            $quantity = (isset($itemDetail->quantity) && $itemDetail->quantity > 0) ? $itemDetail->quantity : 20;

            if($variantId != ''){
                // Get required IDs
                $inventoryItemId = $this->authService->getInventoryItemId($variantId);
                $locationId = $this->authService->getLocationId();

                if (!$inventoryItemId || !$locationId) {
                    Log::info('Invalid inventory item or location for variant '.$variantId.'"]');
                    return false;
                }

                // Prepare inventory update array
                $inventoryUpdates['locationId'] = $locationId;
                $inventoryUpdates['changes'] = [
                    'inventoryItemId' => $inventoryItemId,
                    'locationId' => $locationId,
                    'delta' => $quantity
                ];

                $cnt++;
            }
        }

        // Send the batch update request
        $response = $this->authService->updateInventory($inventoryUpdates);

        if(isset($response['data']) && empty($response['data']['userErrors'])) {

            $listing_items = ListingItemLink::select('item_id')->where('listing_id', $listingId)->get();
            if (count($listing_items ?? []) > 0) {
                $successCnt = $failCnt = 0;
                foreach ($listing_items as $item) {

                    $listingItem = ListingItem::findOrFail($item->item_id);
                    if($listingItem->store_sku != ''){
                        $listingItem->update([
                            'sync_status' => ListingSyncStatus::Synchronized
                        ]);
                        $successCnt++;
                    }else{
                        $listingItem->update([
                            'sync_status' => ListingSyncStatus::Unsynchronize
                        ]);
                        $failCnt++;
                    }
                }
                $feedInfo->update([
                    'status' => FeedStatus::PROCESSED,
                    'success_count' => $successCnt,
                    'fail_count' => $failCnt,
                    'number_of_attempts' => $feedInfo->number_of_attempts ?? 0 + 1,
                    'warning_count' => 0,
                    'procesing_count' => 0
                ]);
            }
        }else{

            $feedInfo->update([
                'success_count' => 0,
                'fail_count' => 1,
                'error_text' => isset($response['errors'][0]['message']) ? $response['errors'][0]['message'] : '',
                'number_of_attempts' => ($feedInfo->number_of_attempts) ? $feedInfo->number_of_attempts + 1 : 0,
                'warning_count' => 0,
                'procesing_count' => 0
            ]);
        }

        return response()->json($response);
    }

    public function submitPriceFeed($listingInfo, $feedInfo, $listingId)
    {
        $store = $listingInfo->store;
        $this->authService = new ShopifyAuthService($store->secret_key, $store->service_url);

        $listingItems = ListingItemLink::with('listingItem')->where('listing_id', $listingId)->cursor();

        $priceUpdatesData = [];
        foreach ($listingItems as $listingItem) {

            $itemDetail = $listingItem->item;
            $variantId = $itemDetail->variant_id;
            $price = $itemDetail->price;

            if($variantId != ''){

                // Prepare price update array
                $priceUpdatesData['productId'] = $itemDetail->store_sku;
                $priceUpdatesData['variant'] = [
                    [
                    'id' => $variantId,
                    'price' => $price
                    ]
                ];
            }
        }
        // Send the batch update request
        $response = $this->authService->updatePrice($priceUpdatesData);

        if(isset($response['data']) && empty($response['data']['userErrors'])) {

            $listing_items = ListingItemLink::select('item_id')->where('listing_id', $listingId)->get();
            if (count($listing_items ?? []) > 0) {
                $successCnt = $failCnt = 0;
                foreach ($listing_items as $item) {

                    $listingItem = ListingItem::findOrFail($item->item_id);
                    if($listingItem->store_sku != ''){
                        $listingItem->update([
                            'sync_status' => ListingSyncStatus::Synchronized
                        ]);
                        $successCnt++;
                    }else{
                        $listingItem->update([
                            'sync_status' => ListingSyncStatus::Unsynchronize
                        ]);
                        $failCnt++;
                    }
                }
                $feedInfo->update([
                    'status' => FeedStatus::PROCESSED,
                    'success_count' => $successCnt,
                    'fail_count' => $failCnt,
                    'number_of_attempts' => ($feedInfo->number_of_attempts) ? $feedInfo->number_of_attempts + 1 : 0,
                    'warning_count' => 0,
                    'procesing_count' => 0
                ]);
            }
        }else{

            $feedInfo->update([
                'success_count' => 0,
                'fail_count' => 1,
                'error_text' => isset($response['errors'][0]['message']) ? $response['errors'][0]['message'] : '',
                'number_of_attempts' => ($feedInfo->number_of_attempts) ? $feedInfo->number_of_attempts + 1 : 0,
                'warning_count' => 0,
                'procesing_count' => 0
            ]);
        }

        return response()->json($response);
    }

    public function updateFullfillment($orderId, $mainorderOperation)
    {
        if($orderId) {
            Log::info("Order ID : ". json_encode($orderId));
            $shipmentDetail = OrderOperation::with('operationItem','order','order.customerAddress','carrierService','store','operationItem.orderItem','operationItem.orderItem.product')->where('order_id',$orderId)->first();

            Log::info("SHipment Detail : ". json_encode($shipmentDetail));

            $store = $shipmentDetail->store;
            $this->authService = new ShopifyAuthService($store->secret_key, $store->service_url);
            $orderId = "gid://shopify/Order/".$shipmentDetail->order->order_id;
            //$locationId = $this->authService->getLocationId();
            $forders_response = $this->authService->getFullfillmentOrders($orderId);

            /*echo '<pre>';
            print_r($forders_response);
            echo '</pre>';die;*/

            $fulfillmentOrder = $forders_response['data']['order']['fulfillmentOrders']['nodes'][0] ?? null;

            if (!$fulfillmentOrder) {
                return response()->json(['error' => 'No fulfillment order found'], 404);
            }


            $fulfillmentOrderId = $fulfillmentOrder['id'];
            $lineItems = array_map(function ($edge) {
                return [
                    'id' => $edge['node']['id'],
                    'quantity' => $edge['node']['lineItem']['quantity']
                ];
            }, $fulfillmentOrder['lineItems']['edges']);

            /*$orderItems = [];
            if(count($shipmentDetail->operationItem ?? []) > 0){
                foreach($shipmentDetail->operationItem as $oitem){
                    $order_item = $oitem->orderItem;
                    if($order_item){
                        $orderItems[] = [
                            "id" => $order_item->id1,
                            "quantity" => $order_item->quantity
                        ];
                    }
                }
            }*/
            $variables = [
                "fulfillment" => [
                    "lineItemsByFulfillmentOrder" => [
                        "fulfillmentOrderId" => $fulfillmentOrderId,
                        "fulfillmentOrderLineItems" => $lineItems
                    ],
                    "notifyCustomer" => true,
                    "trackingInfo" => [
                        "company" => $mainorderOperation->carrierService->ShippingCarrier->name,
                        "number" => $mainorderOperation->tracking_number
                    ]
                ]
            ];

            /*echo '<pre>';
            print_r($variables);
            echo '</pre>';die;*/


            Log::info("Variables : ". json_encode($variables));

            $response = $this->authService->updateFullfillment($variables);

            return $response;

        }
    }


    public function refundOrder($order_id,$store)
    {
        if($order_id) {
            Log::info("Order ID : ". $order_id);

            $this->authService = new ShopifyAuthService($store->secret_key, $store->service_url);
            $orderId = "gid://shopify/Order/".$order_id;
            $transactionData = $this->authService->getTransactions($orderId);

            if($orderId){
                $orderDetail = Order::with('orderItem')->where('order_id',$order_id)->first();
                $items= $refundMethod = $transactions =  [];
                if($orderDetail){
                    if(isset($orderDetail->orderItem)){
                        foreach($orderDetail->orderItem as $item){
                            $items[] = [
                                'lineItemId' => $item->id1,
                                'quantity' => $item->quantity
                            ];

                            /*$refundMethod = [
                                'storeCreditRefund' => [
                                    'amount' => [
                                        'amount' => (string)$item->item_price
                                    ]
                                ]
                            ];*/
                        }
                    }
                }

                if(!empty($transactionData)){
                    foreach($transactionData as $transaction){
                        $transactions[] = [
                            'orderId' => $orderId,
                            'amount' => (string)$transaction['amount'],
                            'kind' => 'REFUND',
                            'gateway' => $transaction['gateway'],
                            'parentId' => $transaction['id'],
                        ];
                    }
                }
                $variables = [
                    'input' => [
                        'orderId' => $orderId,
                        'notify' => true,
                        'refundLineItems' => $items,
                        //'refundMethods' => $refundMethod,
                        'transactions' => $transactions
                    ]
                ];
                /*echo '<pre>';
                print_r($variables);
                echo '</pre>';die;*/
                Log::info("Refund Request : ". json_encode($variables));
                $refundId = $this->authService->createRefund($orderId, $variables);
                if($refundId){
                    //$refund_response = $this->authService->refundOrder($refundId);
                    return $refundId;
                }else{
                    return false;
                }

            }

        }
    }


}
