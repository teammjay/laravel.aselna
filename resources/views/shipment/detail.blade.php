
<form id="frmShippingDetail">
    <input type="hidden" name="id" id="op_id" value="{{ $shipmentDetail->id }}">
    <div class="row">
        <div class="col-md-12">
            <div class="row mt-1 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="carrier_name">Carrier Name</label>
                </div>
                <div class="col-md-8">
                    <select class="form-control" id="carrier_name" name="carrier_name">
                        {{--<option value="">--</option>--}}
                        @if(count($shippingCarrier ?? []) > 0)
                            @foreach($shippingCarrier as $sCarrier)
                                <option value="{{ $sCarrier->id }}" {{ ( $sCarrier->id == $shipmentDetail->shipping_carrier_id ) ? 'selected' : '' }}>{{ $sCarrier->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="row mt-1 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="shipment_carrier">Shipping Carrier</label>
                </div>
                <div class="col-md-8">
                    <select class="form-control" id="shipment_carrier" name="shipping_carrier_service">
                        {{--<option value="">--</option>--}}
                        @if(count($shippingCarrierService ?? []) > 0)
                            @foreach($shippingCarrierService as $sCarrierService)
                                <option {{ $shipmentDetail->shipping_carrier_service_id }} value="{{ $sCarrierService->id }}" {{ ( $sCarrierService->id == $shipmentDetail->shipping_carrier_service_id ) ? 'selected' : '' }}>{{ $sCarrierService->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <div class="row mt-1 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="ship_date">Ship Date</label>
                </div>
                <div class="col-md-8" style="gap:10px;">
                    <input type="text" class="form-control" id="ship_date" name="ship_date" value="{{ $shipmentDetail->ship_date }}">
                </div>
            </div>

            <div class="row mt-1 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="insurance_value">Insurance Value</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" id="insurance_value" name="insurance_value" value="{{ $shipmentDetail->insurance_value }}">
                </div>
            </div>
            <div class="row mt-1 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="weight">Weight</label>
                </div>
                <div class="col-md-8 d-flex" style="gap:10px;">
                    <input type="text" class="form-control" id="weight" name="weight" value="{{ ($shipmentDetail->weight_value) ? $shipmentDetail->weight_value : $product_total_weight }}">
                    <select class="form-control" id="weight_type" name="weight_type">
                        <option value="0">Oz</option>
                        <option value="1">Lb</option>
                        <option value="2">G</option>
                        <option value="3">Kg</option>
                        <option value="4">Mg</option>
                    </select>
                </div>
            </div>

            <div class="row mt-1 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="carrier_package">Carrier Package</label>
                </div>
                <div class="col-md-8">
                    <select class="form-control" id="carrier_package" name="carrier_package">
                        <option value="">--</option>
                        @if(count($shippingCarrierPackage ?? []) > 0)
                            @foreach($shippingCarrierPackage as $sCarrierPackage)
                                <option value="{{ $sCarrierPackage->id }}" {{ ( $sCarrierPackage->id == $shipmentDetail->shipping_package_id || ($package_id != '' && $package_id == $sCarrierPackage->id) ) ? 'selected' : '' }}>{{ $sCarrierPackage->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="row mt-1 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="cost">Cost</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" id="cost" name="cost" value="{{ $shipmentDetail->cost }}" readonly>
                </div>
            </div>
            <div class="row mt-1 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="shipment_from">Shipment from</label>
                </div>
                <div class="col-md-8">
                    <select class="form-control" id="shipment_from" name="shipment_from">
                        <option value="">--</option>
                        @if(count($shipFrom ?? []) > 0)
                            @foreach($shipFrom as $lc)
                                <option value="{{ $lc->id }}" {{ ( $lc->id == $shipmentDetail->ship_from_id ) ? 'selected' : '' }} >{{ $lc->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="row mt-1 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="shipping_address">Shipping Address</label>
                </div>
                <div class="col-md-8">
                    @if(isset($shipmentDetail->order->customerAddress))
                        <a href="javascript:void(0);" class="text-blue" id="edit_address" data-id="{{ $shipmentDetail->order->customerAddress->id }}">
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

            <div class="row mt-1 g-3 align-items-center">
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
            <div class="row mt-1 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="store">Store</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" id="store" name="store" value="{{ $shipmentDetail->store->name }} {{ (!empty((array)$shipmentDetail->store->storeType)) ? ' - '.$shipmentDetail->store->storeType->name : '' }}" readonly>
                </div>
            </div>
        </div>
        <hr>
        <div class="col-md-12 mt-3">
            <div class="table-responsive">
                <table id="shipmentItemTable" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Image</th>
                        <th>Sku</th>
                        <th>Quantity</th>
                    </tr>
                    </thead>
                    <tbody id="shipment-item-list">

                    @if(count($shipmentDetail->operationItem ?? []) > 0)
                        @foreach($shipmentDetail->operationItem as $key=>$oitem)
                            @php
                                $url = '';
                                $listingItem = null;
                                if (isset($oitem->orderItem->listingItemViaId) && !empty($oitem->orderItem->listingItemViaId)) {
                                    $listingItem = $oitem->orderItem->listingItemViaId;
                                } elseif (isset($oitem->orderItem->listingItem) && !empty($oitem->orderItem->listingItem)) {
                                    $listingItem = $oitem->orderItem->listingItem;
                                }
                                if($listingItem) {
                                    $store_sku = $listingItem->store_sku;
                                    $store_type = isset($shipmentDetail->store->storeType->name) ? $shipmentDetail->store->storeType->name : '';
                                    $site_url = isset($shipmentDetail->store->store_url) ? $shipmentDetail->store->store_url : '';
                                    $store_url = isset($listingItem->store_url) ? $listingItem->store_url : '';
                                    if($store_sku != '') {
                                        $url = \App\Utils\ListingUtility::generateItemUrl($store_type, $store_sku, $site_url, $store_url);
                                    }
                                }
                            @endphp
                            <tr>

                                <td valign="center">
                                    <div class="btn-group float-right" role="group">
                                        <button id="btnGroupDrop2" type="button" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-info-circle"></i></button>
                                        <div class="dropdown-menu" aria-labelledby="btnGroupDrop2">
                                            <a class="dropdown-item show_attributes" href="javascript:void(0)" data-id="{{ $oitem->orderItem->id }}">Show Attributes</a>
                                            <a class="dropdown-item clickable-row" data-href="{{ $url }}" href="javascript:void(0)">Open Store Page</a>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    @if(isset($oitem->orderItem->product) && !empty($oitem->orderItem->product->image))
                                        <div class="main-image">
                                            <img src="{{  asset('storage/'. $oitem->orderItem->product->image->getFileUrl()) }}" alt="{{  $oitem->orderItem->product->product_image_url }}" class="mainImage" width="50" height="50" data-product-id="{{ $oitem->orderItem->product->product_id }}">
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <input type="hidden" name="opi_ids[{{ $key }}]" value="{{ $oitem->id }}">
                                    <input type="text" class="form-control" name="sku[{{ $key }}]" value="{{ $oitem->sku }}" style="width:122px;"></td>
                                <td><input type="text" class="form-control" name="quantity[{{ $key }}]" value="{{ $oitem->orderItem->quantity }}"></td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</form>
