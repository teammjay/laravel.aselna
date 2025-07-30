<?php

namespace App\Jobs;

use App\Constants\OrderOperationStatus;
use App\Constants\OrderOperationType;
use App\Constants\OrderStatus;
use App\Constants\OrderSyncronisationType;
use App\Models\Catalog;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerPhone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use App\Models\OrderOperationItem;
use App\Models\OrderSynchronization;
use App\Models\ShippingPackage;
use App\Models\ShippingServiceRule;
use App\Models\Store;
use App\Services\Marketplace\Service\ShopifyService;
use App\Utils\order\OrderUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

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
                    if ($store->secret_key == '' || $store->service_url == '') {
                        continue;
                    }
                    Log::info('Store : ' . $store->name);

                    $ordersyncDetail = OrderSynchronization::where('store_id',$store->id)->where('synchronization_type', OrderSyncronisationType::ORDER)->orderByRaw('COALESCE(last_order_update_date,filter_end_date) desc')->first();
                    $filterStartDate = OrderUtility::getLastSyncDate($store->id, OrderSyncronisationType::ORDER);
                    $filterStartDateFormatted = gmdate('Y-m-d\TH:i:s\Z', strtotime($filterStartDate));

                    /*if(!empty($ordersyncDetail)){
                        $start_date = new DateTime($ordersyncDetail->filter_end_date);
                        $sync_start_date = $start_date->format(DateTime::ATOM);
                    }else{
                        $sync_start_date = now()->subDays(15)->toIso8601String();
                    }*/

                    $sync_start_date = $filterStartDateFormatted;

                    Log::info('Start Date : ' . $sync_start_date);

                    // get shopify order
                    $shopifyService = new ShopifyService();
                    $orders = $shopifyService->getOrders($store, $sync_start_date);

                    if (isset($orders) && !empty($orders)) {

                        $created_by = auth()->user()->name ?? 'admin';
                        /* create order synchronization*/
                        $synchronizeArr = [
                            'store_id' => $store->id,
                            'synchronization_type' => OrderSyncronisationType::ORDER,
                            'filter_start_date' => $filterStartDate,
                            'filter_end_date' => now(),
                            'created_by' => $created_by
                        ];
                        $order_synchronization = OrderSynchronization::create($synchronizeArr);
                        $order_synchronization_id = $order_synchronization->id;

                        Log::info('order_synchronization_id: ' . json_encode($order_synchronization_id));

                        $noOfOrder = 0;
                        $last_order_update_date = null;
                        foreach ($orders as $value) {

                            $order = $value['node'];
                            $gid = $order['id'];
                            $parts = explode('/', $gid);
                            $order['id'] = end($parts);
                            Log::info('Shopify shopping Order: ' . json_encode($order));

                            /* check order id exist*/
                            $orderExist = Order::where('order_id', $order['id'])->count();
                            if ($orderExist) {
                                Log::info('Existing Order: ' . $orderExist);
                                $orderDetail = Order::where('order_id', $order['id'])->first();
                                $orderObj = Order::findOrFail($orderDetail->id); // Retrieve the product by ID
                                $orderOperationDetail = OrderOperation::where('order_id',$orderDetail->id)->first();

                                if(isset($order['displayFulfillmentStatus'])){
                                    switch (strtolower($order['displayFulfillmentStatus'])){
                                        case 'fulfilled';
                                            $orderObj->update(['order_status' => OrderStatus::Shipped]);
                                            /* check order operation for this order*/
                                            if(!empty($orderOperationDetail)){
                                                $orderOpObj = OrderOperation::findOrFail($orderOperationDetail->id);
                                                $orderOpObj->update(['status'=>OrderOperationStatus::MarkAsShipped]);
                                            }
                                            break;
                                    }
                                }

                                if(isset($order['cancelledAt']) && $order['cancelledAt'] != null){
                                    $orderObj->update(['order_status' => OrderStatus::Canceled]);
                                    if(!empty($orderOperationDetail)){
                                        $orderOpObj = OrderOperation::findOrFail($orderOperationDetail->id);
                                        $orderOpObj->update(['status'=>OrderOperationStatus::Canceled]);
                                    }
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

                                /* start - add customer shipping address*/
                                $customer_address_data = [
                                    'customer_id' => $customer_detail->id,
                                    'first_name' => isset($order['shippingAddress']['firstName']) ? $order['shippingAddress']['firstName'] : NULL,
                                    'last_name' => isset($order['shippingAddress']['lastName']) ? $order['shippingAddress']['lastName'] : NULL,
                                    'address_line_1' => $order['shippingAddress']['address1'],
                                    'address_line_2' => $order['shippingAddress']['address2'],
                                    'city' => $order['shippingAddress']['city'],
                                    'state' => $order['shippingAddress']['province'],
                                    'state_code' => $order['shippingAddress']['provinceCode'],
                                    'zip' => $order['shippingAddress']['zip'],
                                    'country' => $order['shippingAddress']['country'],
                                    'country_code' => $order['shippingAddress']['countryCodeV2'],
                                    'address_combined' => $order['shippingAddress']['address1'] . ' ' . $order['shippingAddress']['address2'],
                                ];

                                Log::info("Data " . json_encode($customer_address_data));

                                CustomerAddress::updateOrCreate([
                                    'customer_id' => $customer_detail->id,
                                    'address_combined' => $order['shippingAddress']['address1'] . ' ' . $order['shippingAddress']['address2'],
                                ], $customer_address_data);

                                $customer_address = CustomerAddress::where('customer_id', $customer_detail->id)
                                    ->where('address_combined', $order['shippingAddress']['address1'] . ' ' . $order['shippingAddress']['address2'])
                                    ->first();

                                /* end - add customer shipping address*/

                                /* start - add customers phone records*/
                                if(isset($order['phone']) && $order['phone'] != NULL) {
                                    $customer_phone_data = [
                                        'customer_id' => $customer_detail->id,
                                        'phone_no' => $order['phone'],
                                    ];

                                    CustomerPhone::updateOrCreate($customer_phone_data, $customer_phone_data);
                                }

                                /* end - add customers phone records*/

                                $orderstatus = OrderStatus::Synchronizing;
                                $orderOperationstatus = OrderOperationStatus::Created;

                                if(isset($order['displayFulfillmentStatus'])){
                                    switch (strtolower($order['displayFulfillmentStatus'])){
                                        case 'fulfilled';
                                            $orderstatus = OrderStatus::Shipped;
                                            $orderOperationstatus = OrderOperationStatus::MarkAsShipped;
                                            break;
                                    }
                                }

                                $orderData = [
                                    'order_id' => $order['id'],
                                    'order_date' => date('Y-m-d H:i:s', strtotime($order['createdAt'])),
                                    //'order_status' => OrderStatus::getOrderStatusId($order['displayFulfillmentStatus']),
                                    'order_status' => $orderstatus,
                                    'order_total' => $order['totalPrice'],
                                    'buyer_first_name' => isset($order['customer']) ? $order['customer']['firstName'] : NULL,
                                    'buyer_last_name' => isset($order['customer']) ? $order['customer']['lastName'] : NULL,
                                    'buyer_email' => $order['email'],
                                    'customer_id' => $customer_detail->id,
                                    'purchase_order_id' => isset($order['name']) ? str_replace('#','',$order['name']) : NULL,
                                    'store_id' => $store->id,
                                    'order_synchronization_id' => $order_synchronization_id,
                                    'shipping_address_id' => $customer_address->id,
                                    'requested_shipment_service' => isset($order['shippingLine']['title']) ? $order['shippingLine']['title'] : NULL,
                                    'requested_shipment_carrier' => 'Shopify',
                                ];
                                Log::info('Order Data: ' . json_encode($orderData));

                                $order_detail = Order::updateOrCreate([
                                    'order_id' => $order['id'],
                                ], $orderData);

                                $order_weight = $order['totalWeight'];
                                $rulesDetails = ShippingServiceRule::getOrderShippingCarrierService($order_weight);
                                $sync_message = NULL;
                                if(isset($order['risk']['assessments']['riskLevel']) && $order['risk']['assessments']['riskLevel'] != 'NONE'){
                                    $sync_message = '';
                                    if(isset($order['risk']['assessments']['facts']) && is_array($order['risk']['assessments']['facts'])){
                                        foreach($order['risk']['assessments']['facts'] as $facts){
                                            $sync_message .= $facts['description'].PHP_EOL;
                                        }
                                    }
                                }

                                /* create order operatation*/
                                $orderOperationData = [
                                    'order_id' => $order_detail->id,
                                    'ship_date' => date('Y-m-d H:i:s'),
                                    'insurance_value' => 0,
                                    'status' => $orderOperationstatus,
                                    'sync_message' => $sync_message,
                                    'signature_confirmation' => 1,
                                    'store_id' => $store->id,
                                    'shipping_package_id'=>ShippingPackage::getDefaultShippingPackage(),
                                    'operation_type' => OrderOperationType::OrderShipment,
                                    'label_creation_type' => 1,
                                    'print_to_file' => 0,
                                    'seller_provides_return_label' => 0,
                                    'order_synchronization_id' => $order_synchronization_id
                                ];

                                if(!empty($rulesDetails)){
                                    if(isset($rulesDetails->package_type_filter_id)){
                                        $orderOperationData['shipping_package_id'] = $rulesDetails->package_type_filter_id;
                                    }
                                    if(isset($rulesDetails->shipping_carrier_service_id)){
                                        $orderOperationData['shipping_carrier_service_id'] = $rulesDetails->shipping_carrier_service_id;
                                    }
                                    if(isset($rulesDetails->ShippingCarrierService->ShippingCarrier)){
                                        $orderOperationData['shipping_carrier_id'] = $rulesDetails->ShippingCarrierService->ShippingCarrier->id;
                                    }
                                }

                                $order_operation_detail = OrderOperation::updateOrCreate([
                                    'order_id' => $order_detail->id,
                                ], $orderOperationData);

                                /* get order items*/
                                if (isset($order['lineItems']['nodes'])) {
                                    foreach ($order['lineItems']['nodes'] as $item) {
                                        if ($item['sku'] != '') {
                                            Log::info('Shopify Order Item: ' . json_encode($item));
                                            //$itemDetail = ManageMarketplaceProduct::where('sku', $item['sku'])->first();

                                            $parts = explode('-', $item['sku']);

                                            $product_code = isset($parts[0]) ? $parts[0] : '';
                                            Log::info('Product Code: ' . $product_code);
                                            $product_detail = Catalog::where('product_sku',$product_code)->first();
                                            Log::info('Product Detail: ' . json_encode($product_detail));
                                            $orderItemData = [
                                                'title' => $item['name'],
                                                'order_id' => $order_detail->id,
                                                'product_id' => isset($product_detail->product_id) ? $product_detail->product_id : NULL,
                                                /*'client_id' => '',
                                                'listing_item_id' => '',*/
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
                                                    //'client_id' => '',
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

                            if ($last_order_update_date === null || strtotime($order['createdAt']) > strtotime($last_order_update_date)) {
                                $last_order_update_date = date('Y-m-d H:i:s', strtotime($order['createdAt']));
                            }

                            $noOfOrder++;
                        }

                        $osObject = OrderSynchronization::findOrFail($order_synchronization_id);
                        $osObject->update(['number_of_items'=>$noOfOrder,'last_order_update_date' => $last_order_update_date]);
                    }else{
                        Log::info("No Orders Found");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
        }
    }
}
