@extends('adminlte::page')

@section('title', 'Message Detail')

@section('content_header')
    <h1>Message Detail</h1>
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
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    General
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="first_name">First Name</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="" readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="last_name">Last Name</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="" readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="name">Name</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="name" name="name" value="" readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="store">Store</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="hidden" class="form-control" id="last_name" name="last_name" value="">
                                    <input type="text" class="form-control" id="store" value="" readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="user_id">User ID</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="user_id" name="user_id" value="" readonly>
                                </div>
                            </div>

                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="group">Group</label>
                                </div>
                                <div class="col-md-9">
                                    <select class="form-control" name="group" id="group">
                                        <option value=""></option>
                                    </select>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">

            <div class="card">
                <div class="card-header">
                    Customer
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            Customer Name
                        </div>
                    </div>
                    <div class="row pt-3" >
                        <div class="col-md-12">
                            <input type="text" class="form-control" name="customer" id="customer">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Related Order
                </div>
                <div class="card-body">
                    <div class="row mt-3 g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="carrier_name">Order ID</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="order_id" id="order_id">
                        </div>
                    </div>
                    <div class="row mt-3 g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="carrier_name">Order Date</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="order_date" id="order_date" readonly="">
                        </div>
                    </div>
                    <div class="row mt-3 g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="carrier_name">Order Status</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="order_status" id="order_status" readonly="">
                        </div>
                    </div>
                    <div class="row mt-3 g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="carrier_name">Order Total</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="order_total" id="order_total" readonly="">
                        </div>
                    </div>
                    <div class="row mt-3 g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="carrier_name">Purchase Order Id</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="purchase_order_id" id="purchase_order_id" readonly="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Operations
                </div>
                <div class="card-body">

                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    Ticket History
                </div>
                <div class="card-body">

                </div>
            </div>
        </div>
    </div>

@stop
@section('adminlte_js')
    <script>

    </script>
@stop
