<?php

namespace App\Jobs;

use App\Constants\OrderStatus;
use App\Services\Marketplace\Service\ShopifyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSynchronization;
use App\Models\Store;
use Illuminate\Support\Facades\Log;

class FetchShopifyOrders implements ShouldQueue
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
        $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','shopify')->get();

        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {
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

                // get shopify order
                $shopifyService = new ShopifyService();
                $orders = $shopifyService->getOrders();

                if (isset($orders) && !empty($orders)) {

                    foreach ($orders as $value) {

                        $order = $value['node'];
                        //Log::info('Shopify shopping Order: ' . json_encode($order));

                        /* check order id exist*/
                        $orderExist = Order::where('order_id',$order['id'])->count();
                        if($orderExist){
                            $orderDetail = Order::where('order_id',$order['id'])->first();
                            $orderObj = Order::findOrFail($orderDetail->id); // Retrieve the product by ID
                            $orderObj->update(['order_status' =>OrderStatus::getOrderStatusId($order['displayFulfillmentStatus'])]);
                        }else {
                            $orderData = [
                                'order_id' => $order['id'],
                                'order_date' => date('Y-m-d H:i:s', strtotime($order['createdAt'])),
                                'order_status' => OrderStatus::getOrderStatusId($order['displayFulfillmentStatus']),
                                'order_total' => $order['totalPrice'],
                                'buyer_first_name' => isset($order['customer']) ? $order['customer']['firstName'] : '',
                                'buyer_last_name' => isset($order['customer']) ? $order['customer']['lastName'] : '',
                                'buyer_email' => $order['email'],
                                'customer_id' => isset($order['customer']) ? $order['customer']['id'] : '',
                                'purchase_order_id' => $order['poNumber'],
                                'store_id' => $store->id,
                                'order_synchronization_id' => $order_synchronization_id,
                            ];
                            Log::info('Order Data: ' . json_encode($orderData));

                            $order_detail = Order::updateOrCreate([
                                'order_id' => $order['id'],
                            ], $orderData);

                            /* get order items*/
                            if (isset($order['lineItems']['nodes'])) {
                                foreach ($order['lineItems']['nodes'] as $item) {
                                    Log::info('Shopify Order Item: ' . json_encode($item));
                                    //$itemDetail = ListingItem::where('sku',$item['sku']);
                                    $orderItemData = [
                                        'title' => $item['name'],
                                        'order_id' => $order_detail->id,
                                        //'product_id' => $itemDetail->storeProduct->product->product_id,
                                        //'client_id' => $itemDetail->client,
                                        //'listing_item_id' => $itemDetail->id,
                                        'sku' => $item['sku'],
                                        'store_sku' => '',
                                        'quantity' => $item['quantity'],
                                        'id1' => $item['id'],
                                        'item_price' => $item['variant']['price'],
                                        'shipping_price' => '0',
                                        'item_tax' => '0',
                                        'shipping_tax' => '0',
                                        'shipping_quantity' => '0',
                                    ];

                                    $order_item = OrderItem::updateOrCreate([
                                        'order_id' => $order_detail->id
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
