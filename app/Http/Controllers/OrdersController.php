<?php

namespace App\Http\Controllers;

use App\Constants\OrderStatus;
use App\Models\Customer;
use App\Models\CustomerPhone;
use App\Models\ListingItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use App\Models\OrderSynchronization;
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

        $query = Order::with('orderItem','orderItem.product','store','store.storeType')->select('*');

        // Filter by store
        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('store_id', $request->store_id);
        }

        // Filter by sku
        if ($request->has('sku') && $request->sku != '') {
            $query->whereRelation('orderItem', 'sku', $request->sku);
        }

        // Filter by order id
        if ($request->has('order_id') && $request->order_id != '') {
            $query->where('order_id', $request->order_id);
        }

        // Filter by order date
        if ($request->has('order_date') && $request->order_date != '') {
            $query->whereDate('order_date', $request->order_date);
        }

        // Filter by first name
        if ($request->has('buyer_first_name') && $request->buyer_first_name != '') {
            $query->where('buyer_first_name', $request->buyer_first_name);
        }

        // Filter by last name
        if ($request->has('buyer_last_name') && $request->buyer_last_name != '') {
            $query->where('buyer_last_name', $request->buyer_last_name);
        }

        // Filter by purchase order id
        if ($request->has('purchase_order_id') && $request->purchase_order_id != '') {
            $query->where('purchase_order_id', $request->purchase_order_id);
        }

        // Filter by purchase order id
        if ($request->has('order_status') && $request->order_status != '') {
            $query->where('order_status', $request->order_status);
        }

        // Filter by created date
        if ($request->has('created_at') && $request->created_at != '') {
            $query->whereDate('created_at', $request->created_at);
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
                    "order_id" => $order->order_id.'<input type="checkbox" class="pro-id" name="order_id[]" value="'.$order->id.'" style="visibility:hidden;">',
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
        $orderDetail = Order::with('orderItem','orderItem.product','store','store.storeType','customer','customerAddress','orderOperation','orderOperation.carrier')->findOrFail($id);
        /*echo '<pre>';
        print_r($orderDetail);
        echo '</pre>';die;*/
        $orderOperations = OrderOperation::with('carrier','shipFrom')->where('order_id',$id)->get();
        return view('orders.detail',compact('orderDetail', 'orderOperations'));
    }

    public function create_message(string $id)
    {
        $orderDetail = Order::with('orderItem','orderItem.product','store','store.storeType','customerAddress')->findOrFail($id);
        /*echo '<pre>';
        print_r($orderDetail);
        echo '</pre>';die;*/
        return view('orders.create_message',compact('orderDetail'));
    }

    public function importCsv(){
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();

        return view('orders.upload',compact('stores'));
    }

    public function upload(Request $request){

        $request->validate([
            'csv_file' => 'required|mimes:csv,txt'
        ]);
        $store_id = $request->input('store_id');
        $store = Store::where('id',$store_id)->first();
        $file = $request->file('csv_file');
        $handle = fopen($file, 'r');

        // Get the headers (first row of CSV)
        $headers = fgetcsv($handle);

        $created_by = auth()->user()->name ?? 'admin';
        /* create order synchronization*/
        /*$synchronizeArr = [
            'store_id' => $store_id,
            'number_of_items' => 1,
            'synchronization_type' => '',
            'filter_start_date' => date('Y-m-d H:i:s'),
            'filter_end_date' => '',
            'last_order_update_date' => date('Y-m-d H:i:s'),
            'created_by' => $created_by
        ];
        $order_synchronization = OrderSynchronization::create($synchronizeArr);
        $order_synchronization_id = $order_synchronization->id;*/

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
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
            $orderExist = Order::where('order_id',$data['order-id'])->count();
            if($orderExist){
                $orderDetail = Order::where('order_id',$data['order-id'])->first();
                $orderObj = Order::findOrFail($orderDetail->id); // Retrieve the product by ID
                $orderObj->update(['order_status' =>OrderStatus::getOrderStatusId('Pending')]);
            }else {

                /* create customer*/

                $customerData = [
                    'first_name' => isset($data['buyer-name']) ? $data['buyer-name'] : '',
                    'last_name' => '',
                    'full_name' => isset($data['buyer-name']) ? $data['buyer-name'] : '',
                    'email' => $data['buyer-email']
                ];

                $customer_detail = Customer::updateOrCreate([
                    'order_id' => $data['order-id'],
                ], $customerData);

                $orderData = [
                    'order_id' => $data['order-id'],
                    'order_date' => date('Y-m-d H:i:s', strtotime($data['purchase-date'])),
                    'order_status' => OrderStatus::getOrderStatusId('Pending'),
                    //'order_total' => '',
                    'buyer_first_name' => $data['buyer-name'],
                    'buyer_last_name' => '',
                    'buyer_email' => $data['buyer-email'],
                    'customer_id' => $customer_detail->id,
                    'purchase_order_id' => $data['order-id'],
                    'store_id' => $store_id,
                    //'order_synchronization_id' => $order_synchronization_id,
                ];

                $order_detail = Order::updateOrCreate([
                    'order_id' => $data['order-id'],
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
                    'seller_provides_return_label' => 0
                ];

                $order_operation_detail = OrderOperation::updateOrCreate([
                    'order_id' => $order_detail->id,
                ], $orderOperationData);

                /* get order items*/
                $amazonService = new AmazonService();
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
                    }

                }

                /* get order items*/
                /*$orderItemData = [
                    'title' => $data['product-name'],
                    'order_id' => $order_detail->id,
//                            'product_id' => $itemDetail->storeProduct->product->product_id,
//                            'client_id' => $itemDetail->client,
//                            'listing_item_id' => $itemDetail->id,
                    'sku' => $data['sku'],
                    'store_sku' => $data['order-item-id'],
                    'quantity' => $data['quantity-purchased'],
                    'id1' => '',
                    //'item_price' => $item['ItemPrice']['Amount'],
                    //'shipping_price' => isset($item['ShippingPrice']) ? $item['ShippingPrice']['Amount'] : '0',
                    //'item_tax' => $item['ItemTax']['Amount'],
                    //'shipping_tax' => isset($item['ShippingTax']) ? $item['ShippingTax']['Amount'] : '0',
                    'shipping_quantity' => $data['quantity-shipped'],
                ];

                $order_item = OrderItem::updateOrCreate([
                    'order_id' => $order_detail->id
                ],$orderItemData);*/
            }


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

        }catch (QueryException $e) {
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

}
