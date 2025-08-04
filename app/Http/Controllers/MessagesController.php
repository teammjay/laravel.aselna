<?php

namespace App\Http\Controllers;

use App\Constants\HdTicketStatus;
use App\Constants\HdTicketTypeCode;
use App\Constants\OrderOperationStatus;
use App\Constants\OrderOperationType;
use App\Constants\OrderStatus;
use App\Constants\ShippingLabelCreationType;
use App\Helpers\QueryFilters;
use App\Jobs\ProcessStoreMessageAccountJob;
use App\Models\Catalog;
use App\Models\Customer;
use App\Models\HDMessage;
use App\Models\HdMessageAttachment;
use App\Models\HdMessageTemplate;
use App\Models\HdTicketType;
use App\Models\LogisticCenter;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\OrderOperationItem;
use App\Models\OrderOperationReason;
use App\Models\PostalAddress;
use App\Models\ShippingCarrier;
use App\Models\ShippingCarrierService;
use App\Models\ShippingPackage;
use App\Models\Store;
use App\Models\StoreMessageAccount;
use App\Models\SysFile;
use App\Utils\EmailMessageUtility;
use App\Utils\order\OrderOperationUtility;
use App\Utils\RmaNumberGenerator;
use Illuminate\Http\Request;
use App\Services\DataTable\DataTableService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

        $query = Message::with('store','store.storeType','ticketType','customer','order.orderOperations');

        if($request->has('status') && $request->status != ''){
            $query->where('status',$request->status);
        }
        // Filter by ticket email
        QueryFilters::applyFilter($query, $request, 'sender_email', 'sender_email_operator', 'sender_email');

        // Filter by ticket type
        QueryFilters::applyFilter($query, $request, 'ticket_type_id', 'ticket_type_operator', 'ticket_type');

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

        // Filter by store id
        if($request->has('store_id') && $request->store_id != ''){
            $query->where('store_id',$request->store_id);
        }

        QueryFilters::applyRelationFilter($query, $request, 'customer', 'first_name', 'first_name_operator', 'first_name');
        QueryFilters::applyRelationFilter($query, $request, 'customer', 'last_name', 'last_name_operator', 'last_name');
        QueryFilters::applyRelationFilter($query, $request, 'order.orderOperations', 'rma_number', 'rma_operator', 'rma');

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

                default:
                    $query->orderBy($columnName, $columnSortOrder);
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
        //echo $query->toSql();die;
        $totalRecords = $query->count();
        $messages = $query->skip($start)
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
    public function create(string $orderid)
    {
        $orderDetail = Order::with('customer','store','store.storeType')->where('id',$orderid)->first();
        $customer_id = isset($orderDetail->customer) ? $orderDetail->customer->id : NULL;
        $customer_detail = Customer::where('id',$customer_id)->first();
        $store_type_name = $orderDetail->store->name;

        $ticket_exist = Message::where('related_order_id',$orderid)->first();

        if($ticket_exist){
            $ticket_id = $ticket_exist->id;
            //echo $ticket_id;die;
            return redirect()->route('message.detail',$ticket_id);
        }

        //$ticketDetail = Message::with('store','store.storeType')->where('id',$ticket_id)->first();
        $message_template = HdMessageTemplate::whereNull('order_operation_type')->orderBy('name')->get();
        /*echo '<pre>';
        print_r($messages);
        echo '</pre>';die;*/
        $ticketTypeCode = HdTicketType::orderBy('name')->get();
        $action = [];

        //print_r($action);
        return view('messages.create', compact('message_template','orderDetail','ticketTypeCode','customer_detail','store_type_name'));
    }

    public function saveTicket(Request $request){

        $order_id = $request->input('order_id');
        $subject = $request->input('subject');

        if($order_id && $subject) {

            $orderDetail = Order::with('customer', 'store', 'store.storeType')->where('order_id', $order_id)->first();
            $customer_id = isset($orderDetail->customer) ? $orderDetail->customer->id : NULL;
            $customer_detail = Customer::where('id', $customer_id)->first();

            $ticket_code = $orderDetail->store->short_code . '-' . ProcessStoreMessageAccountJob::getNextTicketNumberForStore($orderDetail->store_id);

            $ticketTypeDetail = HdTicketType::where('code', HdTicketTypeCode::ORDER_INFORMATION)->first();
            $ticketData = [
                'ticket_type_id' => $ticketTypeDetail->id,
                'status' => HdTicketStatus::NEEDS_ACTION,
                'related_order_id' => $orderDetail->id,
                'subject' => $subject,
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

            if ($ticket_id) {
                //return redirect()->route('message.detail',$ticket_id);
                return response()->json([
                    'success' => true,
                    'ticket_id' => $ticket_id,
                    'ticket_code' => $ticket_code,
                ]);
            }
        }

        return response()->json([
            'success' => false
        ]);
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
        $type = 'detail';
        $ticketDetail = Message::with('store','store.storeType')->where('id',$id)->first();
        $ticketDetail->update(['message_read' => 1]);
        $store_type_id = $ticketDetail->store->storeType->id;
        $store_type_name = $ticketDetail->store->name;
        $message_template = HdMessageTemplate::whereNull('order_operation_type')->orderBy('name')->get();
        $messages = HDMessage::with('attachments')->where('ticket_id',$id)->orderBy('message_date','asc')->get();
        $ticketTypeCode = HdTicketType::orderBy('name')->get();
        $action = [];
        $template = [];
        $rma_done = $lable_generate = 0;
        if(isset($ticketDetail->order_id) && $ticketDetail->order_id != NULL && $ticketDetail->order_id !=''){
            $order_id = $ticketDetail->order_id;
            $orderDetail = Order::with('orderItem','orderItem.product','orderItem.product.image','customer','customerAddress','orderOperation')->where('order_id',$order_id)->orWhere('purchase_order_id',$order_id)->first();

            if($orderDetail) {
                $orderOperations = OrderOperation::with('carrier', 'shipFrom', 'operationItem','order','order.customer','store')->where('order_id', $orderDetail->id)->orderBy('created_at', 'desc')->get();

                if (isset($orderDetail->orderItem)) {
                    $canbeShipped = $canbeRefund = $canbeReplace = $canbeReturned = 0;
                    foreach ($orderDetail->orderItem as $oitem) {
                        $canbeShipped += $oitem->quantity - $oitem->shipped_quantity - $oitem->canceled_quantity;
                        $canbeRefund += $oitem->shipped_quantity - $oitem->canceled_quantity;
                        $canbeReplace += $oitem->shipped_quantity - $oitem->canceled_quantity;
                        $canbeReturned += $oitem->shipped_quantity - $oitem->refund_quantity - $oitem->return_request_quantity;
                    }

                    $action = [
                        'canbeShipped' => $canbeShipped,
                        'canbeRefund' => $canbeRefund,
                        'canbeReplace' => $canbeReplace,
                        'canbeReturned' => $canbeReturned,
                    ];
                }

                if (count($orderOperations ?? []) > 0) {
                    $returnOperation = 0;
                    $returnOperationCnt = 0;
                    foreach ($orderOperations as $key=>$op) {
                        if (count($op->operationItem ?? []) > 0) {
                            foreach ($op->operationItem as $oop) {
                                if ($oop->return_operation_item_id) {
                                    $returnOperation += 1;
                                }
                            }
                        }

                        if (isset($op) && $op->operation_type == OrderOperationType::OrderReturn && $op->label_creation_type == ShippingLabelCreationType::NoLabel && $returnOperation == 0) {
                            $returnOperationCnt += 1;
                        }

                        if($key == 0){

                            if($op->operation_type == OrderOperationType::OrderReturn && $op->tracking_number != ''){
                                $templateDetail = HdMessageTemplate::getOrderOperationTemplate(OrderOperationType::OrderReturn, 0, 1);
                                $imageName = $op->tracking_number.'.jpg';
                                $template['imageName'] = $imageName;
                                $template['imageUrl'] = asset('storage/labels/' .$imageName );
                                $template['storagePath'] = storage_path('app/public/labels/' . $imageName);
                            }else if($op->operation_type == OrderOperationType::OrderReturn && ($op->tracking_number == null || $op->tracking_number)){
                                $templateDetail = HdMessageTemplate::getOrderOperationTemplate(OrderOperationType::OrderReturn, 0, 0);

                            }

                            $rma_number = $op->rma_number;

                            $returnAddress = $this->getReturnAddress($op);

                            if(isset($templateDetail)){
                                $customer_name = isset($op->order->customer) ? $op->order->customer->full_name : '';
                                $store_name = isset($op->store) ? $op->store->name : '';
                                $data = [
                                    'customer_firstName' => $customer_name,
                                    'store_name' => $store_name,
                                    'marketplaceName' => '',
                                    'shipment_address' => '',
                                    'trackingNumber' => isset($op->tracking_number) ? $op->tracking_number : '',
                                    'return_address' => $returnAddress,
                                    'carrier_name' => isset($op->carrier) ? $op->carrier->name : '',
                                    'rma_number' => (isset($rma_number) && $rma_number != '') ? $rma_number : '',
                                ];
                                $template_code = \Blade::render($templateDetail->template_code, $data);

                                $template['template_id'] = $templateDetail->id;
                                $template['template_code'] = \Blade::render($templateDetail->template_code, $data);
                            }
                        }

                    }

                    $action['returnLable'] = $returnOperationCnt;
                }


            }
        }

        $ticketHistory = '';
        if(isset($ticketDetail->customer_id)){
            $ticket_list = Message::where('customer_id',$ticketDetail->customer_id)->where('id','!=',$id)->orderBy('created_at','desc')->get();
            if($ticket_list){
                $ticketHistory = view('messages.ticket_history', ['ticket_list' => $ticket_list])->render();
            }
        }


        //print_r($action);
        return view('messages.detail', compact('message_template','ticketDetail','messages','ticketTypeCode','action','store_type_name','ticketHistory','rma_done','lable_generate','type','template'));
    }

    public function searchOrder(Request $request){

        $order_id = $request->input('order_id');
        $ticket_id = $request->input('ticket_id');

        if($order_id){

            $orderDetail = Order::with('orderItem','orderItem.product','orderItem.product.image','customer','customerAddress')->where('order_id',$order_id)->orWhere('purchase_order_id',$order_id)->first();
            if($orderDetail){
                if($ticket_id) {
                    $ticketDetail = Message::findOrFail($ticket_id);
                    $ticketDetail->update(['order_id' => $orderDetail->order_id]);
                }
                $orderOperations = OrderOperation::with('carrier','shipFrom','operationItem')->where('order_id',$orderDetail->id)->orderBy('created_at','desc')->get();
                $data['items'] = view('messages.order_items', ['orderDetail' => $orderDetail])->render();
                $data['orderId'] = $orderDetail->order_id;
                $data['orderDate'] = $orderDetail->order_date;
                $data['orderStatus'] = OrderStatus::getOrderStatusName($orderDetail->order_status);
                $data['orderTotal'] = $orderDetail->order_total;
                $data['orderPOId'] = $orderDetail->purchase_order_id;
                $data['orderOperation'] = view('messages.order_operation', ['orderDetail' => $orderDetail,'orderOperation' => $orderOperations])->render();

                $ticketHistory = '';
                if(isset($orderDetail->customer_id)){
                    $ticket_list = Message::where('customer_id',$orderDetail->customer_id)->orderBy('created_at','desc')->get();
                    if($ticket_list){
                        $ticketHistory = view('messages.ticket_history', ['ticket_list' => $ticket_list])->render();
                    }
                }
                $rma_done = 0;
                $lable_generate = 0;


                if(isset($orderDetail->orderItem)){
                    $canbeShipped = $canbeRefund = $canbeReplace = $canbeReturned = 0;
                    foreach($orderDetail->orderItem as $oitem){
                        $canbeShipped += $oitem->quantity - $oitem->shipped_quantity - $oitem->canceled_quantity;
                        $canbeRefund += $oitem->shipped_quantity - $oitem->canceled_quantity;
                        $canbeReplace += $oitem->shipped_quantity - $oitem->canceled_quantity;
                        $canbeReturned += $oitem->shipped_quantity - $oitem->refund_quantity - $oitem->return_request_quantity;
                    }

                    $action = [
                        'canbeShipped' => $canbeShipped,
                        'canbeRefund' => $canbeRefund,
                        'canbeReplace' => $canbeReplace,
                        'canbeReturned' => $canbeReturned,
                    ];
                }

                if(count($orderOperations ?? []) > 0) {
                    $returnOperation = 0;
                    $returnOperationCnt = 0;
                    foreach($orderOperations as $op) {
                        if(count($op->operationItem ?? []) > 0) {
                            foreach($op->operationItem as $oop) {
                                if($oop->return_operation_item_id){
                                    $returnOperation += 1;
                                }
                            }
                        }

                        if(isset($op) && $op->operation_type == OrderOperationType::OrderReturn && $op->label_creation_type == ShippingLabelCreationType::NoLabel && $returnOperation == 0){
                            $returnOperationCnt += 1;
                        }
                    }

                    $action['returnLable'] = $returnOperationCnt;
                }
                return response()->json([
                    'success' => true,
                    'orderDetail' => $data,
                    'ticketHistory' => $ticketHistory,
                    'action' => $action,
                    'rma_done' => $rma_done,
                    'lable_generate' => $lable_generate,
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
        $operation_id = $request->input('operation_id');
        $ticket_id = $request->input('ticket_id');
        if($template_id || (isset($rma_number) && $rma_number != '')){
            $returnAddress = $imageName = $imageUrl = $imagesPath = "";

            if(isset($operation_id) && $operation_id != ''){
                $orderOperation = OrderOperation::with('shipFrom','shipFrom.returnAddress','carrier','store')->where('id',$operation_id)->first();
                /*echo '<pre>';
                print_r($orderOperation);
                echo '</pre>';die;*/

                if($orderOperation->operation_type == OrderOperationType::OrderReturn && $orderOperation->tracking_number){
                    $templateDetail = HdMessageTemplate::getOrderOperationTemplate(OrderOperationType::OrderReturn, 0, 1);
                    //$attachment = HdMessageAttachment::with('imageObj')->where('message_id',$ticket_id)->first();
                    //$imageObj = $attachment->imageObj;
                    //$imageName = $imageObj->name;
                    $imageName = $orderOperation->tracking_number.'.jpg';
                    //$storagePath = public_path($imageObj->location.$imageObj->name);
                    $storagePath = storage_path('app/public/labels/'.$imageName);
                    $imagesPath = $storagePath;
                    $imageUrl = asset('storage/labels/' .$imageName );
                }else if($orderOperation->operation_type == OrderOperationType::OrderReturn && $orderOperation->rma_number){
                    $templateDetail = HdMessageTemplate::getOrderOperationTemplate(OrderOperationType::OrderReturn, 0, 0);
                }else {
                    $templateDetail = HdMessageTemplate::findOrFail($template_id);
                }

                //print_r($templateDetail);die;

                $returnAddress = $this->getReturnAddress($orderOperation);

                /*if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->company != ''){
                    $returnAddress .= trim($orderOperation->shipFrom->returnAddress->company).PHP_EOL;
                }
                if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->address_line_1 != ''){
                    $returnAddress .= trim($orderOperation->shipFrom->returnAddress->address_line_1).PHP_EOL;
                }
                if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->address_line_2 != ''){
                    $returnAddress .= trim($orderOperation->shipFrom->returnAddress->address_line_2).PHP_EOL;
                }
                if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->address_line_3 != ''){
                    $returnAddress .= trim($orderOperation->shipFrom->returnAddress->address_line_3).PHP_EOL;
                }
                if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->city != ''){
                    $returnAddress .= trim($orderOperation->shipFrom->returnAddress->city);
                }
                if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->state_or_region != ''){
                    $returnAddress .= ','.trim($orderOperation->shipFrom->returnAddress->state_or_region);
                }
                if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->postal_code != ''){
                    $returnAddress .= '-'.trim($orderOperation->shipFrom->returnAddress->postal_code).PHP_EOL;
                }*/

                if($templateDetail){
                    $data = [
                        'customer_firstName' => $customer_name,
                        'store_name' => $store_name,
                        'marketplaceName' => '',
                        'shipment_address' => '',
                        'trackingNumber' => isset($orderOperation->tracking_number) ? $orderOperation->tracking_number : '',
                        'return_address' => $returnAddress,
                        'carrier_name' => isset($orderOperation->carrier) ? $orderOperation->carrier->name : '',
                        'rma_number' => (isset($rma_number) && $rma_number != '') ? $rma_number : '',
                    ];
                    $template_code = \Blade::render($templateDetail->template_code, $data);
                    return response()->json([
                        'success' => true,
                        'template_id' => $templateDetail,
                        'templateCode' => $template_code,
                        'imagesPath' => $imagesPath,
                        'image_name' => $imageName,
                        'image_url' => $imageUrl,

                    ]);
                }
            }else{
                $templateDetail = HdMessageTemplate::findOrFail($template_id);

                if($templateDetail){
                    $data = [
                        'customer_firstName' => $customer_name,
                        'store_name' => $store_name,
                        'marketplaceName' => '',
                    ];
                    $template_code = \Blade::render($templateDetail->template_code, $data);
                    return response()->json([
                        'success' => true,
                        'template_id' => $templateDetail,
                        'templateCode' => $template_code
                    ]);
                }
            }
        }

        return response()->json([
            'success' => false
        ]);
    }

    public function updateTicket(Request $request){

        $ticket_ids = $request->input('ticket_id');
        $status = $request->input('status');
        $ticket_type_id = $request->input('ticket_type');
        $message_read = $request->input('message_read');
        $important = $request->input('important');
        $notes = $request->input('notes');
        $subject = $request->input('subject');
        if($ticket_ids){

            $updated_by = auth()->user()->name ?? 'admin';

            $updatedData = [
                'updated_by' => $updated_by
            ];

            if(isset($subject) && $subject){
                $updatedData['subject'] = $subject;
            }

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
            Message::whereIn('id', $ticket_ids)->update($updatedData);

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
        $labelimage = $request->input('labelimage');
        $imagepath = $request->input('imagepath');
        $imagename = $request->input('imagename');

        if($ticket_id){
            $ticketDetail = Message::with('store')->where('id',$ticket_id)->first();
            //$store= $ticketDetail->store;
            $storeMessageAccount = StoreMessageAccount::with('outgoingAccount')->where('store_id', $ticketDetail->store_id)->first();


            if(isset($storeMessageAccount->outgoingAccount)){
                if($storeMessageAccount->outgoingAccount->requires_ssl){
                    $encryption = 'ssl';
                }
                if($storeMessageAccount->outgoingAccount->requires_tls){
                    $encryption = 'tls';
                }

                $smtpConfig = [
                    'host' => $storeMessageAccount->outgoingAccount->server,
                    'port' => $storeMessageAccount->outgoingAccount->port,
                    'encryption' => $encryption,
                    'username' => $storeMessageAccount->outgoingAccount->user_id,
                    'password' => $storeMessageAccount->outgoingAccount->password,
                    'from_address' => $storeMessageAccount->outgoingAccount->email,
                    'from_name' => isset($ticketDetail->store) ? $ticketDetail->store->name : '',
                ];

                /*$smtpConfig = [
                    'host' => 'email-smtp.us-east-1.amazonaws.com',
                    'port' => 465,
                    'encryption' => 'tls',
                    'username' => 'AKIATLGXH7OD45ES34GW',
                    'password' => 'BC7NSneeB1+Bl/H+ZlVjoBbdB7JpnD7CRoD9ff3hbEf3',
                    'from_address' => 'kryln.services@gmail.com',
                    'from_name' => 'kryln services',
                ];*/

                $imapConfig = [];
                $filepath = $imagepath;

                $connector = new EmailMessageUtility($smtpConfig, $imapConfig);
                $response = $connector->sendEmail($ticketDetail->ticket_email,$ticketDetail->subject,$message_to_send, $ticketDetail->message_id, $filepath);
                /*$response = $connector->sendEmail('jayesh@mjailab.com','Test Mail',$message_to_send, 'CAB3DxwfKHcaot+XFxHBW6Teiq38dDaHs2UU2UqipkfbHnYr+sg@mail.gmail.com', $filepath);*/

                if($response){

                    $hdMessageData = [
                        'ticket_id' => $ticket_id,
                        'subject' => trim($ticketDetail->subject),
                        'message_date' => date('Y-m-d H:i:s'),
                        'to_' => $ticketDetail->ticket_email,
                        'cc' => NULL,
                        'bcc' => NULL,
                        'message_id' => trim($ticketDetail->message_id),
                        'messaging_account_id' => $storeMessageAccount->outgoingAccount->id,
                        'from_email' => trim($storeMessageAccount->outgoingAccount->user_id),
                        'from_name' => trim($storeMessageAccount->outgoingAccount->user_id),
                        'message' => $message_to_send,
                        'draft' => 0,
                        'author' => 1,
                    ];

                    $hd_message = HDMessage::create($hdMessageData);

                    if($filepath){

                        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
                        $fileSize = filesize($filepath);
                        $filedata = [
                            'name' => $imagename,
                            'ext' => $extension ?: 'jpg', // Default to 'jpg' if missing
                            'file_size' => $fileSize ?: 'Unknown', // Use 'Unknown' if not determined
                            'location' => 'images/',
                            'created_by' => 'admin',
                        ];

                        $fileDetail = SysFile::create($filedata);
                        $file_id = $fileDetail->id;

                        if($ticket_id && $file_id){
                            $attachementData = [
                                'client_id' => NULL,
                                'message_id' => $hd_message->id,
                                'attachment_id' => NULL,
                                'file_id' => $file_id,
                                'created_by' => 'admin',
                            ];

                            HdMessageAttachment::create($attachementData);
                        }
                    }

                    if($is_closed){
                        $ticketDetail->update(['status'=>2]);
                    }
                    return response()->json([
                        'success' => true
                    ]);
                }
            }
        }
        return response()->json([
            'success' => false
        ]);
    }

    public function getShippingDetail(Request $request)
    {
        DB::beginTransaction();
        try {
            $order_id = $request->input('order_id');
            if ($request->input('return_lable')) {
                $orderDetail = Order::with(['orderItem', 'orderOperation' => function ($q) {
                    $q->where('operation_type', 2);
                }, 'customer', 'store'])
                    ->where(function ($query) use ($order_id) {
                        $query->where('order_id', $order_id)
                            ->orWhere('purchase_order_id', $order_id);
                    })
                    ->whereHas('orderOperation', function ($q) {
                        $q->where('operation_type', 2);
                    })->first();
            } else {
                $orderDetail = Order::with('orderItem', 'orderOperation', 'customer', 'store')->where('order_id', $order_id)->orWhere('purchase_order_id', $order_id)->first();
            }
            $template_code = $template_id = '';
            if ($orderDetail) {

                if (isset($orderDetail->orderOperation)) {
                    $orderOperation = $orderDetail->orderOperation;
                    $order_opration_id = $orderOperation->id;
                    /*if ($request->input('rma')) {
                        if (isset($orderDetail->orderItem)) {
                            foreach ($orderDetail->orderItem as $oitem) {
                                $oitem->update(['return_request_quantity' => $oitem->quantity]);
                            }
                        }
                        $rmaObject = new RmaNumberGenerator();
                        $rma_number = $rmaObject->generate($orderDetail->store_id);
                        $clonedOrderOperation = [
                            'ship_date' => $orderOperation->ship_date,
                            'client' => $orderOperation->client,
                            'to_address_id' => $orderOperation->to_address_id,
                            'sync_status' => $orderOperation->sync_status,
                            'insurance_value' => $orderOperation->insurance_value,
                            'status' => OrderOperationStatus::Accepted,
                            'signature_confirmation' => $orderOperation->signature_confirmation,
                            'shipping_package_id' => $orderOperation->shipping_package_id,
                            'weight_value' => $orderOperation->weight_value,
                            'weight_weight_unit' => $orderOperation->weight_weight_unit,
                            'ship_from_id' => $orderOperation->ship_from_id,
                            'store_id' => $orderOperation->store_id,
                            'operation_type' => OrderOperationType::OrderReturn,
                            'order_id' => $orderOperation->order_id,
                            'label_creation_type' => 0,
                            'rma_number' => $rma_number,
                            'shipping_carrier_id' => $orderOperation->shipping_carrier_id,
                            'shipping_carrier_service_id' => NULL,
                            'shipping_account_transaction_id' => NULL,
                        ];

                        $newOrderOperation = OrderOperation::create($clonedOrderOperation);
                        $order_opration_id = $newOrderOperation->id;
                        //$templateDetail = HdMessageTemplate::whereLike('name','%Return Without Label%')->first();
                        $templateDetail = HdMessageTemplate::getOrderOperationTemplate(OrderOperationType::OrderReturn, 0, 0);
                        if ($templateDetail) {

                            $logisticDetail = LogisticCenter::with('returnAddress')->where('id', $orderOperation->ship_from_id)->first();
                            $returnAddress = "";
                            if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->company != '') {
                                $returnAddress .= $logisticDetail->returnAddress->company . PHP_EOL;
                            }
                            if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->address_line_1 != '') {
                                $returnAddress .= $logisticDetail->returnAddress->address_line_1 . PHP_EOL;
                            }
                            if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->address_line_2 != '') {
                                $returnAddress .= $logisticDetail->returnAddress->address_line_2 . PHP_EOL;
                            }
                            if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->address_line_3 != '') {
                                $returnAddress .= $logisticDetail->returnAddress->address_line_3 . PHP_EOL;
                            }
                            if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->city != '') {
                                $returnAddress .= $logisticDetail->returnAddress->city . PHP_EOL;
                            }
                            if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->state != '') {
                                $returnAddress .= $logisticDetail->returnAddress->state . PHP_EOL;
                            }
                            if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->zip != '') {
                                $returnAddress .= $logisticDetail->returnAddress->zip . PHP_EOL;
                            }
                            $data = [
                                'customer_firstName' => isset($orderDetail->customer) ? $orderDetail->customer->full_name : '',
                                'store_name' => isset($orderDetail->store) ? $orderDetail->store->name : '',
                                'rma_number' => $rma_number,
                                'return_address' => $returnAddress
                            ];
                            $template_id = $templateDetail->id;
                            $template_code = \Blade::render($templateDetail->template_code, $data);
                        }
                    }*/

                    $shippingCarrier = ShippingCarrier::all();
                    $shipFrom = LogisticCenter::all();
                    $shippingCarrierPackage = ShippingPackage::all();
                    $shipmentDetail = OrderOperation::with('operationItem', 'order', 'order.customerAddress', 'carrierService', 'store', 'store.storeType', 'operationItem.orderItem', 'operationItem.orderItem.product')->where('id', $order_opration_id)->first();

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
                    } else {
                        /*if ($request->input('rma')) {
                            $newshipmentDetail = OrderOperation::with('operationItem', 'operationItem.orderItem')->where('id', $orderOperation->id)->first();
                            if (count($newshipmentDetail->operationItem ?? []) > 0) {
                                foreach ($newshipmentDetail->operationItem as $oitem) {

                                    $newOrderItem = [
                                        'order_item_id' => $oitem->order_item_id,
                                        'order_operation_id' => $order_opration_id,
                                        'client_id' => $oitem->client_id,
                                        'sync_status' => $oitem->sync_status,
                                        'quantity' => $oitem->quantity,
                                        'item_price' => $oitem->item_price,
                                        'shipping_price' => $oitem->shipping_price
                                    ];

                                    OrderOperationItem::updateOrCreate([
                                        'order_item_id' => $oitem->order_item_id,
                                        'order_operation_id' => $order_opration_id,
                                    ], $newOrderItem);
                                }
                            }

                            $shipmentDetail = OrderOperation::with('operationItem', 'order', 'order.customerAddress', 'carrierService', 'store', 'operationItem.orderItem', 'operationItem.orderItem.product')->where('id', $order_opration_id)->first();
                        }*/
                    }

                    $shipping_carrier_id = $shipmentDetail->shipping_carrier_id;
                    $shippingCarrierService = ShippingCarrierService::where('shipping_carrier_id', $shipping_carrier_id)->orderBy('name', 'asc')->get();

                    if ($request->input('rma')) {
                        $reasons = [];
                        if (isset($shipmentDetail->store->storeType)) {
                            $store_type = $shipmentDetail->store->storeType->id;
                            $reasons = OrderOperationReason::where('store_type_id', $store_type)->where('operation_type', 1)->get();
                        }
                        $view = view('messages.make_rma', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight','reasons'))->render();
                    } else if ($request->input('ship_order')) {
                        $view = view('messages.ship_order', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight'))->render();
                    } else if ($request->input('return_lable')) {
                        $return_lable = 1;
                        $view = view('messages.order_return', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight','return_lable'))->render();
                    } else if ($request->input('replacement')) {
                        $view = view('messages.order_replace', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight'))->render();
                    } else {
                        $view = view('messages.order_refund', compact('shipmentDetail'))->render();
                    }
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'html' => $view,
                        'template_code' => $template_code,
                        'template_id' => $template_id,
                    ]);
                }
            }
            DB::commit();
            return response()->json([
                'success' => false
            ]);

        }catch (\Exception $exception) {
            DB::commit();
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function saveRMA(Request $request){

        DB::beginTransaction();
        try {
            $order_id = $request->input('order_id');
            $ticket_id = $request->input('ticket_id');
            $reason = $request->input('reason');

            $orderDetail = Order::with('orderItem', 'orderOperation', 'customer', 'store')->where('order_id', $order_id)->orWhere('purchase_order_id', $order_id)->first();

            if ($orderDetail) {

                if (isset($orderDetail->orderOperation)) {
                    $orderOperation = $orderDetail->orderOperation;

                    if (isset($orderDetail->orderItem)) {
                        foreach ($orderDetail->orderItem as $oitem) {
                            $oitem->update(['return_request_quantity' => $oitem->quantity]);
                        }
                    }
                    $rmaObject = new RmaNumberGenerator();
                    $rma_number = $rmaObject->generate($orderDetail->store_id);
                    $clonedOrderOperation = [
                        'ship_date' => $orderOperation->ship_date,
                        'client' => $orderOperation->client,
                        'to_address_id' => $orderOperation->to_address_id,
                        'sync_status' => $orderOperation->sync_status,
                        'insurance_value' => $orderOperation->insurance_value,
                        'status' => OrderOperationStatus::Accepted,
                        'signature_confirmation' => $orderOperation->signature_confirmation,
                        'shipping_package_id' => $orderOperation->shipping_package_id,
                        'weight_value' => $orderOperation->weight_value,
                        'weight_weight_unit' => $orderOperation->weight_weight_unit,
                        'ship_from_id' => $orderOperation->ship_from_id,
                        'store_id' => $orderOperation->store_id,
                        'operation_type' => OrderOperationType::OrderReturn,
                        'order_id' => $orderOperation->order_id,
                        'label_creation_type' => 0,
                        'rma_number' => $rma_number,
                        'shipping_carrier_id' => $orderOperation->shipping_carrier_id,
                        'shipping_carrier_service_id' => NULL,
                        'shipping_account_transaction_id' => NULL,
                    ];

                    $newOrderOperation = OrderOperation::create($clonedOrderOperation);
                    $order_opration_id = $newOrderOperation->id;


                    $templateDetail = HdMessageTemplate::getOrderOperationTemplate(OrderOperationType::OrderReturn, 0, 0);
                    if ($templateDetail) {

                        $logisticDetail = LogisticCenter::with('returnAddress')->where('id', $orderOperation->ship_from_id)->first();
                        $returnAddress = "";
                        if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->company != '') {
                            $returnAddress .= $logisticDetail->returnAddress->company . PHP_EOL;
                        }
                        if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->address_line_1 != '') {
                            $returnAddress .= $logisticDetail->returnAddress->address_line_1 . PHP_EOL;
                        }
                        if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->address_line_2 != '') {
                            $returnAddress .= $logisticDetail->returnAddress->address_line_2 . PHP_EOL;
                        }
                        if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->address_line_3 != '') {
                            $returnAddress .= $logisticDetail->returnAddress->address_line_3 . PHP_EOL;
                        }
                        if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->city != '') {
                            $returnAddress .= $logisticDetail->returnAddress->city . PHP_EOL;
                        }
                        if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->state != '') {
                            $returnAddress .= $logisticDetail->returnAddress->state . PHP_EOL;
                        }
                        if (isset($logisticDetail->returnAddress) && $logisticDetail->returnAddress->zip != '') {
                            $returnAddress .= $logisticDetail->returnAddress->zip . PHP_EOL;
                        }
                        $data = [
                            'customer_firstName' => isset($orderDetail->customer) ? $orderDetail->customer->full_name : '',
                            'store_name' => isset($orderDetail->store) ? $orderDetail->store->name : '',
                            'rma_number' => $rma_number,
                            'return_address' => $returnAddress
                        ];
                        $template_id = $templateDetail->id;
                        $template_code = \Blade::render($templateDetail->template_code, $data);
                    }

                    $newshipmentDetail = OrderOperation::with('operationItem', 'operationItem.orderItem')->where('id', $orderOperation->id)->first();
                    if (count($newshipmentDetail->operationItem ?? []) > 0) {
                        foreach ($newshipmentDetail->operationItem as $oitem) {

                            $newOrderItem = [
                                'order_item_id' => $oitem->order_item_id,
                                'order_operation_id' => $order_opration_id,
                                'client_id' => $oitem->client_id,
                                'sync_status' => $oitem->sync_status,
                                'quantity' => $oitem->quantity,
                                'item_price' => $oitem->item_price,
                                'shipping_price' => $oitem->shipping_price,
                                'reason_id' => (isset($reason) && $reason != '') ? $reason : ''
                            ];

                            OrderOperationItem::updateOrCreate([
                                'order_item_id' => $oitem->order_item_id,
                                'order_operation_id' => $order_opration_id,
                            ], $newOrderItem);
                        }
                    }

                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'template_code' => $template_code,
                        'template_id' => $template_id,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => false
            ]);

        }catch (\Exception $exception) {
            DB::commit();
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function getCancelOrderDetail(Request $request)
    {
        $order_id = $request->input('order_id');
        $orderDetail = Order::with('orderOperation','customer','store')->where('order_id',$order_id)->orWhere('purchase_order_id',$order_id)->first();

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

    public function cancelOrder(Request $request)
    {

        $selected_shipment_id = $request->input('id');
        $ticket_id = $request->input('ticket_id');
        $reason = $request->input('reason');
        DB::beginTransaction();
        try {
            if ($selected_shipment_id) {
                $orderOperation = OrderOperation::with('order', 'store', 'store.storeType')->where('id', $selected_shipment_id)->first();
                $response = OrderOperationUtility::cancelOrder($orderOperation, $reason);

                if ($response) {
                    $orderOperation->update(['status' => OrderOperationStatus::Canceled]);
                    if (isset($orderOperation->order->id)) {
                        $order = Order::findOrFail($orderOperation->order->id);
                        $order->update(['order_status' => OrderStatus::Canceled]);
                    }
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Order Cancelled Successfully',
                    ]);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Something went wrong with API. Please try again!'
                    ]);
                }

            } else {
                DB::commit();
                return response()->json([
                    'success' => false,
                    'message' => 'Please select shipment'
                ]);
            }
        } catch (\Exception $exception) {
            DB::commit();
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function returnLabel(Request $request)
    {
        if($request->input('label_generation') > 0) {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'carrier_name' => 'required',
                'carrier_package' => 'required',
                'shipping_carrier_service' => 'required',
                'shipment_from' => 'required',
                'weight' => 'required|numeric|gt:0',
                'weight_type' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
        }

        DB::beginTransaction();

        $ticket_id = $request->input('ticket_id');
        $request['label_generation'] = 1;

        $orderOperation = OrderOperation::with('order', 'order.customer', 'store', 'store.storeType','carrier')->where('id', $request->id)->first();

        $successRes = OrderOperationUtility::processShipment($orderOperation, $request);

        if (isset($successRes) && $successRes['success'] == false) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $successRes['message'] ?? 'Something went wrong with API. Please try again!'
            ]);
        }

        $templateDetail = HdMessageTemplate::getOrderOperationTemplate(OrderOperationType::OrderReturn, 0, 1);
        if ($templateDetail) {
            $data = [
                'customer_firstName' => isset($orderOperation->order->customer) ? $orderOperation->order->customer->full_name : '',
                'store_name' => isset($orderOperation->store) ? $orderOperation->store->name : '',
                'carrier_name' => isset($orderOperation->carrier) ? $orderOperation->carrier->name : ''
            ];
            $template_id = $templateDetail->id;
            $template_code = \Blade::render($templateDetail->template_code, $data);
        }

        $orderOperation = OrderOperation::with('order', 'store', 'store.storeType')->where('id', $request->id)->first();
        $imageName = $orderOperation->tracking_number.'.jpg';
        $imageUrl = asset('storage/labels/' .$imageName );

        $storagePath = storage_path('app/public/labels/' . $imageName);
        if (file_exists($storagePath)) {
            $fileSize = filesize($storagePath);
            $extension = pathinfo($imageName, PATHINFO_EXTENSION);
            $filedata = [
                'name' => $imageName,
                'ext' => $extension ?: 'jpg', // Default to 'jpg' if missing
                'file_size' => $fileSize ?: 'Unknown', // Use 'Unknown' if not determined
                'location' => 'storage/labels/',
                'created_by' => 'admin',
            ];

            $imageFile = SysFile::create($filedata);
            $file_id = $imageFile->id;

            if ($ticket_id && $file_id) {
                $attachementData = [
                    'client_id' => NULL,
                    'message_id' => $ticket_id,
                    'attachment_id' => $ticket_id,
                    'file_id' => $file_id,
                    'created_by' => 'admin',
                ];

                HdMessageAttachment::create($attachementData);
            }
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'image_name' => $imageName,
            'image_url' => $imageUrl,
            'template_id' => $template_id,
            'template_code' => $template_code,
            'imagesPath' => $storagePath
        ]);
    }

    public function replaceOrder(Request $request){

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'carrier_name' => 'required',
            'carrier_package' => 'required',
            'shipping_carrier_service' => 'required',
            'shipment_from' => 'required',
            'weight' => 'required|numeric|gt:0',
            'weight_type' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        $operation_id = $request->input('id');
        if($operation_id) {
            $orderOperation = OrderOperation::with('operationItem')->where('id',$operation_id)->first();
            $clonedOrderOperation = [
                'ship_date' => $orderOperation->ship_date,
                'client' => $orderOperation->client,
                'to_address_id' => $orderOperation->to_address_id,
                'sync_status' => $orderOperation->sync_status,
                'insurance_value' => $orderOperation->insurance_value,
                'status' => OrderOperationStatus::Accepted,
                'signature_confirmation' => $orderOperation->signature_confirmation,
                'shipping_package_id' => $orderOperation->shipping_package_id,
                'weight_value' => $orderOperation->weight_value,
                'weight_weight_unit' => $orderOperation->weight_weight_unit,
                'ship_from_id' => $orderOperation->ship_from_id,
                'store_id' => $orderOperation->store_id,
                'operation_type' => OrderOperationType::OrderReplacement,
                'order_id' => $orderOperation->order_id,
                'label_creation_type' => ShippingLabelCreationType::CreateLabel,
                'rma_number' => NULL,
                'shipping_carrier_id' => $orderOperation->shipping_carrier_id,
                'shipping_carrier_service_id' => NULL,
                'shipping_account_transaction_id' => NULL,
            ];

            $newOrderOperation = OrderOperation::create($clonedOrderOperation);
            $order_opration_id = $newOrderOperation->id;

            if (count($orderOperation->operationItem ?? []) > 0) {
                foreach ($orderOperation->operationItem as $oitem) {

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

        $request['label_generation'] = 1;
        $orderOperations = OrderOperation::with('order','order.customer', 'store', 'store.storeType')->where('id', $order_opration_id)->get();

        $successRes = OrderOperationUtility::processShipment($orderOperations, $request);

        if (isset($successRes) && $successRes['success'] == false) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $successRes['message'] ?? 'Something went wrong with API. Please try again!'
            ]);
        }

        $templateDetail = HdMessageTemplate::getOrderOperationTemplate(OrderOperationType::OrderReplacement,1,0);
        $template_id = $template_code = '';
        if($templateDetail) {
            $data = [
                'customer_firstName' => isset($orderOperations->order->customer) ? $orderOperations->order->customer->full_name : '',
                'store_name' => isset($orderOperations->store) ? $orderOperations->store->name : '',
                'trackingNumber' => isset($orderOperations->tracking_number) ? $orderOperations->tracking_number : '',
                'ima_number' => '',
                'return_address' => ''
            ];
            $template_id = $templateDetail->id;
            $template_code = \Blade::render($templateDetail->template_code, $data);
        }
        DB::commit();
        return response()->json([
            'success' => true,
            'template_id' => $template_id,
            'template_code' => $template_code,
        ]);
    }


    public function refundOrder(Request $request){

        DB::beginTransaction();
        $order_id = $request->input('order_id');
        $id = $request->input('o_id');
        $accept_return_charge = 0;
        $return_charges = 0;
        $return_charge_feedback = $reason_id = NULL;
        if($request->has('accept_return_charge') && $request->input('accept_return_charge') == 'on'){
            $accept_return_charge = 1;
            $return_charges = $request->input('return_charge');
            $return_charge_feedback = $request->input('return_charge_feedback');
            $reason_id = $request->input('reason');
        }
        $item_ids = $request->input('item_id');
        $refund_quantities = $request->input('refund_quantity');
        $refund_tax_prices = $request->input('refund_tax_price');
        $refund_shipping_prices = $request->input('refund_shipping_price');
        $refund_shipping_taxs = $request->input('refund_shipping_tax');
        if($order_id) {
            $orderDetail = Order::with('orderItem')->where('order_id',$order_id)->first();
            if($orderDetail){
                if(isset($orderDetail->orderItem)){
                    foreach($orderDetail->orderItem as $key=>$oItem){
                        if($oItem->id == $item_ids[$oItem->id]){
                            $updateOrderItemData = [
                                'refund_price' => ($oItem->quantity * $oItem->item_price - $return_charges),
                                'canceled_quantity' => $refund_quantities[$oItem->id],
                                'refund_quantity' => $refund_quantities[$oItem->id],
                                'return_charge_feedback' => $return_charge_feedback,
                                'refund_tax_price' => $oItem->quantity * $refund_tax_prices[$oItem->id],
                                'refund_shipping_price' => $oItem->quantity * $refund_shipping_prices[$oItem->id],
                                'refund_shipping_tax' => $oItem->quantity * $refund_shipping_taxs[$oItem->id]
                            ];
                            $oItem->update($updateOrderItemData);
                        }
                    }
                }
            }
            //$orderOperation = OrderOperation::with('operationItem')->where('order_id',$id)->where('operation_type',OrderOperationType::OrderReturn)->first();
            $orderOperation = OrderOperation::with('operationItem')->where('order_id',$id)->orderBy('created_at','desc')->first();

            if($orderOperation->operation_type == OrderOperationType::OrderReturn){
                $operation_type = OrderOperationType::OrderReturnRefund;
            }else{
                $operation_type = OrderOperationType::OrderRefund;
            }

            $clonedOrderOperation = [
                'ship_date' => $orderOperation->ship_date,
                'client' => NULL,
                'to_address_id' => $orderOperation->to_address_id,
                'sync_status' => $orderOperation->sync_status,
                'insurance_value' => $orderOperation->insurance_value,
                'status' => OrderOperationStatus::PARTIAL_REFUND_ACCEPTED,
                'signature_confirmation' => $orderOperation->signature_confirmation,
                'shipping_package_id' => $orderOperation->shipping_package_id,
                'weight_value' => $orderOperation->weight_value,
                'weight_weight_unit' => $orderOperation->weight_weight_unit,
                'ship_from_id' => $orderOperation->ship_from_id,
                'store_id' => $orderOperation->store_id,
                'operation_type' => $operation_type,
                'order_id' => $orderOperation->order_id,
                'label_creation_type' => ShippingLabelCreationType::NoLabel,
                'rma_number' => NULL,
                'shipping_carrier_id' => $orderOperation->shipping_carrier_id,
                'shipping_carrier_service_id' => NULL,
                'shipping_account_transaction_id' => NULL,
                'return_operation_id' => $orderOperation->id,
            ];

            $newOrderOperation = OrderOperation::create($clonedOrderOperation);
            $order_opration_id = $newOrderOperation->id;

            if (count($orderOperation->operationItem ?? []) > 0) {
                foreach ($orderOperation->operationItem as $oitem) {

                    $newOrderItem = [
                        'order_item_id' => $oitem->order_item_id,
                        'order_operation_id' => $order_opration_id,
                        'client_id' => $oitem->client_id,
                        'sync_status' => $oitem->sync_status,
                        'quantity' => $oitem->quantity,
                        'item_price' => $oitem->item_price,
                        'shipping_price' => ($oItem->quantity * $oItem->item_price - $return_charges),
                        'reason_id' => $reason_id,
                        'refund_feedback_id' => $return_charge_feedback,
                        'return_operation_item_id' => $oitem->id
                    ];

                    OrderOperationItem::updateOrCreate([
                        'order_item_id' => $oitem->order_item_id,
                        'order_operation_id' => $order_opration_id,
                    ], $newOrderItem);
                }
            }

            $request['label_generation'] = 1;
            $orderOperation = OrderOperation::with('order','order.customer', 'store', 'store.storeType')->where('id', $order_opration_id)->first();

            $successRes = OrderOperationUtility::processShipment($orderOperation, $request);

            //$isSuccess = true;
            if (isset($successRes) && $successRes['success'] == false) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $successRes['message'] ?? 'Something went wrong with API. Please try again!'
                ]);
            }
            DB::commit();
            $templateDetail = HdMessageTemplate::getOrderOperationTemplate($operation_type,0,0);
            $template_id = $template_code = '';
            if($templateDetail) {
                $data = [
                    'customer_firstName' => isset($orderOperation->order->customer) ? $orderOperation->order->customer->full_name : '',
                    'store_name' => isset($orderOperation->store) ? $orderOperation->store->name : '',
                    'trackingNumber' => isset($orderOperation->tracking_number) ? $orderOperation->tracking_number : '',
                    'ima_number' => '',
                    'return_address' => ''
                ];
                $template_id = $templateDetail->id;
                $template_code = \Blade::render($templateDetail->template_code, $data);
            }

            return response()->json([
                'success' => true,
                'template_id' => $template_id,
                'template_code' => $template_code,
                'message' => 'Order refund successfuly!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong with API. Please try again!'
        ]);

    }

    public function imageUpload(Request $request){

        $request->validate([
            'attach_file' => 'required|image|mimes:jpeg,png,jpg|max:4096'
        ]);

        $ticket_id= $request->input('ticket_id');

        $imageName = time().'.'.$request->attach_file->extension();
        $storagePath = storage_path('app/public/images/' . $imageName);
        $path = storage_path('app/public/images/');
        $request->attach_file->move($path, $imageName);
        $imageUrl = asset('storage/images/' .$imageName );

        /*if ($imageName !== false) {

            $extension = pathinfo($imageName, PATHINFO_EXTENSION);
            $fileSize = filesize($storagePath);

            $filedata = [
                'name' => $imageName,
                'ext' => $extension ?: 'jpg', // Default to 'jpg' if missing
                'file_size' => $fileSize ?: 'Unknown', // Use 'Unknown' if not determined
                'location' => 'images/',
                'created_by' => 'admin',
            ];

            $fileDetail = SysFile::create($filedata);
            $file_id = $fileDetail->id;

            if($ticket_id && $file_id){
                $attachementData = [
                    'client_id' => NULL,
                    'message_id' => $ticket_id,
                    'attachment_id' => NULL,
                    'file_id' => $file_id,
                    'created_by' => 'admin',
                ];

                $attchment = HdMessageAttachment::create($attachementData);
                $messageAttach = HdMessageAttachment::with('fileSystem')->where('id',$attchment->id)->first();
                //$fileSystem = $messageAttach->fileSystem;
                //$imagesPath = asset('storage/' .$messageAttach->imageObj->getFileUrl());
                $storagePath = public_path('images/' . $imageName);
                $imagesPath = $storagePath;

            }
        }*/

        return response()->json([
            'success' => true,
            'image_name' => $imageName,
            'image_url' => $imageUrl,
            'imagesPath' => $storagePath,
        ]);

    }

    private function getReturnAddress($orderOperation){

        $returnAddress = '';
        if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->company != ''){
            $returnAddress .= trim($orderOperation->shipFrom->returnAddress->company).PHP_EOL;
        }
        if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->address_line_1 != ''){
            $returnAddress .= trim($orderOperation->shipFrom->returnAddress->address_line_1).PHP_EOL;
        }
        if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->address_line_2 != ''){
            $returnAddress .= trim($orderOperation->shipFrom->returnAddress->address_line_2).PHP_EOL;
        }
        if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->address_line_3 != ''){
            $returnAddress .= trim($orderOperation->shipFrom->returnAddress->address_line_3).PHP_EOL;
        }
        if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->city != ''){
            $returnAddress .= trim($orderOperation->shipFrom->returnAddress->city);
        }
        if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->state_or_region != ''){
            $returnAddress .= ','.trim($orderOperation->shipFrom->returnAddress->state_or_region);
        }
        if(isset($orderOperation->shipFrom->returnAddress) && $orderOperation->shipFrom->returnAddress->postal_code != ''){
            $returnAddress .= '-'.trim($orderOperation->shipFrom->returnAddress->postal_code).PHP_EOL;
        }

        return $returnAddress;
    }
}
