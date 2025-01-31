@extends('adminlte::page')

@section('title', 'Order Detail')

@section('content_header')
    <h1>Order Detail</h1>
@stop

@section('adminlte_css')

@stop

@section('content')

        @csrf
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Order Summary
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="order_id">Order Id</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="order_id" name="order_id" value="" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="store_id">Store</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="store_id" name="store_id" value="" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="order_date">Order Date</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="order_date" name="order_date" value="" readonly>
                                    </div>
                                </div>

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="order_status">Order Status</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="order_status" name="order_status" value="" readonly>
                                    </div>
                                </div>

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="customer">Customer</label>
                                    </div>
                                    <div class="col-md-9">

                                    </div>
                                </div>

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="buyer_name">Buyer Name</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="buyer_name" name="buyer_name" value="" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="buyer_last_name">Buyer Last Name</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="buyer_last_name" name="buyer_last_name" value="" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="buyer_email">Buyer Email</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="buyer_email" name="buyer_email" value="" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="buyer_user_id">Buyer User Id</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="buyer_user_id" name="buyer_user_id" value="" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="order_total">Order Total</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="order_total" name="order_total" value="" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="purchase_order_id">Purchase Order Id</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="purchase_order_id" name="purchase_order_id" value="" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="order_risk_score">Order risk score</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="order_risk_score" name="order_risk_score" value="" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="order_risk_message">Order risk message</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="order_risk_message" name="order_risk_message" value="" readonly>
                                    </div>
                                </div>

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="contigent">Contingent</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="checkbox" id="contigent" name="contigent">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">

                <div class="card">
                    <div class="card-header">
                        Order Summary
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="order_id">Shipping Address</label>
                                    </div>
                                    <div class="col-md-9">

                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="requested_shipment">Requested Shipment</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="requested_shipment" name="requested_shipment" value="" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="requested_shipment">Requested Shipment</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="requested_shipment" name="requested_shipment" value="" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        Operations
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" rows="10"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        Order Items
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="itemTable" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>Thumbnail</th>
                                    <th>Sku</th>
                                    <th>Store Sku</th>
                                    <th>Code</th>
                                    <th>Quantity</th>
                                    <th>Item Price</th>
                                    <th>Id1</th>
                                    <th>Id2</th>
                                    <th>Title</th>
                                </tr>
                                </thead>
                                <tbody id="order-item-list">
                                    <tr>
                                        <td colspan="9"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

@stop
@section('adminlte_js')
    <script>

    </script>
@stop
