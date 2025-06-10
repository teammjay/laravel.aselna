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


                        </div>
                    </div>
                </div>
            </div>

            <div class="card">

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="composeMessage-tab" data-toggle="pill" href="#tab1" role="tab" aria-controls="tab1" aria-selected="true">
                                        Compose Message
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="internalNotes-tab" data-toggle="pill" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                                        Internal Notes
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content" id="custom-tabs-one-tabContent">
                                <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="composeMessage-tab">
                                    <div class="row pt-3">
                                        <div class="col-md-4">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label for="template">Template</label>
                                                </div>
                                                <div class="col-md-8">
                                                    <select class="form-control" name="message_template" id="message_template">
                                                        <option value=""></option>
                                                        @if(count($message_template ?? []) > 0)
                                                            @foreach($message_template as $temp)
                                                                <option value="{{ $temp->id }}">{{ $temp->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="file" class="btn btn-default" name="attach_file" id="attach_file">
                                        </div>
                                    </div>
                                    <div class="row pt-3">
                                        <div class="col-md-12">
                                            <textarea class="form-control" rows="10" id="message_to_send" name="message_to_send"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fad" id="tab2" role="tabpanel" aria-labelledby="internalNotes-tab">

                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="row pt-3">
                        <div class="col-md-5 border-r">
                            <div class="d-flex align-items-center" style="gap:10px;">
                                <button type="button" class="btn btn-primary" name="submit_message" id="submit_message"> Submit</button>
                                <button type="button" class="btn btn-default" name="rma" id="rma">RMA</button>
                                <button type="button" class="btn btn-default" name="replacement" id="replacement">Replacement</button>
                                <button type="button" class="btn btn-default" name="refund" id="refund">Refund</button>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="d-flex align-items-center" style="gap:10px;">
                                <select class="form-control" name="ticket_type" id="ticket_type">

                                </select>
                                <select class="form-control" name="action_type" id="action_type">

                                </select>
                                <button type="button" class="btn btn-default" name="revert" id="revert">Revert</button>
                                <button type="button" class="btn btn-default" name="close" id="close">Close</button>
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
                        <div class="col-md-8 d-flex align-items-center">
                            <input type="text" class="form-control" name="order_id" id="order_id">
                            <button type="button" class="btn btn-default" id="search_order"><i class="fa fa-search"></i></button>
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
                    <div class="border mt-3 p-3">
                        <p>Items:</p>
                        <table class="table" id="order_item_table">

                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Operations
                </div>
                <div class="card-body" id="order_operation">

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
        $(document).ready(function () {
            $(document).on('click','#search_order',function () {
                let order_id = $('#order_id').val();
                $.ajax({
                    type: "POST",
                    url: "{{ route('message.searchorder') }}",
                    data: {
                        order_id: order_id
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },

                    success: function(data) {
                        if(data.success){

                            $('#order_item_table').html(data.orderDetail.items);
                            $('#order_date').val(data.orderDetail.orderDate);
                            $('#order_status').val(data.orderDetail.orderStatus);
                            $('#order_total').val(data.orderDetail.orderTotal);
                            $('#purchase_order_id').val(data.orderDetail.orderPOId);
                            $('#order_operation').html(data.orderDetail.orderOperation);
                        }
                    },
                    error: function (xhr) {
                    },
                    complete: function () {
                        $("#ajax-preloader").hide();
                    }
                });
            });

            $(document).on('change','#message_template',function () {
                var template_id = $(this).val();
                $.ajax({
                    type: "POST",
                    url: "{{ route('message.getmessagetemplate') }}",
                    data: {
                        template_id: template_id
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },

                    success: function(data) {
                        if(data.success){

                            $('#message_to_send').html(data.templateCode);
                        }
                    },
                    error: function (xhr) {
                    },
                    complete: function () {
                        $("#ajax-preloader").hide();
                    }
                });
            });

        });
    </script>
@stop
