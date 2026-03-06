<?php

namespace App\Http\Controllers;

use App\Constants\HdTicketStatus;
use App\Constants\HdTicketTypeCode;
use App\Constants\OrderOperationStatus;
use App\Constants\OrderOperationType;
use App\Constants\OrderStatus;
use App\Jobs\ProcessStoreMessageAccountJob;
use App\Models\Catalog;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerPhone;
use App\Models\HdTicketType;
use App\Models\ListingItem;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAttribute;
use App\Models\OrderOperation;
use App\Models\OrderOperationItem;
use App\Models\OrderSynchronization;
use App\Models\ShippingPackage;
use App\Models\Store;
use App\Services\Marketplace\Service\AmazonService;
use App\Services\Marketplace\Service\EbayService;
use App\Utils\shipment\ShipmentDocumentsCreator;
use Illuminate\Http\Request;
use App\Helpers\QueryFilters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrdersController extends Controller
{
    private $perPage = 25;

    public function index(Request $request)
    {
        $perPage = $this->perPage;
        /*$amazonServie = new AmazonService();
        $orders = $amazonServie->getOrders();
        echo '<pre>';
        print_r($orders);
        echo '</pre>';die;*/
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();

        return view('orders.list',compact('perPage','stores'));
    }

    public function filter(Request $request)
    {

        $draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];

        $query = Order::with('orderItem','orderItem.product','store','store.storeType','orderOperations','customer')->select('*');

        // Filter by store
        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('store_id', $request->store_id);
        }

        // Filter by sku
        if ($request->has('sku') && $request->sku != '') {
            $query->whereRelation('orderItem', 'sku', $request->sku);
        }

        // Filter by sku
        if ($request->has('tracking_number') && $request->tracking_number != '') {
            $query->whereRelation('orderOperations', 'tracking_number', $request->tracking_number);
        }

        // Filter by order id
        if ($request->has('order_id') && $request->order_id != '') {
            $query->whereLike('order_id', '%' . $request->order_id . '%');
        }

        // Filter by order date
        if ($request->has('order_date') && $request->order_date != '') {
            $query->whereDate('order_date', $request->order_date);
        }

        // Filter by first name
        if ($request->has('buyer_first_name') && $request->buyer_first_name != '') {
            $query->whereLike('buyer_first_name', '%' . $request->buyer_first_name . '%');
        }

        // Filter by last name
        if ($request->has('buyer_last_name') && $request->buyer_last_name != '') {
            $query->whereLike('buyer_last_name', '%' . $request->buyer_last_name . '%');
        }

        // Filter by purchase order id
        if ($request->has('purchase_order_id') && $request->purchase_order_id != '') {
            $query->whereLike('purchase_order_id', '%'.$request->purchase_order_id.'%');
        }

        // Filter by purchase order id
        if ($request->has('order_status') && $request->order_status != '') {
            $query->where('order_status', $request->order_status);
        }

        // Filter by created date
        if ($request->has('created_at') && $request->created_at != '') {
            $query->whereDate('created_at', $request->created_at);
        }

        // Filter by created date
        if ($request->has('buyer_username') && $request->buyer_username != '') {

            $query->where(function ($q) use ($request) {
                $q->whereRelation('customer', 'user_id', 'like', '%'.$request->buyer_username.'%')
                    ->orWhere('buyer_username', 'like', '%'.$request->buyer_username.'%');
            });

        }

        if ($request->has('buyer_email') && $request->buyer_email != '') {

            $query->where(function ($q) use ($request) {
                $q->whereRelation('customer', 'email', '=', $request->buyer_email)
                    ->orWhere('buyer_email', '=', $request->buyer_email);
            });

        }

        if ($request->has('customer_email') && $request->customer_email != '') {

            $query->where(function ($q) use ($request) {
                $q->whereRelation('customer', 'email', $request->customer_email);
            });

        }

        // Filter by product code
        if ($request->has('product_code') && $request->product_code != '') {
            $query->whereHas('orderItem', function ($q) use ($request) {
                $q->where('sku', 'like', '%' . $request->product_code . '%');
            });
        }

        if($columnName != ''){
            switch(strtolower($columnName)){
                case 'order_id':
                    $query->orderBy('order_id', $columnSortOrder);
                    break;
                case 'product_sku':
                    $query->orderBy('product_sku', $columnSortOrder);
                    break;
                case 'order_date':
                    $query->orderBy('order_date', $columnSortOrder);
                    break;
                case 'order_sku':
                    $query->orderBy('order_sku', $columnSortOrder);
                    break;
                case 'order_status':
                    $query->orderBy('order_status', $columnSortOrder);
                    break;
                case 'buyer_first_name':
                    $query->orderBy('buyer_first_name', $columnSortOrder);
                    break;
                case 'buyer_last_name':
                    $query->orderBy('buyer_last_name', $columnSortOrder);
                    break;
                case 'buyer_email':
                    $query->orderBy('buyer_email', $columnSortOrder);
                    break;
                case 'created_at':
                    $query->orderBy('created_at', $columnSortOrder);
                    break;
            }
        }
        //echo $query->toSql();die;
        $totalRecords = $query->count();
        $orders = $query->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($perPage)
            ->get();
        $totalRecordwithFilter = $totalRecords;
        /*echo '<pre>';
        print_r($orders);
        echo '</pre>';die;*/

        $data = [];
        if(!empty((array) $orders)){
            foreach($orders as $key=>$order) {
                $storeType = (isset($order->store->storeType) && !empty($order->store->storeType)) ? $order->store->storeType->name : '';
                $sku = '';
                $image = '<div class="d-flex" style="gap:5px">';
                if(isset($order->orderItem) && !empty($order->orderItem)){
                    foreach($order->orderItem as $oitem){
                        $sku .= $oitem->sku.' x '.$oitem->quantity.'<br>';
                        if(isset($oitem->product) && !empty($oitem->product)){
                            if(!empty($oitem->product->image)) {
                                $imagename = $oitem->product->product_image;
                                $image .= '<div class="main-image">
                                                <img src="'.asset('storage/'. $oitem->product->image->getFileUrl()).'" alt="'.$imagename.'" class="mainImage" width="70" height="70" data-product-id="'.$oitem->product->product_id.'">
                                            </div>';
                            }
                        }
                    }
                }
                $image .= '</div>';
                $data[] = array(
                    "order_id" => $order->order_id.'<input type="checkbox" class="pro-id" name="order_id[]" data-order="'.$order->order_id.'" value="'.$order->id.'" style="visibility:hidden;">',
                    "store" => $order->store->name.' - '.$storeType,
                    "order_date" => $order->order_date,
                    "order_image" => $image,
                    "order_sku" => $sku,
                    "order_status" =>  $order->order_status,
                    "first_name" => $order->buyer_first_name,
                    "last_name" =>  $order->buyer_last_name,
                    "phone" => $order->customer->phones[0]->phone_no ?? '',
                    "order_total" => $order->order_total,
                    "buyer_last_name" => $order->buyer_last_name,
                    "state" => isset($order->customerAddress) ? $order->customerAddress->state : '',
                    "purchase_order_id" => $order->purchase_order_id,
                    "customer" => $order->customer->full_name ?? '',
                );
            }
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordwithFilter,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        );

        return $response;

    }


    public function edit(string $id)
    {
        $orderDetail = Order::with('orderItem','orderItem.product','orderItem.listingItem','store','store.storeType','customer','customer.addresse','customerAddress','orderOperation','orderOperation.carrier')->findOrFail($id);
        /*echo '<pre>';
        print_r($orderDetail);
        echo '</pre>';die;*/
        $orderOperations = OrderOperation::with('carrier','shipFrom')->where('order_id',$id)->get();
        return view('orders.detail',compact('orderDetail', 'orderOperations'));
    }

    public function create_message(Request $request)
    {
        $id = $request->order_id;
        $suject = $request->subject;
        $orderDetail = Order::with('customer','orderItem','orderItem.product','store','store.storeType','customerAddress')->findOrFail($id);

        $customer_id = isset($orderDetail->customer) ? $orderDetail->customer->id : NULL;
        $customer_detail = Customer::where('id', $customer_id)->first();

        $ticket_exist = Message::where('related_order_id',$id)->first();

        if($ticket_exist){
            $ticket_id = $ticket_exist->id;
            //echo $ticket_id;die;
            //return redirect()->route('message.detail',$ticket_id);
            return response()->json([
                'success' => true,
                'url' => route('message.detail',$ticket_id)
            ]);
        }

        $ticket_code = $orderDetail->store->short_code . '-' . ProcessStoreMessageAccountJob::getNextTicketNumberForStore($orderDetail->store_id);

        $ticketTypeDetail = HdTicketType::where('code', HdTicketTypeCode::ORDER_INFORMATION)->first();
        $ticketData = [
            'ticket_email' => $customer_detail->email,
            'ticket_type_id' => $ticketTypeDetail->id,
            'status' => HdTicketStatus::NEEDS_ACTION,
            'related_order_id' => $orderDetail->id,
            'subject' => $suject,
            'customer_id' => $customer_id,
            'order_id' => trim($orderDetail->order_id),
            'store_id' => $orderDetail->store_id,
            'ticket_date' => date('Y-m-d H:i:s'),
            'ticket_code' => $ticket_code,
            'message_read' => 1,
            'replies' => 0,
            'important' => 0,
            'customer_merge_status' => 0,
            'ticket_user_id' => $customer_id,
        ];

        $hd_ticket = Message::create($ticketData);
        $ticket_id = $hd_ticket->id;
        /*echo '<pre>';
        print_r($orderDetail);
        echo '</pre>';die;*/
        //return redirect()->route('message.detail',$ticket_id);
        //return view('orders.create_message',compact('orderDetail'));

        return response()->json([
            'success' => true,
            'url' => route('message.detail',$ticket_id)
        ]);
    }

    public function importCsv(){
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();

        return view('orders.upload',compact('stores'));
    }

    public function upload(Request $request){

        try {
            $request->validate([
                'store_id' => 'required',
                'csv_file' => 'required|mimes:csv,txt'
            ]);


            $store_id = $request->input('store_id');
            $store = Store::where('id', $store_id)->first();
            $file = $request->file('csv_file');
            $path = $file->getRealPath();
            //$handle = fopen($file, 'r');

            // Get the headers (first row of CSV)
            if (($handle = fopen($path, 'r')) !== false) {
                $headers = fgetcsv($handle, 0, "\t");
                $rowNumber = 1;
                while (($row = fgetcsv($handle, 0, "\t")) !== false) {
                    if (count($row) !== count($headers)) {
                        $rowNumber++;
                        // Skip or handle error
                        Log::info("Row $rowNumber has a column mismatch. Skipping.\n");
                        continue; // or log it
                    }
                    $data = array_combine($headers, $row);

                    /*echo '<pre>';
                    print_r($data);
                    echo '</pre>';die;*/

                    /* check order id exist*/
                    $orderExist = Order::where('order_id', $data['order-id'])->count();
                    if ($orderExist) {
                        $order_detail = Order::where('order_id', $data['order-id'])->first();
                        //$orderObj = Order::findOrFail($order_detail->id); // Retrieve the product by ID
                        //$orderObj->update(['order_status' =>OrderStatus::getOrderStatusId('Synchronizing')]);

                        $firstname = $lastname = '';
                        $customer_address = null;
                        $customer_detail = null;

                        if (isset($data['buyer-name'])) {
                            $nameArr = explode(' ', $data['buyer-name']);
                            $firstname = isset($nameArr[0]) ? $nameArr[0] : $data['buyer-name'];
                            $lastname = isset($nameArr[1]) ? $nameArr[1] : '';
                        }

                        $customer_address = CustomerAddress::where('customer_id', $order_detail->customer_id)
                            ->where('address_combined', $data['ship-address-1'] . ' ' . $data['ship-address-2'])
                            ->first();

                        $orderData = [
                            'order_id' => $data['order-id'],
                            'buyer_first_name' => $firstname,
                            'buyer_last_name' => $lastname,
                            'buyer_email' => $data['buyer-email'],
                            'customer_id' => $order_detail->customer_id,
                            'store_id' => $store_id,
                            'shipping_address_id' => $customer_address->id,
                        ];

                        $order_detail = Order::updateOrCreate([
                            'order_id' => $data['order-id'],
                        ], $orderData);

                        /* get order item detail*/

                        $orderItem = OrderItem::where('order_id', $order_detail->id)->where('sku', $data['sku'])->first();

                        if ($orderItem == null) {

                            if (isset($data['sku']) && $data['sku'] != '') {
                                //$itemDetail = ManageMarketplaceProduct::where('sku', $item['sku'])->first();

                                $parts = explode('-', $data['sku']);
                                if (count($parts) > 2) {
                                    foreach ($parts as $ele) {
                                        $product_detail = Catalog::where('product_sku', $ele)->first();
                                        if ($product_detail) {
                                            $product_code = $ele;
                                            break;
                                        }
                                    }
                                } else {
                                    $product_code = isset($parts[0]) ? $parts[0] : '';
                                }
                                $product_detail = Catalog::where('product_sku', $product_code)->first();

                                $orderItemData = [
                                    'title' => $data['product-name'],
                                    'order_id' => $order_detail->id,
                                    'product_id' => isset($product_detail->product_id) ? $product_detail->product_id : NULL,
                                    'sku' => $data['sku'],
                                    'store_sku' => $data['order-item-id'],
                                    'quantity' => $data['quantity-purchased'],
                                    'id1' => $data['order-item-id'],
                                    'item_price' => isset($product_detail->product_marketplace_price) ? $product_detail->product_marketplace_price : 0,
                                    'shipping_price' => '0',
                                    'item_tax' => '0',
                                    'shipping_tax' => '0',
                                    'shipping_quantity' => $data['quantity-shipped'],
                                ];

                                $order_item = OrderItem::updateOrCreate([
                                    'order_id' => $order_detail->id,
                                    'sku' => $data['sku'],
                                ], $orderItemData);

                                /* create order operation item*/
                                $order_operation_detail = OrderOperation::where('order_id', $order_detail->id)->first();
                                if ($order_operation_detail == null) {

                                    $orderOperationData = [
                                        'order_id' => $order_detail->id,
                                        'insurance_value' => 0,
                                        'status' => OrderOperationStatus::Created,
                                        'signature_confirmation' => 1,
                                        'store_id' => $store->id,
                                        'shipping_package_id' => ShippingPackage::getDefaultShippingPackage(),
                                        'operation_type' => OrderOperationType::OrderShipment,
                                        'label_creation_type' => 1,
                                        'print_to_file' => 0,
                                        'seller_provides_return_label' => 0,
                                        'expected_delivery_date' => (isset($data['promise-date']) && $data['promise-date'] != '') ? date('Y-m-d H:i:s', strtotime($data['promise-date'])) : NULL,
                                        'expected_shipment_date' => (isset($data['reporting-date']) && $data['reporting-date'] != '') ? date('Y-m-d H:i:s', strtotime($data['reporting-date'])) : NULL
                                    ];

                                    $order_operation_detail = OrderOperation::updateOrCreate([
                                        'order_id' => $order_detail->id,
                                    ], $orderOperationData);
                                }

                                if ($order_item->id) {
                                    $orderOperationItemData = [
                                        'order_item_id' => $order_item->id,
                                        'order_operation_id' => $order_operation_detail->id,
                                        'sku' => $data['sku'],
                                        'quantity' => $data['quantity-purchased'],
                                        'sync_status' => 6
                                    ];

                                    OrderOperationItem::updateOrCreate([
                                        'order_operation_id' => $order_operation_detail->id,
                                        'order_item_id' => $order_item->id,
                                    ], $orderOperationItemData);
                                }
                            }
                        }
                    } else {

                        /* create customer*/
                        $firstname = $lastname = '';
                        $customer_address = null;
                        $customer_detail = null;

                        if (isset($data['buyer-name'])) {
                            $nameArr = explode(' ', $data['buyer-name']);
                            $firstname = isset($nameArr[0]) ? $nameArr[0] : $data['buyer-name'];
                            $lastname = isset($nameArr[1]) ? $nameArr[1] : '';
                        }

                        $customerData = [
                            'first_name' => $firstname,
                            'last_name' => $lastname,
                            'full_name' => isset($data['buyer-name']) ? $data['buyer-name'] : '',
                            'email' => $data['buyer-email']
                        ];

                        $customer_detail = Customer::updateOrCreate([
                            'email' => $data['buyer-email'],
                        ], $customerData);

                        $customer_address_data = [
                            'customer_id' => $customer_detail->id,
                            'address_line_1' => $data['ship-address-1'],
                            'address_line_2' => $data['ship-address-2'] ?? '',
                            'address_line_3' => $data['ship-address-3'] ?? '',
                            'city' => $data['ship-city'],
                            'state' => $data['ship-state'],
                            'zip' => explode('-', $data['ship-postal-code'] ?? '')[0],
                            'country' => $data['ship-country'],
                            'address_combined' => $data['ship-address-1'] . ' ' . $data['ship-address-2'],
                        ];

                        Log::info("Data " . json_encode($customer_address_data));

                        CustomerAddress::updateOrCreate([
                            'customer_id' => $customer_detail->id,
                            'address_combined' => $data['ship-address-1'] . ' ' . $data['ship-address-2'],
                        ], $customer_address_data);

                        $customer_address = CustomerAddress::where('customer_id', $customer_detail->id)
                            ->where('address_combined', $data['ship-address-1'] . ' ' . $data['ship-address-2'])
                            ->first();

                        if (isset($data['buyer-phone-number']) && $data['buyer-phone-number'] != null) {
                            $number = preg_replace('/\s+ext\..*/', '', $data['buyer-phone-number']);
                            $customer_phone_data = [
                                'customer_id' => $customer_detail->id,
                                'phone_no' => $number,
                            ];

                            CustomerPhone::updateOrCreate($customer_phone_data, $customer_phone_data);
                        }

                        $orderData = [
                            'order_id' => $data['order-id'],
                            'order_date' => date('Y-m-d H:i:s', strtotime($data['purchase-date'])),
                            'order_status' => OrderStatus::getOrderStatusId('Synchronizing'),
                            'buyer_first_name' => $firstname,
                            'buyer_last_name' => $lastname,
                            'buyer_email' => $data['buyer-email'],
                            'customer_id' => $customer_detail->id,
                            'purchase_order_id' => $data['purchase-order-number'],
                            'store_id' => $store_id,
                            'shipping_address_id' => $customer_address->id,
                        ];

                        $order_detail = Order::updateOrCreate([
                            'order_id' => $data['order-id'],
                        ], $orderData);

                        /* create order operatation*/
                        $orderOperationData = [
                            'order_id' => $order_detail->id,
                            'insurance_value' => 0,
                            'status' => OrderOperationStatus::Created,
                            'signature_confirmation' => (isset($data['signature-confirmation-recommended']) && $data['signature-confirmation-recommended']) ? 1 : 0,
                            'store_id' => $store->id,
                            'shipping_package_id' => ShippingPackage::getDefaultShippingPackage(),
                            'operation_type' => OrderOperationType::OrderShipment,
                            'label_creation_type' => 1,
                            'print_to_file' => 0,
                            'seller_provides_return_label' => 0,
                            'expected_delivery_date' => date('Y-m-d H:i:s', strtotime($data['promise-date'])),
                            'expected_shipment_date' => date('Y-m-d H:i:s', strtotime($data['reporting-date']))
                        ];

                        $order_operation_detail = OrderOperation::updateOrCreate([
                            'order_id' => $order_detail->id,
                        ], $orderOperationData);

                        if (isset($data['sku']) && $data['sku'] != '') {
                            //$itemDetail = ManageMarketplaceProduct::where('sku', $item['sku'])->first();

                            $parts = explode('-', $data['sku']);
                            if (count($parts) > 2) {
                                foreach ($parts as $ele) {
                                    $product_detail = Catalog::where('product_sku', $ele)->first();
                                    if ($product_detail) {
                                        $product_code = $ele;
                                        break;
                                    }
                                }
                            } else {
                                $product_code = isset($parts[0]) ? $parts[0] : '';
                            }
                            $product_detail = Catalog::where('product_sku', $product_code)->first();

                            $orderItemData = [
                                'title' => $data['product-name'],
                                'order_id' => $order_detail->id,
                                'product_id' => isset($product_detail->product_id) ? $product_detail->product_id : NULL,
                                'sku' => $data['sku'],
                                'store_sku' => $data['order-item-id'],
                                'quantity' => $data['quantity-purchased'],
                                'id1' => $data['order-item-id'],
                                'item_price' => isset($product_detail->product_marketplace_price) ? $product_detail->product_marketplace_price : 0,
                                'shipping_price' => '0',
                                'item_tax' => '0',
                                'shipping_tax' => '0',
                                'shipping_quantity' => $data['quantity-shipped'],
                            ];

                            $order_item = OrderItem::updateOrCreate([
                                'order_id' => $order_detail->id,
                                'sku' => $data['sku'],
                            ], $orderItemData);

                            /* create order operation item*/
                            if ($order_item->id) {
                                $orderOperationItemData = [
                                    'order_item_id' => $order_item->id,
                                    'order_operation_id' => $order_operation_detail->id,
                                    'sku' => $data['sku'],
                                    'quantity' => $data['quantity-purchased'],
                                    'sync_status' => 6
                                ];

                                OrderOperationItem::updateOrCreate([
                                    'order_operation_id' => $order_operation_detail->id,
                                    'order_item_id' => $order_item->id,
                                ], $orderOperationItemData);
                            }
                        }

                        /* get order items*/
                        /*$amazonService = new AmazonService();
                        $ordersItems = $amazonService->getOrderItems($data['order-id'],$store);
                        if (!isset($ordersItems['error'])) {
                            foreach($ordersItems as $item){
                                //Log::info('Amazon Order Item: ' . json_encode($item));
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

                                if ($order_item->id) {
                                    $orderOperationItemData = [
                                        'order_item_id' => $order_item->id,
                                        'order_operation_id' => $order_operation_detail->id,
                                        'quantity' => $item['QuantityOrdered'],
                                        'client_id' => '',
                                        'sync_status' => 6
                                    ];

                                    $order_operation_item = OrderOperationItem::updateOrCreate([
                                        'order_item_id' => $order_item->id,
                                        'order_operation_id' => $order_operation_detail->id
                                    ], $orderOperationItemData);
                                }
                            }

                        }*/

                    }
                }

                fclose($handle);
            }
        }catch (\Exception $e) {
            // Handle the exception and show an error message
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'CSV Imported Successfully');
    }

    public function getCustomerDetail(Request $request){
        $customer_id = $request->input('customer_id');
        if($customer_id){
            $customerDetail = Customer::with('phones')->where('id',$customer_id)->first();

            $view = view('orders.edit_customer', compact('customerDetail'))->render();

            return response()->json([
                'success' => true,
                'html' => $view
            ]);
        }
    }

    public function saveCustomer(Request $request){

        $id = $request->input('id');
        try {
            $customer = Customer::findOrFail($id);
            $name = $request['full_name'] = $request['first_name'].' '.$request['last_name'];
            $customer->update($request->except(['phone', 'id']));
            if(isset($request->phone)){
               foreach ($request->phone as $key=>$value){
                    $customerPhone = CustomerPhone::findOrFail($key);
                    $customerPhone->update(['phone_no'=>$value]);
                }
            }
            return response()->json(['success' => true,'name'=>$name, 'message' => 'Data saved successfully!']);

        }catch (\Exception $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

    public function reprintLabel(Request $request)
    {
        $operationId = $request->input('operation_id');
        $orderOperation = OrderOperation::with('carrier')->find($operationId);

        if ($orderOperation) {
            $shippingDocumentCreator = new ShipmentDocumentsCreator($orderOperation);
            $printer = $shippingDocumentCreator->getPrinter();

            $trackingNumber = $orderOperation->tracking_number;
            $imageUrl = asset('storage/labels/' . $trackingNumber . '.txt');

            DB::connection('aselna_printer_db')->table('PrintJobs')->insert([
                'id' => (string)Str::uuid(),
                'fileName' => $trackingNumber . '.txt',
                'filePath' => $imageUrl,
                'status' => 'pending',
                'createdAt' => now(),
                'updatedAt' => now(),
                'fileSize' => 0,
                'printerId' => $printer->id ?? null,
                'systemId' => $printer->internal_name ?? null,
            ]);
        }
        return back()->withErrors(['error' => 'Order not found.']);
    }

    public function getOrderItemAttributes(Request $request){
        try {
            $order_item_id = $request->input('order_item_id');

            if ($order_item_id) {
                $orderItemAttributes = OrderItemAttribute::where('order_item_id', $order_item_id)->get()->toArray();

                return response()->json(['success' => true, 'attr' => $orderItemAttributes, 'message' => 'Data saved successfully!']);
            }

            return response()->json(['success' => false, 'message' => 'No order item found!']);
        }catch (\Exception $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

}
