@extends('adminlte::page')

@section('title', 'Message Detail')

@section('content_header')
    <h1>Message Detail</h1>
@stop

@section('adminlte_css')
<style>
    .ticket-title{
        font-size:20px;
    }
    .msg-container {
        border: 2px solid #dedede;
        background-color: #eaeaea;
        border-radius: 5px;
        padding: 10px;
        margin: 10px 0;
    }
    .darker {
        border-color: #ccc;
        background-color: #fff;
    }
    .msg-container::after {
        content: "";
        clear: both;
        display: table;
    }
    .time-right {
        float: right;
        color: #aaa;
    }
    .time-left {
        float: left;
        color: #999;
    }
    .time-center{
        text-align: center;
        font-size: 18px;
        color: #333;
        font-weight: bold;
    }
    .mail-box{
        max-height: 500px;
        overflow: auto;
    }
    #btnGroupDrop2::after{
        display:none;
    }
</style>
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
                    <h2 class="ticket-title">ON <strong>{{ $ticketDetail->ticket_code }}</strong>    {{ $ticketDetail->subject }}</h2>
                </div>
                <div class="card-body">

                    <div class="row">
                        <div class="col-md-12 mail-box">
                            @if(count($messages ?? []) > 0)
                                @foreach($messages as $key=>$message)
                                    <div class="time-center">{{ $message->message_date }}</div>
                                    <div class="msg-container @if(($key % 2)) darker @endif">
                                        {!! nl2br(html_entity_decode($message->message)) !!}
                                    </div>

                                @endforeach
                            @endif

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
                                    <a class="nav-link @if($ticketDetail->notes !='' && $ticketDetail->notes != NULL) text-red @endif" id="internalNotes-tab" data-toggle="pill" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                                        @if($ticketDetail->notes !='' && $ticketDetail->notes != NULL) <i class="fa fa-exclamation-triangle"></i> @endif Internal Notes
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
                                                    <input type="hidden" id="template_id" value="">
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
                                    <div class="row pt-3">
                                        <div class="col-md-12">
                                            <textarea class="form-control" rows="10" id="internal_notes" name="internal_notes">{{ $ticketDetail->notes }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="row btn-sec pt-3">
                        <div class="col-md-8 border-r">
                            <div class="d-flex align-items-center" style="gap:10px;">
                                {{--<div class="btn-group" role="group">
                                    <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-paper-plane"></i> Submit</button>
                                    <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                        <a class="dropdown-item" href="#">Submit and close ticket</a>
                                        <a class="dropdown-item" href="#">Dropdown link</a>
                                    </div>
                                </div>--}}

                                <button type="button" class="btn btn-primary" name="submit_message" id="submit_message"><i class="fa fa-paper-plane"></i> Submit</button>

                                @if(isset($action['canbeReturned']) && $action['canbeReturned'] > 0)
                                    <button type="button" class="btn btn-default" name="rma" id="rma">RMA</button>
                                @endif
                                @if(isset($action['canbeReplace']) && $action['canbeReplace'] > 0)
                                    <button type="button" class="btn btn-default" name="replacement" id="replacement">Replacement</button>
                                @endif
                                @if(isset($action['canbeRefund']) && $action['canbeRefund'] > 0 && strtolower($store_type_name) != 'ebay')
                                    <button type="button" class="btn btn-default" name="refund" id="refund">Refund</button>
                                @endif
                                @if(isset($action['canbeShipped']) && $action['canbeShipped'] > 0)
                                    <button type="button" class="btn btn-default" name="ship_order" id="ship_order">Ship Order</button>
                                @endif
                                @if((isset($action['canbeShipped']) && $action['canbeShipped'] > 0) || (isset($action['canbeRefund']) && $action['canbeRefund'] > 0 && strtolower($store_type_name) != 'ebay'))
                                <button type="button" class="btn btn-default" name="cancel_order" id="cancel_order">Cancel Order</button>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center float-right" style="gap:10px;">
                                {{--<select class="form-control" name="ticket_type" id="ticket_type">
                                    @if(count($ticketTypeCode ?? []) >0)
                                        @foreach($ticketTypeCode as $tcode)
                                            <option value="{{ $tcode->id }}">{{ $tcode->name }}</option>
                                        @endforeach
                                    @endif

                                </select>
                                <select class="form-control" name="action_type" id="action_type">
                                    <option value="0">Needs Action</option>
                                    <option value="1">Awaiting Customer</option>
                                    <option value="2">Closed</option>
                                    <option value="3">Deleted</option>
                                    <option value="4">Spam</option>
                                </select>
                                <button type="button" class="btn btn-default" name="revert" id="revert_ticket">Revert</button>--}}
                                <button type="button" class="btn btn-default" name="close" id="close_ticket">Close</button>
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
                    <div class="row pt-3" >
                        <div class="col-md-12">
                            {{ $ticketDetail->ticket_email }}
                            <input type="hidden" id="customer_id" value="{{ $ticketDetail->customer_id }}">
                            <input type="hidden" id="customer_name" value="{{ isset($ticketDetail->customer) ? $ticketDetail->customer->first_name : '' }}">
                            <input type="hidden" id="store_name" value="{{ isset($ticketDetail->store) ? $ticketDetail->store->name : '' }}">
                        </div>
                    </div>
                    <div class="row pt-3" >
                        <div class="col-md-12 d-flex">
                            <input type="text" class="form-control" name="customer" id="customer" value="{{ isset($ticketDetail->customer) ? $ticketDetail->customer->full_name : '' }}">
                            <button type="button" class="btn btn-default" id="search_customer"><i class="fa fa-search"></i></button>
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
                            <input type="text" class="form-control" name="order_id" id="order_id" value="{{ ($ticketDetail->order_id != '') ? $ticketDetail->order_id : '' }}">
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
                        <table class="table table-bordered" id="order_item_table">

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
                    <table class="table table-bordered" id="ticket_history_table">

                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('messages.modals', ['ticketDetail' => $ticketDetail])
@stop
@section('adminlte_js')
    <script src="{{ asset('js/jquery.datetimepicker.full.js') }}"></script>
    <script>
        $(document).ready(function () {
            $(document).on('click','#search_order',function () {
                loadOrderDetail();
            });

            $(document).on('click','#internalNotes-tab',function () {
                $('.btn-sec').hide();
            });

            $(document).on('click','#composeMessage-tab',function () {
                $('.btn-sec').show();
            });

            loadOrderDetail();

            $(document).on('change','#message_template',function () {
                var template_id = $(this).val();
                var customer_name = $('#customer_name').val();
                var store_name = $('#store_name').val();
                $.ajax({
                    type: "POST",
                    url: "{{ route('message.getmessagetemplate') }}",
                    data: {
                        template_id: template_id,
                        customer_name: customer_name,
                        store_name: store_name,
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

            $(document).on('click','#close_ticket',function () {
                window.location.href = '{{ route('message.index') }}';
            });

            $(document).on('blur','#internal_notes',function () {
                var notes = $(this).val();
                if(notes != '') {
                    let ticket_id = '{{ $ticketDetail->id }}';
                    $.ajax({
                        type: "POST",
                        url: "{{ route('message.updateticket') }}",
                        data: {
                            ticket_id: ticket_id,
                            notes: notes
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },

                        success: function (data) {
                            if (data.success) {

                            }
                        },
                        error: function (xhr) {
                        },
                        complete: function () {
                            $("#ajax-preloader").hide();
                        }
                    });
                }
            });



            /*$(document).on('click','#close_ticket',function () {
                let ticket_type = $('#ticket_type').val();
                let status = $('#action_type').val();
                let ticket_id = '{{ $ticketDetail->id }}';

                if(ticket_id != '') {
                    $.ajax({
                        type: "POST",
                        url: "{{ route('message.updateticket') }}",
                        data: {
                            ticket_id: ticket_id,
                            status: status,
                            ticket_type: ticket_type,
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },

                        success: function (data) {
                            if (data.success) {
                                window.location.href = "{{ route('message.index') }}";
                            }
                        },
                        error: function (xhr) {
                        },
                        complete: function () {
                            $("#ajax-preloader").hide();
                        }
                    });
                }
            });*/

            $(document).on('click','#submit_message',function () {
                var template_id = $('#message_template').val();
                var message_to_send = $('#message_to_send').val();
                var customer_id = $('#customer_id').val();
                var ticket_id = '{{ $ticketDetail->id }}';
                if(message_to_send == ''){
                    alert('Please add text to send message');
                    return false;
                }
                $.ajax({
                    type: "POST",
                    url: "{{ route('message.sendmessagetemplate') }}",
                    data: {
                        template_id: template_id,
                        message_to_send: message_to_send,
                        customer_id: customer_id,
                        ticket_id: ticket_id,
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

            $(document).on('click','#rma',function () {
                var order_id = $('#order_id').val();
                if(order_id != '' && order_id != null){
                    $.ajax({
                        url: "{{ route('message.getshippingdetail') }}",
                        type: 'POST',
                        data: {
                            order_id: order_id,
                            rma: 1,
                            "_token": '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                if(response.template_code != ''){
                                    $('#template_id').val(response.template_id);
                                    $('#message_to_send').html(response.template_code);
                                }
                                $('#makeRMAModal').find('.modal-body').html(response.html);
                                $('#date').datetimepicker({
                                    format:'Y-m-d H:i',
                                    /* minDate: 'now', */
                                    step:10,
                                    onSelectTime: function(dp,$input) {
                                        var sel_date = $input.val();
                                        $('#date').val(sel_date);
                                    }
                                });
                                $('#makeRMAModal').modal('show');

                                getShippingCarrierService($('#carrier_name_ms'));
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }else{
                    alert('There were no order found.');
                }
            });

            $(document).on('click','#regenerate_rma_message',function () {
                var template_id = $('#template_id').val();
                var customer_name = $('#customer_name').val();
                var store_name = $('#store_name').val();
                var rma_number = $('#rma_number').val();
                $.ajax({
                    type: "POST",
                    url: "{{ route('message.getmessagetemplate') }}",
                    data: {
                        template_id: template_id,
                        customer_name: customer_name,
                        store_name: store_name,
                        rma_number: rma_number,
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



            $(document).on('click','#refund',function () {
                var order_id = $('#order_id').val();
                if(order_id != '' && order_id != null){
                    $.ajax({
                        url: "{{ route('message.getshippingdetail') }}",
                        type: 'POST',
                        data: {
                            order_id: order_id,
                            "_token": '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                $('#orderRefundModal').find('.modal-body').html(response.html);
                                $('#orderRefundModal').modal('show');
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }else{
                    alert('There were no order found.');
                }
            });

        });

        function getShippingCarrierService(element){
            $.ajax({
                url: "<?php echo e(route('shipment.getshippingcarrier')); ?>",
                type: 'POST',
                data: {
                    carrier_id: element.val(),
                    "_token": '<?php echo e(csrf_token()); ?>'
                },
                success: function (response) {
                    // If success, remove the user row from the table
                    if (response.success) {
                        $('#carrier_service_id').html(response.option);
                        $('#shipping_carrier_service').html(response.option);
                    }
                },
                error: function (xhr) {
                    // Handle error response
                    alert('Error: ' + xhr.responseJSON.error);
                }
            });
        }

        function loadOrderDetail(){
            let order_id = $('#order_id').val();
            if(order_id != '') {
                $.ajax({
                    type: "POST",
                    url: "{{ route('message.searchorder') }}",
                    data: {
                        order_id: order_id
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },

                    success: function (data) {
                        if (data.success) {

                            $('#order_item_table').html(data.orderDetail.items);
                            $('#order_date').val(data.orderDetail.orderDate);
                            $('#order_status').val(data.orderDetail.orderStatus);
                            $('#order_total').val(data.orderDetail.orderTotal);
                            $('#purchase_order_id').val(data.orderDetail.orderPOId);
                            $('#order_operation').html(data.orderDetail.orderOperation);
                            if(data.ticketHistory != '')
                                $('#ticket_history_table').html(data.ticketHistory);
                        }
                    },
                    error: function (xhr) {
                    },
                    complete: function () {
                        $("#ajax-preloader").hide();
                    }
                });
            }
        }
    </script>
@stop
