<?php

namespace App\Utils\order;

use App\Constants\OrderOperationType;
use App\Constants\OrderOperationStatus;
use App\Constants\OrderStatus;
use App\Helpers\Common;
use App\Models\Catalog;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerPhone;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use App\Models\OrderOperationItem;
use App\Models\OrderSynchronization;
use App\Models\ShippingPackage;
use Illuminate\Support\Facades\Log;

class OrderUtility
{
    public static function getLastSyncDate($store_id, $synchronizationType)
    {
        $lastOrderUpdateDate = OrderSynchronization::where('store_id', $store_id)
            ->where('synchronization_type', $synchronizationType)
            ->orderBy('last_order_update_date', 'desc')->first();

        if ($lastOrderUpdateDate) {
            $date = \Carbon\Carbon::parse($lastOrderUpdateDate->last_order_update_date ?? $lastOrderUpdateDate->filter_end_date, 'EST')->addSeconds(10);
            return $date->toDateTimeString();
        } else {
            // get last 30 days
            return now()->subDays(1)->toDateTimeString();
        }
    }

    public static function mapWalmartOrders($order, mixed $sorder, $store)
    {
        $firstname = $lastname = '';
        $orderTotal = 0;
        $taxTotal = 0;
        $discountTotal = 0;
        $orderTotals = 0;
        $order_date = date('Y-m-d H:i:s', $sorder['orderDate'] / 1000);

        $totalWeight = 0;
        foreach ($sorder['orderLines']['orderLine'] as $line) {
            $itemTotal = 0;
            $sku = preg_split('/[_-]/', $line['item']['sku'])[0];
            $catalog = Catalog::where('product_sku', $sku)->first();
            if ($catalog) {
                $totalWeight += $catalog->product_weight;
            }
            foreach ($line['charges']['charge'] as $charge) {
                $amount = (float)$charge['chargeAmount']['amount'];

                if ($charge['chargeType'] === 'PRODUCT') {
                    $itemTotal += $amount;
                }

                if ($charge['chargeType'] === 'PRODUCT_TAX' || $charge['chargeType'] === 'SHIPPING_TAX') {
                    $taxTotal += $amount;
                }

                if ($charge['chargeType'] === 'SHIPPING') {
                    $orderTotal += $amount;
                }

                if ($charge['chargeType'] === 'DISCOUNT') {
                    $discountTotal += $amount; // Discount is negative, so adding reduces total
                }
            }
            $orderTotal += $itemTotal;
        }

        $orderTotals = $orderTotal + $taxTotal - $discountTotal;

        if (isset($sorder['shippingInfo']['postalAddress']['name'])) {
            $nameArr = explode(' ', $sorder['shippingInfo']['postalAddress']['name']);
            $firstname = $nameArr[0];
            $lastname = $nameArr[1];
        }

        $customerData = [
            'first_name' => $firstname,
            'last_name' => $lastname,
            'full_name' => $sorder['shippingInfo']['postalAddress']['name'],
        ];

        $customer_detail = Customer::updateOrCreate([
            'full_name' => $sorder['shippingInfo']['postalAddress']['name'],
        ], $customerData);

        $customer_address_data = [
            'customer_id' => $customer_detail->id,
            'address_line_1' => $sorder['shippingInfo']['postalAddress']['address1'],
            'address_line_2' => $sorder['shippingInfo']['postalAddress']['address2'] ?? '',
            'city' => $sorder['shippingInfo']['postalAddress']['city'],
            'state' => $sorder['shippingInfo']['postalAddress']['state'],
            'zip' => $sorder['shippingInfo']['postalAddress']['postalCode'],
            'country' => $sorder['shippingInfo']['postalAddress']['country'],
            'address_combined' => $sorder['shippingInfo']['postalAddress']['address1'] . ' ' . $sorder['shippingInfo']['postalAddress']['address2'],
        ];

        Log::info("Data " . json_encode($customer_address_data));

        CustomerAddress::updateOrCreate([
            'customer_id' => $customer_detail->id,
            'address_combined' => $sorder['shippingInfo']['postalAddress']['address1'] . ' ' . $sorder['shippingInfo']['postalAddress']['address2'],
        ], $customer_address_data);

        $customer_address = CustomerAddress::where('customer_id', $customer_detail->id)
            ->where('address_combined', $sorder['shippingInfo']['postalAddress']['address1'] . ' ' . $sorder['shippingInfo']['postalAddress']['address2'])
            ->first();

        if (isset($sorder['shippingInfo']['phone']) && $sorder['shippingInfo']['phone'] != null) {
            $customer_phone_data = [
                'customer_id' => $customer_detail->id,
                'phone_no' => $sorder['shippingInfo']['phone'],
            ];

            CustomerPhone::updateOrCreate($customer_phone_data, $customer_phone_data);
        }

        $orderData = [
            //'order_date' => date('Y-m-d H:i:s', $sorder['orderDate'] / 1000),
            //'order_total' => $orderTotals,
            'buyer_first_name' => $firstname,
            'buyer_last_name' => $lastname,
            //'buyer_email' => $sorder['customerEmailId'],
            'customer_id' => $customer_detail->id,
            'purchase_order_id' => $sorder['purchaseOrderId'],
            'shipping_address_id' => $customer_address->id,
        ];

        $order->update($orderData);

        $orderOperationData = [
            'order_id' => $order->id,
            'insurance_value' => $orderTotals,
            'status' => OrderOperationStatus::Created,
            'signature_confirmation' => 1,
            'store_id' => $store->id,
            'shipping_package_id' => ShippingPackage::getDefaultShippingPackage(),
            'operation_type' => OrderOperationType::OrderShipment,
            'label_creation_type' => 1,
            'print_to_file' => 0,
            'seller_provides_return_label' => 0,
            'order_synchronization_id' => $order->order_synchronization_id,
            'expected_delivery_date' => date('Y-m-d H:i:s', $sorder['shippingInfo']['estimatedDeliveryDate'] / 1000),
            'expected_shipment_date' => date('Y-m-d H:i:s', $sorder['shippingInfo']['estimatedShipDate'] / 1000),
        ];

        $order_operation_detail = OrderOperation::updateOrCreate([
            'order_id' => $order->id,
        ], $orderOperationData);

        $ordersItems = $sorder['orderLines']['orderLine'];
        //duplicate the order items
        if (!empty($ordersItems)) {
            foreach ($ordersItems as $item) {
                Log::info("Order Item: " . json_encode($item));
                if ($item['item']['sku'] != '') {
                    $itemDetail = ListingItem::with('storeProduct', 'storeProduct.product')->where('sku', $item['item']['sku'])->first();

                    if (isset($itemDetail)) {

                        // sale count update
                        $sku = $item['item']['sku'];
                        Common::updateListingItemSaleDetail($sku, $itemDetail);

                        $orderItemData = [
                            'title' => $item['item']['productName'],
                            'order_id' => $order->id,
                            'product_id' => $itemDetail->storeProduct->product->product_id ?? null,
                            'client_id' => $itemDetail->client ?? null,
                            'listing_item_id' => $itemDetail->id ?? null,
                            'sku' => $item['item']['sku'],
                            //'store_sku' => $item['item']['upc'],
                            'quantity' => $item['orderLineQuantity']['amount'],
                            'id1' => '',
                            'item_price' => ($item['charges']['charge'][0]['chargeType'] == 'PRODUCT') ? $item['charges']['charge'][0]['chargeAmount']['amount'] : 0,
                            'shipping_price' => ($item['charges']['charge'][0]['chargeType'] == 'SHIPPING') ? $item['charges']['charge'][0]['chargeAmount']['amount'] : 0,
                            'item_tax' => ($item['charges']['charge'][0]['chargeType'] == 'PRODUCT_TAX') ? $item['charges']['charge']['tax'][0]['taxAmount']['amount'] : 0,
                            'shipping_tax' => ($item['charges']['charge'][0]['chargeType'] == 'SHIPPING_TAX') ? $item['charges']['charge'][0]['tax']['taxAmount']['amount'] : 0,
                            'shipping_quantity' => $item['orderLineQuantity']['amount'],
                        ];

                        $order_item = OrderItem::updateOrCreate([
                            'order_id' => $order->id,
                            'sku' => $item['item']['sku'],
                        ], $orderItemData);

                        if ($order_item->id) {

                            $orderOperationItemData = [
                                'order_item_id' => $order_item->id,
                                'order_operation_id' => $order_operation_detail->id,
                                'client_id' => $itemDetail->client,
                                'sku' => $item['item']['sku'],
                                'quantity' => $item['orderLineQuantity']['amount'],
                                'sync_status' => 6
                            ];

                            $order_operation_item = OrderOperationItem::updateOrCreate([
                                'order_item_id' => $order_item->id,
                                'order_operation_id' => $order_operation_detail->id
                            ], $orderOperationItemData);

                            if (isset($itemDetail->storeProduct->product->shipping_package_id) && $itemDetail->storeProduct->product->shipping_package_id != '') {
                                $package_id = $itemDetail->storeProduct->product->shipping_package_id;
                                $order_operation_detail->update(['shipping_package_id' => $package_id]);
                            }
                        }
                    } else {
                        $sku = preg_split('/[_-]/', $item['item']['sku'])[0];
                        if (env('APP_ENV') == 'local') {
                            $sku = 'Z67';
                        }

                        $catalog = [];
                        if($item['item']['sku'] !='') {
                            $sku = preg_split('/[_-]/', $item['item']['sku'])[0];
                            Log::info("Item SKU :: " . json_encode($sku));
                            $catalog = Catalog::where('product_sku', $sku)->first();
                            Log::info("Catalog Found :: " . json_encode($catalog));
                        }

                        //$catalog = Catalog::where('product_sku', $sku)->first();


                        $orderItemData = [
                            'title' => $item['item']['productName'],
                            'order_id' => $order->id,
                            'product_id' => $catalog->product_id ?? null,
                            'client_id' => null,
//                            'listing_item_id' => $itemDetail->id ?? '',
                            'sku' => $item['item']['sku'],
                            //'store_sku' => $item['item']['upc'],
                            'quantity' => $item['orderLineQuantity']['amount'],
                            'id1' => '',
                            'item_price' => ($item['charges']['charge'][0]['chargeType'] == 'PRODUCT') ? $item['charges']['charge'][0]['chargeAmount']['amount'] : 0,
                            'shipping_price' => ($item['charges']['charge'][0]['chargeType'] == 'SHIPPING') ? $item['charges']['charge'][0]['chargeAmount']['amount'] : 0,
                            'item_tax' => ($item['charges']['charge'][0]['chargeType'] == 'PRODUCT_TAX') ? $item['charges']['charge']['tax'][0]['taxAmount']['amount'] : 0,
                            'shipping_tax' => ($item['charges']['charge'][0]['chargeType'] == 'SHIPPING_TAX') ? $item['charges']['charge'][0]['tax']['taxAmount']['amount'] : 0,
                            'shipping_quantity' => $item['orderLineQuantity']['amount'],
                        ];

                        $order_item = OrderItem::updateOrCreate([
                            'order_id' => $order->id,
                            'sku' => $item['item']['sku'],
                        ], $orderItemData);

                        if ($order_item->id) {

                            $orderOperationItemData = [
                                'order_item_id' => $order_item->id,
                                'order_operation_id' => $order_operation_detail->id,
//                                'client_id' => $itemDetail->client,
                                'sku' => $item['item']['sku'],
                                'quantity' => $item['orderLineQuantity']['amount'],
                                'sync_status' => 6
                            ];

                            $order_operation_item = OrderOperationItem::updateOrCreate([
                                'order_item_id' => $order_item->id,
                                'order_operation_id' => $order_operation_detail->id
                            ], $orderOperationItemData);

                            if (isset($catalog->shipping_package_id) && $catalog->shipping_package_id != '') {
                                $package_id = $catalog->shipping_package_id;
                                $order_operation_detail->update(['shipping_package_id' => $package_id]);
                            }
                        }
                    }
                }

                if ($item['orderLineStatuses'] && $item['orderLineStatuses']['orderLineStatus']) {
                    $lineStatus = $item['orderLineStatuses']['orderLineStatus'][0];
                    if ($lineStatus['status'] === 'Shipped') {
                        $order->order_status = OrderStatus::Shipped;
                        $order_operation_detail->status = OrderOperationStatus::MarkAsShipped;
                        if ($lineStatus['trackingInfo']) {
                            $trackingNumber = $lineStatus['trackingInfo']['trackingNumber'] ?? null;
                            $order_operation_detail->tracking_number = $trackingNumber;
                        }
                        $order->save();
                        $order_operation_detail->save();
                    } else if ($lineStatus['status'] === 'Delivered') {
                        $order->order_status = OrderStatus::Shipped;
                        $order_operation_detail->status = OrderOperationStatus::Delivered;
                        if ($lineStatus['trackingInfo']) {
                            $trackingNumber = $lineStatus['trackingInfo']['trackingNumber'] ?? null;
                            $order_operation_detail->tracking_number = $trackingNumber;
                        }
                        $order->save();
                        $order_operation_detail->save();
                    } else if ($lineStatus['status'] === 'Cancelled') {
                        $order->order_status = OrderStatus::Canceled;
                        $order_operation_detail->status = OrderOperationStatus::Canceled;
                        $order->save();
                        $order_operation_detail->save();
                    }
                    Log::info("Order Line Cancelled: " . json_encode($item));
                }
            }
        }
    }

