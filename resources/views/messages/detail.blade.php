@extends('adminlte::page')

@section('title', 'Message Detail')

@section('content_header')
    @if($type == 'create')
        <h1>Create Message</h1>
    @else
        <h1>Message Detail</h1>
    @endif

@stop

@section('adminlte_css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
        width:90%;
    }
    .darker {
        border-color: #ccc;
        background-color: #fff;
        margin-left: 10%;
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
    #order_operation{
        position: relative;
        overflow: hidden;
    }

    #order_operation .dropdown-menu{
        left: -12px !important;
    }
    .hide{display:none !important;}
    .show{display:block !important;}
    .width-50{width:100px;}
    .btn-group.lableImageGroup.show{display: inline-block !important;}
    .select2-container--default .select2-dropdown{
        width:320px !important;
    }
    .select2-dropdown {
        z-index: 9999;
    }
    .select2-container {
         width: 100% !important;
         /*margin: 15px 0;*/
    }

    .select2-container--default .select2-selection--single {
        border: 1px solid #ced4da;
        padding: .46875rem .75rem;
        height: calc(2.25rem + 2px);
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b{
        top:70%;
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
                        <div class="col-md-12 mail-box" id="scrollable">
                            @if(count($messages ?? []) > 0)
                                @foreach($messages as $key=>$message)
                                    <div class="time-center">{{ $message->message_date }}</div>
                                    <div class="msg-container @if($message->author == 1) darker @endif">
                                        {{--<iframe srcdoc="{!! htmlspecialchars($message->message) !!}"></iframe>--}}
                                        <div class="mb-3">
                                        {!! nl2br(html_entity_decode($message->message)) !!}
                                        </div>
                                        @if(isset($message->attachments))
                                            @foreach($message->attachments as $attachment)
                                                @php
                                                    $imageObj = $attachment->imageObj;
                                                    $imageName = $imageObj->name;
                                                    $imageUrl = asset('storage/'.$imageObj->location.$imageObj->name);
                                                @endphp
                                                <div class="btn-group attachmentGroup" role="group">
                                                    <button id="btnGroupDrop3" type="button" class="lable-image dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-paperclip"></i> {{ $imageName }}</button>
                                                    <div class="dropdown-menu" aria-labelledby="btnGroupDrop3">
                                                        <a class="dropdown-item" target="_blank" href="{{ $imageUrl }}">Download</a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
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
                                        <i class="fa fa-reply"></i> Compose Message
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link @if($ticketDetail->notes !='' && $ticketDetail->notes != NULL) text-red @endif" id="internalNotes-tab" data-toggle="pill" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                                        @if($ticketDetail->notes !='' && $ticketDetail->notes != NULL) <i class="fa fa-exclamation-triangle"></i> @else <i class="fa fa-sticky-note"></i> @endif Internal Notes </a>
                                </li>
                            </ul>

                            <div class="tab-content" id="custom-tabs-one-tabContent">
                                <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="composeMessage-tab">
                                    <div class="row pt-3">
                                        <div class="col-md-4">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label for="message_template">Template</label>
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
                                                    <input type="hidden" id="template_id" value="@if(isset($template['template_id']) && $template['template_id'] != '') {{ $template['template_id'] }} @endif">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="file" class="btn btn-default" name="attach_file" id="attach_file">
                                            <input type="hidden" id="image_url" value="@if(isset($template['imageUrl']) && $template['imageUrl'] != '') {{ $template['imageUrl'] }} @endif">
                                            <input type="hidden" id="image_storage_path" value="@if(isset($template['storagePath']) && $template['storagePath'] != '') {{ $template['storagePath'] }} @endif">
                                            <div class="btn-group lableImageGroup @if(!isset($template['imageName'])) hide @endif" role="group">
                                                <button id="btnGroupDrop2" type="button" class="btn btn-default lable-image dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@if(isset($template['imageName']) && $template['imageName'] != '') {{ $template['imageName'] }} @endif</button>
                                                <div class="dropdown-menu" aria-labelledby="btnGroupDrop2">
                                                    <a class="dropdown-item download_image" href="javascript:void(0);">Download</a>
                                                    <a class="dropdown-item delete_images" href="javascript:void(0);">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row pt-3">
                                        <div class="col-md-12">
                                            <textarea class="form-control" rows="10" id="message_to_send" name="message_to_send">@if(isset($template['template_code']) && $template['template_code'] != ''){{ $template['template_code'] }}@endif
                                            </textarea>
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
                        <div class="col-md-9 border-r">
                            <div class="d-flex align-items-center" style="gap:10px;">
                                <div class="btn-group" role="group">
                                    <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-paper-plane"></i> Submit</button>
                                    <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                        <a class="dropdown-item submit_message" href="javascript:void(0);" data-close="1">Submit and Close Ticket</a>
                                        <a class="dropdown-item submit_message" href="javascript:void(0);" data-close="0">Submit and Leave Ticket Open</a>
                                    </div>
                                </div>
                                {{--<button type="button" class="btn btn-primary" name="submit_message" id="submit_message"><i class="fa fa-paper-plane"></i> Submit</button>--}}
                                @php
                                    $displayReturned = $displayReplace = $displayRefund = $displayShipped = $displayCanceled = $displayReturnLabel = 'hide';
                                    if(isset($action['canbeReturned']) && $action['canbeReturned'] > 0){
                                        $displayReturned = 'show';
                                    }

                                    if(isset($action['returnLable']) && $action['returnLable'] > 0){
                                        $displayReturnLabel = 'show';
                                    }

                                    if(isset($action['canbeReplace']) && $action['canbeReplace'] > 0){
                                        $displayReplace = 'show';
                                    }

                                    if(isset($action['canbeRefund']) && $action['canbeRefund'] > 0 && strtolower($store_type_name) != 'ebay'){
                                        $displayRefund = 'show';
                                    }

                                    if(isset($action['canbeShipped']) && $action['canbeShipped'] > 0){
                                        $displayShipped = 'show';
                                    }

                                    if((isset($action['canbeShipped']) && $action['canbeShipped'] > 0) || (isset($action['canbeRefund']) && $action['canbeRefund'] > 0 && strtolower($store_type_name) != 'ebay')){
                                        $displayCanceled = 'show';
                                    }

                                @endphp

                                @if(strtolower($store_type_name) == 'accessorywiz')
                                    <button type="button" class="btn btn-default {{ $displayReturned }}" name="rma" id="rma">RMA</button>
                                    <button type="button" class="btn btn-default {{ $displayReturnLabel }}" name="return_label" id="return_label">Return Label</button>
                                    <button type="button" class="btn btn-default {{ $displayReplace }}" name="replacement" id="replacement">Replacement</button>
                                    <button type="button" class="btn btn-default {{ $displayRefund }}" name="refund" id="refund">Refund</button>
                                    <button type="button" class="btn btn-default {{ $displayShipped }}" name="ship_order" id="ship_order">Ship Order</button>
                                    <button type="button" class="btn btn-default {{ $displayCanceled }}" name="cancel_order" id="cancel_order">Cancel Order</button>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
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
                            <label for="order_id">Order ID</label>
                        </div>
                        <div class="col-md-8 d-flex align-items-center">
                            <input type="text" class="form-control" name="order_id" id="order_id" value="{{ ($ticketDetail->order_id != '') ? $ticketDetail->order_id : '' }}">
                            <button type="button" class="btn btn-default" id="search_order"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                    <div class="row mt-3 g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="order_date">Order Date</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="order_date" id="order_date" readonly="">
                        </div>
                    </div>
                    <div class="row mt-3 g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="order_status">Order Status</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="order_status" id="order_status" readonly="">
                        </div>
                    </div>
                    <div class="row mt-3 g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="order_total">Order Total</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="order_total" id="order_total" readonly="">
                        </div>
                    </div>
                    <div class="row mt-3 g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="purchase_order_id">Purchase Order Id</label>
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
                    <div style="max-height: 150px; overflow: auto">
                        <table class="table table-bordered" id="ticket_history_table">
                            {!! $ticketHistory  !!}
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('messages.modals', ['ticketDetail' => $ticketDetail])
@stop
@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/jquery.datetimepicker.full.js') }}"></script>
    <script>

        var ticket_id = '{{ $ticketDetail->id }}';

        const scrollable = document.getElementById("scrollable");
        scrollable.scrollTop = scrollable.scrollHeight;

        window.onload = function() {
            // or use DOMContentLoaded
            scrollable.scrollTop = scrollable.scrollHeight;
        };


        $(document).ready(function () {

            $('#message_template').select2();
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
                        ticket_id: ticket_id,
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },

                    success: function(data) {
                        if(data.success){

                            $('#message_to_send').html(data.templateCode);
                            if(data.image_name){
                                $('.lableImageGroup').removeClass('hide');
                                $('#image_url').val(data.image_url);
                                $('#image_storage_path').val(data.imagesPath);
                                $('.lable-image').html(data.image_name);
                            }
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
                //if(notes != '') {
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
                            if (notes != '') {
                                $('#internalNotes-tab').addClass('text-red').find('i').removeClass('fa-sticky-note').addClass('fa-exclamation-triangle');
                            }else{
                                $('#internalNotes-tab').removeClass('text-red').find('i').removeClass('fa-exclamation-triangle').addClass('fa-sticky-note');
                            }
                        },
                        error: function (xhr) {
                        },
                        complete: function () {
                            $("#ajax-preloader").hide();
                        }
                    });
                //}
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

            $(document).on('click','.submit_message',function () {
                var template_id = $('#message_template').val();
                var message_to_send = $('#message_to_send').val();
                var customer_id = $('#customer_id').val();
                //var ticket_id = '{{ $ticketDetail->id }}';
                var ticket_status = $(this).data('close');
                if(message_to_send == ''){
                    alert('Please add text to send message');
                    return false;
                }

                var labelimage = '';
                var imagepath = '';
                var imagename = '';
                if($('#image_url').val() != ''){
                    var labelimage = $('#image_url').val();
                    var imagepath = $('#image_storage_path').val();
                    var imagename = $('.lable-image').html();
                }
                $.ajax({
                    type: "POST",
                    url: "{{ route('message.sendmessagetemplate') }}",
                    data: {
                        template_id: template_id,
                        message_to_send: message_to_send,
                        customer_id: customer_id,
                        ticket_id: ticket_id,
                        ticket_status: ticket_status,
                        labelimage: labelimage,
                        imagepath: imagepath,
                        imagename: imagename,
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },

                    success: function(data) {
                        if(data.success){

                            alert('Message send successfuly.');
                            location.reload();
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
                                //$('#makeRMAModal').find('.modal-body').html(response.html);
                                $('#returnLabelModal').find('.modal-body').html(response.html);
                                $('#date').datetimepicker({
                                    format:'Y-m-d H:i',
                                    /* minDate: 'now', */
                                    step:10,
                                    onSelectTime: function(dp,$input) {
                                        var sel_date = $input.val();
                                        $('#date').val(sel_date);
                                    }
                                });
                                //$('#makeRMAModal').modal('show');
                                $('#returnLabelModal').modal('show');
                                $('#return_label').removeClass('hide').addClass('show');

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
                var operationID = $(this).data('opid');
                $.ajax({
                    type: "POST",
                    url: "{{ route('message.getmessagetemplate') }}",
                    data: {
                        template_id: template_id,
                        customer_name: customer_name,
                        store_name: store_name,
                        rma_number: rma_number,
                        operation_id: operationID,
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },

                    success: function(data) {
                        if(data.success){

                            if($('#message_to_send').val() != '' && data.templateCode != ''){
                                if (confirm("Are you sure, you want to overwrite the template?") == true) {
                                    $('#template_id').val(data.template_id);
                                    $('#message_to_send').html(data.templateCode);
                                } else {

                                }
                            }else if(data.templateCode != ''){
                                $('#template_id').val(data.template_id);
                                $('#message_to_send').html(data.templateCode);
                            }

                            if(data.image_url){
                                $('.lableImageGroup').removeClass('hide');
                                $('#image_url').val(data.image_url);
                                $('#image_storage_path').val(data.imagesPath);
                                $('.lable-image').html(data.image_name);
                            }

                            //$('#message_to_send').html(data.templateCode);
                        }
                    },
                    error: function (xhr) {
                    },
                    complete: function () {
                        $("#ajax-preloader").hide();
                    }
                });
            });



            $(document).on('click','input[name="label_generation"]',function () {
                if($(this).val() > 0){
                    $('.ship-sec').removeClass('hide');
                }else{
                    $('.ship-sec').addClass('hide');
                }
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
                }
            });

            $(document).on('click','#ship_order',function () {
                var order_id = $('#order_id').val();
                if(order_id != '' && order_id != null){
                    $.ajax({
                        url: "{{ route('message.getshippingdetail') }}",
                        type: 'POST',
                        data: {
                            order_id: order_id,
                            ship_order: 1,
                            "_token": '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                $('#shipOrderModal').find('.modal-body').html(response.html);
                                $('#date').datetimepicker({
                                    format:'Y-m-d H:i',
                                    /* minDate: 'now', */
                                    step:10,
                                    onSelectTime: function(dp,$input) {
                                        var sel_date = $input.val();
                                        $('#date').val(sel_date);
                                    }
                                });
                                $('#shipOrderModal').modal('show');

                                getShippingCarrierService($('#carrier_name_ms'));
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }
            });

            $(document).on('click','#return_label',function () {
                var order_id = $('#order_id').val();
                if(order_id != '' && order_id != null){
                    $.ajax({
                        url: "{{ route('message.getshippingdetail') }}",
                        type: 'POST',
                        data: {
                            order_id: order_id,
                            return_lable: 1,
                            "_token": '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                $('#returnLabelModal').find('.modal-body').html(response.html);
                                if($('input[name="label_generation"]').val() > 0){
                                    $('.ship-sec').removeClass('hide');
                                }else{
                                    $('.ship-sec').addClass('hide');
                                }
                                $('#date').datetimepicker({
                                    format:'Y-m-d H:i',
                                    /* minDate: 'now', */
                                    step:10,
                                    onSelectTime: function(dp,$input) {
                                        var sel_date = $input.val();
                                        $('#date').val(sel_date);
                                    }
                                });
                                $('#returnLabelModal').modal('show');

                                getShippingCarrierService($('#carrier_name_ms'));
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }
            });

            $(document).on('click','#submit_return_label',function () {

                const form = document.querySelector("#frmReturnLabel");
                let formData = new FormData(form);
                formData.append('ticket_id', ticket_id);

                const carrier_package = formData.get("carrier_package");
                const label_generation = formData.get("label_generation");

                if (label_generation > 0){
                    if (!carrier_package || carrier_package.trim() === "") {
                        alert("Carrier package is required.");
                        return false;
                    }
                }

                $.ajax({
                    url: "{{ route('message.returnlabel') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {

                            if($('#message_to_send').val() != '' && response.templateCode != ''){
                                if (confirm("Are you sure, you want to overwrite the template?") == true) {
                                    $('#template_id').val(response.template_id);
                                    $('#message_to_send').html(response.templateCode);
                                }
                            }else if(data.templateCode != ''){
                                $('#template_id').val(response.template_id);
                                $('#message_to_send').html(response.templateCode);
                            }

                            if(response.image_url){
                                $('.lableImageGroup').removeClass('hide');
                                $('#image_url').val(response.image_url);
                                $('#image_storage_path').val(response.imagesPath);
                                $('.lable-image').html(response.image_name);
                            }

                            loadOrderDetail();

                        }
                        else {
                            alert(response.message);
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function (key, messages) {
                                alert(key + ': ' + messages[0]);
                            });
                        }
                        /*const response = xhr.responseJSON;
                        if ( response.errors) {
                            alert(response.message);
                        } else {
                            alert('Error: ' + response.message);
                        }*/
                    }
                });

            });

            $(document).on('click','.download_image',function () {
                let imgUrl = $('#image_url').val();
                window.open(imgUrl, '_blank');
            });

            $(document).on('click','.delete_images',function () {
                $('#image_url').val('');
                $('#image_storage_path').val('');
                $('.lableImageGroup').addClass('hide');
                $('.lable-image').html('');
            });



            $(document).on('click','#replacement',function () {
                var order_id = $('#order_id').val();
                if(order_id != '' && order_id != null){
                    $.ajax({
                        url: "{{ route('message.getshippingdetail') }}",
                        type: 'POST',
                        data: {
                            order_id: order_id,
                            replacement: 1,
                            "_token": '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                $('#replaceOrderModal').find('.modal-body').html(response.html);
                                $('#date').datetimepicker({
                                    format:'Y-m-d H:i',
                                    /* minDate: 'now', */
                                    step:10,
                                    onSelectTime: function(dp,$input) {
                                        var sel_date = $input.val();
                                        $('#date').val(sel_date);
                                    }
                                });
                                $('#replaceOrderModal').modal('show');

                                getShippingCarrierService($('#carrier_name_ms'));
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }
            });


            $(document).on('click','#submit_replace_order',function () {

                const form = document.querySelector("#frmReplaceOrder");
                let formData = new FormData(form);
                $.ajax({
                    url: "{{ route('message.replaceorder') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {

                            if($('#message_to_send').val() != '' && response.template_id != ''){
                                if (confirm("Are you sure, you want to overwrite the template?") == true) {
                                    $('#template_id').val(response.template_id);
                                    $('#message_to_send').html(response.template_code);
                                } else {

                                }
                            }else if(response.template_id != ''){
                                $('#template_id').val(response.template_id);
                                $('#message_to_send').html(response.template_code);
                            }

                            loadOrderDetail();
                            //alert('Order shipped successfully!!');
                            //location.reload();
                        }
                        else {
                            alert(response.message);
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function (key, messages) {
                                alert(key + ': ' + messages[0]);
                            });
                        }
                        /*const response = xhr.responseJSON;
                        if ( response.errors) {
                            alert(response.message);
                        } else {
                            alert('Error: ' + response.message);
                        }*/
                    }
                });
            });

            $(document).on('click','#submit_ship_order',function () {

                const form = document.querySelector("#frmShipOrder");
                let formData = new FormData(form);

                const trackingNumber = formData.get("tracking_number");
                const label_generation = formData.get("label_generation");

                if(label_generation.trim() != 1) {
                    $('#tracking_number').removeClass('error');
                    if (!trackingNumber || trackingNumber.trim() === "") {
                        $('#tracking_number').addClass('error');
                        alert("Tracking number is required.");
                        return;
                    }
                }

                const orderId = formData.get("shipping_order_id");
                $.ajax({
                    url: "{{ route('shipment.markshipped') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            alert('Order shipped successfully!!');
                            location.reload();
                        }
                        else {
                            alert(response.message);
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function (key, messages) {
                                alert(key + ': ' + messages[0]);
                            });
                        }
                        /*const response = xhr.responseJSON;
                        if ( response.errors) {
                            alert(response.message);
                        } else {
                            alert('Error: ' + response.message);
                        }*/
                    }
                });
            });

            $(document).on('click','#cancel_order',function () {
                var order_id = $('#order_id').val();
                if(order_id != '' && order_id != null){
                    $.ajax({
                        url: "{{ route('message.getcancelorderdetail') }}",
                        type: 'POST',
                        data: {
                            order_id: order_id,
                            "_token": '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                $('#cancelOrderModal').find('.modal-body').html(response.html);
                                $('#canceldate').datetimepicker({
                                    format:'Y-m-d H:i',
                                    /* minDate: 'now', */
                                    step:10,
                                    onSelectTime: function(dp,$input) {
                                        var sel_date = $input.val();
                                        $('#canceldate').val(sel_date);
                                    }
                                });
                                $('#cancelOrderModal').modal('show');
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }else{
                    alert('No order');
                }
            });

            $(document).on('click','#cancelOrder',function () {

                const form = document.querySelector("#frmCancelOrder");
                let formData = new FormData(form);
                $.ajax({
                    url: "{{ route('shipment.cancelorder') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        alert(response.message);
                        if (response.success) {
                            location.reload();
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });
            });

            $(document).on('click','#refund_order',function () {

                const form = document.querySelector("#frmRefundOrder");
                let formData = new FormData(form);

                var shipping_cost = formData.get('return_charge');
                if(shipping_cost != null && shipping_cost > 0){
                    if($('#accept_return_charge').is(':checked')){
                        if($('#return_charge_feedback').val() == ''){
                            alert('Please specify the reason for return charge ('+shipping_cost+') rejection.');
                            return false;
                        }
                    }
                }
                $.ajax({
                    url: "{{ route('message.refundorder') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        alert(response.message);
                        if (response.success) {

                            $('#orderRefundModal').modal('hide');
                            loadOrderDetail();
                            if(response.template_id != ''){
                                $('#template_id').val(response.template_id);
                                $('#message_to_send').html(response.template_code);
                            }
                            //location.reload();
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });
            });

            $('#attach_file').on('change', function() {
                let formData = new FormData();
                formData.append('attach_file', this.files[0]);
                formData.append('ticket_id', ticket_id);

                $.ajax({
                    url: '{{ route("message.imageupload") }}',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        //alert(response.success);
                        if(response.image_url){
                            $('.lableImageGroup').removeClass('hide');
                            $('#image_url').val(response.image_url);
                            $('#image_storage_path').val(response.imagesPath);
                            $('.lable-image').html(response.image_name);
                        }
                    },
                    error: function(xhr) {
                        alert('Upload failed');
                    }
                });
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
            var storeType = '{{ $store_type_name }}';
            if(order_id != '') {
                $.ajax({
                    type: "POST",
                    url: "{{ route('message.searchorder') }}",
                    data: {
                        order_id: order_id,
                        ticket_id: ticket_id,
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

                            if(data.action){

                                if(data.action.canbeReturned && data.action.canbeReturned > 0){
                                    $('#rma').removeClass('hide').addClass('show');
                                }else{
                                    $('#rma').removeClass('show').addClass('hide');
                                }

                                if(data.action.canbeReplace && data.action.canbeReplace > 0){
                                    $('#replacement').removeClass('hide').addClass('show');
                                }else{
                                    $('#replacement').removeClass('show').addClass('hide');
                                }

                                if(data.action.canbeRefund && data.action.canbeRefund > 0 && storeType != 'eBay'){
                                    $('#refund').removeClass('hide').addClass('show');
                                }else{
                                    $('#refund').removeClass('show').addClass('hide');
                                }

                                if(data.action.canbeShipped && data.action.canbeShipped > 0){
                                    $('#ship_order').removeClass('hide').addClass('show');
                                }else{
                                    $('#ship_order').removeClass('show').addClass('hide');
                                }

                                if((data.action.canbeShipped && data.action.canbeShipped > 0) || (data.action.canbeRefund && data.action.canbeRefund > 0 && storeType != 'eBay')){
                                    $('#cancel_order').removeClass('hide').addClass('show');
                                }else{
                                    $('#cancel_order').removeClass('show').addClass('hide');
                                }


                            }

                            if(data.action.returnLable){
                                $('#return_label').removeClass('hide').addClass('show');
                            }else{
                                $('#return_label').removeClass('show').addClass('hide');
                            }
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

        function loadCustomerDetail(){
            let customer_id_name = $('#customer').val();
            if(order_id != '') {
                $.ajax({
                    type: "POST",
                    url: "{{ route('message.searchcustomer') }}",
                    data: {
                        customer_id_name: customer_id_name
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
        }
    </script>
@stop
