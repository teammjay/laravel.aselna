<?php

namespace App\Http\Controllers;

use App\Constants\OrderOperationStatus;
use App\Constants\OrderStatus;
use App\Helpers\Common;
use App\Models\Catalog;
use App\Models\CustomerAddress;
use App\Models\LogisticCenter;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\OrderOperationItem;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ShipmentsController extends Controller
{
    private $perPage = 25;

    public function index(Request $request)
    {
        $perPage = $this->perPage;
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $shippingCarrier = ShippingCarrier::all();
        $shippingCarrierPackage = ShippingPackage::all();
        $shipFrom = LogisticCenter::all();

        return view('shipment.list', compact('perPage', 'stores', 'shippingCarrier', 'shipFrom', 'shippingCarrierPackage'));
    }

    public function filter(Request $request)
    {

        /*$draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];*/

        if ($request->input('combine_address', false) === 'true') {
            $this->mergeSameAddressOrders($request);
        } else {
            $this->unmergeSameAddressOrders($request);
        }

        $query = OrderOperation::with([
            'order:id,order_id,order_date,buyer_first_name,buyer_last_name,customer_id,shipping_address_id,purchase_order_id',
            'order.customer',
            'order.customerAddress',
            'order.orderItem',
            'order.orderItem.product',
            'order.orderItem.product.image',
            'carrierService:id,name,shipping_carrier_id',
            'carrierService.ShippingCarrier',
            'store:id,name,store_type_id',
            'shippingPackage:id,name',
        ])->select('order_operation.*', 'order_operation.id as oid');

        $query->where('order_operation.status', 0);
        $query->where('order_operation.is_merged', '=', false);

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
                $product_total_weight = Common::calculateProductWeight($order);
                $total_qty = [];
                $package_id = $order->shipping_package_id;
                if (isset($order->operationItem) && !empty($order->operationItem)) {
                    $totalQty = 0;
                    foreach ($order->operationItem as $opitem) {
                        $oitem = $opitem->orderItem;
                        if (isset($oitem->product) && !empty($oitem->product)) {
                            $imagename[] = $oitem->product->product_image;
                            if (!empty($oitem->product->image)) {
                                $image[] = asset('storage/' . $oitem->product->image->getFileUrl());
                                $product_ids[] = $oitem->product->product_id;
                            }
                            if ($oitem->product->product_sku != '') {
                                $code .= $oitem->product->product_sku . ' x ' . $oitem->quantity . PHP_EOL;
                                $onlycode .= $oitem->product->product_sku . '<br>';
                            }

                            $product_weight = $oitem->product->product_weight;
                            //$product_total_weight = $product_total_weight + $product_weight;

                            /*if ($oitem->product->shipping_package_id != '') {
                                $package_id = $oitem->product->shipping_package_id;
                                $order->update(['shipping_package_id' => $package_id]);
                            }*/
                        }else{
                            if($opitem->sku != '') {
                                $parts = explode('-', $opitem->sku);
                                $product_code = isset($parts[0]) ? $parts[0] : '';
                                //$product_detail = Catalog::where('product_sku', $product_code)->first();
                                $code .= $product_code . ' x ' . $opitem->quantity . PHP_EOL;
                                $onlycode .= $product_code . '<br>';
                            }
                        }
                        if(isset($oitem->quantity)){
                            $totalQty = $totalQty + $oitem->quantity;
                        }

                        $total_qty[] = $totalQty;
                    }
                }
                $orderIds = isset($order->order) ? [$order->order->order_id] : [];
                $porderIds = isset($order->order) ? [$order->order->purchase_order_id] : [];
                if (isset($order->orderIds)) {
                    $mergedOrderIds = json_decode($order->orderIds, true) ?? [];
                    foreach ($mergedOrderIds as $oid) {
                        $orderInfo = Order::where('id', $oid)->first();
                        if ($orderInfo) {
                            $orderIds[] = $orderInfo->order_id;
                            $porderIds[] = $orderInfo->purchase_order_id;
                        }
                    }
                }
                $status = 0;
                $status_message = '';
                if($order->status_message != NULL){
                    $status = 1;
                    $status_message = $order->status_message;
                }
                $packageName = isset($order->shippingPackage) ? $order->shippingPackage->name : '';
                if($package_id){
                    $packageDetail = ShippingPackage::findOrFail($package_id);
                    $packageName = $packageDetail->name;
                }
                $shipments[] = array(
                    "oid" => $order->oid,
                    "image" => (!empty($image)) ? $image : '',
                    "imagename" => (!empty($imagename)) ? $imagename : '',
                    "product_id" => (!empty($product_ids)) ? $product_ids : '',
                    "status" => $status,
                    "status_message" => $status_message,
                    "carrier_package" => $packageName,
                    "carrier_service" => isset($order->carrierService) ? $order->carrierService->ShippingCarrier->name . ' ' . $order->carrierService->name : '',
                    "store" => $order->store->name . ' - ' . $storeType,
                    "order_id" => implode(', <br/> ', $orderIds),
                    "purchase_order_id" => implode(', <br/> ', $porderIds),
                    "order_date" => isset($order->order) ? $order->order->order_date : '',
                    "expected_shipment_date" => isset($order) ? $order->expected_shipment_date : '',
                    "product_code" => $code,
                    "weight" => ($order->weight_value) ? $order->weight_value : $product_total_weight,
                    "first_name" => (isset($order->order) && !empty($order->order)) ? $order->order->buyer_first_name : '',
                    "last_name" => (isset($order->order) && !empty($order->order)) ? $order->order->buyer_last_name : '',
                    "state" => (isset($order->order->customerAddress) && !empty($order->order->customerAddress)) ? $order->order->customerAddress->state : '',
                    'product_ids' => $product_ids,
                    'total_qty' => $total_qty,
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
        $carrier_package = $request->input('carrier_package');

        if (!empty($selected_shipment)) {
            foreach ($selected_shipment as $id) {
                $updateRecord = array(
                    'shipping_carrier_id' => $carrier_id,
                    'shipping_carrier_service_id' => $carrier_service_id,
                    'ship_from_id' => $ship_from,
                    'shipping_package_id' => $carrier_package
                );

                $orderOperation = OrderOperation::findOrFail($id);
                if ($orderOperation) {
                    $orderOperation->update($updateRecord);
                }
            }

            return response()->json([
                'success' => true
            ]);
        }
        return response()->json([
            'success' => false
        ]);
    }

    public function getShippingCarrier(Request $request)
    {
        $shipping_carrier_id = $request->input('carrier_id');
        $selected_service = $request->input('selected_service');
        $carrier_servie = ShippingCarrierService::where('shipping_carrier_id', $shipping_carrier_id)->orderBy('name', 'asc')->get();
        $option = '';
        if (!empty($carrier_servie)) {
            foreach ($carrier_servie as $service) {
                if($selected_service != '' && $service->id == $selected_service){
                    $sel = 'selected';
                }else{
                    $sel = '';
                }
                $option .= '<option value="' . $service->id . '" '.$sel.'>' . $service->name . '</option>';
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
        $shippingCarrierPackage = ShippingPackage::all();
        $shipmentDetail = OrderOperation::with('operationItem', 'order', 'order.customerAddress', 'carrierService', 'store', 'store.storeType', 'store.logisticCenters', 'operationItem.orderItem', 'operationItem.orderItem.product', 'operationItem.orderItem.listingItem')->where('id', $order_opration_id)->first();

        $package_id = '';
        $product_total_weight = Common::calculateProductWeight($shipmentDetail);
        $product_weight_unit = 0;
        //print_r($shipmentDetail->operationItem);
        if (count($shipmentDetail->operationItem ?? []) > 0) {
            $product_weight = 0;
            foreach ($shipmentDetail->operationItem as $oitem) {
                if (isset($oitem->orderItem->product)) {
                    $product_id = $oitem->orderItem->product->product_id;
                    if ($product_id) {
                        $product_detail = Catalog::findOrFail($product_id);
                        /*if ($product_detail->product_weight > $product_weight) {
                            $package_id = $product_detail->shipping_package_id;
                        }*/
                        if ($product_detail->product_weight > $product_weight) {
                            $package_id = $product_detail->shipping_package_id;
                        }
                        if ($package_id && $shipmentDetail->shipping_package_id == NULL) {
                            $shipmentDetail->shipping_package_id = $package_id;
                            $shipmentDetail->save();
//                          $shippingPackage = ShippingPackage::findOrFail($package_id);
//                            $shippingCarrierPackageLink = ShippingPackageShippingCarrierPackageLink::where('shipping_package_id', $package_id)->first();
//                            if ($shippingCarrierPackageLink) {
//                                $shippingCPackage = ShippingCarrierPackage::findOrFail($shippingCarrierPackageLink->shipping_carrier_package_id);
//                                if (!isset($shipmentDetail->shipping_package_id)) {
//                                    $package_id = $shippingCPackage->id;
//                                    $shipmentDetail->shipping_package_id = $shippingCPackage->id;
//                                    $shipmentDetail->save();
//                                }
//                            }
                        } else {
                            $package_id = $shipmentDetail->shipping_package_id;
                        }

                        $product_weight = $product_detail->product_weight;
                        $product_weight_unit = $product_detail->product_weight_unit;
                    }
                }
            }
        }

        Log::info('Shipment Info :: ' . $shipmentDetail->ship_from_id);
        $storeLogisticCenter = $shipmentDetail->store->logisticCenters->first();
        if (isset($storeLogisticCenter->logistic_center_id)) {
            $shipmentDetail->ship_from_id = $storeLogisticCenter->logistic_center_id;
        } else {
            $shipmentDetail->ship_from_id = null;
        }

        /*echo '<pre>';
        print_r($shipmentDetail);
        echo '</pre>';die;*/
        $shipping_carrier_id = $shipmentDetail->shipping_carrier_id;
        $shippingCarrierService = ShippingCarrierService::where('shipping_carrier_id', $shipping_carrier_id)->orderBy('name', 'asc')->get();


        if ($request->input('mark_as_ship')) {
            $view = view('shipment.mark_as_shipped', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight', 'product_weight_unit'))->render();
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
            $address = CustomerAddress::with('customer')->where('id', $id)->first();
            $request['address_combined'] = $address_combined = $request['address_line_1'] . ' ' . $request['address_line_2'];
            $fullname = $address1 = $address2 = '';
            if (isset($address->customer)) {
                $fullname = $address->customer->full_name . ', <br>';
            }

            if (isset($request['address_line_1']) && $request['address_line_1'] != '') {
                $address1 = $request['address_line_1'] . ',<br>';
            }

            if (isset($request['address_line_2']) && $request['address_line_2'] != '') {
                $address2 = $request['address_line_2'] . ',<br>';
            }

            $address_combined = $fullname . $address1 . $address2 . $request['city'] . ', ' . $request['state'] . ' ' . $request['zip'];
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
            $reasons = OrderOperationReason::where('store_type_id', $store_type)->where('operation_type', 1)->get();
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
                $orderOperation = OrderOperation::with('order', 'store', 'store.storeType', 'operationItem', 'operationItem.orderItem', 'carrier', 'carrierService')->where('id', $selected_shipment_id)->first();
                $successRes = OrderOperationUtility::processShipment($orderOperation, $request);

                if (isset($successRes) && $successRes['success'] == false) {
                    return response()->json([
                        'success' => false,
                        'message' => $successRes['message'] ?? 'Something went wrong with API. Please try again!'
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
            Log::error('Shipment Marked Error: ' . $exception->getMessage());
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
                $response = OrderOperationUtility::cancelOrder($orderOperation, strtoupper($reason));

                if (!empty($response) && $response['status']) {
                    $orderOperation->update(['status' => OrderOperationStatus::Canceled]);
                    if (isset($orderOperation->order->id)) {
                        $order = Order::findOrFail($orderOperation->order->id);
                        $order->update(['order_status' => OrderStatus::Canceled]);
                    }
                    return response()->json([
                        'success' => true,
                        'message' => 'Order Cancelled Successfully',
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => isset($response['message']) ? $response['message'] : 'Something went wrong with API. Please try again!'
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

    public function shipSelected(Request $request)
    {
        $selected_shipment_id = $request->input('shipment_ids');
        $store_id = $request->input('store_id');

        // For streaming response
        if ($request->input('process_id')) {
            $process_id = $request->input('process_id');
            $order_id = $request->input('order_id');
            Log::info("Starting Shipment of OrderID : " . $order_id);

            // Process a single order operation
            $operation = OrderOperation::with('order', 'operationItem', 'store', 'store.storeType')
                ->where('id', $order_id)
                ->first();

            if (!$operation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid shipment ID'
                ]);
            }

            $store = $operation->store;

            $package_id = '';
            $product_total_weight = Common::calculateProductWeight($operation);

            $product_weight_unit = '';

            if (count($operation->operationItem ?? []) > 0) {
                $product_weight = 0;
                foreach ($operation->operationItem as $oitem) {
                    if (isset($oitem->orderItem->product)) {
                        $product_id = $oitem->orderItem->product->product_id;
                        if ($product_id) {
                            $product_detail = Catalog::findOrFail($product_id);
                            if (!isset($operation->shipping_package_id)) {
                                if ($product_detail->product_weight > $product_weight) {
                                    $package_id = $product_detail->shipping_package_id;
                                }
                                if ($package_id) {
                                    $request['carrier_package'] = $package_id;
                                }
                            } else {
                                $request['carrier_package'] = $operation->shipping_package_id;
                            }
                            $product_weight = $product_detail->product_weight;
                            $product_weight_unit = $product_detail->product_weight_unit;
                        }
                    }
                }
            }

            $request['carrier_name'] = $operation->shipping_carrier_id;
            $request['shipping_carrier_service'] = $operation->shipping_carrier_service_id;
            $request['weight'] = $product_total_weight;
            $request['weight_type'] = isset($operation->weight_weight_unit) ? $operation->weight_weight_unit : $product_weight_unit;
            $request['label_generation'] = 1;
            $request['tracking_number'] = null;
            $request['shipment_from'] = $operation->ship_from_id;

            if (!isset($request['shipment_from']) || $request['shipment_from'] == '') {
                $storeLogisticCenter = $store->logisticCenters->first();
                if (isset($storeLogisticCenter->logistic_center_id)) {
                    $request['shipment_from'] = $storeLogisticCenter->logistic_center_id;
                } else {
                    return response()->json([
                        'success' => false,
                        'process_id' => $process_id,
                        'order_id' => $order_id,
                        'status' => 'error',
                        'message' => 'No shipping location found for store'
                    ]);
                }
            }

            $successRes = OrderOperationUtility::processShipment($operation, $request);

            $remainingCount = OrderOperation::where('status', 0)
                ->where('is_merged', false)
                ->when($store_id, function ($query) use ($store_id) {
                    return $query->where('store_id', $store_id);
                })
                ->count();

            return response()->json([
                'success' => $successRes['success'],
                'remaining_count' => $remainingCount,
                'process_id' => $process_id,
                'order_id' => $order_id,
                'status' => $successRes['success'] ? 'completed' : 'error',
                'message' => $successRes['message'] ?? 'Shipment processed successfully'
            ]);
        }

        // Initial request - just return the IDs that need to be processed
        if (count($selected_shipment_id ?? []) > 0) {
            $process_id = uniqid('ship_');
            $shipmentInfos = OrderOperation::with('order', 'store', 'store.storeType')
                ->whereIn('id', $selected_shipment_id)
                ->get();
            $shipmentDetails = [];
            foreach ($shipmentInfos as $shipmentInfo) {
                $shipmentDetails[] = [
                    'id' => $shipmentInfo->id,
                    'order_id' => $shipmentInfo->order->order_id ?? '',
                    'store_name' => $shipmentInfo->store->name ?? '',
                    'store_type' => $shipmentInfo->store->storeType->name ?? '',
                ];
            }

            return response()->json([
                'success' => true,
                'process_id' => $process_id,
                'shipment_ids' => $selected_shipment_id,
                'shipment_details' => $shipmentDetails,
                'total_count' => count($selected_shipment_id)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No shipments selected'
        ]);
    }

    public function shipThis(Request $request)
    {

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

    public function updateShipment(Request $request)
    {
        $order_operation_id = $request->input('id');
        $carrier_id = $request->input('carrier_name');
        $carrier_service_id = $request->input('shipping_carrier_service');
        $shipping_package_id = $request->input('carrier_package');
        $ship_date = $request->input('ship_date');
        $ship_from = $request->input('shipment_from');
        $insurance_value = $request->input('insurance_value');
        $weight_value = $request->input('weight');
        $weight_type = $request->input('weight_type');
        $skus = $request->input('sku');
        $quantities = $request->input('quantity');
        $opi_ids = $request->input('opi_ids');

        if ($order_operation_id) {
            $updateRecord = array(
                'ship_date' => $ship_date,
                'shipping_carrier_id' => $carrier_id,
                'shipping_carrier_service_id' => $carrier_service_id,
                'shipping_package_id' => $shipping_package_id,
                'ship_from_id' => $ship_from,
                'insurance_value' => $insurance_value,
                'weight_value' => $weight_value,
                'weight_weight_unit' => $weight_type,
            );

            $orderOperation = OrderOperation::findOrFail($order_operation_id);
            if ($orderOperation) {
                $orderOperation->update($updateRecord);

                if(is_array($opi_ids) && !empty($opi_ids)){
                    foreach($opi_ids as $key=>$id){
                        $itemData = [
                            'sku' => $skus[$key],
                            'quantity' => $quantities[$key],
                        ];

                        $orderOperationItem = OrderOperationItem::findOrFail($id);
                        $orderOperationItem->update($itemData);
                    }
                }
            }
            return response()->json([
                'success' => true
            ]);
        }
        return response()->json([
            'success' => false
        ]);
    }

    private function mergeSameAddressOrders(Request $request)
    {
        //(select shipping_address_id from `order` group by shipping_address_id having count(*) > 1;
        // I want all the orderId whose addresses are same.
        $sameAddressOrders = [];
        $addressIds = Order::select('shipping_address_id')
            ->groupBy('shipping_address_id','store_id')
            ->havingRaw('COUNT(*) > 1')
            ->whereNotIn('order_status', [
                OrderStatus::Canceled,
                OrderStatus::Shipped,
                OrderStatus::Refund,
                OrderStatus::Replaced,
                OrderStatus::PartiallyShipped,
                OrderStatus::SynchronizatonFailed
            ])
            ->whereIn('id', function ($query) {
                $query->select('order_id')
                    ->from('order_operation')
                    ->where('is_merged', false)
                    ->where('status', 0);
            })
            ->pluck('shipping_address_id');

        if ($addressIds->isNotEmpty()) {
            foreach ($addressIds as $addressId) {
                Log::info('Processing Address ID: ' . $addressId);
                if ($addressId == null || $addressId == '' || $addressId == 0) {
                    continue; // Skip null addresses
                }
                $sameAddressOrders[$addressId] = Order::where('shipping_address_id', $addressId)
                    ->whereIn('id', function ($query) {
                        $query->select('order_id')
                            ->from('order_operation')
                            ->where('is_merged', false)
                            ->where('status', 0);
                    })
                    ->pluck('id')
                    ->toArray();
            }
        }
        Log::info('Processing Address IDs: ' . json_encode($sameAddressOrders));

        foreach ($sameAddressOrders as $addressId => $orderIds) {
            if (count($orderIds) > 1) {
                Log::info('Merging Orders for Address ID: ' . $addressId . ' - Orders: ' . implode(',', $orderIds));
                $mainShipment = null;
                $totalweight = 0;
                $totalInsurance = 0;
                foreach ($orderIds as $orderId) {
                    $orderOperation = OrderOperation::where('order_id', $orderId)->first();
                    $totalweight += $orderOperation->weight_value;
                    $totalInsurance += $orderOperation->insurance_value;
                    if ($orderOperation) {
                        if (!isset($mainShipment)) {
                            $mainShipment = $orderOperation; // Keep the first one as the main shipment
                            continue;
                        }
                        $mainShipment->operationItem()->createMany($orderOperation->operationItem->toArray());
                        $orderOperation->update([
                            'is_merged' => true,
                            'merged_with' => $mainShipment->id,
                        ]);
                        // save the comma separated order IDs in the main shipment
                        $mergedOrderIds = json_decode($mainShipment->orderIds, true) ?? [];
                        $mergedOrderIds[] = $orderId;
                        $mainShipment->orderIds = json_encode(array_unique($mergedOrderIds));
                        $mainShipment->save();
                    }
                }
                $mainShipment->weight_value = $totalweight;
                $mainShipment->insurance_value = $totalInsurance;
                $mainShipment->save();
            }
        }
    }

    private function unmergeSameAddressOrders(Request $request)
    {
        Log::info("Coming HEre");
        $mergedOperations = OrderOperation::where('is_merged', true)
            ->where('status','0')
            ->whereNotNull('merged_with')->get();

        foreach ($mergedOperations as $mergedOperation) {
            $mainOperation = OrderOperation::where('id', $mergedOperation->merged_with)->first();

            foreach ($mergedOperation->operationItem as $operationItem) {
                OrderOperationItem::where('order_item_id', $operationItem->order_item_id)
                    ->where('order_operation_id',$mainOperation->id)->delete();
            }
            $mergedOperation->update([
                'is_merged' => false,
                'merged_with' => null,
            ]);

            $mergedOrderIds = json_decode($mainOperation->orderIds, true) ?? [];
            // remove the merged orderId
            $mergedOrderIds = array_filter($mergedOrderIds, function ($id) use ($mergedOperation) {
                return $id != $mergedOperation->order_id;
            });

            $mainOperation->orderIds = json_encode($mergedOrderIds);
            $mainOperation->save();

            Log::info('Merging Orders : ' . json_encode($mergedOrderIds));

        }
    }

    public function approveShipment(Request $request){

        $selected_shipment_id = $request->input('shipment_id');

        if($selected_shipment_id != ''){

            $orderOperation = OrderOperation::findOrFail($selected_shipment_id);
            if ($orderOperation) {
                $updateRecord = [
                    'status_message' => NULL
                ];
                $orderOperation->update($updateRecord);
            }
            return response()->json([
                'success' => true
            ]);
        }
        return response()->json([
            'success' => false
        ]);

    }

}
