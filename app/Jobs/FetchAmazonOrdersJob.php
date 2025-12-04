<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Jobs\Concerns\TrackableJob;
use App\Constants\OrderStatus;
use App\Models\ListingItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSynchronization;
use App\Models\Store;
use App\Services\Marketplace\Service\AmazonService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchAmazonOrdersJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3000;

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

        $this->updateTrackingRecord(['tags' => 'Amazon Orders']);

        /* get active store jobs*/
        $store_ids = Common::getActiveStoreJobs('FetchAmazonOrdersJob','amazon');
        /* get active store jobs*/

        /* get amazon stores*/
        $stores = Store::join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','amazon')->where('store.store_status','<>',0)->whereIn('id',$store_ids)->get();

        if(count($stores ?? []) > 0){
            foreach($stores as $store){
                $created_by = auth()->user()->name ?? 'admin';
                /* create order synchronization*/
                $synchronizeArr = [
                    'store_id' => $store->id,
                    'number_of_items' => null,
                    'synchronization_type' => null,
                    'filter_start_date' => date('Y-m-d H:i:s'),
                    'filter_end_date' => '',
                    'last_order_update_date' => date('Y-m-d H:i:s'),
                    'created_by' => $created_by
                ];
                $order_synchronization = OrderSynchronization::create($synchronizeArr);
                $order_synchronization_id = $order_synchronization->id;

                // get amazon order
                $amazonService = new AmazonService();
                $orders = $amazonService->getOrders($store);
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

                        /* check order id exist*/
                        $orderExist = Order::where('order_id',$order['AmazonOrderId'])->count();
                        if($orderExist){
                            $orderDetail = Order::where('order_id',$order['AmazonOrderId'])->first();
                            $orderObj = Order::findOrFail($orderDetail->id); // Retrieve the product by ID
                            $orderObj->update(['order_status' =>OrderStatus::getOrderStatusId($order['OrderStatus'])]);
                        }else {
                            $orderData = [
                                'order_id' => $order['AmazonOrderId'],
                                'order_date' => date('Y-m-d H:i:s', strtotime($order['PurchaseDate'])),
                                'order_status' => OrderStatus::getOrderStatusId($order['OrderStatus']),
                                'order_total' => $order['OrderTotal']['Amount'],
                                'buyer_first_name' => $firstname,
                                'buyer_last_name' => $lastname,
                                'buyer_email' => $order['BuyerInfo']['BuyerEmail'],
                                'purchase_order_id' => $order['AmazonOrderId'],
                                'store_id' => $store->id,
                                'order_synchronization_id' => $order_synchronization_id,
                            ];
                            //                Log::info('Order Data: ' . json_encode($orderData));

                            $order_detail = Order::updateOrCreate([
                                'order_id' => $order['AmazonOrderId'],
                            ], $orderData);

                            /* get order items*/
                            $ordersItems = $amazonService->getOrderItems($order['AmazonOrderId'],$store);
                            if (!isset($ordersItems['error'])) {
                                foreach($ordersItems as $item){
                                    Log::info('Amazon Order Item: ' . json_encode($item));
//                        $itemDetail = ListingItem::where('sku',$item['SellerSKU']);
                                    $orderItemData = [
                                        'title' => $item['Title'],
                                        'order_id' => $order_detail->id,
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

                                    $order_item = OrderItem::updateOrCreate([
                                        'order_id' => $order_detail->id
                                    ],$orderItemData);
                                }

                            }
                        }
                    }
                }
            }
        }

    }
}
