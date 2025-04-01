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
        <div class="col-md-6">
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
        <div class="col-md-6">

            <div class="card">
                <div class="card-header">
                    Emails
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table id="itemTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Email</th>
                                            <th>Primary address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Phones
                </div>
                <div class="card-body">
                    <textarea class="form-control" name="phones" rows="2"></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    Addresses
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="itemTable" class="table table-bordered table-striped">
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    Orders
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="itemTable" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Order Date</th>
                                <th>Order Status</th>
                                <th>Order Total</th>
                            </tr>
                            </thead>
                            <tbody id="order-item-list">
                            {{--@if(count($orderDetail->orderItem ?? []) > 0)
                                @foreach($orderDetail->orderItem as $item)
                                    <tr>
                                        <td>{{ $item->sku }}</td>
                                        <td>{{ $item->store_sku }}</td>
                                        <td>{{ (isset($item->product)) ? $item->product->product_sku : '' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9">No item found</td>
                                </tr>
                            @endif--}}
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
