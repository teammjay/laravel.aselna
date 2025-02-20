<?php

namespace App\Http\Controllers;

use App\Models\LogisticCenter;
use App\Models\OrderOperation;
use App\Models\ShippingCarrier;
use App\Models\ShippingCarrierPackage;
use App\Models\ShippingCarrierService;
use App\Models\Store;
use Illuminate\Http\Request;

class ShipmentsController extends Controller
{
    private $perPage = 25;

    public function index(Request $request)
    {
        $perPage = $this->perPage;
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $shippingCarrier = ShippingCarrier::all();
        $shipFrom = LogisticCenter::all();

        return view('shipment.list',compact('perPage','stores','shippingCarrier','shipFrom'));
    }

    public function filter(Request $request)
    {

        $draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];

        $query = OrderOperation::with([
            'order:id,order_id,order_date,buyer_first_name,buyer_last_name',
            'order.orderItem',
            'order.orderItem.product',
            'order.orderItem.product.image',
            'carrierService:id,name',
            'store:id,name,store_type_id',
        ])->select('order_operation.*','order_operation.id as oid');

        // Filter by store
        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('order_operation.store_id', $request->store_id);
        }

        if($columnName != ''){
            switch(strtolower($columnName)){
                case 'order_id':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                        ->orderBy('order.order_id', $columnSortOrder);
                    break;
                case 'carrier_service':
                    $query->join('shipping_carrier_service', 'shipping_carrier_service.id', '=', 'order_operation.shipping_carrier_service_id')
                        ->orderBy('shipping_carrier_service.name', $columnSortOrder);
                    break;
                case 'order_date':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                        ->orderBy('order.order_date', $columnSortOrder);
                    break;
                case 'store':
                    $query->join('store', 'store.id', '=', 'order_operation.store_id')
                        ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
                        ->orderByRaw("CONCAT(store.name, ' - ', store_type.name) $columnSortOrder");
                    break;
                case 'order_id':
                    $query->orderBy('order_operation.order_id', $columnSortOrder);
                    break;
                case 'buyer_first_name':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                        ->orderBy('order.buyer_first_name', $columnSortOrder);
                    break;
                case 'buyer_last_name':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                        ->orderBy('order.buyer_last_name', $columnSortOrder);
                    break;
            }

            $sortableColumns = [
                'sku' => 'sku',
                'sale_count' => 'sale_count',
                'last_sale_date' => 'last_sale_date',
                'store_sku' => 'store_sku',
                'store_name' => 'store.name',
                'title' => 'title',
                'status' => 'status',
                'sync_status' => 'sync_status',
                'price' => 'price',
                'qty' => 'quantity',
                'created_at' => 'created_at',
            ];
            if (isset($sortableColumns[$columnName])) {
                $query->orderBy($sortableColumns[$columnName], $columnSortOrder);
            }
        }
        //echo $query->toSql();die;
        $totalRecords = $query->count();
        $order_operations = $query->skip($start)
            ->take($perPage)
            ->get();
        $totalRecordwithFilter = $totalRecords;
        /*echo '<pre>';
        print_r($order_operations);
        echo '</pre>';die;*/

        $data = [];
        if(!empty((array) $order_operations)){
            foreach($order_operations as $key=>$order) {
                $storeType = (isset($order->store->storeType) && !empty($order->store->storeType)) ? $order->store->storeType->name : '';
                $code = '';
                $image = $product_id = '';
                if(isset($order->order->orderItem) && !empty($order->order->orderItem)){
                    foreach($order->order->orderItem as $oitem){
                        if(isset($oitem->product) && !empty($oitem->product)){
                            $imagename = $oitem->product->product_image;
                            if(!empty($oitem->product->image)) {
                                $image = asset('storage/' . $oitem->product->image->getFileUrl());
                                $product_id = $oitem->product->product_id;
                            }
                            if($oitem->product->product_sku != ''){
                                $code .= $oitem->product->product_sku.' x '.$oitem->quantity.'<br>';
                            }
                        }
                    }
                }
                $data[] = array(
                    "action" => '<button type="button" class="btn btn-warning show-detail" data-id="'.$order->oid.'">Show Detail</button><input type="checkbox" class="pro-id" name="shipment_id[]" value="'.$order->oid.'" style="visibility:hidden;">',
                    "image" => ($image) ? '<div class="main-image"><img src="'.$image.'" alt="'.$imagename.'" class="mainImage" width="70" height="70" data-product-id="'.$product_id.'"></div>' : '',
                    "status" => '',
                    "carrier_service" => $order->carrierService->name,
                    "store" => $order->store->name.' - '.$storeType,
                    "order_id" => $order->order->order_id,
                    "order_date" => $order->order->order_date,
                    "product_code" =>  $code,
                    "weight" =>  $order->weight_value,
                    "first_name" => (isset($order->order) && !empty($order->order)) ? $order->order->buyer_first_name : '',
                    "last_name" =>  (isset($order->order) && !empty($order->order)) ? $order->order->buyer_last_name : '',
                    "city" => ''
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
        return view('shipment.detail');
    }

    public function updateShippingDetails(Request $request){
        $selected_shipment = $request->input('selected_shipment');
        $carrier_id = $request->input('carrier_id');
        $carrier_service_id = $request->input('carrier_service_id');
        $ship_from = $request->input('ship_from');
        $ship_option = $request->input('ship_option');

        if(!empty($selected_shipment)){
            foreach($selected_shipment as $id){
                $updateRecord = array(
                    'shipping_carrier_id' => $carrier_id,
                    'shipping_carrier_service_id' => $carrier_service_id,
                    'ship_from_id' => $ship_from,
                );

                OrderOperation::findOrFail($id)->update($updateRecord);
                return response()->json([
                    'success' => true
                ]);
            }
        }
        return response()->json([
            'success' => false
        ]);
    }

    public function getShippingCarrier(Request $request){
        $shipping_carrier_id = $request->input('carrier_id');
        $carrier_servie = ShippingCarrierService::where('shipping_carrier_id',$shipping_carrier_id)->orderBy('name','asc')->get();
        $option = '';
        if(!empty($carrier_servie)){
            foreach($carrier_servie as $service){
                $option .= '<option value="'.$service->id.'">'.$service->name.'</option>';
            }
        }
        return response()->json([
            'success' => true,
            'option' => $option
        ]);
    }

    public function getShippingDetail(Request $request){
        $order_opration_id = $request->input('operation_id');
        $shippingCarrier = ShippingCarrier::all();
        $shipFrom = LogisticCenter::all();
        $shippingCarrierPackage = ShippingCarrierPackage::all();
        $shipmentDetail = OrderOperation::with('operationItem','order','carrierService','store','operationItem.orderItem','operationItem.orderItem.product')->where('id',$order_opration_id)->first();
        /*echo '<pre>';
        print_r($shipmentDetail);
        echo '</pre>';die;*/
        $shipping_carrier_id = $shipmentDetail->shipping_carrier_id;
        $shippingCarrierService = ShippingCarrierService::where('shipping_carrier_id',$shipping_carrier_id)->orderBy('name','asc')->get();

        if($request->input('mark_as_ship')){
            $view = view('shipment.mark_as_shipped', compact('shipmentDetail','shippingCarrier','shippingCarrierService','shippingCarrierPackage','shipFrom'))->render();
        }else {
            $view = view('shipment.detail', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom'))->render();
        }
        return response()->json([
            'success' => true,
            'html' => $view
        ]);

    }
}
