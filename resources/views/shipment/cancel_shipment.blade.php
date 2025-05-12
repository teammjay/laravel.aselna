<form id="frmCancelShipment">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="order_id">Order ID</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="order_id" id="order_id" value="{{ $shipmentDetail->order->order_id }}" readonly="">
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="date">Date</label>
                                </div>
                                <div class="col-md-8" >
                                    <input type="text" class="form-control" name="canceldate" id="canceldate" value="{{ date('m/d/Y H:i') }}">
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="status">Status</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="status" id="status" value="Created" readonly="">
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 mt-5">
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
                                <th>Reason</th>
                                <th>Sync Status</th>
                                <th>Sync Message</th>
                            </tr>
                            </thead>
                            <tbody id="shipment-item-list">

                                @if(count($shipmentDetail->operationItem ?? []) > 0)
                                    @foreach($shipmentDetail->operationItem as $oitem)
                                        <tr>
                                            <td>
                                                @if($oitem->orderItem->product->image != '')
                                                    <div class="main-image">
                                                        <img src="{{ asset('storage/' .$oitem->orderItem->product->image->getFileUrl()) }}" alt="{{ asset('storage/' .$product->image->getFileUrl())}}" loading="lazy" width="50" height="50" class="mainImage" data-product-id="{{ $oitem->orderItem->product->product_id }}">

                                                        <img src="{{  asset('storage/product_images/'. $oitem->orderItem->product->product_image_url) }}" alt="{{  $oitem->orderItem->product->product_image_url }}" class="mainImage" width="70" height="70" data-product-id="{{ $oitem->orderItem->product->product_id }}">
                                                    </div>
                                                @endif
                                            </td>
                                            <td>{{ $oitem->orderItem->sku }}</td>
                                            <td><input type="text" class="form-control" name="quantity" value="{{ $oitem->orderItem->quantity }}"></td>
                                            <td>
                                                <select class="form-control" name="reason" id="reason" style="width:150px;">
                                                    <option value=""></option>
                                                    @if(count($reasons ?? []) > 0)
                                                        @foreach($reasons as $reason)
                                                            <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </td>
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