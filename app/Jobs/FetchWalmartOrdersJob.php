<?php

namespace App\Jobs;

use App\Constants\OrderStatus;
use App\Models\Customer;
use App\Models\ListingItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use App\Models\OrderOperationItem;
use App\Models\OrderSynchronization;
use App\Models\Store;
use App\Services\Marketplace\Service\WalmartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchWalmartOrdersJob implements ShouldQueue
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
        $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'walmart')->get();

        Log::info('Walmart Store ' . json_encode($stores));
        if (count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                if ($store->name != 'AWAccessory') {
                    continue;
                }

                $last_synchronization = OrderSynchronization::where('store_id', $store->id)->orderBy('filter_end_date', 'desc')->first();

                $created_by = auth()->user()->name ?? 'admin';
                $synchronizeArr = [
                    'store_id' => $store->id,
                    'synchronization_type' => 1,
                    'filter_start_date' => isset($last_synchronization) ? $last_synchronization->filter_end_date : date('Y-m-d H:i:s'),
                    'created_by' => $created_by
                ];
                Log::info("Syncronisation :: " . json_encode($synchronizeArr));
                $order_synchronization = OrderSynchronization::create($synchronizeArr);
                $order_synchronization_id = $order_synchronization->id;

                // get amazon order
                $walmartService = new WalmartService();
                $orders = $walmartService->getOrders($store);

                if (!isset($orders['errors']) && isset($orders['list']['elements']['order'])) {
                    /* create order synchronization*/
                    $noOfOrder = 0;
                    $last_order_date = $order_synchronization->filter_start_date;
                    foreach ($orders['list']['elements']['order'] as $order) {
                        try {
                            $firstname = $lastname = '';
                            $orderTotal = 0;
                            $taxTotal = 0;
                            $discountTotal = 0;
                            $orderTotals = 0;
                            $order_date = date('Y-m-d H:i:s',$order['orderDate'] / 1000);
                            Log::info('Order Date : ' . $order_date . ' Order Date Response : ' . $order['orderDate']);

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
                            $orderExist = Order::where('order_id', $order['purchaseOrderId'])->count();
                            if ($orderExist) {
                                $orderDetail = Order::where('order_id', $order['purchaseOrderId'])->first();
                                $orderObj = Order::findOrFail($orderDetail->id); // Retrieve the product by ID
                                $orderObj->update(['order_status' => OrderStatus::getOrderStatusId($order['OrderStatus'])]);

                                /* check order operation for this order*/
                                $orderOperationDetail = OrderOperation::where('order_id', $orderDetail->id)->count();
                                if ($orderOperationDetail) {
                                    $orderOperationDetail->update(['status' => 2]);
                                }
                            }
                            else
                            {

                                /* create customer*/

                                $customerData = [
                                    'first_name' => $firstname,
                                    'last_name' => $lastname,
                                    'full_name' => $order['postalAddress']['name'],
                                    'email' => $order['customerEmailId'],
                                    'marketplace_customer_id' => isset($order['customer']) ? $order['customer']['id'] : '',
                                ];

                                $customer_detail = Customer::updateOrCreate([
                                    'order_id' => $order['id'],
                                ], $customerData);

                                $orderData = [
                                    'order_id' => $order['purchaseOrderId'],
                                    'order_date' => date('Y-m-d H:i:s', strtotime($order['orderDate'])),
                                    'order_status' => OrderStatus::getOrderStatusId($order['OrderStatus']),
                                    'order_total' => $orderTotals,
                                    'buyer_first_name' => $firstname,
                                    'buyer_last_name' => $lastname,
                                    'buyer_email' => $order['customerEmailId'],
                                    'customer_id' => $customer_detail->id,
                                    'purchase_order_id' => $order['purchaseOrderId'],
                                    'store_id' => $store->id,
                                    'order_synchronization_id' => $order_synchronization_id
                                ];

                                $order_detail = Order::updateOrCreate([
                                    'order_id' => $order['purchaseOrderId'],
                                ], $orderData);

                                /* create order operatation*/
                                $orderOperationData = [
                                    'order_id' => $order['purchaseOrderId'],
                                    'ship_date' => date('Y-m-d H:i:s', strtotime($order['shippingInfo']['estimatedShipDate'])),
                                    'insurance_value' => $orderTotals,
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
                                    'order_id' => $order['purchaseOrderId'],
                                ], $orderOperationData);

                                /* get order items*/
                                $ordersItems = $order['orderLines']['orderLine'];
                                if (!!empty($ordersItems)) {
                                    $cnt = 0;
                                    foreach ($ordersItems as $item) {
                                        if ($item['item']['sku'] != '') {
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

                                            /* create order operation item*/
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

                                            $cnt++;
                                        }
                                    }
                                }
                            }
                            $noOfOrder++;
                            break;
                        } catch (\Exception $e) {
                            break;
                        }
                    }

                    $order_synchronization->update(['number_of_items' => $noOfOrder, 'last_order_update_date' => date('Y-m-d H:i:s'), 'filter_end_date' => date('Y-m-d H:i:s')]);
                } else {
                    $order_synchronization->update([
                        'number_of_items' => 0,
                        'last_order_update_date' => isset($last_synchronization) ? $last_synchronization->filter_end_date : date('Y-m-d H:i:s'),
                        'filter_end_date' => isset($last_synchronization) ? $last_synchronization->filter_end_date : date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
    }
}
