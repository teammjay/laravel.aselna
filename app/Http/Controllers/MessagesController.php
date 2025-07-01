<?php

namespace App\Http\Controllers;

use App\Constants\HdTicketStatus;
use App\Constants\HdTicketTypeCode;
use App\Constants\OrderStatus;
use App\Helpers\QueryFilters;
use App\Models\Catalog;
use App\Models\Customer;
use App\Models\HDMessage;
use App\Models\HdMessageTemplate;
use App\Models\HdTicketType;
use App\Models\LogisticCenter;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\OrderOperationItem;
use App\Models\OrderOperationReason;
use App\Models\ShippingCarrier;
use App\Models\ShippingCarrierService;
use App\Models\ShippingPackage;
use App\Models\Store;
use App\Models\StoreMessageAccount;
use App\Utils\EmailMessageUtility;
use App\Utils\RmaNumberGenerator;
use Illuminate\Http\Request;
use App\Services\DataTable\DataTableService;

class MessagesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stores = Store::orderBy('name','asc')->get();
        $ticketTypes = HdTicketType::all();
        return view('messages.list', compact('stores','ticketTypes'));
    }

    public function getMessageData(Request $request)
    {

        $draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];

        $query = Message::with('store','store.storeType','ticketType','customer');

        if($request->has('status') && $request->status != ''){
            $query->where('status',$request->status);
        }
        // Filter by ticket email
        QueryFilters::applyFilter($query, $request, 'sender_email', 'sender_email_operator', 'sender_email');

        // Filter by ticket type
        QueryFilters::applyFilter($query, $request, 'ticket_type', 'ticket_type_operator', 'ticket_type_id');

        // Filter by ticket code
        QueryFilters::applyFilter($query, $request, 'ticket_code', 'ticket_code_operator', 'ticket_code');

        // Filter by order id
        QueryFilters::applyFilter($query, $request, 'order_id', 'order_id_operator', 'order_id');

        // Filter by subject
        QueryFilters::applyFilter($query, $request, 'subject', 'subject_operator', 'subject');

        // Filter by notes
        QueryFilters::applyFilter($query, $request, 'notes', 'body_operator', 'body');

        // Filter by user id
        QueryFilters::applyFilter($query, $request, 'user_id', 'user_id_operator', 'user_id');

        QueryFilters::applyRelationFilter($query, $request, 'customer', 'first_name', 'first_name_operator', 'first_name');
        QueryFilters::applyRelationFilter($query, $request, 'customer', 'last_name', 'last_name_operator', 'last_name');

        if ($columnName != '') {
            switch (strtolower($columnName)) {
                case 'first_name':

                    $query->join('customers', 'customers.id', '=', 'hd_ticket.customer_id','left')
                        ->orderBy('customers.first_name', $columnSortOrder);
                    break;
                case 'last_name':

                    $query->join('customers', 'customers.id', '=', 'hd_ticket.customer_id','left')
                        ->orderBy('customers.last_name', $columnSortOrder);

                    break;

                case 'store':
                    $query->join('store', 'hd_ticket.store_id', '=', 'store.id')
                        ->orderBy('store.name', $columnSortOrder);

                    break;
                case 'ticket_type':
                    $query->join('hd_ticket_type', 'hd_ticket_type.id', '=', 'hd_ticket.ticket_type_id')
                        ->orderBy('hd_ticket_type.name', $columnSortOrder);
                    break;
            }


        }

        //$dataTableService = new DataTableService($query, $request);

        /*$dataTableService->setCustomSorting(function ($query, $request) {
            $columnIndex = $request->input('order.0.column', 0);
            $columnName = $request->input('columns.' . $columnIndex . '.name', 'id');
            $sortDirection = $request->input('order.0.dir', 'asc');
            $query->orderBy($columnName, $sortDirection);
        });*/

        //$data = $dataTableService->handle();

        $totalRecords = $query->count();
        $messages = $query->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($perPage)
            ->get();
        $totalRecordwithFilter = $totalRecords;

        $data = [];
        if(!empty((array) $messages)){
            foreach($messages as $key=>$message) {
                $important = '';
                if($message->important){
                    $important = '<i class="fa fa-star"></i>';
                }
                if($message->notes != ''){
                    $important .= '<i class="fa fa-exclamation-triangle"></i>';
                }
                $data[] = array(
                    "message_id" => '<input type="checkbox" class="message-id" name="message_id[]" value="'.$message->id.'" style="visibility:hidden;">'.$important,
                    "store" => $message->store->name.'-'.$message->store->storeType->name,
                    "first_name" => isset($message->customer) ? $message->customer->first_name : '' ,
                    "last_name" => isset($message->customer) ? $message->customer->last_name : '',
                    "subject" => $message->subject,
                    "ticket_date" =>  date('Y-m-d H:i',strtotime($message->ticket_date)),
                    "ticket_type" => $message->ticketType->name,
                    "status" =>  HdTicketStatus::getTicketStatus($message->status),
                    "order_id" => $message->order_id,
                    "ticket_code" => $message->ticket_code,
                    "read" => $message->message_read,
                    "created_at" => date('Y-m-d H:i',strtotime($message->created_at))
                );
            }
        }

        /*echo '<pre>';
        print_r($data);
        echo '<pre>';die;*/

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordwithFilter,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        );
        return $response;
    }

    public function need_action_list()
    {
        $stores = Store::orderBy('name','asc')->get();
        $ticketTypes = HdTicketType::all();
        return view('messages.action_list', compact('stores','ticketTypes'));
    }

    public function awaiting_list()
    {
        $stores = Store::orderBy('name','asc')->get();
        $ticketTypes = HdTicketType::all();
        return view('messages.awaiting_list', compact('stores','ticketTypes'));
    }

    public function closed_list()
    {
        $stores = Store::orderBy('name','asc')->get();
        $ticketTypes = HdTicketType::all();
        return view('messages.closed_list', compact('stores','ticketTypes'));
    }

    public function spam_list()
    {
        $stores = Store::orderBy('name','asc')->get();
        $ticketTypes = HdTicketType::all();
        return view('messages.spam_list', compact('stores','ticketTypes'));
    }

    public function trash_list()
    {
        $stores = Store::orderBy('name','asc')->get();
        $ticketTypes = HdTicketType::all();
        return view('messages.trash_list', compact('stores','ticketTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('messages.update');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        Message::create($request->all());

        return redirect()->route('message.index')
            ->with('success', 'Message created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $ticket = Message::findOrFail($id);
        return view('messages.edit', compact('ticket'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        Message::findOrFail($id)->update($request->all());

        return redirect()->route('message.index')
            ->with('success', 'Message updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        Message::findOrFail($id)->delete();

        return redirect()->route('message.index')
            ->with('success', 'Message deleted successfully.');
    }

    public function detail(string $id)
    {
        $ticketDetail = Message::with('store','store.storeType')->where('id',$id)->first();
        $ticketDetail->update(['message_read' => 1]);
        $store_type_id = $ticketDetail->store->storeType->id;
        $store_type_name = $ticketDetail->store->storeType->name;
        $message_template = HdMessageTemplate::where('store_type_id',$store_type_id)->get();
        $messages = HDMessage::where('ticket_id',$id)->orderBy('message_date','desc')->get();
        $ticketTypeCode = HdTicketType::orderBy('name')->get();
        $action = [];
        $rma_done = 0;
        if(isset($ticketDetail->order_id) && $ticketDetail->order_id != NULL && $ticketDetail->order_id !=''){
            $order_id = $ticketDetail->order_id;
            $orderDetail = Order::with('orderItem','orderItem.product','orderItem.product.image','customer','customerAddress','orderOperation')->where('order_id',$order_id)->first();

            if(isset($orderDetail->orderItem)){
                foreach($orderDetail->orderItem as $oitem){
                    $action['canbeShipped'] = $oitem->quantity - $oitem->shipped_quantity - $oitem->canceled_quantity;
                    $action['canbeRefund'] = $oitem->shipped_quantity - $oitem->canceled_quantity;
                    $action['canbeReplace'] = $oitem->shipped_quantity - $oitem->canceled_quantity;
                    $action['canbeReturned'] = $oitem->shipped_quantity - $oitem->refund_quantity - $oitem->return_request_quantity;
                }
            }

            if(isset($orderDetail->orderOperation) && $orderDetail->orderOperation->rma_number != '' && $orderDetail->orderOperation->rma_number != NULL){
                $rma_done = 1;
            }
        }

        $ticketHistory = '';
        if(isset($ticketDetail->customer_id)){
            $ticket_list = Message::where('customer_id',$ticketDetail->customer_id)->where('id','!=',$id)->get();
            if($ticket_list){
                $ticketHistory = view('messages.ticket_history', ['ticket_list' => $ticket_list])->render();
            }
        }

        //print_r($action);
        return view('messages.detail', compact('message_template','ticketDetail','messages','ticketTypeCode','action','store_type_name','ticketHistory','rma_done'));
    }

    public function searchOrder(Request $request){

        $order_id = $request->input('order_id');

        if($order_id){

            $orderDetail = Order::with('orderItem','orderItem.product','orderItem.product.image','customer','customerAddress')->where('order_id',$order_id)->first();
            /*echo '<pre>';
            print_r($orderDetail);
            echo '</pre>';die;*/

            if($orderDetail){
                $orderOperation = OrderOperation::with('carrier','shipFrom')->where('order_id',$orderDetail->id)->get();
                $data['items'] = view('messages.order_items', ['orderDetail' => $orderDetail])->render();
                $data['orderId'] = $orderDetail->order_id;
                $data['orderDate'] = $orderDetail->order_date;
                $data['orderStatus'] = OrderStatus::getOrderStatusName($orderDetail->order_status);
                $data['orderTotal'] = $orderDetail->order_total;
                $data['orderPOId'] = $orderDetail->purchase_order_id;
                $data['orderOperation'] = view('messages.order_operation', ['orderDetail' => $orderDetail,'orderOperation' => $orderOperation])->render();

                $ticketHistory = '';
                if(isset($orderDetail->customer_id)){
                    $ticket_list = Message::where('customer_id',$orderDetail->customer_id)->get();
                    if($ticket_list){
                        $ticketHistory = view('messages.ticket_history', ['ticket_list' => $ticket_list])->render();
                    }
                }

                if($orderOperation->rma_number != '' && $orderOperation->rma_number != NULL){
                    $rma_done = 1;
                }

                if(isset($orderDetail->orderItem)){
                    foreach($orderDetail->orderItem as $oitem){
                        $action['canbeShipped'] = $oitem->quantity - $oitem->shipped_quantity - $oitem->canceled_quantity;
                        $action['canbeRefund'] = $oitem->shipped_quantity - $oitem->canceled_quantity;
                        $action['canbeReplace'] = $oitem->shipped_quantity - $oitem->canceled_quantity;
                        $action['canbeReturned'] = $oitem->shipped_quantity - $oitem->refund_quantity - $oitem->return_request_quantity;
                    }


                }

                if(count($orderOperation ?? []) > 0) {
                    foreach($orderOperation as $op) {
                        if ($op->rma_number != '') {
                            $action['canbeReturned'] = 0;
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'orderDetail' => $data,
                    'ticketHistory' => $ticketHistory,
                    'action' => $action,
                    'rma_done' => $rma_done,
                ]);
            }
        }

        return response()->json([
            'success' => false
        ]);
    }

    public function searchCustomer(Request $request){

        $customer_id_name= $request->input('customer_id_name');

        if($customer_id_name){

            $customerDetail = Customer::with('emails','addresse')->where('id',$customer_id_name)->orWhere('full_name','%'.$customer_id_name.'%')->first();
            /*echo '<pre>';
            print_r($customerDetail);
            echo '</pre>';die;*/

            if($customerDetail){


                return response()->json([
                    'success' => true,
                    'customerDetail' => NULL
                ]);
            }
        }

        return response()->json([
            'success' => false
        ]);
    }

    public function getMessageTemplate(Request $request){
        $template_id = $request->input('template_id');
        $store_name = $request->input('store_name');
        $customer_name = $request->input('customer_name');
        $rma_number = $request->input('rma_number');
        if($template_id || $rma_number != ''){
            if($rma_number){
                $templateDetail = HdMessageTemplate::whereLike('name','%Return Without Label%')->first();
            }else {
                $templateDetail = HdMessageTemplate::findOrFail($template_id);
            }
            if($templateDetail){
                $data = [
                    'customer_firstName' => $customer_name,
                    'store_name' => $store_name,
                    'marketplaceName' => '',
                    'shipment_address' => '',
                    'trackingNumber' => '',
                    'return_address' => '',
                    'rma_number' => (isset($rma_number) && $rma_number != '') ? $rma_number : '',
                ];
                $template_code = \Blade::render($templateDetail->template_code, $data);
                return response()->json([
                    'success' => true,
                    'templateCode' => $template_code
                ]);
            }
        }

        return response()->json([
            'success' => false
        ]);
    }

    public function updateTicket(Request $request){

        $ticket_id= $request->input('ticket_id');
        $status = $request->input('status');
        $ticket_type_id = $request->input('ticket_type');
        $message_read = $request->input('message_read');
        $important = $request->input('important');
        $notes = $request->input('notes');
        if($ticket_id){

            $updated_by = auth()->user()->name ?? 'admin';

            $updatedData = [
                'updated_by' => $updated_by
            ];

            if(isset($status) && $status){
                $updatedData['status'] = $status;
            }

            if(isset($message_read)){
                $updatedData['message_read'] = $message_read;
            }

            if(isset($important) && $important){
                $updatedData['important'] = $important;
            }

            if(isset($ticket_type_id) && $ticket_type_id){
                $updatedData['ticket_type_id'] = $updatedData;
            }
            if($request->has('notes')){
                if($notes == ''){
                    $notes = NULL;
                }
                $updatedData['notes'] = $notes;
            }
            Message::findOrFail($ticket_id)->update($updatedData);

            return response()->json([
                'success' => true
            ]);
        }

        return response()->json([
            'success' => false
        ]);
    }

    public function sendMessageTemplate(Request $request){
        $ticket_id = $request->input('ticket_id');
        $message_to_send = $request->input('message_to_send');
        $customer_id = $request->input('customer_id');
        $template_id = $request->input('template_id');
        $is_closed = $request->input('ticket_status');

        if($ticket_id){
            $ticketDetail = Message::with('store')->where('id',$ticket_id)->first();
            $store= $ticketDetail->store;
            $storeMessageAccount = StoreMessageAccount::with('outgoingAccount')->where('store_id', $store->id)->first();


            //if(isset($storeMessageAccount->outgoingAccount)){
                if($storeMessageAccount->outgoingAccount->requires_ssl){
                    $encryption = 'ssl';
                }
                if($storeMessageAccount->outgoingAccount->requires_tls){
                    $encryption = 'tls';
                }

                /*$smtpConfig = [
                    'host' => $storeMessageAccount->outgoingAccount->server,
                    'port' => $storeMessageAccount->outgoingAccount->port,
                    'encryption' => $encryption,
                    'username' => $storeMessageAccount->outgoingAccount->user_id,,
                    'password' => $storeMessageAccount->outgoingAccount->password,
                    'from_address' => '86defa001@smtp-brevo.com',
                    'from_name' => 'Mj AILAB',
                ];*/

                $smtpConfig = [
                    'host' => 'email-smtp.us-east-1.amazonaws.com',
                    'port' => 465,
                    'encryption' => 'tls',
                    'username' => 'AKIATLGXH7OD45ES34GW',
                    'password' => 'BC7NSneeB1+Bl/H+ZlVjoBbdB7JpnD7CRoD9ff3hbEf3',
                    'from_address' => 'kryln.services@gmail.com',
                    'from_name' => 'kryln services',
                ];

                $imapConfig = [];

                $connector = new EmailMessageUtility($smtpConfig, $imapConfig);
                $response = $connector->sendEmail('jayesh@mjailab.com','Test Mail',$message_to_send);

                if($response){
                    if($is_closed){
                        $ticketDetail->update(['status'=>2]);
                    }
                    return response()->json([
                        'success' => true
                    ]);
                }
           // }
        }
        return response()->json([
            'success' => false
        ]);
    }

    public function getShippingDetail(Request $request)
    {

        $order_id = $request->input('order_id');
        $orderDetail = Order::with('orderOperation','customer','store')->where('order_id',$order_id)->first();
        $template_code = $template_id = '';
        /*echo '<pre>';
        print_r($orderDetail);
        echo '</pre>';die;*/
        if(isset($orderDetail->orderOperation)){
            $orderOperation = $orderDetail->orderOperation;
            $order_opration_id = $orderOperation->id;
            if($request->input('rma')){
                $rma_number = RmaNumberGenerator::generate($orderDetail->store_id);
                $clonedOrderOperation = [
                    'ship_date' => $orderOperation->ship_date,
                    'client' => $orderOperation->client,
                    'to_address_id' => $orderOperation->to_address_id,
                    'sync_status' => $orderOperation->sync_status,
                    'insurance_value' => $orderOperation->insurance_value,
                    'status' => 0,
                    'signature_confirmation' => $orderOperation->signature_confirmation,
                    'shipping_package_id' => $orderOperation->shipping_package_id,
                    'weight_value' => $orderOperation->weight_value,
                    'weight_weight_unit' => $orderOperation->weight_weight_unit,
                    'ship_from_id' => $orderOperation->ship_from_id,
                    'store_id' => $orderOperation->store_id,
                    'operation_type' => 2,
                    'order_id' => $orderOperation->order_id,
                    'label_creation_type' => 0,
                    'rma_number' => $rma_number,
                    'shipping_carrier_id' => $orderOperation->shipping_carrier_id,
                    'shipping_carrier_service_id' => NULL,
                    'shipping_account_transaction_id' => NULL,
                ];

                $newOrderOperation = OrderOperation::create($clonedOrderOperation);
                $order_opration_id = $newOrderOperation->id;
                $templateDetail = HdMessageTemplate::whereLike('name','%Return Without Label%')->first();
                if($templateDetail){
                    $data = [
                        'customer_firstName' => isset($orderDetail->customer) ? $orderDetail->customer->full_name : '',
                        'store_name' => isset($orderDetail->store) ? $orderDetail->store->name : '',
                        'ima_number' => $rma_number,
                        'return_address ' => ''
                    ];
                    $template_id = $templateDetail->id;
                    $template_code = \Blade::render($templateDetail->template_code, $data);
                }

            }


            $shippingCarrier = ShippingCarrier::all();
            $shipFrom = LogisticCenter::all();
            $shippingCarrierPackage = ShippingPackage::all();
            $shipmentDetail = OrderOperation::with('operationItem', 'order', 'order.customerAddress', 'carrierService', 'store', 'operationItem.orderItem', 'operationItem.orderItem.product')->where('id', $order_opration_id)->first();

            $package_id = '';
            $product_total_weight = 0;
            //print_r($shipmentDetail->operationItem);
            if (count($shipmentDetail->operationItem ?? []) > 0) {
                $product_weight = 0;
                foreach ($shipmentDetail->operationItem as $oitem) {
                    if (isset($oitem->orderItem->product)) {
                        $product_id = $oitem->orderItem->product->product_id;
                        if ($product_id) {
                            $product_detail = Catalog::findOrFail($product_id);
                            if ($product_detail->product_weight > $product_weight) {
                                $package_id = $product_detail->shipping_package_id;
                            }
                            if ($package_id) {
                                $shipmentDetail->shipping_package_id = $package_id;
                                $shipmentDetail->save();
                            }

                            $product_weight = $product_detail->product_weight;
                            $product_total_weight = $product_total_weight + $product_weight;
                        }
                    }
                }
            }else{
                if($request->input('rma')) {
                    $newshipmentDetail = OrderOperation::with('operationItem','operationItem.orderItem')->where('id', $orderOperation->id)->first();
                    if (count($newshipmentDetail->operationItem ?? []) > 0) {
                        foreach ($newshipmentDetail->operationItem as $oitem) {

                            $newOrderItem = [
                                'order_item_id' => $oitem->order_item_id,
                                'order_operation_id' => $order_opration_id,
                                'client_id' => $oitem->client_id,
                                'sync_status' => $oitem->sync_status,
                                'quantity' => $oitem->quantity,
                            ];

                            OrderOperationItem::updateOrCreate([
                                'order_item_id' => $oitem->order_item_id,
                                'order_operation_id' => $order_opration_id,
                            ], $newOrderItem);
                        }
                    }
                }
            }

            /*echo '<pre>';
            print_r($shipmentDetail);
            echo '</pre>';die;*/
            $shipping_carrier_id = $shipmentDetail->shipping_carrier_id;
            $shippingCarrierService = ShippingCarrierService::where('shipping_carrier_id', $shipping_carrier_id)->orderBy('name', 'asc')->get();

            if($request->input('rma')){
                $view = view('messages.make_rma', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight'))->render();
            }else if($request->input('ship_order')){
                $view = view('messages.ship_order', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight'))->render();
            }else if($request->input('return_lable')){
                $view = view('messages.order_return', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight'))->render();
            }else{
                $view = view('messages.order_refund', compact('shipmentDetail'))->render();
            }

            return response()->json([
                'success' => true,
                'html' => $view,
                'template_code' => $template_code,
                'template_id' => $template_id,
            ]);
        }

        return response()->json([
            'success' => false
        ]);
    }

    public function getCancelOrderDetail(Request $request)
    {
        $order_id = $request->input('order_id');
        $orderDetail = Order::with('orderOperation','customer','store')->where('order_id',$order_id)->first();

        $order_opration_id = $orderDetail->orderOperation->id;

        $shipmentDetail = OrderOperation::with('operationItem', 'store.storeType', 'order', 'operationItem.orderItem', 'operationItem.orderItem.product')->where('id', $order_opration_id)->first();

        $reasons = [];
        if (isset($shipmentDetail->store->storeType)) {
            $store_type = $shipmentDetail->store->storeType->id;
            $reasons = OrderOperationReason::where('store_type_id', $store_type)->where('operation_type', 1)->get();
        }

        $view = view('messages.cancel_order', compact('shipmentDetail', 'reasons'))->render();

        return response()->json([
            'success' => true,
            'html' => $view
        ]);
    }
}