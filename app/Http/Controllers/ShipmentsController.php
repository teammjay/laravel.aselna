<?php

namespace App\Http\Controllers;

use App\Constants\OrderOperationStatus;
use App\Constants\OrderStatus;
use App\Helpers\Common;
use App\Helpers\QueryFilters;
use App\Jobs\SyncAllOrderStatusJob;
use App\Jobs\SyncFailedMarketplaceShipmentJob;
use App\Models\Catalog;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\LogisticCenter;
use App\Models\Order;
use App\Models\OrderItem;
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
use Google\Service\ShoppingContent\OrderCancellation;
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
            'order.orderItem.product:product_id,product_sku,product_weight,shipping_package_id,product_image_url',
            'order.orderItem.product.image',
            'carrierService:id,name,shipping_carrier_id',
            'carrierService.ShippingCarrier:id,name',
            'store:id,name,store_type_id',
            'store.storeType:id,name',
            'shippingPackage:id,name',
            'operationItem:id,order_operation_id,order_item_id,sku,quantity',
            'operationItem.orderItem:id,product_id,quantity,sku',
            'operationItem.orderItem.product:product_id,product_sku,product_weight,shipping_package_id,product_image_url',
            'operationItem.orderItem.product.image',
            'mergedOperations:id,merged_with,sync_message'
        ])->select('order_operation.*', 'order_operation.id as oid');

        $query->whereIn('order_operation.status', [OrderOperationStatus::Created, OrderOperationStatus::CanceledRequested, OrderOperationStatus::LabelPrinted, OrderOperationStatus::PaymentPending]);
        $query->where('order_operation.is_merged', '=', false);

        // Filter by store
        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('order_operation.store_id', $request->store_id);
        }

        if ($request->has('product_code') && $request->product_code != '') {
            /*$query->whereHas('order.orderItem.product', function ($q) use ($request){
                $q->orWhere('product_code', '%' . $request->product_code . '%');
            });*/

            QueryFilters::applyRelationFilter($query, $request, 'order.orderItem.product', 'product_sku', 'product_code_operator', 'product_code');
        } else if ($request->has('is_searched') && $request->is_searched == 1) {

                if ($request->has('carrier_id') && $request->carrier_id != '') {
                    $query->where('order_operation.shipping_carrier_id', $request->carrier_id);
                }

                if ($request->has('carrier_service_id') && $request->carrier_service_id != '') {
                    $query->where('order_operation.shipping_carrier_service_id', $request->carrier_service_id);
                }

                if ($request->has('carrier_package') && $request->carrier_package != '') {
                    $query->where('order_operation.shipping_package_id', $request->carrier_package);
                }
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
                if ($order->operationItem->isNotEmpty()) {
                    foreach ($order->operationItem as $opitem) {
                        if (isset($opitem->orderItem->product)) {
                            $product_total_weight += $opitem->orderItem->product->product_weight * $opitem->quantity;
                        }
                    }
                }
                $total_qty = [];
                $package_id = $order->shipping_package_id;
                if ($order->operationItem->isNotEmpty()) {
                    $totalQty = 0;
                    $p20Items = [];
                    foreach ($order->operationItem as $opitem) {
                        $oitem = $opitem->orderItem;

                        if (isset($oitem->product) && !empty($oitem->product)) {
                            //$imagename[] = $oitem->product->product_image;
                            if (!empty($oitem->product->image)) {
                                $image[] = asset('storage/' . $oitem->product->image->getFileUrl());
                                $imagename[] = $oitem->product->image->name;
                                $product_ids[] = $oitem->product->product_id;
                            }
                        }

                        if($opitem->sku != '') {
                            $parts = explode('-', $opitem->sku);
                            $product_code = isset($parts[0]) ? $parts[0] : '';
                            if (strtolower($product_code) === 'p20') {
                                // hold for later
                                $p20Items[] = [
                                    'code' => $product_code,
                                    'qty'  => $opitem->quantity
                                ];
                            } else {
                                $code .= $product_code . ' x ' . $opitem->quantity . PHP_EOL;
                                $onlycode .= $product_code . '<br>';
                            }

                        }else{

                          if (isset($oitem->product) && !empty($oitem->product)) {
                              if ($oitem->product->product_sku != '') {
                                  if (strtolower($oitem->product->product_sku) === 'p20') {
                                      $p20Items[] = [
                                          'code' => $oitem->product->product_sku,
                                          'qty'  => $opitem->quantity
                                      ];
                                  } else {
                                      $code .= $oitem->product->product_sku . ' x ' . $oitem->quantity . PHP_EOL;
                                      $onlycode .= $oitem->product->product_sku . '<br>';
                                  }
                              }

                              //$product_weight = $oitem->product->product_weight * $opitem->quantity;
                              //$product_total_weight = $product_total_weight + $product_weight;

                              /*if ($oitem->product->shipping_package_id != '') {
                                  $package_id = $oitem->product->shipping_package_id;
                                  $order->update(['shipping_package_id' => $package_id]);
                              }*/

                          }
                        }
                        if(isset($oitem->quantity)){
                            $totalQty = $totalQty + $oitem->quantity;
                        }

                        $total_qty[] = $totalQty;
                    }

                    if (!empty($p20Items)) {
                        foreach ($p20Items as $item) {
                            $code .= $item['code'] . ' x ' . $item['qty'] . PHP_EOL;
                            $onlycode .= $item['code'] . '<br>';
                        }
                    }
                }
                $orderIds = isset($order->order) ? [$order->order->order_id] : [];
                $porderIds = isset($order->order) ? [$order->order->purchase_order_id] : [];
                if (isset($order->orderIds)) {
                    $mergedOrderIdList = json_decode($order->orderIds, true) ?? [];
                    if (!empty($mergedOrderIdList)) {
                        $mergedOrders = Order::whereIn('id', $mergedOrderIdList)->select('id', 'order_id', 'purchase_order_id')->get();
                        foreach ($mergedOrders as $orderInfo) {
                            $orderIds[] = $orderInfo->order_id;
                            $porderIds[] = $orderInfo->purchase_order_id;
                        }
                    }
                }
                $status = 0;
                $status_message = '';
                if($order->sync_message != NULL){
                    $status = 1;
                    $status_message = $order->sync_message;
                }

                if ($order->mergedOperations->isNotEmpty()) {
                    foreach ($order->mergedOperations as $operation) {
                        if ($operation->sync_message != NULL) {
                            $status = 1;
                            $status_message = $operation->sync_message;
                            break; // if any of the merged operation has sync message, we will show that message in shipment list
                        }
                    }
                }
                $opstatus = null;
                if ($order->status == OrderOperationStatus::PaymentPending) {
                    $status = 2;
                    $status_message = 'Payment not completed';
                }
                if ($order->status == OrderOperationStatus::CanceledRequested) {
                    $status = 2;
                    $status_message = 'Customer Requested Cancellation for this order';
                }
                if ($order->status == OrderOperationStatus::LabelPrinted) {
                    $status = 3;
                    $status_message = 'Label Printed. Remain to mark as shipped in marketplace.';
                }
                if ($order->status == OrderOperationStatus::LabelCreated) {
                    $status = 3;
                    $status_message = 'Label Created. Remain to print & mark as shipped in marketplace.';
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
                    'op_status' => $order->status,
                    'hold_shipment' => $order->hold_shipment,
                    'operation_retry_count' => $order->operation_retry_count,
                    'carrier_service_name' => $order->carrierService->name ?? NULL,
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

    public function all_shipment(Request $request)
    {
        $perPage = $this->perPage;
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $shippingCarrier = ShippingCarrier::all();
        $shippingCarrierPackage = ShippingPackage::all();
        $shipFrom = LogisticCenter::all();

        return view('shipment.all_shipment_list', compact('perPage', 'stores', 'shippingCarrier', 'shipFrom', 'shippingCarrierPackage'));
    }

    public function allFilter(Request $request)
    {

        $draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        //$columnIndex = $request['order'][0]['column'];
        //$columnName = $request['columns'][$columnIndex]['data'];
        //$columnSortOrder = $request['order'][0]['dir'];

        /*if ($request->input('combine_address', false) === 'true') {
            $this->mergeSameAddressOrders($request);
        } else {
            $this->unmergeSameAddressOrders($request);
        }*/

        $query = OrderOperation::with([
            'order:id,order_id,order_date,buyer_first_name,buyer_last_name,customer_id,shipping_address_id,purchase_order_id',
            'order.customer',
            'order.customerAddress',
            'order.orderItem',
            'order.orderItem.product:product_id,product_sku,product_weight,shipping_package_id,product_image_url',
            'order.orderItem.product.image',
            'carrierService:id,name,shipping_carrier_id',
            'carrierService.ShippingCarrier:id,name',
            'store:id,name,store_type_id',
            'store.storeType:id,name',
            'shippingPackage:id,name',
            'operationItem:id,order_operation_id,order_item_id,sku,quantity',
            'operationItem.orderItem:id,product_id,quantity,sku',
            'operationItem.orderItem.product:product_id,product_sku,product_weight,shipping_package_id,product_image_url',
            'operationItem.orderItem.product.image',
            'mergedOperations:id,merged_with,sync_message'
        ])->select('order_operation.*', 'order_operation.id as oid');

        // Filter by order id
        if ($request->has('order_id') && $request->order_id != '') {
            $query->whereHas('order', function ($q) use($request){
                $q->where('order.order_id', $request->order_id);
            });
        }

        // Filter by purchase order id
        if ($request->has('purchase_order_id') && $request->purchase_order_id != '') {
            $query->whereHas('order', function ($q) use($request){
                $q->where('order.purchase_order_id', $request->purchase_order_id);
            });
        }

        // Filter by first name
        if ($request->has('buyer_first_name') && $request->buyer_first_name != '') {
            $query->whereHas('order', function ($q) use($request){
                $q->whereLike('order.buyer_first_name', '%'.$request->buyer_first_name.'%');
            });
        }

        // Filter by last name
        if ($request->has('buyer_last_name') && $request->buyer_last_name != '') {
            $query->whereHas('order', function ($q) use($request){
                $q->whereLike('order.buyer_last_name', '%'.$request->buyer_last_name.'%');
            });
        }

        // Filter by sku
        if ($request->has('sku') && $request->sku != '') {
            $query->whereHas('order.orderItem', function ($q) use($request){
                $q->whereLike('order_item.sku', '%'.$request->sku.'%');
            });
        }

        // Filter by order date
        if ($request->has('order_date') && $request->order_date != '') {
            $query->whereHas('order', function ($q) use($request){
                $q->whereDate('order.order_date', $request->order_date);
            });
        }

        if ($request->has('operation_status') && $request->operation_status != '') {
            $query->where('order_operation.status', $request->operation_status);
        }

        if ($request->has('tracking_number') && $request->tracking_number != '') {
            $query->where('order_operation.tracking_number', $request->tracking_number);
        }

        // Filter by store
        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('order_operation.store_id', $request->store_id);
        }

        // Filter by store
        if ($request->has('resubmit_count') && $request->resubmit_count != '') {
            $query->where('order_operation.resubmit_count', $request->resubmit_count);
        }



        /*if($columnName != ''){
            switch(strtolower($columnName)){
                case 'status':
                    break;
                case 'carrier_package':

                    $query->whereHas('shippingPackage', function ($q) use($columnSortOrder){
                        $q->orderBy('shipping_package.name', $columnSortOrder);
                    });
                    break;
                case 'carrier_service':
                    $query->whereHas('carrierService.ShippingCarrier', function ($q) use($columnSortOrder){
                        $q->orderBy('shipping_carrier.name', $columnSortOrder);
                    });
                    break;
                case 'store':
                    break;
                case 'order_id':
                    break;
                case 'purchase_order_id':
                    break;
                case 'order_date':
                    break;
                case 'product_code':
                    break;
                case 'weight':
                    break;
                case 'expected_shipment_date':
                    break;
                case 'first_name':
                    break;
                case 'last_name':
                    break;
                case 'state':
                    break;

            }
        }*/


        $query->join('order', 'order.id', '=', 'order_operation.order_id');
        $query->orderBy('order.order_date', 'desc');
        $totalRecords = $query->count();
        $order_operations = $query->skip($start)
            ->take($perPage)
            ->get();
        $totalRecordwithFilter = $totalRecords;

        //$order_operations = $query->distinct()->get();

        /*echo '<pre>';
        print_r($order_operations);
        echo '</pre>';die;*/

        $shipments = [];
        if (!empty((array)$order_operations)) {
            foreach ($order_operations as $key => $order) {
                $storeType = (isset($order->store->storeType) && !empty($order->store->storeType)) ? $order->store->storeType->name : '';
                $code = '';
                $onlycode = '';
                $image = '';
                $imagename = $product_ids = [];
                $product_total_weight = Common::calculateProductWeight($order);
                $total_qty = [];
                $package_id = $order->shipping_package_id;
                if (isset($order->operationItem) && !empty($order->operationItem)) {
                    $totalQty = 0;
                    foreach ($order->operationItem as $opitem) {
                        $oitem = $opitem->orderItem;
                        $image .= '<input type="checkbox" class="pro-id" name="shipment_id[]" data-status="'.$order->status.'" value="'.$order->oid.'" style="visibility:hidden;"><div class="image-grid">';
                        if (isset($oitem->product) && !empty($oitem->product)) {
                            if (!empty($oitem->product->image)) {
                                $img = asset('storage/' . $oitem->product->image->getFileUrl());
                                $imgName = $oitem->product->image->name;
                                $imagename[] = $imgName;

                                $image .= '<div class="main-image mb-1">
                                                <img src="'.$img.'" alt="'.$imgName.'" class="mainImage" width="70" height="70" data-product-id="'.$oitem->product->product_id.'">
                                            </div>';

                                $product_ids[] = $oitem->product->product_id;
                            }
                        }
                        $image .= '</div>';

                        if($opitem->sku != '') {
                            $parts = explode('-', $opitem->sku);
                            $product_code = isset($parts[0]) ? $parts[0] : '';
                            //$product_detail = Catalog::where('product_sku', $product_code)->first();
                            $code .= $product_code . ' x ' . $opitem->quantity . PHP_EOL;
                            $onlycode .= $product_code . '<br>';
                        }else{

                            if (isset($oitem->product) && !empty($oitem->product)) {
                                if ($oitem->product->product_sku != '') {
                                    $code .= $oitem->product->product_sku . ' x ' . $oitem->quantity . PHP_EOL;
                                    $onlycode .= $oitem->product->product_sku . '<br>';
                                }
                            }
                        }
                        if(isset($oitem->quantity)){
                            $totalQty = $totalQty + $oitem->quantity;
                        }

                        $total_qty[] = $totalQty;
                    }
                }
                $orderIds = isset($order->order->order_id) ? [$order->order->order_id] : [];
                $porderIds = isset($order->order->purchase_order_id) ? [$order->order->purchase_order_id] : [];
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
                $opstatus = OrderOperationStatus::getOrderStatusName($order->status);
                $status = 0;
                $status_message = '';
                if($order->sync_message != NULL){
                    $status = 1;
                    $status_message = $order->sync_message;
                }
                $packageName = isset($order->shippingPackage) ? $order->shippingPackage->name : '';
                /*if($package_id){
                    $packageDetail = ShippingPackage::findOrFail($package_id);
                    $packageName = $packageDetail->name;
                }*/

                if(isset($opstatus)){
                    $status = $opstatus;
                }else{
                    if($status == 1){
                        $status = '<i class="fa fa-exclamation-triangle" style="font-size:24px" title="'.$status_message.'"></i>';
                    }
                }
                $shipments[] = array(
                    "oid" => $order->oid,
                    "image" => (!empty($image)) ? $image : '',
                    "imagename" => (!empty($imagename)) ? $imagename : '',
                    "product_id" => (!empty($product_ids)) ? $product_ids : '',
                    "status" => $status,
                    "operationstatus" => $opstatus,
                    "status_message" => $status_message,
                    "carrier_package" => $packageName,
                    "carrier_service" => isset($order->carrierService) ? $order->carrierService->ShippingCarrier->name . ' ' . $order->carrierService->name : '',
                    "store" => $order->store->name . ' - ' . $storeType,
                    "order_id" => implode(', <br/> ', $orderIds),
                    "purchase_order_id" => (!empty($porderIds)) ? implode(', <br/> ', $porderIds) : '',
                    "order_date" => isset($order->order) ? $order->order->order_date : '',
                    "expected_shipment_date" => isset($order) ? $order->expected_shipment_date : '',
                    "product_code" => $code,
                    "weight" => ($order->weight_value) ? $order->weight_value : $product_total_weight,
                    "first_name" => (isset($order->order) && !empty($order->order)) ? $order->order->buyer_first_name : '',
                    "last_name" => (isset($order->order) && !empty($order->order)) ? $order->order->buyer_last_name : '',
                    "state" => (isset($order->order->customerAddress) && !empty($order->order->customerAddress)) ? $order->order->customerAddress->state : '',
                    'product_ids' => $product_ids,
                    'total_qty' => $total_qty,
                    'submit_count' => $order->resubmit_count
                );
            }
        }

        /*echo '<pre>';
        print_r($shipments);
        echo '</pre>';die;*/

        /*if ($request->ajax()) {
            return response()->json([
                'html' => view('shipment.search_shipment_list', compact('shipments'))->render(),
                'totalRecords' => $order_operations->count()
            ]);
        }*/

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordwithFilter,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $shipments
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
        $carrier_package = $request->input('carrier_package');


        if (!empty($selected_shipment)) {
            $updatedRecord = [];
            foreach ($selected_shipment as $key=>$id) {
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

                $norderOperation = OrderOperation::with([
                    'carrierService:id,name,shipping_carrier_id',
                    'carrierService.ShippingCarrier',
                    'shippingPackage:id,name'])->where('id',$id)->first();

                $updatedRecord[$key]['shipping_carrier'] = $norderOperation->carrierService->name ?? null;
                $updatedRecord[$key]['carrier_service'] = isset($norderOperation->carrierService) ? $norderOperation->carrierService->ShippingCarrier->name . ' ' . $norderOperation->carrierService->name : null;
                $updatedRecord[$key]['shipping_package'] = $norderOperation->shippingPackage->name ?? null;
                $updatedRecord[$key]['id'] = $id;
            }

            return response()->json([
                'success' => true,
                'operations' => $updatedRecord
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
        $shipmentDetail = OrderOperation::with('operationItem', 'order', 'order.customerAddress', 'carrierService', 'store', 'store.storeType', 'store.logisticCenters', 'operationItem.orderItem', 'operationItem.orderItem.product', 'operationItem.orderItem.product.image', 'operationItem.orderItem.listingItem')->where('id', $order_opration_id)->first();

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
                        if (($product_detail->product_weight * $oitem->quantity) > $product_weight) {
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

                        $product_weight = $product_detail->product_weight * $oitem->quantity;
                        $product_weight_unit = $product_detail->product_weight_unit;
                    }
                }
            }
        }

        Log::info('Shipment Info :: ' . $shipmentDetail->ship_from_id);
        if($shipmentDetail->ship_from_id == '' || $shipmentDetail->ship_from_id == null) {
            $storeLogisticCenter = $shipmentDetail->store->logisticCenters->first();
            if (isset($storeLogisticCenter->logistic_center_id)) {
                $shipmentDetail->ship_from_id = $storeLogisticCenter->logistic_center_id;
            } else {
                $shipmentDetail->ship_from_id = null;
            }
        }

        /*echo '<pre>';
        print_r($shipmentDetail);
        echo '</pre>';die;*/
        $shipping_carrier_id = $shipmentDetail->shipping_carrier_id;
        $shippingCarrierService = ShippingCarrierService::where('shipping_carrier_id', $shipping_carrier_id)->orderBy('name', 'asc')->get();

        $shipmentItems = [];
        if(isset($shipmentDetail->operationItem)) {
            $items = $shipmentDetail->operationItem;

            $normalItems = $items->filter(function ($item) {
                $sku = explode('-', $item->sku)[0] ?? '';
                return strtolower($sku) !== 'p20';
            });

            $p20Items = $items->filter(function ($item) {
                $sku = explode('-', $item->sku)[0] ?? '';
                return strtolower($sku) === 'p20';
            });

            $shipmentItems = $normalItems->merge($p20Items);
        }


        if ($request->input('mark_as_ship')) {
            $view = view('shipment.mark_as_shipped', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight', 'product_weight_unit','shipmentItems'))->render();
        } else {
            $view = view('shipment.detail', compact('shipmentDetail', 'shippingCarrier', 'shippingCarrierService', 'shippingCarrierPackage', 'shipFrom', 'package_id', 'product_total_weight','shipmentItems'))->render();
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

            if (isset($request['full_name']) && $request['full_name'] != '') {
                $fullname = $request['full_name'] . ',<br>';
            }else if(isset($request['first_name']) && $request['first_name'] != ''){
                $fullname = $request['first_name'] .' '. $request['last_name']. ',<br>';
                $request['full_name'] = $request['first_name'].' '.$request['last_name'];
            }

            if (isset($request['address_line_1']) && $request['address_line_1'] != '') {
                $address1 = $request['address_line_1'] . ',<br>';
            }

            if (isset($request['address_line_2']) && $request['address_line_2'] != '') {
                $address2 = $request['address_line_2'] . ',<br>';
            }

            $address_combined = $fullname . $address1 . $address2 . $request['city'] . ', ' . $request['state'] . ' ' . $request['zip'];
            //$address->update($request->except(['phone', 'id']));

            /*if(isset($address->customer_id) && $address->customer_id != ''){
                $customer = Customer::findOrFail($address->customer_id);
                $customerData = [
                    'first_name' => $request['first_name'],
                    'last_name' => $request['last_name'],
                    'full_name' => (isset($request['full_name']) && $request['full_name'] != '') ? $request['full_name'] : $request['first_name'].' '.$request['last_name'],
                ];

                //$customer->update($customerData);
            }*/

            $singleAddressChange = true;

            /* get customer active orders and update address*/
            if($request->has('same_address') && $request->same_address == 1) {
                $active_orders = Order::with('customerAddress')->where('shipping_address_id', $id)->where('customer_id', $address->customer->id)->where('order_status', OrderStatus::Synchronizing)->get();

                Log::info('Same address count : '.count($active_orders ?? []));

                if(count($active_orders ?? []) > 1){
                    $singleAddressChange = false;
                    foreach($active_orders as $order){
                        $order_address = $order->customerAddress;
                        Log::info('order address: '.json_encode($order_address));
                        $order_address->update($request->except(['phone', 'id']));
                    }
                }
            }else if($request->has('same_address') && $request->same_address == 0){
                /* check any other order has same address*/
                if($request->has('op_id') && $request->op_id != '') {
                    $operation = OrderOperation::select('order_id')->where('id',$request->op_id)->first();
                    Log::info('order Operation: '.json_encode($operation));
                    $orderinfo = Order::where('id', $operation->order_id)->first();
                    Log::info('order deail: '.json_encode($orderinfo));
                    $other_order = Order::with('customerAddress')->where('id','!=',$orderinfo->id)->where('shipping_address_id', $orderinfo->shipping_address_id)->count();

                    Log::info('other order: '.json_encode($other_order));

                    if($orderinfo && $other_order > 0){
                        $customer_address_data = [
                            'customer_id' => $address->customer_id,
                            'first_name' => isset($request['first_name']) ? $request['first_name'] : ' ',
                            'last_name' => isset($request['last_name']) ? $request['last_name'] : ' ',
                            'full_name' => (isset($request['full_name']) && $request['full_name'] != '') ? $request['full_name'] : $request['first_name'].' '.$request['last_name'],
                            'address_line_1' => $request['address_line_1'],
                            'address_line_2' => $request['address_line_2'] ?? '',
                            'city' => $request['city'],
                            'state' => $request['state'],
                            'state_code' => $request['state_code'],
                            'zip' => $request['zip'],
                            'country' => $request['country'],
                            'country_code' => $request['country_code'],
                            'address_combined' => $address_combined
                        ];

                        Log::info("New address created for seperate order " . json_encode($customer_address_data));

                        $newCustomerAddress = CustomerAddress::create($customer_address_data);
                        $orderinfo->update(['shipping_address_id' => $newCustomerAddress->id]);
                        $singleAddressChange = false;
                    }
                }
            }

            if($singleAddressChange){

                $address->update($request->except(['phone', 'id']));

                if(isset($address->customer_id) && $address->customer_id != ''){
                    $customer = Customer::findOrFail($address->customer_id);
                    $customerData = [
                        'first_name' => $request['first_name'],
                        'last_name' => $request['last_name'],
                        'full_name' => (isset($request['full_name']) && $request['full_name'] != '') ? $request['full_name'] : $request['first_name'].' '.$request['last_name'],
                    ];

                    $customer->update($customerData);
                }
            }
            /* get customer active orders and update address*/

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
        Log::info('Cancel Order Request :: ' . $selected_shipment_id);
        $reason = $request->input('reason');

        try {
            if ($selected_shipment_id) {
                $orderOperation = OrderOperation::with('order', 'store', 'store.storeType')->where('id', $selected_shipment_id)->first();
                $response = OrderOperationUtility::cancelOrder($orderOperation, strtoupper($reason));

                if (!empty($response) && $response['status']) {
                    /*$orderOperation->update(['status' => OrderOperationStatus::Canceled]);
                    if (isset($orderOperation->order->id)) {
                        $order = Order::findOrFail($orderOperation->order->id);
                        $order->update(['order_status' => OrderStatus::Canceled]);
                    }*/
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

    public function updateShipmentStatus(Request $request){
        $selected_shipment_ids = $request->input('operation_ids');
        $status = $request->input('status');
        $orderstatus = $request->input('orderstatus');

        try {
            if ($selected_shipment_ids) {
                $orderOperations = OrderOperation::with('order', 'store', 'store.storeType')->whereIn('id', $selected_shipment_ids)->get();

                if (!empty($orderOperations)) {
                    foreach($orderOperations as $orderOperation){
                        $orderOperation->update(['status' => $status]);
                        if (isset($orderOperation->order->id)) {
                            $order = Order::findOrFail($orderOperation->order->id);
                            $order->update(['order_status' => $orderstatus]);
                        }
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Shipment status updated successfully',
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order operation not found. Please try again!'
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

            if ($operation->hold_shipment == 1) {
                return response()->json([
                    'success' => false,
                    'process_id' => $process_id,
                    'order_id' => $order_id,
                    'status' => 'error',
                    'message' => 'Skipping, Shipment is on hold for this order operation'
                ]);
            }

            $store = $operation->store;

            $package_id = '';
            $product_total_weight = Common::calculateProductWeight($operation);

            $product_weight_unit = 0;

            if(isset($operation->shipping_package_id)) {
                $request['carrier_package'] = $operation->shipping_package_id;
            }

            if (count($operation->operationItem ?? []) > 0) {
                $product_weight = 0;
                foreach ($operation->operationItem as $oitem) {
                    if (isset($oitem->orderItem->product)) {
                        $product_id = $oitem->orderItem->product->product_id;
                        if ($product_id) {
                            $product_detail = Catalog::findOrFail($product_id);
                            if (!isset($operation->shipping_package_id)) {
                                if (($product_detail->product_weight * $oitem->quantity) > $product_weight) {
                                    $package_id = $product_detail->shipping_package_id;
                                }
                                if ($package_id) {
                                    $request['carrier_package'] = $package_id;
                                }
                            } else {
                                $request['carrier_package'] = $operation->shipping_package_id;
                            }

                            $product_weight = $product_detail->product_weight * $oitem->quantity;
                            $product_weight_unit = $product_detail->product_weight_unit;
                        }
                    }
                }
            }

            $request['carrier_name'] = $operation->shipping_carrier_id;
            $request['shipping_carrier_service'] = $operation->shipping_carrier_service_id;
            $request['weight'] = $operation->weight_value ?? $product_total_weight;
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
        $hold_shipment = $request->input('hold_shipment');
        $holdshipment = ($hold_shipment === 'on') ? 1 : 0;

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
                'hold_shipment' => $holdshipment
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

                        $sku = preg_split('/[_-]/', $skus[$key])[0];
                        $updateRecord['items'][] = [
                            'sku' => $sku,
                            'quantity' => $quantities[$key],
                        ];
                        $catalog = Catalog::where('product_sku', $sku)->where('product_status','active')->first();
                        if($catalog) {
                            $order_item_id = $orderOperationItem->order_item_id;
                            $order_item = OrderItem::findOrFail($order_item_id);
                            $order_item->update(['product_id' => $catalog->product_id ?? null]);
                        }
                    }
                }

                $norderOperation = OrderOperation::with([
                    'carrierService:id,name,shipping_carrier_id',
                    'carrierService.ShippingCarrier',
                    'store:id,name,store_type_id',
                    'shippingPackage:id,name'])->where('id',$order_operation_id)->first();

                $updateRecord['shipping_carrier'] = $norderOperation->carrierService->name ?? null;
                $updateRecord['carrier_service'] = isset($norderOperation->carrierService) ? $norderOperation->carrierService->ShippingCarrier->name . ' ' . $norderOperation->carrierService->name : null;
                $updateRecord['shipping_package'] = $norderOperation->shippingPackage->name ?? null;
                $updateRecord['id'] = $order_operation_id;
            }
            return response()->json([
                'success' => true,
                'operation' => $updateRecord
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
        $addressIds = Order::select('shipping_address_id','store_id')
            ->groupBy('shipping_address_id','store_id')
            ->havingRaw('COUNT(*) > 1')
            ->whereNotIn('order_status', [
                OrderStatus::Canceled,
                OrderStatus::Shipped,
                OrderStatus::Refund,
                OrderStatus::Replaced,
                OrderStatus::PartiallyShipped,
                OrderStatus::SynchronizatonFailed,
                OrderStatus::CanceledRequested
            ])
            ->whereIn('id', function ($query) {
                $query->select('order_id')
                    ->from('order_operation')
                    ->where('is_merged', false)
                    ->where('status', 0);
            })->get();

        if (isset($addressIds)) {
            foreach ($addressIds as $address) {
                $addressId = $address->shipping_address_id;
                $address_store_id = $address->store_id;
                Log::info('Processing Address ID: ' . $address->shipping_address_id . ' for Store ID: ' . $address->store_id);
                if ($addressId == null || $addressId == '' || $addressId == 0) {
                    continue; // Skip null addresses
                }
                $sameAddressOrders[$addressId] = Order::where('shipping_address_id', $addressId)
                    ->where('store_id', $address_store_id)
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
                    $product_weight = 0;
                    foreach ($orderOperation->operationItem as $oitem) {
                        if (isset($oitem->orderItem->product)) {
                            $product_id = $oitem->orderItem->product->product_id;
                            if ($product_id) {
                                $product_detail = Catalog::findOrFail($product_id);
                                $product_weight = $product_detail->product_weight * $oitem->quantity;
                            }
                        }
                    }
                    $totalweight += $orderOperation->weight_value ?? $product_weight;
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
            ->whereIn('status',[0,20])
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
        $status = $request->input('status');

        if($selected_shipment_id != ''){

            $orderOperation = OrderOperation::findOrFail($selected_shipment_id);
            if ($orderOperation) {
                $updateRecord = [
                    'sync_message' => NULL
                ];
                if($status == 2){
                    $updateRecord['status'] = OrderOperationStatus::Created;
                }
                OrderOperation::where('is_merged', true)->where('merged_with', $selected_shipment_id)->update($updateRecord);
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

    public function resubmitShipment(Request $request)
    {

        $selected_shipment_ids = $request->input('shipment_ids');

        try {
            if ($selected_shipment_ids) {

                foreach($selected_shipment_ids as $selected_shipment_id) {
                    $orderOperation = OrderOperation::with('order', 'store', 'store.storeType', 'operationItem', 'operationItem.orderItem', 'carrier', 'carrierService')->where('id', $selected_shipment_id)->first();

                    $request['label_generation'] = 1;
                    $request['resubmit'] = 1;

                    OrderOperationUtility::processShipment($orderOperation, $request);

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

}
