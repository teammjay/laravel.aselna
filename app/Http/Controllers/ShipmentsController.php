<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\CustomerAddress;
use App\Models\LogisticCenter;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\OrderOperationReason;
use App\Models\ShippingCarrier;
use App\Models\ShippingCarrierPackage;
use App\Models\ShippingCarrierService;
use App\Models\Store;
use App\Utils\order\OrderOperationUtility;
use App\Services\Marketplace\Service\ShopifyService;
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

        return view('shipment.list', compact('perPage', 'stores', 'shippingCarrier', 'shipFrom'));
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
            'order.customer',
            'order.orderItem',
            'order.orderItem.product',
            'order.orderItem.product.image',
            'carrierService:id,name,shipping_carrier_id',
            'carrierService.ShippingCarrier',
            'store:id,name,store_type_id',
        ])->select('order_operation.*', 'order_operation.id as oid');

        // Filter by store
        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('order_operation.store_id', $request->store_id);
        }

        if ($columnName != '') {
            switch (strtolower($columnName)) {
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
        if (!empty((array)$order_operations)) {
            foreach ($order_operations as $key => $order) {
                $storeType = (isset($order->store->storeType) && !empty($order->store->storeType)) ? $order->store->storeType->name : '';
                $code = '';
                $image = $product_id = '';
                $product_total_weight = 0;
                if (isset($order->order->orderItem) && !empty($order->order->orderItem)) {
                    foreach ($order->order->orderItem as $oitem) {
                        if (isset($oitem->product) && !empty($oitem->product)) {
                            $imagename = $oitem->product->product_image;
                            if (!empty($oitem->product->image)) {
                                $image = asset('storage/' . $oitem->product->image->getFileUrl());
                                $product_id = $oitem->product->product_id;
                            }
                            if ($oitem->product->product_sku != '') {
                                $code .= $oitem->product->product_sku . ' x ' . $oitem->quantity . '<br>';
                            }

                            $product_weight = $oitem->product->product_weight;
                            $product_total_weight = $product_total_weight + $product_weight;
                        }
                    }
                }
                $data[] = array(
                    "action" => '<button type="button" class="btn btn-warning show-detail" data-id="' . $order->oid . '">Show Detail</button><input type="checkbox" class="pro-id" name="shipment_id[]" value="' . $order->oid . '" style="visibility:hidden;">',
                    "image" => ($image) ? '<div class="main-image"><img src="' . $image . '" alt="' . $imagename . '" class="mainImage" width="70" height="70" data-product-id="' . $product_id . '"></div>' : '',
                    "status" => '',
                    "carrier_service" => isset($order->carrierService) ? $order->carrierService->ShippingCarrier->name . ' ' . $order->carrierService->name : '',
                    "store" => $order->store->name . ' - ' . $storeType,
                    "order_id" => $order->order->order_id,
                    "order_date" => $order->order->order_date,
                    "product_code" => $code,
                    "weight" => ($order->weight_value) ? $order->weight_value : $product_total_weight,
                    "first_name" => (isset($order->order) && !empty($order->order)) ? $order->order->buyer_first_name : '',
                    "last_name" => (isset($order->order) && !empty($order->order)) ? $order->order->buyer_last_name : '',
                    "city" => (isset($order->order->customer) && !empty($order->order->customer)) ? $order->order->customer->city : '',
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

    public function updateShippingDetails(Request $request)
    {
        $selected_shipment = $request->input('selected_shipment');
        $carrier_id = $request->input('carrier_id');
        $carrier_service_id = $request->input('carrier_service_id');
        $ship_from = $request->input('ship_from');
        $ship_option = $request->input('ship_option');

        if (!empty($selected_shipment)) {
            foreach ($selected_shipment as $id) {
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

    public function getShippingCarrier(Request $request)
    {
        $shipping_carrier_id = $request->input('carrier_id');
        $carrier_servie = ShippingCarrierService::where('shipping_carrier_id', $shipping_carrier_id)->orderBy('name', 'asc')->get();
        $option = '';
        if (!empty($carrier_servie)) {
            foreach ($carrier_servie as $service) {
                $option .= '<option value="' . $service->id . '">' . $service->name . '</option>';
            }
        }
        return response()->json([
            'success' => true,
            'option' => $option
        ]);
    }

    public function getShippingDetail(Request $request)
    {
        $order_opration_id = $request->input('operation_id');
        $shippingCarrier = ShippingCarrier::all();
        $shipFrom = LogisticCenter::all();
        $shippingCarrierPackage = ShippingCarrierPackage::all();
        $shipmentDetail = OrderOperation::with('operationItem', 'order', 'order.customerAddress', 'carrierService', 'store', 'operationItem.orderItem', 'operationItem.orderItem.product')->where('id', $order_opration_id)->first();

        $package_id = '';
        $product_total_weight = 0;
        //print_r($shipmentDetail->operationItem);
        if (count($shipmentDetail->operationItem ?? []) > 0) {
            $product_weight = 0;
            foreach ($shipmentDetail->operationItem as $oitem) {
                $product_id = $oitem->orderItem->product->product_id;
                if ($product_id) {
                    $product_detail = Catalog::findOrFail($product_id);
                    if ($product_detail->product_weight > $product_weight) {
                        $package_id = $product_detail->shipping_package_id;
                    }
                    $product_weight = $product_detail->product_weight;
                    $product_total_weight = $product_total_weight + $product_weight;
                }
            }
        }
        /*echo '<pre>';
        print_r($shipmentDetail);
        echo '</pre>';die;*/
        $shipping_carrier_id = $shipmentDetail->shipping_carrier_id;
        $shippingCarrierService = ShippingCarrierService::where('shipping_carrier_id', $shipping_carrier_id)->orderBy('name', 'asc')->get();


        if ($request->input('mark_as_ship')) {
            $view = view('shipment.mark_as_shipped', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight'))->render();
        } else {
            $view = view('shipment.detail', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight'))->render();
        }
        return response()->json([
            'success' => true,
            'html' => $view
        ]);

    }

    public function getShippingAddress(Request $request)
    {
        $address_id = $request->input('address_id');
        if ($address_id) {
            $addressDetail = CustomerAddress::with('customer', 'customerPhone')->where('id', $address_id)->first();

            $view = view('shipment.edit_shipping_address', compact('addressDetail'))->render();

            return response()->json([
                'success' => true,
                'html' => $view
            ]);
        }
    }

    public function saveAddress(Request $request)
    {

        $id = $request->input('id');
        try {
            $address = CustomerAddress::findOrFail($id);
            $request['address_combined'] = $address_combined = $request['address_line_1'] . ' ' . $request['address_line_2'];
            $address_combined = $address_combined . ' ' . $request['city'] . ' ' . $request['state'] . ' ' . $address->country . ' ' . $request['zip_code'];
            $address->update($request->except(['phone', 'id']));
            return response()->json(['success' => true, 'address' => $address_combined, 'message' => 'Data saved successfully!']);

        } catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

    public function getCancelShipmentDetail(Request $request)
    {

        $order_opration_id = $request->input('operation_id');

        $shipmentDetail = OrderOperation::with('operationItem', 'store.storeType', 'order', 'operationItem.orderItem', 'operationItem.orderItem.product')->where('id', $order_opration_id)->first();

        $reasons = [];
        if (isset($shipmentDetail->store->storeType)) {
            $store_type = $shipmentDetail->store->storeType->id;
            $reasons = OrderOperationReason::where('store_type_id', $store_type)->get();
        }

        $view = view('shipment.cancel_shipment', compact('shipmentDetail', 'reasons'))->render();

        return response()->json([
            'success' => true,
            'html' => $view
        ]);
    }

    public function markShipped(Request $request){

        $request->validate([
            'id' => 'required',
            'carrier_name' => 'required',
//            'carrier_package' => 'required',
            'shipping_carrier_service' => 'required',
            'tracking_number' => 'required',
            'shipment_from' => 'required',
            'weight' => 'required|numeric|gt:0',
            'weight_type' => 'required'
        ]);

        $selected_shipment_id = $request->input('id');

        try {
            if($selected_shipment_id){
                $orderOperation = OrderOperation::with('order','store','store.storeType')->where('id',$selected_shipment_id)->first();
                OrderOperationUtility::processShipment($orderOperation, $request);
//
//            $shipmentDetail = OrderOperation::with('order','store','store.storeType')->where('id',$selected_shipment_id)->first();
//            $shipmentDetail->update($updateRecord);
//
//            if(isset($shipmentDetail->order) && isset($shipmentDetail->store->storeType)){
//                $storeType = $shipmentDetail->store->storeType->name;
//                if(strtolower($storeType) == 'shopify'){
//                    $shopifyServie = new ShopifyService();
//                    $shopifyServie->updateFullfillment($shipmentDetail->order_id);
//                }else{
//
//                }
//            }

                return response()->json([
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select shipment'
                ]);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function cancelShipment(Request $request){

    }
}
