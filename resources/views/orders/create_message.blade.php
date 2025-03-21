@extends('adminlte::page')

@section('title', 'Order Detail')

@section('content_header')
    <h1>Create Message</h1>
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
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="subject">Subject</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="subject" name="subject" value="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="compose-message-tab" data-toggle="tab" data-target="#tab1" role="tab" aria-controls="tab1" aria-selected="true">
                                    Select Product(s)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="internal-message-tab" data-toggle="tab" data-target="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                                    Product Update
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content" id="custom-tabs-one-tabContent">
                            <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="compose-message-tab">
                                <div class="row p-3">
                                    <div class="col-md-6 d-flex justify-content-start" style="gap:15px">
                                        <select class="form-control w-50" name="template">
                                            <option value="">--</option>
                                        </select>
                                        <button type="button" class="btn btn-default">Attach Files</button>
                                    </div>
                                </div>

                            </div>
                            <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="internal-message-tab">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">

                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-user"></i> Customer
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-12">
                                        <select class="form-control" name="customer">
                                            <option value="--">--</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-fw fa-shopping-cart"></i> Related Order
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="order_id">Order Id</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="order_id" name="order_id" value="{{ old('order_id',$orderDetail->order_id) }}" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="order_date">Order Date</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="order_date" name="order_date" value="{{ old('order_date',$orderDetail->order_date) }}" readonly>
                                    </div>
                                </div>

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="order_status">Order Status</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="order_status" name="order_status" value="{{ old('order_status',$orderDetail->order_status) }}" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mt-2">
                            <div class="card-header">
                                Operations
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-2">
                    <div class="card-header">
                        <i class="fa fa-refresh"></i> Ticket History
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" rows="2"></textarea>
                    </div>
                </div>


            </div>
        </div>

@stop
@section('adminlte_js')
    <script>

    </script>
@stop
