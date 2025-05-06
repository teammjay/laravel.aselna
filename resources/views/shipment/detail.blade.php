<form id="frmShippingDetail">
    <div class="row">
        <div class="col-md-12">
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="carrier_name">Carrier Name</label>
                </div>
                <div class="col-md-8">
                    <select class="form-control" id="carrier_name" name="carrier_name">
                        @if(count($shippingCarrier ?? []) > 0)
                            @foreach($shippingCarrier as $sCarrier)
                                <option value="{{ $sCarrier->id }}" {{ ( $sCarrier->id == $shipmentDetail->shipping_carrier_id ) ? 'selected' : '' }}>{{ $sCarrier->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="shipment_carrier">Shipping Carrier</label>
                </div>
                <div class="col-md-8">
                    <select class="form-control" id="shipment_carrier" name="shipment_carrier">
                        @if(count($shippingCarrierService ?? []) > 0)
                            @foreach($shippingCarrierService as $sCarrierService)
                                <option value="{{ $sCarrierService->id }}" {{ ( $sCarrierService->id == $shipmentDetail->shipping_carrier_service_id ) ? 'selected' : '' }}>{{ $sCarrierService->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="ship_date">Ship Date</label>
                </div>
                <div class="col-md-8" style="gap:10px;">
                    <input type="text" class="form-control" id="ship_date" name="ship_date" value="{{ $shipmentDetail->ship_date }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="insurance_value">Insurance Value</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" id="insurance_value" name="insurance_value" value="{{ $shipmentDetail->insurance_value }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="weight">Weight</label>
                </div>
                <div class="col-md-8 d-flex" style="gap:10px;">
                    <input type="text" class="form-control" id="weight" name="weight" value="{{ $shipmentDetail->weight_value }}">
                    <select class="form-control" id="weight_type" name="weight_type">

                    </select>
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="carrier_package">Carrier Package</label>
                </div>
                <div class="col-md-8">
                    <select class="form-control" id="carrier_package" name="carrier_package">
                        @if(count($shippingCarrierPackage ?? []) > 0)
                            @foreach($shippingCarrierPackage as $sCarrierPackage)
                                <option value="{{ $sCarrierPackage->id }}" {{ ( $sCarrierPackage->id == $shipmentDetail->shipping_carrier_service_id ) ? 'selected' : '' }}>{{ $sCarrierPackage->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="cost">Cost</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" id="cost" name="cost" value="{{ $shipmentDetail->cost }}" readonly>
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="shipment_from">Shipment from</label>
                </div>
                <div class="col-md-8">
                    <select class="form-control" id="shipment_from" name="shipment_from">
                        @if(count($shipFrom ?? []) > 0)
                            @foreach($shipFrom as $lc)
                                <option value="{{ $lc->id }}" {{ ( $lc->id == $shipmentDetail->ship_from_id ) ? 'selected' : '' }} >{{ $lc->name }}</option>
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
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="store">Store</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" id="store" name="store" value="{{ $shipmentDetail->store->name }} {{ (!empty((array)$shipmentDetail->store->storeType)) ? ' - '.$shipmentDetail->store->storeType->name : '' }}" readonly>
                </div>
            </div>
        </div>
        <div class="col-md-12 mt-5">
            <div class="card">
                <div class="card-header">
                    Shipment Items
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="shipmentItemTable" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>Image</th>
                                <th>Sku</th>
                                <th>Quantity</th>
                            </tr>
                            </thead>
                            <tbody id="shipment-item-list">

                                @if(count($shipmentDetail->operationItem ?? []) > 0)
                                    @foreach($shipmentDetail->operationItem as $oitem)
                                        <tr>
                                            <td>
                                                @if(isset($oitem->orderItem->product) && $oitem->orderItem->product->product_image_url != '')
                                                    <div class="main-image">
                                                        <img src="{{  asset('storage/product_images/'. $oitem->orderItem->product->product_image_url) }}" alt="{{  $oitem->orderItem->product->product_image_url }}" class="mainImage" width="70" height="70" data-product-id="{{ $oitem->orderItem->product->product_id }}">
                                                    </div>
                                                @endif
                                            </td>
                                            <td>{{ $oitem->orderItem->sku }}</td>
                                            <td><input type="text" class="form-control" name="quantity" value="{{ $oitem->orderItem->quantity }}"></td>
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