<?php

namespace App\Http\Controllers;

use App\Constants\OrderOperationStatus;
use App\Constants\OrderStatus;
use App\Models\Catalog;
use App\Models\CustomerAddress;
use App\Models\LogisticCenter;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\OrderOperationReason;
use App\Models\ShippingCarrier;
use App\Models\ShippingCarrierPackage;
use App\Models\ShippingCarrierService;
use App\Models\ShippingPackage;
use App\Models\ShippingPackageShippingCarrierPackageLink;
use App\Models\Store;
use App\Utils\order\OrderOperationUtility;
use App\Services\Marketplace\Service\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        /*$draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];*/

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

        $query->where('order_operation.status', 0);

        // Filter by store
        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('order_operation.store_id', $request->store_id);
        }

        /*if ($columnName != '') {
            switch (strtolower($columnName)) {
                case 'order_id':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                        ->orderBy('order.order_id', $columnSortOrder);
                    break;
                case 'carrier_service':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                        ->join('shipping_carrier_service', 'shipping_carrier_service.id', '=', 'order_operation.shipping_carrier_service_id')
                        ->orderBy('shipping_carrier_service.name', $columnSortOrder);
                    break;
                case 'order_date':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                        ->orderBy('order.order_date', $columnSortOrder);
                    break;
                case 'store':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                    ->join('store', 'store.id', '=', 'order_operation.store_id')
                        ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
                        ->orderByRaw("CONCAT(store.name, ' - ', store_type.name) $columnSortOrder");
                    break;
                case 'first_name':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                        ->orderBy('order.buyer_first_name', $columnSortOrder);
                    break;
                case 'last_name':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                        ->orderBy('order.buyer_last_name', $columnSortOrder);
                    break;

                case 'product_code':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                        ->join('order_item', 'order_item.order_id', '=', 'order.id')
                        ->join('products', 'products.product_id', '=', 'order_item.product_id')
                        ->orderBy('products.product_sku', $columnSortOrder);
                    break;

                case 'weight':
                    $query->join('order', 'order.id', '=', 'order_operation.order_id')
                        ->join('order_item', 'order_item.order_id', '=', 'order.id')
                        ->join('products', 'products.product_id', '=', 'order_item.product_id')
                        ->orderBy('products.product_sku', $columnSortOrder);
                    break;

            }

            $sortableColumns = [
                'carrier_service' => 'carrier_service',
                'order_date' => 'order_date',
                'store' => 'store',
                'buyer_first_name' => 'buyer_first_name',
                'buyer_last_name' => 'buyer_last_name'
            ];
            if (isset($sortableColumns[$columnName])) {
                //$query->orderBy($sortableColumns[$columnName], $columnSortOrder);
            }
        }*/

        $order_operations = $query->distinct()->get();

        /*echo '<pre>';
        print_r($order_operations);
        echo '</pre>';die;*/

        $shipments = [];
        if (!empty((array)$order_operations)) {
            foreach ($order_operations as $key => $order) {
                $storeType = (isset($order->store->storeType) && !empty($order->store->storeType)) ? $order->store->storeType->name : '';
                $code = '';
                $onlycode = '';
                $image = $imagename = $product_ids = [];
                $product_total_weight = 0;
                if (isset($order->order->orderItem) && !empty($order->order->orderItem)) {
                    foreach ($order->order->orderItem as $oitem) {
                        if (isset($oitem->product) && !empty($oitem->product)) {
                            $imagename[] = $oitem->product->product_image;
                            if (!empty($oitem->product->image)) {
                                $image[] = asset('storage/' . $oitem->product->image->getFileUrl());
                                $product_ids[] = $oitem->product->product_id;
                            }
                            if ($oitem->product->product_sku != '') {
                                $code .= $oitem->product->product_sku . ' x ' . $oitem->quantity . PHP_EOL;
                                $onlycode .= $oitem->product->product_sku.'<br>';
                            }

                            $product_weight = $oitem->product->product_weight;
                            $product_total_weight = $product_total_weight + $product_weight;
                        }
                    }
                }
                $shipments[] = array(
                    "oid" => $order->oid,
                    "image" => (!empty($image)) ? $image : '',
                    "imagename" => (!empty($imagename)) ? $imagename : '',
                    "product_id" => (!empty($product_ids)) ? $product_ids : '',
                    "status" => '',
                    "carrier_service" => isset($order->carrierService) ? $order->carrierService->ShippingCarrier->name . ' ' . $order->carrierService->name : '',
                    "store" => $order->store->name . ' - ' . $storeType,
                    "order_id" => isset($order->order) ? $order->order->order_id : '',
                    "order_date" => isset($order->order) ? $order->order->order_date : '',
                    "product_code" => $code,
                    "weight" => ($order->weight_value) ? $order->weight_value : $product_total_weight,
                    "first_name" => (isset($order->order) && !empty($order->order)) ? $order->order->buyer_first_name : '',
                    "last_name" => (isset($order->order) && !empty($order->order)) ? $order->order->buyer_last_name : '',
                    "city" => (isset($order->order->customer) && !empty($order->order->customer)) ? $order->order->customer->city : ''
                );
            }
        }

        /*echo '<pre>';
        print_r($shipments);
        echo '</pre>';die;*/

        if ($request->ajax()) {
            return response()->json([
                'html' => view('shipment.search_shipment_list', compact('shipments'))->render(),
                'totalRecords' => $order_operations->count()
            ]);
        }

        //echo $query->toSql();die;
        //$totalRecords = $query->count();
        /*$order_operations = $query->skip($start)
            ->take($perPage)
            ->get();
        $totalRecordwithFilter = $totalRecords;*/
        /*echo '<pre>';
        print_r($order_operations);
        echo '</pre>';die;*/



        /*$response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordwithFilter,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $shipments
        );

        return $response;*/

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
                if(isset($oitem->orderItem->product)) {
                    $product_id = $oitem->orderItem->product->product_id;
                    if ($product_id) {
                        $product_detail = Catalog::findOrFail($product_id);
                        if ($product_detail->product_weight > $product_weight) {
                            $package_id = $product_detail->shipping_package_id;
                        }
                        if ($package_id) {
//                        $shippingPackage = ShippingPackage::findOrFail($package_id);
                            $shippingCarrierPackageLink = ShippingPackageShippingCarrierPackageLink::where('shipping_package_id', $package_id)->first();
                            if ($shippingCarrierPackageLink) {
                                $shippingCPackage = ShippingCarrierPackage::findOrFail($shippingCarrierPackageLink->shipping_carrier_package_id);
                                if (!isset($shipmentDetail->shipping_package_id)) {
                                    $package_id = $shippingCPackage->id;
                                    $shipmentDetail->shipping_package_id = $shippingCPackage->id;
                                    $shipmentDetail->save();
                                }
                            }
                        }

                        $product_weight = $product_detail->product_weight;
                        $product_total_weight = $product_total_weight + $product_weight;
                    }
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
            $reasons = OrderOperationReason::where('store_type_id', $store_type)->where('operation_type',1)->get();
        }

        $view = view('shipment.cancel_shipment', compact('shipmentDetail', 'reasons'))->render();

        return response()->json([
            'success' => true,
            'html' => $view
        ]);
    }

    public function markShipped(Request $request)
    {

        /*$request->validate([
            'id' => 'required',
            'carrier_name' => 'required',
//            'carrier_package' => 'required',
            'shipping_carrier_service' => 'required',
//            'tracking_number' => 'required',
            'shipment_from' => 'required',
            'weight' => 'required|numeric|gt:0',
            'weight_type' => 'required'
        ]);*/

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'carrier_name' => 'required',
            'carrier_package' => 'required',
            'shipping_carrier_service' => 'required',
//            'tracking_number' => 'required',
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

        $selected_shipment_id = $request->input('id');

        try {
            if ($selected_shipment_id) {
                $orderOperation = OrderOperation::with('order','store','store.storeType','operationItem','operationItem.orderItem','carrier','carrierService')->where('id',$selected_shipment_id)->first();
                $isSuccess = OrderOperationUtility::processShipment($orderOperation, $request);

                if (!$isSuccess) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Something went wrong with API. Please try again!'
                    ]);
                }

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

    public function cancelOrder(Request $request)
    {

        $selected_shipment_id = $request->input('id');
        $reason = $request->input('reason');

        try {
            if ($selected_shipment_id) {
                $orderOperation = OrderOperation::with('order', 'store', 'store.storeType')->where('id', $selected_shipment_id)->first();
                $response = OrderOperationUtility::cancelOrder($orderOperation,$reason);

                if($response){
                    $orderOperation->update(['status'=>OrderOperationStatus::Canceled]);
                    if(isset($orderOperation->order->id)){
                        $order = Order::findOrFail($orderOperation->order->id);
                        $order->update(['order_status'=>OrderStatus::Canceled]);
                    }
                    return response()->json([
                        'success' => true,
                        'message' => 'Order Cancelled Successfully',
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Something went wrong with API. Please try again!'
                    ]);
                }

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

    public function shipSelected(Request $request){

        $selected_shipment_id = $request->input('shipment_ids');

        $orderOperations = OrderOperation::with('order', 'store', 'store.storeType')->whereIn('id', $selected_shipment_id)->get();

        if(count($orderOperations ?? []) > 0){
            $isSuccess = false;
            foreach($orderOperations as $operation){

               $package_id = '';
               $product_total_weight = 0;
               //print_r($shipmentDetail->operationItem);
                if (count($operation->operationItem ?? []) > 0) {
                    $product_weight = 0;
                    foreach ($operation->operationItem as $oitem) {
                        $product_id = $oitem->orderItem->product->product_id;
                        if ($product_id) {
                            $product_detail = Catalog::findOrFail($product_id);
                            if ($product_detail->product_weight > $product_weight) {
                               $package_id = $product_detail->shipping_package_id;
                            }
                            if ($package_id) {
                               $request['carrier_package'] = $package_id;
                            }
                            $product_weight = $product_detail->product_weight;
                            $product_total_weight = $product_total_weight + $product_weight;
                        }
                    }
                }

                $request['carrier_name'] = $operation->shipping_carrier_id;
                $request['shipping_carrier_service'] = $operation->shipping_carrier_service_id;
                $request['weight'] = $product_total_weight;
                $request['weight_type'] = $operation->weight_weight_unit;
                $request['label_generation'] = 1;
                $request['tracking_number'] = null;
                $request['shipment_from'] = $operation->ship_from_id;

                $isSuccess = OrderOperationUtility::processShipment($operation, $request);
            }

            if (!$isSuccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong with API. Please try again!'
                ]);
            }

            return response()->json([
                'success' => true
            ]);
        }
    }

    public function shipThis(Request $request){

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'carrier_name' => 'required',
            'carrier_package' => 'required',
            'shipping_carrier_service' => 'required',
//            'tracking_number' => 'required',
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

        $request['label_generation'] = 1;

        $orderOperations = OrderOperation::with('order', 'store', 'store.storeType')->whereIn('id', $request->id)->get();

        $isSuccess = OrderOperationUtility::processShipment($orderOperations, $request);

        if (!$isSuccess) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong with API. Please try again!'
            ]);
        }

        return response()->json([
            'success' => true
        ]);
    }
}
