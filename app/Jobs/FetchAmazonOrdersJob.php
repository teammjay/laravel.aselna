<?php

namespace App\Jobs;

use App\Constants\OrderStatus;
use App\Models\ListingItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Marketplace\Service\AmazonService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchAmazonOrdersJob implements ShouldQueue
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
        $amazonService = new AmazonService();
        $orders = $amazonService->getOrders();
//        Log::info('Amazon Orders: ' . json_encode($orders));

        if (!isset($orders['error'])) {
            foreach($orders as $order){
//                Log::info('Amazon Order: ' . json_encode($order));
                $firstname = $lastname = '';
                if(isset($order['BuyerInfo']['BuyerName'])){
                    $nameArr = explode(' ',$order['BuyerInfo']['BuyerName']);
                    $firstname = $nameArr[0];
                    $lastname = $nameArr[1];
                }

                $orderData = [
                    'order_id' => $order['AmazonOrderId'],
                    'order_date' => date('Y-m-d H:i:s',strtotime($order['PurchaseDate'])),
                    'order_status' => OrderStatus::getOrderStatusId($order['OrderStatus']),
                    'order_total' => $order['OrderTotal']['Amount'],
                    'buyer_first_name' => $firstname,
                    'buyer_last_name' => $lastname,
                    'buyer_email' => $order['BuyerInfo']['BuyerEmail'],
                    'purchase_order_id' => $order['AmazonOrderId'],
                    'store_id' => 'c476797e-9682-cc87-c841-5ecc63b9b002',
                ];
//                Log::info('Order Data: ' . json_encode($orderData));

                $order_id = Order::updateOrCreate([
                    'order_id' => $order['AmazonOrderId'],
                ],$orderData);

                /* get order items*/
                $ordersItems = $amazonService->getOrderItems($order['AmazonOrderId']);
                if (!isset($ordersItems['error'])) {
                    foreach($ordersItems as $item){
                        Log::info('Amazon Order Item: ' . json_encode($item));
//                        $itemDetail = ListingItem::where('sku',$item['SellerSKU']);
                        $orderItemData = [
                            'title' => $item['Title'],
                            'order_id' => $order_id->id,
//                            'product_id' => $itemDetail->storeProduct->product->product_id,
//                            'client_id' => $itemDetail->client,
//                            'listing_item_id' => $itemDetail->id,
                            'sku' => $item['SellerSKU'],
                            'store_sku' => $item['ASIN'],
                            'quantity' => $item['QuantityOrdered'],
                            'id1' => $item['OrderItemId'],
                            'item_price' => $item['ItemPrice']['Amount'],
                            'shipping_price' => isset($item['ShippingPrice']) ? $item['ShippingPrice']['Amount'] : '0',
                            'item_tax' => $item['ItemTax']['Amount'],
                            'shipping_tax' => isset($item['ShippingTax']) ? $item['ShippingTax']['Amount'] : '0',
                            'shipping_quantity' => $item['QuantityShipped'],
                        ];

                        $order_item_id = OrderItem::updateOrCreate([
                            'order_id' => $order_id->id
                        ],$orderItemData);
                    }

                }

                return;
            }
        }
    }
}
