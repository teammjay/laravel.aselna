<?php

namespace App\Http\Controllers;

use App\Constants\HdTicketStatus;
use App\Constants\HdTicketTypeCode;
use App\Constants\OrderStatus;
use App\Models\HDMessage;
use App\Models\HdMessageTemplate;
use App\Models\HdTicketType;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\Store;
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
        return view('messages.list', compact('stores'));
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

        // Filter by ticket email
        if ($request->has('sender_email') && $request->input('sender_email') != '') {
            $query->where('ticket_email', $request->input('sender_email'));
        }

        // Filter by ticket type
        if ($request->has('ticket_type') && $request->input('ticket_type') != '') {
            $query->where('ticket_type_id', $request->input('ticket_type'));
        }

        // Filter by ticket code
        if ($request->has('ticket_code') && $request->input('ticket_code') != '') {
            $query->where('ticket_code', $request->input('ticket_code'));
        }

        // Filter by order id
        if ($request->has('order_id') && $request->input('order_id') != '') {
            $query->where('order_id', $request->input('order_id'));
        }

        // Filter by subject
        if ($request->has('subject') && $request->input('subject') != '') {
            $query->where('subject', $request->input('subject'));
        }

        // Filter by notes
        if ($request->has('body') && $request->input('body') != '') {
            $query->where('notes', $request->input('body'));
        }

        // Filter by notes
        if ($request->has('user_id') && $request->input('user_id') != '') {
            $query->where('customer_id', $request->input('user_id'));
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
                $data[] = array(
                    "message_id" => '<input type="checkbox" class="message-id" name="message_id[]" value="'.$message->id.'" style="visibility:hidden;">',
                    "store" => $message->store->name.'-'.$message->store->storeType->name,
                    "first_name" => isset($message->customer) ? $message->customer->first_name : '' ,
                    "last_name" => isset($message->customer) ? $message->customer->last_name : '',
                    "subject" => $message->subject,
                    "ticket_date" =>  date('Y-m-d H:i',strtotime($message->ticket_date)),
                    "ticket_type" => $message->ticketType->name,
                    "status" =>  HdTicketStatus::getTicketStatus($message->status),
                    "order_id" => $message->order_id,
                    "ticket_code" => $message->ticket_code,
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
        $store_type_id = $ticketDetail->store->storeType->id;
        $message_template = HdMessageTemplate::where('store_type_id',$store_type_id)->get();
        $messages = HDMessage::where('ticket_id',$id)->orderBy('message_date','desc')->get();
        $ticketTypeCode = HdTicketType::orderBy('name')->get();
        return view('messages.detail', compact('message_template','ticketDetail','messages','ticketTypeCode'));
    }

    public function searchOrder(Request $request){

        $order_id = $request->input('order_id');

        if($order_id){

            $orderDetail = Order::with('orderItem','orderItem.product','orderItem.product.image','customer','customerAddress')->where('order_id',$order_id)->first();
            /*echo '<pre>';
            print_r($orderDetail);
            echo '</pre>';die;*/

            if($orderDetail){
                $orderOperation = OrderOperation::with('carrier')->where('order_id',$orderDetail->id)->first();
                $data['items'] = view('messages.order_items', ['orderDetail' => $orderDetail])->render();
                $data['orderId'] = $orderDetail->order_id;
                $data['orderDate'] = $orderDetail->order_date;
                $data['orderStatus'] = OrderStatus::getOrderStatusName($orderDetail->order_status);
                $data['orderTotal'] = $orderDetail->order_total;
                $data['orderPOId'] = $orderDetail->purchase_order_id;
                $data['orderOperation'] = view('messages.order_operation', ['orderDetail' => $orderDetail,'orderOperation' => $orderOperation])->render();

                return response()->json([
                    'success' => true,
                    'orderDetail' => $data
                ]);
            }
        }

        return response()->json([
            'success' => false
        ]);
    }

    public function getMessageTemplate(Request $request){
        $template_id = $request->input('template_id');
        if($template_id){
            $templateDetail = HdMessageTemplate::findOrFail($template_id);
            if($templateDetail){
                return response()->json([
                    'success' => true,
                    'templateCode' => $templateDetail->template_code
                ]);
            }
        }

        return response()->json([
            'success' => false
        ]);
    }
}
