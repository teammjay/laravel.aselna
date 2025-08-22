@php use App\Constants\ShippingLabelCreationType; @endphp
<form id="frmReplaceOrder">
    <input type="hidden" name="order_operation_id" value="{{ $shipmentDetail->id }}">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="order_id">Order ID</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="hidden" name="id" value="{{ $shipmentDetail->id }}">
                                    <input type="text" class="form-control" name="order_id" id="shipping_order_id"
                                           value="{{ $shipmentDetail->order->order_id }}" readonly="">
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="date">Date</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="order_date" id="order_date"
                                           value="{{ $shipmentDetail->order->order_date }}" readonly="">
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="status">Status</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="status"
                                           value="{{ \App\Constants\OrderOperationStatus::getOrderStatusName($shipmentDetail->status) }}"
                                           readonly="">
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="shipment_from">Shipment from</label>
                                </div>
                                <div class="col-md-8">
                                    <select class="form-control" name="shipment_from">
                                        @if(count($shipFrom ?? []) > 0)
                                            @foreach($shipFrom as $lc)
                                                <option
                                                    value="{{ $lc->id }}" {{ ( $lc->id == $shipmentDetail->ship_from_id ) ? 'selected' : '' }} >{{ $lc->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="shipment_from">Create Label?</label>
                                </div>
                                <div class="border-dark border-width-2">
                                    <div class="d-flex flex-column">
                                        <div>
                                            <input type="radio" name="label_generation"
                                                   value="<?= ShippingLabelCreationType::CreateLabel ?>" checked="" disabled>
                                            Create Label
                                        </div>
                                        <div>
                                            <input type="radio" name="label_generation"
                                                   value="<?= ShippingLabelCreationType::MarkAsShipped ?>" disabled="disabled">
                                            Mark As Shipped
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">

                                </div>
                                <div class="col-md-8">
                                    <div>
                                        <input type="checkbox" name="print_to_file" id="print_to_file"> Print To File?
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label>Product Rubber Stamp</label>
                                </div>
                                <div class="col-md-8">
                                    <div>
                                        <input type="text" class="form-control" name="products_rubber_stamp" value="{{ $shipmentDetail->products_rubber_stamp }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="carrier_name">Carrier Name</label>
                                </div>
                                <div class="col-md-8">
                                    <select class="form-control" id="carrier_name_ms" name="carrier_name">
                                        @if(count($shippingCarrier ?? []) > 0)
                                            @foreach($shippingCarrier as $sCarrier)
                                                <option
                                                    value="{{ $sCarrier->id }}" {{ ( $sCarrier->id == $shipmentDetail->shipping_carrier_id ) ? 'selected' : '' }}>{{ $sCarrier->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="shipping_carrier_service">Shipping Carrie Service</label>
                                </div>
                                <div class="col-md-8">
                                    <select class="form-control" id="shipping_carrier_service"
                                            name="shipping_carrier_service">
                                        @if(count($shippingCarrierService ?? []) > 0)
                                            @foreach($shippingCarrierService as $sCarrierService)
                                                <option
                                                    value="{{ $sCarrierService->id }}" {{ ( $sCarrierService->id == $shipmentDetail->shipping_carrier_service_id ) ? 'selected' : '' }}>{{ $sCarrierService->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="tracking_number">Tracking Number</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="tracking_number" id="tracking_number" readonly>
                                </div>
                            </div>

                            {{--<div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="cost">Cost</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="cost" id="cost">
                                </div>
                            </div>--}}
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="insurance_value">Insurance Value</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" id="insurance_value" name="insurance_value"
                                           value="{{ $shipmentDetail->insurance_value }}">
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="weight">Weight</label>
                                </div>
                                <div class="col-md-8 d-flex" style="gap:10px;">
                                    <input type="text" class="form-control" id="weight" name="weight"
                                           value="{{ ($shipmentDetail->weight_value) ? $shipmentDetail->weight_value : $product_total_weight }}">
                                    <select class="form-control" id="weight_type" name="weight_type">
                                        <option value="0">Oz</option>
                                        <option value="1">Lb</option>
                                        <option value="2">G</option>
                                        <option value="3">Kg</option>
                                        <option value="4">Mg</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="carrier_package">Carrier Package</label>
                                </div>
                                <div class="col-md-8">
                                    <select class="form-control" id="carrier_package" name="carrier_package">
                                        <option value="">--</option>
                                        @if(count($shippingCarrierPackage ?? []) > 0)
                                            @foreach($shippingCarrierPackage as $sCarrierPackage)
                                                <option
                                                    value="{{ $sCarrierPackage->id }}" {{ ( ($package_id != '' && $package_id == $sCarrierPackage->id) ) ? 'selected' : '' }}>{{ $sCarrierPackage->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="shipping_address">Shipping Address</label>
                                </div>
                                <div class="col-md-8">
                                    @if(isset($shipmentDetail->order->customerAddress))
                                        <a href="javascript:void(0);" class="text-blue edit_address" id="edit_address"
                                           data-id="{{ $shipmentDetail->order->customerAddress->id }}">
                                            {{ $shipmentDetail->order->customer->first_name }} {{ $shipmentDetail->order->customer->last_name }}<br>
                                            {{ $shipmentDetail->order->customerAddress->address_line_1 }},<br>
                                            @if($shipmentDetail->order->customerAddress->address_line_2 != '')
                                                {{ $shipmentDetail->order->customerAddress->address_line_2 }},<br>
                                            @endif
                                            {{$shipmentDetail->order->customerAddress->city}},
                                            {{$shipmentDetail->order->customerAddress->state}} {{$shipmentDetail->order->customerAddress->zip}}
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label>Signature</label>
                                </div>
                                <div class="col-md-8">
                                    <div>
                                        <input type="radio" name="signature" value="signature" checked> None
                                    </div>
                                    <div>
                                        <input type="radio" name="signature" value="directsignature"> Direct Signature
                                    </div>
                                    <div>
                                        <input type="radio" name="signature" value="adultsignature"> Adult Signature
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 mt-3">
            <div class="card">
                <div class="card-header">
                    Items
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="shipmentItemTable" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>Image</th>
                                <th>Sku</th>
                                <th>Quantity</th>
                                <th>Sync Status</th>
                                <th>Sync Message</th>
                            </tr>
                            </thead>
                            <tbody id="shipment-item-list">

                            @if(count($shipmentDetail->operationItem ?? []) > 0)
                                @foreach($shipmentDetail->operationItem as $oitem)
                                    @php
                                        $url = '';
                                        if(isset($oitem->orderItem->listingItem) && !empty($oitem->orderItem->listingItem)){
                                            $store_sku = $oitem->orderItem->listingItem->store_sku;
                                            $store_type = isset($shipmentDetail->store->storeType->name) ? $shipmentDetail->store->storeType->name : '';
                                            $site_url = isset($shipmentDetail->store->store_url) ? $shipmentDetail->store->store_url : '';
                                            $store_url = isset($oitem->orderItem->listingItem->store_url) ? $oitem->orderItem->listingItem->store_url : '';
                                            if($store_sku != ''){
                                                $url = \App\Utils\ListingUtility::generateItemUrl($store_type, $store_sku, $site_url, $store_url);
                                            }
                                        }
                                    @endphp
                                    <tr class="clickable-row" data-href="{{ $url }}">
                                        <td>
                                            @if($oitem->orderItem->product->image != '')
                                                <div class="main-image">
                                                    <img src="{{ asset('storage/' .$oitem->orderItem->product->image->getFileUrl()) }}" loading="lazy" width="50" height="50" class="mainImage" data-product-id="{{ $oitem->orderItem->product->product_id }}">
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $oitem->orderItem->sku }}</td>
                                        <td><input type="text" class="form-control" name="quantity" value="{{ $oitem->orderItem->quantity }}" style="width:50px;" readonly></td>
                                        <td>{{ \App\Constants\OrderSyncStatus::getOrderSyncStatusName($oitem->sync_status) }}</td>
                                        <td>{{ $oitem->sync_message }}</td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
