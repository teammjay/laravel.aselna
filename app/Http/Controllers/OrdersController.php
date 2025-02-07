<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Helpers\QueryFilters;

class OrdersController extends Controller
{
    private $perPage = 25;

    public function index(Request $request)
    {
        $perPage = $this->perPage;
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

        // Filter by order id
        if ($request->has('order_id') && $request->order_id != '') {
            $query->where('order_id', $request->order_id);
        }

        // Filter by order date
        if ($request->has('order_date') && $request->order_date != '') {
            $query->where('order_date', $request->order_date);
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

        // Filter by created date
        if ($request->has('created_at') && $request->created_at != '') {
            $query->where('created_at', $request->created_at);
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
                $image = '';
                if(isset($order->orderItem) && !empty($order->orderItem)){
                    foreach($order->orderItem as $oitem){
                        $sku .= $oitem->sku.' x '.$oitem->quantity.'<br>';
                        if(isset($oitem->product) && !empty($oitem->product)){
                            if($oitem->product->product_image_url != ''){
                                $image = '<div class="main-image">
                                                <img src="'.asset('storage/product_images/'. $oitem->orderItem->product->product_image_url).'" alt="'.$oitem->orderItem->product->product_image_url.'" class="mainImage" width="70" height="70" data-product-id="'.$oitem->orderItem->product->product_id.'">
                                            </div>';
                            }
                        }
                    }
                }
                $data[] = array(
                    "order_id" => $order->order_id.'<input type="checkbox" class="pro-id" name="order_id[]" value="'.$order->id.'" style="visibility:hidden;">',
                    "store" => $order->store->name.' - '.$storeType,
                    "order_date" => $order->order_date,
                    "order_image" => $image,
                    "order_sku" => $sku,
                    "order_status" =>  $order->order_status,
                    "first_name" => $order->buyer_first_name,
                    "last_name" =>  $order->buyer_last_name,
                    "phone" => '',
                    "order_total" => $order->order_total,
                    "buyer_last_name" => $order->buyer_last_name,
                    "state" => '',
                    "purchase_order_id" => $order->purchase_order_id,
                    "customer" => '',
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
        $orderDetail = Order::with('orderItem','orderItem.product','store','store.storeType')->findOrFail($id);
        /*echo '<pre>';
        print_r($orderDetail);
        echo '</pre>';die;*/
        return view('orders.detail',compact('orderDetail'));
    }
}
