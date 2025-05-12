<?php

namespace App\Utils;

use App\Constants\OrderSyncronisationType;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\ListingItem;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use App\Models\OrderOperationItem;
use App\Models\OrderSynchronization;
use Illuminate\Support\Facades\Log;

class OrderUtility
{
    public static function getLastSyncDate($store_id, $synchronizationType)
    {
        $lastOrderUpdateDate = OrderSynchronization::where('store_id', $store_id)
            ->where('synchronization_type', $synchronizationType)
            ->max('last_order_update_date');

        if ($lastOrderUpdateDate) {
            $date = \Carbon\Carbon::parse($lastOrderUpdateDate)->addSeconds(10);
            return $date->toDateTimeString();
        } else {
            // get last 30 days
            return now()->subDays(30)->toDateTimeString();
        }
    }

    public static function mapOrders($order, mixed $sorder, $store)
    {
        $firstname = $lastname = '';
        $orderTotal = 0;
        $taxTotal = 0;
        $discountTotal = 0;
        $orderTotals = 0;
        $order_date = date('Y-m-d H:i:s', $sorder['orderDate'] / 1000);

        foreach ($sorder['orderLines']['orderLine'] as $line) {
            $itemTotal = 0;
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
            'user_id' => $sorder['customerOrderId'],
        ];

        $customer_detail = Customer::updateOrCreate([
            'user_id' => $sorder['customerOrderId'],
        ], $customerData);

        $customer_address_data = [
            'address_line_1' => $sorder['shippingInfo']['postalAddress']['address1'],
            'address_line_2' => $sorder['shippingInfo']['postalAddress']['address2'],
            'city' => $sorder['shippingInfo']['postalAddress']['city'],
            'state' => $sorder['shippingInfo']['postalAddress']['state'],
            'zip' => $sorder['shippingInfo']['postalAddress']['postalCode'],
            'country' => $sorder['shippingInfo']['postalAddress']['country'],
            'address_combined' => $sorder['shippingInfo']['postalAddress']['address1'] . ' ' . $sorder['shippingInfo']['postalAddress']['address2'],
        ];

        Log::info("Data " . json_encode($customer_address_data));

        $customer_address = CustomerAddress::updateOrCreate([
            'customer_id' => $customer_detail->id,
            'address_combined' => $sorder['shippingInfo']['postalAddress']['address1'] . ' ' . $sorder['shippingInfo']['postalAddress']['address2'],
        ], $customer_address_data);

        $orderData = [
            'order_date' => date('Y-m-d H:i:s', $sorder['orderDate'] / 1000),
            'order_total' => $orderTotals,
            'buyer_first_name' => $firstname,
            'buyer_last_name' => $lastname,
            'buyer_email' => $sorder['customerEmailId'],
            'customer_id' => $customer_detail->id,
            'purchase_order_id' => $sorder['purchaseOrderId'],
            'shipping_address_id' => $customer_address->id,
        ];

        $order->update($orderData);

        $orderOperationData = [
            'order_id' => $order->id,
            'ship_date' => date('Y-m-d H:i:s', $sorder['shippingInfo']['estimatedShipDate'] / 1000),
            'insurance_value' => $orderTotals,
            'status' => 1,
            'signature_confirmation' => 1,
            'store_id' => $store->id,
            'operation_type' => 1,
            'label_creation_type' => 1,
            'print_to_file' => 0,
            'seller_provides_return_label' => 0,
            'order_synchronization_id' => $order->order_synchronization_id
        ];

        $order_operation_detail = OrderOperation::updateOrCreate([
            'order_id' => $order->id,
        ], $orderOperationData);

        $ordersItems = $sorder['orderLines']['orderLine'];
        if (empty($ordersItems)) {
            foreach ($ordersItems as $item) {
                Log::info("Order Item: " . json_encode($item));
                if ($item['item']['sku'] != '') {
                    $itemDetail = ListingItem::with('storeProduct', 'storeProduct.product')->where('sku', $item['item']['sku']);

                    $orderItemData = [
                        'title' => $item['item']['productName'],
                        'order_id' => $order->id,
                        'product_id' => $itemDetail->storeProduct->product->product_id ?? '',
                        'client_id' => $itemDetail->client ?? '',
                        'listing_item_id' => $itemDetail->id ?? '',
                        'sku' => $item['item']['sku'],
                        'store_sku' => $item['item']['upc'],
                        'quantity' => $item['orderLineQuantity']['amount'],
                        'id1' => '',
                        'item_price' => ($item['charges']['charge']['chargeType'] == 'PRODUCT') ? $item['charges']['charge']['chargeAmount']['amount'] : 0,
                        'shipping_price' => ($item['charges']['charge']['chargeType'] == 'SHIPPING') ? $item['charges']['charge']['chargeAmount']['amount'] : 0,
                        'item_tax' => ($item['charges']['charge']['chargeType'] == 'PRODUCT_TAX') ? $item['charges']['charge']['tax']['taxAmount']['amount'] : 0,
                        'shipping_tax' => ($item['charges']['charge']['chargeType'] == 'SHIPPING_TAX') ? $item['charges']['charge']['tax']['taxAmount']['amount'] : 0,
                        'shipping_quantity' => $item['orderLineQuantity']['amount'],
                    ];

                    $order_item = OrderItem::updateOrCreate([
                        'order_id' => $order->id,
                        'product_id' => $itemDetail->storeProduct->product->product_id,
                    ], $orderItemData);

                    if ($order_item->id) {
                        $orderOperationItemData = [
                            'order_item_id' => $order_item->id,
                            'order_operation_id' => $order_operation_detail->id,
                            'client_id' => $itemDetail->client,
                            'sync_status' => 6
                        ];

                        $order_operation_item = OrderOperationItem::updateOrCreate([
                            'order_operation_id' => $order_operation_detail->id
                        ], $orderOperationItemData);
                    }
                }
            }
        }
    }
}
