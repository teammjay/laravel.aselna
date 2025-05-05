<?php

namespace App\Jobs;

use App\Constants\OrderStatus;
use App\Models\ListingItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use App\Models\OrderOperationItem;
use App\Models\OrderSynchronization;
use App\Models\Store;
use App\Services\Marketplace\Service\EbayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchEbayOrders implements ShouldQueue
{
    use Queueable;

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
        /* get amazon stores*/
        $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','ebay')->get();

        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {

                $ebayService = new EbayService();
                $orders = $ebayService->getOrders($store);

                if (!isset($orders['total']) && $orders['total'] > 0) {

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

                    foreach ($orders['orders'] as $order) {

                        /* check order id exist*/
                        $orderExist = Order::where('order_id',$order['orderId'])->count();
                        if($orderExist){
                            $orderDetail = Order::where('order_id',$order['orderId'])->first();
                            $orderObj = Order::findOrFail($orderDetail->id); // Retrieve the product by ID
                            $orderObj->update(['order_status' =>OrderStatus::getOrderStatusId($order['orderFulfillmentStatus'])]);

                            /* check order operation for this order*/
                            $orderOperationDetail = OrderOperation::where('order_id',$orderDetail->id)->count();
                            if($orderOperationDetail){
                                $orderOperationDetail->update(['status'=>2]);
                            }
                        }else {
                            $orderData = [
                                'order_id' => $order['orderId'],
                                'order_date' => date('Y-m-d H:i:s', strtotime($order['creationDate'])),
                                'order_status' => OrderStatus::getOrderStatusId($order['orderFulfillmentStatus']),
                                'order_total' => $order['totalFeeBasisAmount']['value'],
                                'buyer_first_name' => $order['buyer']['buyerRegistrationAddress']['fullName'],
                                'buyer_last_name' => '',
                                'buyer_email' => $order['buyer']['buyerRegistrationAddress']['email'],
                                'purchase_order_id' => $order['orderId'],
                                'store_id' => $store->id,
                                'order_synchronization_id' => $order_synchronization_id
                            ];

                            $order_detail = Order::updateOrCreate([
                                'order_id' => $order['orderId'],
                            ], $orderData);

                            /* create order operatation*/
                            $orderOperationData = [
                                'order_id' => $order_detail->id,
                                'ship_date' => date('Y-m-d H:i:s'),
                                'insurance_value' => 0,
                                'status' => 1,
                                'signature_confirmation' => 1,
                                'ship_from_id' => '',
                                'store_id' => $store->id,
                                'operation_type' => 1,
                                'label_creation_type' => 1,
                                'print_to_file' => 0,
                                'seller_provides_return_label' => 0,
                                'order_synchronization_id' => $order_synchronization_id
                            ];

                            $order_operation_detail = OrderOperation::updateOrCreate([
                                'order_id' => $order_detail->id,
                            ], $orderOperationData);

                            /* get order items*/
                            $ordersItems = $order['lineItems'];
                            if (!!empty($ordersItems)) {
                                foreach ($ordersItems as $item) {
                                    $itemDetail = ListingItem::with('storeProduct', 'storeProduct.product')->where('sku', $item['product_info']['sku']);
                                    $orderItemData = [
                                        'title' => $item['title'],
                                        'order_id' => $order_detail->id,
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

                                    $order_item = OrderItem::updateOrCreate([
                                        'order_id' => $order_detail->id,
                                        'product_id' => $itemDetail->storeProduct->product->product_id,
                                    ], $orderItemData);

                                    /* create order operation item*/
                                    if ($order_item->id) {
                                        $orderOperationItemData = [
                                            'order_item_id' => $order_item->id,
                                            'order_operation_id' => $order_operation_detail->id,
                                            'quantity' => $item['quantity'],
                                            'client_id' => '',
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
            }
        }
    }
}
