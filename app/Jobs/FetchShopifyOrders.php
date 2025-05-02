<?php

namespace App\Jobs;

use App\Constants\OrderStatus;
use App\Models\Customer;
use App\Models\ListingItem;
use App\Models\ManageMarketplaceProduct;
use App\Models\OrderOperation;
use App\Models\OrderOperationItem;
use App\Services\Marketplace\Service\ShopifyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSynchronization;
use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class FetchShopifyOrders implements ShouldQueue
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

        try {
            /* get shopify stores*/
            $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->get();

            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {
                    Log::info('Store : ' . $store->name);
                    if ($store->name != 'AccessoryWiz') {
                        continue;
                    }

                    // get shopify order
                    $shopifyService = new ShopifyService();
                    $orders = $shopifyService->getOrders($store);

                    if (isset($orders) && !empty($orders)) {

                        $created_by = auth()->user()->name ?? 'admin';
                        /* create order synchronization*/
                        $synchronizeArr = [
                            'store_id' => $store->id,
                            'synchronization_type' => 1,
                            'filter_start_date' => date('Y-m-d H:i:s'),
                            'created_by' => $created_by
                        ];
                        $order_synchronization = OrderSynchronization::create($synchronizeArr);
                        $order_synchronization_id = $order_synchronization->id;

                        Log::info('order_synchronization_id: ' . json_encode($order_synchronization_id));

                        $noOfOrder = 0;
                        foreach ($orders as $value) {

                            $order = $value['node'];
                            Log::info('Shopify shopping Order: ' . json_encode($order));

                            /* check order id exist*/
                            $orderExist = Order::where('order_id', $order['id'])->count();
                            if ($orderExist) {
                                Log::info('Existing Order: ' . $orderExist);
                                $orderDetail = Order::where('order_id', $order['id'])->first();
                                $orderObj = Order::findOrFail($orderDetail->id); // Retrieve the product by ID
                                $orderObj->update(['order_status' => OrderStatus::getOrderStatusId($order['displayFulfillmentStatus'])]);

                                /* check order operation for this order*/
                                $orderOperationDetail = OrderOperation::where('order_id',$orderDetail->id)->count();
                                if($orderOperationDetail){
                                    $orderOperationDetail->update(['status'=>2]);
                                }
                            } else {

                                /* create customer*/

                                $customerData = [
                                    'first_name' => isset($order['customer']) ? $order['customer']['firstName'] : '',
                                    'last_name' => isset($order['customer']) ? $order['customer']['lastName'] : '',
                                    'full_name' => isset($order['customer']) ? $order['customer']['displayName'] : '',
                                    'email' => $order['email'],
                                    'marketplace_customer_id' => isset($order['customer']) ? $order['customer']['id'] : '',
                                ];

                                $customer_detail = Customer::updateOrCreate([
                                    'email' => $order['email'],
                                ], $customerData);

                                $orderData = [
                                    'order_id' => $order['id'],
                                    'order_date' => date('Y-m-d H:i:s', strtotime($order['createdAt'])),
                                    //'order_status' => OrderStatus::getOrderStatusId($order['displayFulfillmentStatus']),
                                    'order_status' => OrderStatus::getOrderStatusId('Pending'),
                                    'order_total' => $order['totalPrice'],
                                    'buyer_first_name' => isset($order['customer']) ? $order['customer']['firstName'] : '',
                                    'buyer_last_name' => isset($order['customer']) ? $order['customer']['lastName'] : '',
                                    'buyer_email' => $order['email'],
                                    'customer_id' => $customer_detail->id,
                                    'purchase_order_id' => isset($order['poNumber']) ? $order['poNumber'] : '',
                                    'store_id' => $store->id,
                                    'order_synchronization_id' => $order_synchronization_id,
                                ];
                                Log::info('Order Data: ' . json_encode($orderData));

                                $order_detail = Order::updateOrCreate([
                                    'order_id' => $order['id'],
                                ], $orderData);

                                /* create order operatation*/
                                $orderOperationData = [
                                    'order_id' => $order_detail->id,
                                    'ship_date' => date('Y-m-d H:i:s'),
                                    'insurance_value' => 0,
                                    'status' => 1,
                                    'signature_confirmation' => 1,
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
                                if (isset($order['lineItems']['nodes'])) {
                                    foreach ($order['lineItems']['nodes'] as $item) {
                                        if ($item['sku'] != '') {
                                            Log::info('Shopify Order Item: ' . json_encode($item));
                                            $itemDetail = ManageMarketplaceProduct::where('sku', $item['sku'])->first();
                                            $orderItemData = [
                                                'title' => $item['name'],
                                                'order_id' => $order_detail->id,
                                                'product_id' => $itemDetail->product_id,
                                                'client_id' => '',
                                                'listing_item_id' => '',
                                                'sku' => $item['sku'],
                                                'store_sku' => $order['id'],
                                                'quantity' => $item['quantity'],
                                                'id1' => $item['id'],
                                                'item_price' => isset($item['variant']['price']) ? $item['variant']['price'] : 0,
                                                'shipping_price' => '0',
                                                'item_tax' => '0',
                                                'shipping_tax' => '0',
                                                'shipping_quantity' => $item['currentQuantity'],
                                            ];

                                            $order_item = OrderItem::updateOrCreate([
                                                'order_id' => $order_detail->id,
                                                'sku' => $item['sku'],
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
                                                    'order_operation_id' => $order_operation_detail->id,
                                                    'order_item_id' => $order_item->id,
                                                ], $orderOperationItemData);
                                            }
                                        }
                                    }
                                }


                            }
                            $noOfOrder++;
                        }

                        $order_synchronization->update(['number_of_items'=>$noOfOrder,'last_order_update_date' => date('Y-m-d H:i:s'), 'filter_end_date' => date('Y-m-d H:i:s')]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
        }
    }
}
