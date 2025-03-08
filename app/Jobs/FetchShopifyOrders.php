<?php

namespace App\Jobs;

use App\Constants\OrderStatus;
use App\Services\Marketplace\Service\ShopifyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Order;
use App\Models\OrderItem;
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
        // get shopify order
        $shopifyService = new ShopifyService();
        $orders = $shopifyService->getOrders();

        if (isset($orders) && !empty($orders)) {

            foreach($orders as $value){

                $order = $value['node'];
//                Log::info('Shopify shopping Order: ' . json_encode($order));
                $orderData = [
                    'order_id' => $order['id'],
                    'order_date' => date('Y-m-d H:i:s',strtotime($order['createdAt'])),
                    'order_status' => OrderStatus::getOrderStatusId($order['displayFulfillmentStatus']),
                    'order_total' => $order['totalPrice'],
                    'buyer_first_name' => isset($order['customer']) ? $order['customer']['firstName'] : '',
                    'buyer_last_name' => isset($order['customer']) ? $order['customer']['lastName'] : '',
                    'buyer_email' => $order['email'],
                    'customer_id' => isset($order['customer']) ? $order['customer']['id'] : '',
                    'purchase_order_id' => $order['poNumber'],
                    'store_id' => '98fb0179-d0a2-ae8a-5c87-8753be8db300',
                ];
                Log::info('Order Data: ' . json_encode($orderData));

                $order_id = Order::updateOrCreate([
                    'order_id' => $order['id'],
                ],$orderData);

                /* get order items*/
                if (isset($order['lineItems']['nodes'])) {
                    foreach($order['lineItems']['nodes'] as $item){
                        Log::info('Shopify Order Item: ' . json_encode($item));
                        //$itemDetail = ListingItem::where('sku',$item['sku']);
                        $orderItemData = [
                            'title' => $item['name'],
                            'order_id' => $order_id->id,
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

                        $order_item_id = OrderItem::updateOrCreate([
                            'order_id' => $order_id->id
                        ],$orderItemData);
                    }
                }
            }
        }
    }
}
