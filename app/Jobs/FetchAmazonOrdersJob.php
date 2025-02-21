<?php

namespace App\Jobs;

use App\Models\ListingItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Marketplace\Service\AmazonService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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

        if (!isset($orders['error'])) {
            foreach($orders as $order){
                $firstname = $lastname = '';
                if(isset($order['BuyerInfo']['BuyerName'])){
                    $nameArr = explode(' ',$order['BuyerInfo']['BuyerName']);
                    $firstname = $nameArr[0];
                    $lastname = $nameArr[1];
                }

                $orderData = [
                    'order_id' => $order['AmazonOrderId'],
                    'order_date' => date('Y-m-d H:i:s',strtotime($order['PurchaseDate'])),
                    'order_status' => $order['OrderStatus'],
                    'order_total' => $order['OrderTotal']['Amount'],
                    'buyer_first_name' => $firstname,
                    'buyer_last_name' => $lastname,
                    'buyer_email' => $order['BuyerInfo']['BuyerEmail'],
                    'purchase_order_id' => $order['OrderTotal'],
                ];

                $order_id = Order::updateOrCreate([
                    'order_id' => $order['AmazonOrderId'],
                ],$orderData);

                /* get order items*/
                $ordersItems = $amazonService->getOrderItems($order['AmazonOrderId']);
                if (!isset($ordersItems['error'])) {
                    foreach($ordersItems as $item){
                        $itemDetail = ListingItem::with('storeProduct','storeProduct.product')->where('sku',$item['product_info']['sku']);
                        $orderItemData = [
                            'title' => $item['title'],
                            'order_id' => $order_id,
                            'product_id' => $itemDetail->storeProduct->product->product_id,
                            'client_id' => $itemDetail->client,
                            'listing_item_id' => $itemDetail->id,
                            'sku' => $item['product_info']['sku'],
                            'store_sku' => $item['product_info']['asin'],
                            'quantity' => $item['quantity'],
                            'id1' => $item['item_id'],
                            'item_price' => $item['price']['amount'],
                            'shipping_price' => $item['ShippingPrice']['amount'],
                            'item_tax' => $item['ItemTax']['amount'],
                            'shipping_tax' => $item['ShippingTax']['amount'],
                            'shipping_quantity' => $item['QuantityShipped'],
                        ];

                        $order_item_id = OrderItem::updateOrCreate([
                            'order_id' => $order_id,
                            'product_id' => $itemDetail->storeProduct->product->product_id,
                        ],$orderItemData);
                    }

                }

            }
        }
    }
}