    public static function mapEbayOrders($order, $sorder, $store)
    {
        $firstname = $lastname = '';
        $customer_address = null;
        $customer_detail = null;

        if ($sorder['buyer']) {
            $addressDetails = $sorder['buyer']['buyerRegistrationAddress'];
            if (isset($addressDetails['fullName'])) {
                $nameArr = explode(' ', $addressDetails['fullName']);
                $firstname = $nameArr[0] ?? '';
                $lastname = $nameArr[1] ?? '';
            }

            $customerData = [
                'first_name' => $firstname,
                'last_name' => $lastname,
                'full_name' => $addressDetails['fullName'],
                'user_id' => $sorder['buyer']['username'],
            ];

            $customer_detail = Customer::updateOrCreate([
                'user_id' => $sorder['buyer']['username'],
            ], $customerData);

            $addressLine1 = $addressDetails['contactAddress']['addressLine1'] ?? '';
            $addressLine2 = $addressDetails['contactAddress']['addressLine2'] ?? '';

            $customer_address_data = [
                'customer_id' => $customer_detail->id,
                'full_name' => $firstname . ' ' . $lastname,
                'address_line_1' => $addressLine1,
                'address_line_2' => $addressLine2,
                'city' => $addressDetails['contactAddress']['city'],
                'state' => $addressDetails['contactAddress']['stateOrProvince'],
                'zip' => explode('-', $addressDetails['contactAddress']['postalCode'] ?? '')[0],
                'country_code' => $addressDetails['contactAddress']['countryCode'],
                'address_combined' => $addressLine1 . ' ' . $addressLine2,
            ];

            $customer_address = CustomerAddress::updateOrCreate([
                'customer_id' => $customer_detail->id,
                'address_combined' => $addressLine1 . ' ' . $addressLine2,
            ], $customer_address_data);

            if (isset($addressDetails['primaryPhone']['phoneNumber']) && $addressDetails['primaryPhone']['phoneNumber'] != null) {
                $customer_phone_data = [
                    'customer_id' => $customer_detail->id,
                    'phone_no' => $addressDetails['primaryPhone']['phoneNumber'],
                ];

                CustomerPhone::updateOrCreate($customer_phone_data, $customer_phone_data);
            }
        }

        $estimateShipmentDate = null;
        $estimateDeliveryDate = null;
        if ($sorder['fulfillmentStartInstructions']) {
            $shipTo = $sorder['fulfillmentStartInstructions'][0]['shippingStep']['shipTo'];
            $full_name = $shipTo['fullName'] ?? '';
            $addressLine1 = $shipTo['contactAddress']['addressLine1'] ?? '';
            $addressLine2 = $shipTo['contactAddress']['addressLine2'] ?? '';
            $estimateShipmentDate = $sorder['fulfillmentStartInstructions'][0]['minEstimatedDeliveryDate'];
            $estimateDeliveryDate = $sorder['fulfillmentStartInstructions'][0]['maxEstimatedDeliveryDate'];
            $customer_address_data = [
                'customer_id' => $customer_detail->id,
                'full_name' => $full_name,
                'address_line_1' => $addressLine1,
                'address_line_2' => $addressLine2,
                'city' => $shipTo['contactAddress']['city'] ?? '',
                'state' => $shipTo['contactAddress']['stateOrProvince'] ?? '',
                'zip' => explode('-', $shipTo['contactAddress']['postalCode'] ?? '')[0],
                'country_code' => $shipTo['contactAddress']['countryCode'] ?? '',
                'address_combined' => $addressLine1 . ' ' . $addressLine2,
                'phone_no' => $shipTo['primaryPhone']['phoneNumber'] ?? '',
            ];

            $customer_address = CustomerAddress::updateOrCreate([
                'customer_id' => $customer_detail->id,
                'address_combined' => $addressLine1 . ' ' . $addressLine2,
            ], $customer_address_data);

            if (isset($shipTo['primaryPhone']['phoneNumber']) && $shipTo['primaryPhone']['phoneNumber'] != null) {
                $customer_phone_data = [
                    'customer_id' => $customer_detail->id,
                    'phone_no' => $shipTo['primaryPhone']['phoneNumber'],
                ];

                CustomerPhone::updateOrCreate($customer_phone_data, $customer_phone_data);
            }
        }

        $orderData = [
//            'order_date' => date('Y-m-d H:i:s', $sorder['orderDate'] / 1000),
//            'order_total' => $orderTotals,
            'buyer_first_name' => $firstname,
            'buyer_last_name' => $lastname,
            //'order_status' => OrderStatus::Synchronizing,
//            'buyer_email' => $sorder['customerEmailId'],
            'customer_id' => $customer_detail->id ?? null,
            'purchase_order_id' => $sorder['orderId'],
            'shipping_address_id' => $customer_address->id ?? null,
            'expected_shipment_date' => isset($estimateShipmentDate) ? date('Y-m-d H:i:s', strtotime($estimateShipmentDate)) : '',
            'expected_delivery_date' => isset($estimateDeliveryDate) ? date('Y-m-d H:i:s', strtotime($estimateDeliveryDate)) : '',
        ];

        $order->update($orderData);

        Log::info("Order Status: " . $sorder['orderFulfillmentStatus']);
        switch (strtolower($sorder['orderFulfillmentStatus'])) {
            case 'fulfilled';
                $operationstatus = OrderOperationStatus::MarkAsShipped;
                break;
            case 'not_started';
            default:
                $operationstatus = OrderOperationStatus::Created;
                break;
        }

        if (isset($sorder['cancelStatus']) && $sorder['cancelStatus']['cancelState'] == 'CANCELED') {
            $operationstatus = OrderOperationStatus::Canceled;
            $order->update(['order_status' => OrderStatus::Canceled]);
        } else if (isset($sorder['cancelStatus']) && $sorder['cancelStatus']['cancelState'] == 'IN_PROGRESS') {
            $operationstatus = OrderOperationStatus::CanceledRequested;
            $order->update(['order_status' => OrderStatus::CanceledRequested]);
        }

        Log::info("Order Operation Status: " . $operationstatus);

        /* create order operatation*/
        $orderOperationData = [
            'order_id' => $order->id,
            'insurance_value' => 0,
            'status' => $operationstatus,
            'signature_confirmation' => 1,
//            'ship_from_id' => '',
            'shipping_package_id' => ShippingPackage::getDefaultShippingPackage(),
            'store_id' => $store->id,
            'operation_type' => OrderOperationType::OrderShipment,
            'label_creation_type' => 1,
            'print_to_file' => 0,
            'seller_provides_return_label' => 0,
            'order_synchronization_id' => $order->order_synchronization_id
        ];

        $order_operation_detail = OrderOperation::updateOrCreate([
            'order_id' => $order->id,
        ], $orderOperationData);

        Log::info("Came Till Here");

        $ordersItems = $sorder['lineItems'];
        if (!empty($ordersItems)) {
            foreach ($ordersItems as $item) {
                /*if (!isset($item['sku']) || $item['sku'] == '') {
                    continue;
                }*/
                //Log:info("Inside Order :: " . json_encode($item));
                $itemDetail = ListingItem::with('storeProduct', 'storeProduct.product')->where('sku', $item['sku'])->first();

                if (!isset($itemDetail)) {
                    $itemDeviceDetail = ListingItemDetail::where('sku',$item['sku'])->first();
                    if(isset($itemDeviceDetail)) {
                        Log::info('Came Inside Item Device Detail');
                        $itemDetail = ListingItem::with('storeProduct', 'storeProduct.product')
                            ->where('id', $itemDeviceDetail->listing_item_id)->first();
                    }
                }

                Log::info("Came Till Here 1 :: " . json_encode($itemDetail));
                if (isset($itemDetail)) {

                    // sale count update
                    $sku = $item['sku'];
                    Common::updateListingItemSaleDetail($sku, $itemDetail);

                    $orderItemData = [
                        'title' => $item['title'],
                        'order_id' => $order->id,
                        'product_id' => $itemDetail->storeProduct->product->product_id,
                        'client_id' => $itemDetail->client,
                        'listing_item_id' => $itemDetail->id,
                        'sku' => $item['sku'],
                        'store_sku' => $item['lineItemId'],
                        'quantity' => $item['quantity'],
                        'id1' => '',
                        'item_price' => $item['lineItemCost']['value'],
                        'shipping_price' => $item['deliveryCost']['shippingCost']['value'],
                        'item_tax' => 0,
                        'shipping_tax' => 0,
                        'shipping_quantity' => $item['quantity'],
                    ];
                    Log::info("Came Till Here 2 :: " . json_encode($orderItemData));

                    $order_item = OrderItem::updateOrCreate([
                        'order_id' => $order->id,
                        'product_id' => $itemDetail->storeProduct->product->product_id,
                        'store_sku' => $item['lineItemId'],
                    ], $orderItemData);
                    Log::info("Came Till Here 3 :: " . json_encode($order_item));

                    if ($order_item->id) {
                        $orderOperationItemData = [
                            'order_item_id' => $order_item->id,
                            'order_operation_id' => $order_operation_detail->id,
                            'sku' => $item['sku'],
                            'quantity' => $item['quantity'],
                            'client_id' => '',
                            'sync_status' => 6
                        ];

                        $order_operation_item = OrderOperationItem::updateOrCreate([
                            'order_item_id' => $order_item->id,
                            'order_operation_id' => $order_operation_detail->id
                        ], $orderOperationItemData);

                        if (isset($itemDetail->storeProduct->product->shipping_package_id) && $itemDetail->storeProduct->product->shipping_package_id != '') {
                            $package_id = $itemDetail->storeProduct->product->shipping_package_id;
                            $order_operation_detail->update(['shipping_package_id' => $package_id]);
                        }
                    }
                } else {
                    Log::info("Item Not Found :: " . json_encode($item));
                    // split the sku from ES56_li to ES56
                    $catalog = [];
                    if($item['sku'] !='') {
                        $sku = preg_split('/[_-]/', $item['sku'])[0];
                        Log::info("Item SKU :: " . json_encode($sku));
                        $catalog = Catalog::where('product_sku', $sku)->first();
                        Log::info("Catalog Found :: " . json_encode($catalog));
                    }

                    //if(!isset($catalog)) continue;

                    $orderItemData = [
                        'title' => $item['title'],
                        'order_id' => $order->id,
                        'product_id' => $catalog->product_id ?? null,
                        'client_id' => null,
//                        'listing_item_id' => $itemDetail->id,
                        'sku' => $item['sku'],
                        'store_sku' => $item['lineItemId'],
                        'quantity' => $item['quantity'],
                        'id1' => '',
                        'item_price' => $item['lineItemCost']['value'],
                        'shipping_price' => $item['deliveryCost']['shippingCost']['value'],
                        'item_tax' => 0,
                        'shipping_tax' => 0,
                        'shipping_quantity' => $item['quantity'],
                    ];

                    Log::info("Order Item Data :: " . json_encode($orderItemData));

                    $order_item = OrderItem::updateOrCreate([
                        'order_id' => $order->id,
                        'store_sku' => $item['lineItemId'],
                    ], $orderItemData);

                    if ($order_item->id) {
                        $orderOperationItemData = [
                            'order_item_id' => $order_item->id,
                            'order_operation_id' => $order_operation_detail->id,
                            'sku' => $item['sku'],
                            'quantity' => $item['quantity'],
                            'client_id' => null,
                            'sync_status' => 6
                        ];

                        Log::info("Order Operation Item Data :: " . json_encode($orderOperationItemData));

                        $order_operation_item = OrderOperationItem::updateOrCreate([
                            'order_item_id' => $order_item->id,
                            'order_operation_id' => $order_operation_detail->id
                        ], $orderOperationItemData);

                        if (isset($catalog->shipping_package_id) && $catalog->shipping_package_id != '') {
                            $package_id = $catalog->shipping_package_id;
                            $order_operation_detail->update(['shipping_package_id' => $package_id]);
                        }
                    }
                }
            }
        }
    }

