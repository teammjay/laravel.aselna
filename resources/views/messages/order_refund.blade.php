@php use App\Constants\ShippingLabelCreationType; @endphp
<form id="frmRefundOrder">
    <input type="hidden" name="order_operation_id" value="{{ $shipmentDetail->id }}">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="order_id">Order ID</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="hidden" name="id" value="{{ $shipmentDetail->id }}">
                                    <input type="hidden" name="o_id" value="{{ $shipmentDetail->order_id }}">
                                    <input type="text" class="form-control" name="order_id" id="order_id"
                                           value="{{ $shipmentDetail->order->order_id }}" readonly="">
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="date">Refund Date</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="order_date" id="order_date"
                                           value="{{ date('Y-m-d H:i:s') }}" readonly="">
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="status">Status</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="status" id="status"
                                           value="{{ \App\Constants\OrderOperationStatus::getOrderStatusName($shipmentDetail->status) }}"
                                           readonly="">
                                </div>
                            </div>

                        </div>
                        <div class="col-md-7">
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-5">
                                    <label>Accept return charge</label>
                                </div>
                                <div class="col-md-7">
                                    <input type="checkbox" id="accept_return_charge" name="accept_return_charge">
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-5">
                                    <label for="return_charge">Return Charge</label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" name="return_charge" id="return_charge">
                                </div>
                            </div>

                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-5">
                                    <label for="return_charge_feedback">Return charge feedback</label>
                                </div>
                                <div class="col-md-7">
                                    <select class="form-control" id="return_charge_feedback" name="return_charge_feedback">
                                        <option value=""></option>

                                    </select>
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
                                <th>Reason</th>
                                <th>Sync Status</th>
                                <th>Sync Message</th>
                                <th>Merchant Feedback</th>
                                <th>Returned</th>
                                <th>Product Refund</th>
                                <th>Shipping Refund</th>
                                <th>Product Tax Refund</th>
                                <th>Shipping Tax Refund</th>
                            </tr>
                            </thead>
                            <tbody id="shipment-item-list">

                            @if(count($shipmentDetail->operationItem ?? []) > 0)
                                @foreach($shipmentDetail->operationItem as $oitem)
                                    <input type="hidden" name="item_id[{{ $oitem->orderItem->id }}]" value="{{ $oitem->orderItem->id }}">
                                    <tr>
                                        <td>
                                            @if($oitem->orderItem->product->image != '')
                                                <div class="main-image">
                                                    <img
                                                        src="{{ asset('storage/' .$oitem->orderItem->product->image->getFileUrl()) }}"
                                                        {{--                                                        alt="{{ asset('storage/' .$product->image->getFileUrl())}}"--}}
                                                        loading="lazy" width="50" height="50" class="mainImage"
                                                        data-product-id="{{ $oitem->orderItem->product->product_id }}">
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $oitem->orderItem->sku }}</td>
                                        <td><input type="text" class="form-control width-50" name="quantity[{{ $oitem->orderItem->id }}]" value="{{ $oitem->orderItem->quantity }}"></td>
                                        <td>

                                        </td>
                                        <td>{{ \App\Constants\OrderSyncStatus::getOrderSyncStatusName($oitem->sync_status) }}</td>
                                        <td>{{ $oitem->sync_message }}</td>
                                        <td>
                                            <select class="form-control" name="merchant_feedback[{{ $oitem->id }}]">

                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control width-50" name="refund_quantity[{{ $oitem->orderItem->id }}]" value="{{ $oitem->orderItem->quantity }}"></td>
                                        <td><input type="text" class="form-control width-50" name="refund_price[{{ $oitem->orderItem->id }}]" value="{{ $oitem->orderItem->item_price }}"></td>
                                        <td><input type="text" class="form-control width-50" name="refund_shipping_price[{{ $oitem->orderItem->id }}]" value="{{ ($oitem->orderItem->refund_shipping_price != '') ? $oitem->orderItem->refund_shipping_price : 0 }}"></td>
                                        <td><input type="text" class="form-control width-50" name="refund_tax_price[{{ $oitem->orderItem->id }}]" value="{{ ($oitem->orderItem->refund_tax_price != '') ? $oitem->orderItem->refund_tax_price : 0 }}"></td>
                                        <td><input type="text" class="form-control width-50" name="refund_shipping_tax[{{ $oitem->orderItem->id }}]" value="{{ ($oitem->orderItem->refund_shipping_tax != '') ? $oitem->orderItem->refund_shipping_tax : 0 }}"></td>
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
