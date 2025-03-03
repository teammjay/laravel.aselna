<?php

namespace App\Jobs;

use App\Constants\OrderStatus;
use App\Models\ListingItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Marketplace\Service\MagentoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchMagentoOrderJob implements ShouldQueue
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
        // get amazon order
        $magentoService = new MagentoService();
        $orders = $magentoService->getOrders();

        if (isset($orders['items']) && !empty($orders['items'])) {

            foreach($orders['items'] as $order){
//                Log::info('google shopping Order: ' . json_encode($order));
                $orderData = [
                    'order_id' => $order['entity_id'],
                    'order_date' => date('Y-m-d H:i:s',strtotime($order['created_at'])),
                    'order_status' => OrderStatus::getOrderStatusId($order['status']),
                    'order_total' => $order['grand_total'],
                    'buyer_first_name' => isset($order['customer_firstname']) ? $order['customer_firstname'] : '',
                    'buyer_last_name' => isset($order['customer_middlename']) ? $order['customer_middlename']: '',
                    'buyer_email' => $order['customer_email'],
                    'customer_id' => $order['customer_id'],
                    'purchase_order_id' => $order['increment_id'],
                    'store_id' => '94428796-cdf9-dc2b-a1e7-aa4100066fd9',
                ];
                Log::info('Order Data: ' . json_encode($orderData));

                $order_id = Order::updateOrCreate([
                    'order_id' => $order['entity_id'],
                ],$orderData);

                /* get order items*/
                if (isset($order['items'])) {
                    foreach($order['items'] as $item){
                        Log::info('Google Order Item: ' . json_encode($item));
                        //$itemDetail = ListingItem::where('sku',$item['sku']);
                        $orderItemData = [
                            'title' => $item['name'],
                            'order_id' => $order_id->id,
                            //'product_id' => $itemDetail->storeProduct->product->product_id,
                            //'client_id' => $itemDetail->client,
                            //'listing_item_id' => $itemDetail->id,
                            'sku' => $item['sku'],
                            'store_sku' => '',
                            'quantity' => $item['qty_ordered'],
                            'id1' => $item['item_id'],
                            'item_price' => $item['price'],
                            'shipping_price' => '0',
                            'item_tax' => '0',
                            'shipping_tax' => '0',
                            'shipping_quantity' => $item['qty_shipped'],
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