    public static function mapTemuOrders($order, $presentOrder, $store)
    {
        $firstname = $lastname = '';
        $customer_address = null;
        $customer_detail = null;

        if ($presentOrder['shippingInfo']) {
            $buyerInfo = $presentOrder['shippingInfo'] ?? [];
            Log::info('Shipping Info ::' . json_encode($buyerInfo));
            if (isset($buyerInfo['receiptName'])) {
                $nameArr = explode(' ', $buyerInfo['receiptName']);
                $firstname = $nameArr[0] ?? '';
                $lastname = $nameArr[1] ?? '';
            }

            $customerData = [
                'first_name' => $firstname,
                'last_name' => $lastname,
                'full_name' => $buyerInfo['receiptName'] ?? '',
                'user_id' => $buyerInfo['receiptAdditionalName'] ?? '',
            ];

            $customer_detail = Customer::updateOrCreate([
                'full_name' => $buyerInfo['receiptName'] ?? '',
            ], $customerData);

            // Process shipping address if available
            if (isset($presentOrder['shippingInfo'])) {
                $addressLine1 = $buyerInfo['addressLine1'] ?? '';
                $addressLine2 = $buyerInfo['addressLine2'] ?? '';

                $customer_address_data = [
                    'customer_id' => $customer_detail->id,
                    'full_name' => $firstname . ' ' . $lastname,
                    'address_line_1' => $addressLine1,
                    'address_line_2' => $addressLine2,
//                    'city' => $presentOrder['shippingAddress']['city'] ?? '',
//                    'state' => $presentOrder['shippingAddress']['state'] ?? '',
                    'zip' => explode('-', $buyerInfo['postCode'] ?? '')[0],
                    'country_code' => $buyerInfo['shippingAddress']['countryCode'] ?? '',
                    'address_combined' => $buyerInfo['addressLineAll'],
                ];

                $customer_address = CustomerAddress::updateOrCreate([
                    'customer_id' => $customer_detail->id,
                    'address_combined' => $buyerInfo['addressLineAll'],
                ], $customer_address_data);

                // Handle phone number if available
                if (isset($buyerInfo['mobile']) && $buyerInfo['mobile'] != null) {
                    $customer_phone_data = [
                        'customer_id' => $customer_detail->id,
                        'phone_no' => $buyerInfo['mobile'],
                    ];

                    CustomerPhone::updateOrCreate($customer_phone_data, $customer_phone_data);
                }
            }
        }

        $estimateShipmentDate = null;
        $estimateDeliveryDate = null;
//        if (isset($presentOrder['fulfillmentDetails'])) {
//            $estimateShipmentDate = $presentOrder['fulfillmentDetails']['shipByDate'] ?? null;
//            $estimateDeliveryDate = $presentOrder['fulfillmentDetails']['deliverByDate'] ?? null;
//        }

        $orderData = [
            'buyer_first_name' => $firstname,
            'buyer_last_name' => $lastname,
            'customer_id' => $customer_detail->id ?? null,
//            'purchase_order_id' => $presentOrder['orderId'],
            'shipping_address_id' => $customer_address->id ?? null,
            'expected_shipment_date' => isset($estimateShipmentDate) ? date('Y-m-d H:i:s', strtotime($estimateShipmentDate)) : '',
            'expected_delivery_date' => isset($estimateDeliveryDate) ? date('Y-m-d H:i:s', strtotime($estimateDeliveryDate)) : '',
        ];

        $order->update($orderData);

        Log::info("Order Status: " . $presentOrder['parentOrderMap']['orderStatus']);
        switch (strtolower($presentOrder['parentOrderMap']['orderStatus'])) {
            case 4:
            case 5:
                $operationstatus = OrderOperationStatus::MarkAsShipped;
                break;
            case 3:
                $operationstatus = OrderOperationStatus::Canceled;
                break;
            case 1:
            case 2:
            default:
                $operationstatus = OrderOperationStatus::Created;
                break;
        }

        Log::info("Order Operation Status: " . $operationstatus);

        /* create order operation */
        $orderOperationData = [
            'order_id' => $order->id,
            'insurance_value' => 0, // TODO Need to add Insurance Value
            'status' => $operationstatus,
            'signature_confirmation' => 1,
            'shipping_package_id' => ShippingPackage::getDefaultShippingPackage(),
            'store_id' => $store->id,
            'operation_type' => OrderOperationType::OrderShipment,
            'label_creation_type' => 1,
            'print_to_file' => 0,
            'seller_provides_return_label' => 0,
            'order_synchronization_id' => $order->order_synchronization_id
        ];

        $order_operation_detail = OrderOperation::updateOrCreate([
            'order_id' => $order->id,
        ], $orderOperationData);

        // Process order items
        $orderItems = $presentOrder['orderList'] ?? [];
        if (!empty($orderItems)) {
            foreach ($orderItems as $item) {

                $productSku = $item['productList'][0]['extCode'] ?? null;
                $itemDetail = null;
                if (isset($productSku)) {
                    $itemDetail = ListingItem::with('storeProduct', 'storeProduct.product')->where('sku', $productSku)->first();
//                    if (!isset($itemDetail)) {
//                        $itemDeviceDetail = ListingItemDetail::where('sku', $productSku)->first();
//                        if (isset($itemDeviceDetail)) {
//                            Log::info('Found Item Device Detail');
//                            $itemDetail = ListingItem::with('storeProduct', 'storeProduct.product')
//                                ->where('id', $itemDeviceDetail->listing_item_id)->first();
//                        }
//                    }

                    if (isset($itemDetail)) {
                        $orderItemData = [
                            'title' => $item['productName'] ?? $item['title'] ?? '',
                            'order_id' => $order->id,
                            'product_id' => $itemDetail->storeProduct->product->product_id,
                            'client_id' => $itemDetail->client,
                            'listing_item_id' => $itemDetail->id,
                            'sku' => $item['sku'],
                            'store_sku' => $item['productId'] ?? $item['itemId'] ?? '',
                            'quantity' => $item['quantity'],
                            'id1' => '',
                            'item_price' => $item['unitPrice']['value'] ?? 0,
                            'shipping_price' => $item['shippingPrice']['value'] ?? 0,
                            'item_tax' => $item['tax']['value'] ?? 0,
                            'shipping_tax' => 0,
                            'shipping_quantity' => $item['quantity'],
                        ];

                        $order_item = OrderItem::updateOrCreate([
                            'order_id' => $order->id,
                            'product_id' => $itemDetail->storeProduct->product->product_id,
                        ], $orderItemData);

                        if ($order_item->id) {
                            $orderOperationItemData = [
                                'order_item_id' => $order_item->id,
                                'order_operation_id' => $order_operation_detail->id,
                                'sku' => $item['sku'],
                                'quantity' => $item['quantity'],
                                'client_id' => '',
                                'sync_status' => 6
                            ];

                            OrderOperationItem::updateOrCreate([
                                'order_item_id' => $order_item->id,
                                'order_operation_id' => $order_operation_detail->id
                            ], $orderOperationItemData);

                            if (isset($itemDetail->storeProduct->product->shipping_package_id) && $itemDetail->storeProduct->product->shipping_package_id != '') {
                                $package_id = $itemDetail->storeProduct->product->shipping_package_id;
                                $order_operation_detail->update(['shipping_package_id' => $package_id]);
                            }
                        }
                    } else {
                        Log::info("Item Not Found :: " . json_encode($item));
                        // Try to find catalog by splitting the SKU
                        $catalog = [];
                        if ($productSku != '') {
                            $sku = preg_split('/[_-]/', $productSku)[0];
                            Log::info("Item SKU :: " . json_encode($sku));
                            $catalog = Catalog::where('product_sku', $sku)->first();
                            Log::info("Catalog Found :: " . json_encode($catalog));
                        }

                        $orderItemData = [
                            'title' => $item['originalGoodsName'] ?? $item['title'] ?? '',
                            'order_id' => $order->id,
                            'product_id' => $catalog->product_id ?? null,
                            'client_id' => null,
                            'sku' => $productSku,
                            'store_sku' => $item['skuId'] ?? $item['goodsId'] ?? '',
                            'quantity' => $item['quantity'],
                            'id1' => '',
//                            'item_price' => $item['unitPrice']['value'] ?? 0,
//                            'shipping_price' => $item['shippingPrice']['value'] ?? 0,
//                            'item_tax' => $item['tax']['value'] ?? 0,
//                            'shipping_tax' => 0,
                            'shipping_quantity' => $item['quantity'],
                        ];

                        Log::info("Order Item Data :: " . json_encode($orderItemData));

                        $order_item = OrderItem::updateOrCreate([
                            'order_id' => $order->id,
                            'store_sku' => $item['skuId'] ?? $item['goodsId'] ?? '',
                        ], $orderItemData);

                        if ($order_item->id) {
                            $orderOperationItemData = [
                                'order_item_id' => $order_item->id,
                                'order_operation_id' => $order_operation_detail->id,
                                'sku' => $productSku,
                                'quantity' => $item['quantity'],
                                'client_id' => null,
                                'sync_status' => 6
                            ];

                            Log::info("Order Operation Item Data :: " . json_encode($orderOperationItemData));

                            OrderOperationItem::updateOrCreate([
                                'order_item_id' => $order_item->id,
                                'order_operation_id' => $order_operation_detail->id
                            ], $orderOperationItemData);

                            if (isset($catalog->shipping_package_id) && $catalog->shipping_package_id != '') {
                                $package_id = $catalog->shipping_package_id;
                                $order_operation_detail->update(['shipping_package_id' => $package_id]);
                            }
                        }
                    }
                }
            }
        }
    }
}
