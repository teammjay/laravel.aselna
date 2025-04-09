<?php

namespace App\Jobs;

use App\Constants\OrderStatus;
use App\Models\ListingItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSynchronization;
use App\Models\Store;
use App\Services\Marketplace\Service\WalmartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchWalmartOrdersJob implements ShouldQueue
{
    use Queueable;

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
        /* get amazon stores*/
        $stores = Store::join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','walmart')->get();

        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                if($store->name != 'AWAccessory'){
                    continue;
                }
                $created_by = auth()->user()->name ?? 'admin';
                /* create order synchronization*/
                $synchronizeArr = [
                    'store_id' => $store->id,
                    'number_of_items' => '',
                    'synchronization_type' => '',
                    'filter_start_date' => date('Y-m-d H:i:s'),
                    'filter_end_date' => '',
                    'last_order_update_date' => date('Y-m-d H:i:s'),
                    'created_by' => $created_by
                ];
                $order_synchronization = OrderSynchronization::create($synchronizeArr);
                $order_synchronization_id = $order_synchronization->id;

                // get amazon order
                $walmartService = new WalmartService();
                $orders = $walmartService->getOrders();

                if (!isset($orders['errors']) && isset($orders['elements']['order'])) {

                    foreach ($orders['elements']['order'] as $order) {
                        $firstname = $lastname = '';
                        $orderTotal = 0;
                        $taxTotal = 0;
                        $discountTotal = 0;
                        $orderTotals = 0;
                        foreach ($order['orderLines']['orderLine'] as $line) {
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

                        if (isset($order['postalAddress']['name'])) {
                            $nameArr = explode(' ', $order['postalAddress']['name']);
                            $firstname = $nameArr[0];
                            $lastname = $nameArr[1];
                        }

                        /* check order id exist*/
                        $orderExist = Order::where('order_id',$order['purchaseOrderId'])->count();
                        if($orderExist){
                            $orderDetail = Order::where('order_id',$order['purchaseOrderId'])->first();
                            $orderObj = Order::findOrFail($orderDetail->id); // Retrieve the product by ID
                            $orderObj->update(['order_status' =>OrderStatus::getOrderStatusId($order['OrderStatus'])]);
                        }else {
                            $orderData = [
                                'order_id' => $order['purchaseOrderId'],
                                'order_date' => date('Y-m-d H:i:s', strtotime($order['orderDate'])),
                                'order_status' => OrderStatus::getOrderStatusId($order['OrderStatus']),
                                'order_total' => $orderTotals,
                                'buyer_first_name' => $firstname,
                                'buyer_last_name' => $lastname,
                                'buyer_email' => '',
                                'purchase_order_id' => $order['purchaseOrderId'],
                                'store_id' => $store->id,
                                'order_synchronization_id' => $order_synchronization_id
                            ];

                            $order_detail = Order::updateOrCreate([
                                'order_id' => $order['purchaseOrderId'],
                            ], $orderData);

                            /* get order items*/
                            $ordersItems = $order['orderLines']['orderLine'];
                            if (!!empty($ordersItems)) {
                                foreach ($ordersItems as $item) {
                                    $itemDetail = ListingItem::with('storeProduct', 'storeProduct.product')->where('sku', $item['product_info']['sku']);
                                    $orderItemData = [
                                        'title' => $item['item']['productName'],
                                        'order_id' => $order_detail->id,
                                        'product_id' => $itemDetail->storeProduct->product->product_id,
                                        'client_id' => $itemDetail->client,
                                        'listing_item_id' => $itemDetail->id,
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
                                        'order_id' => $order_detail->id,
                                        'product_id' => $itemDetail->storeProduct->product->product_id,
                                    ], $orderItemData);
                                }

                            }
                        }
                    }
                }
            }
        }
    }
}
