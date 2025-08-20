@extends('adminlte::page')

@section('title', 'Listing')
@section('adminlte_css_pre')
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <link href="{{ asset('css/jquery.multiselect.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /*.select2-container--default .select2-dropdown .select2-search__field:focus, .select2-container--default .select2-search--inline .select2-search__field:focus {
            outline: 0;
            border: none;
        }
        .select2-container{width:100% !important;}
        .select2-container .select2-selection--single{
            height: calc(2.25rem + 2px);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: auto;
            position: absolute;
            top: 50%;
            right: 1px;
            width: 20px;
            transform: translateY(-50%);
        }*/
        #product_type_filter option, #manufacturer_filter option, select[multiple] option {
            border-bottom: 1px solid #eaeaea;
        }
        #frmproductfilter .d-flex > .border {
            width: 14%;
        }
        .error{
            border-color: #dc3545 !important;
        }
        .gap-5{gap:5px;}
        .proCheck{
            visibility: hidden;
        }
        .selectproRow:hover:not(.bg-blue) td {
            background-color: #ddf1ff;
            color:#000;
            cursor:pointer;
        }
        .bd-example {
            background: #fff;
            padding: 2rem !important;
            width: 30% !important;
            text-align: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 99;
        }
        .pro-list-title{width: 85% !important;}
        #modelTable tr td, #modelTable tr th{
            padding: 5px 10px;
        }
        .sticky-bottom{
            position: sticky !important;
            bottom: 0;
            background: #fff;
            z-index: 99;
        }
        /*.ui-autocomplete{z-index: 99999; max-height: 250px; overflow: auto;}*/
        #frmproductfilter select.form-control[multiple]{height: 100%;}
        table th, table td, .column-no-whitespace{
            white-space: nowrap;
            font-size: 14px;
        }
        .form-group span,  .border label, .form-control, .d-flex span, .btn{
            font-size: 14px !important;
        }
        .nav-tabs .nav-item .nav-link, .nav-tabs .nav-link.active{font-size: 14px;}
        .ui-widget.ui-widget-content {
            border: 1px solid #c5c5c5;
            max-height: 200px;
            overflow-y: scroll;
        }
        #frmproductfilter .select2-container{
            width: 100% !important;
            margin: 15px 0;
        }
        .select2-container--default .select2-dropdown{
            width:320px !important;
        }
        span.titleLength {
            border: 1px solid #eaeaea;
            padding: 2px 5px;
        }
        span.titleLength.success {
            color: green;
            border-color: green;
        }
        span.titleLength.error {
            color: red;
            border-color: red;
        }
        tr.bg-blue {
            background-color: #9dccff !important;
        }
        .pro-rating.multi-select-filter Select, .pro-popular.multi-select-filter Select{
            width: 60px !important;
        }
        .select2-dropdown {
            z-index: 9999;
        }
        .btn-close {
            box-sizing: content-box;
            width: 1em;
            height: 1em;
            padding: .25em .25em;
            color: #000;
            background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
            border: 0;
            border-radius: .25rem;
            opacity: .5;
        }
        .modal-header .btn-close {
            padding: .5rem .5rem;
            margin: -.5rem -.5rem -.5rem auto;
        }
        #productListingTable .sticky-top{z-index: 99;}
        .total_sel_product, .total_product{
            font-size: 16px !important;
        }
        .total_sel_product b, .total_product b{ font-size: 18px;}
        .multi-select-filter input[type="button"] {
            padding: 0px 4px;
            border: 2px solid #ccc;
            background: #f1f1f1;
            font-size: 16px;
            line-height: 1;
            margin-bottom: 2px;
            width: 30px;
            height: 30px;
        }
    </style>
@stop
@section('content_header')
    <h1>Create Feed</h1>
@stop

@section('content')

    @csrf
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">


            <div class="row mt-3 mb-3">
                <form id="frmproductfilter">
                    <div class="col-md-12">
                        <div class="d-flex" style="gap:5px;">
                            <div class="border p-2" style="width: 20%;">
                                <label>Store</label>
                                <select class="form-control mt-3 mb-3" name="store_id" id="listing_store">
                                    <option value="">--</option>
                                    @if(count($stores ?? []) > 0)
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}" data-storetype="{{ (!empty((array)$store->storeType)) ? $store->storeType->name : '' }}">{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                                        @endforeach
                                    @endif
                                </select>

                            </div>
                            <div class="border p-2" style="width: 20%;">
                                <label>Feed Type</label>
                                <div class="form-group position-relative">
                                    <div>
                                        <input type="radio" name="feed_type" value="Google"> Google
                                    </div>
                                    <div>
                                        <input type="radio" name="feed_type" value="Microsoft"> Microsoft
                                    </div>
                                </div>
                            </div>
                            <div class="border p-2" style="width: 20%;">
                                <label>File Type</label>
                                <div class="form-group d-flex position-relative">
                                    <select class="form-control" name="file_type" id="file_type">
                                        <option value="csv">CSV</option>
                                        <option value="excel">Excel</option>
                                    </select>
                                </div>
                            </div>
                            <div class="border p-2" style="width: 20%;">
                                <label>Product Code Filter</label>
                                <div class="form-group d-flex position-relative">
                                    <input type="text" class="form-control" id="product_code_filter" placeholder="Search...."><i class="fa fa-search position-absolute" style="top: 50%;right: 5px;transform: translateY(-50%);"></i>
                                </div>
                                <div id="pro_code_list">

                                </div>
                            </div>
                            <div class="border p-2" style="width: 20%;">
                                <label>Device Series</label>
                                <div class="form-group d-flex position-relative">
                                    <input type="text" class="form-control" id="device_series_filter" placeholder="Search...."><i class="fa fa-search position-absolute" style="top: 50%;right: 5px;transform: translateY(-50%);"></i>
                                </div>
                                <div id="device_series_list">

                                </div>
                            </div>
                            <div class="border p-2 " style="width: 20%;">
                                <label>Device Filter</label>
                                <div class="form-group d-flex position-relative">
                                    <input type="text" class="form-control" id="device_filter" placeholder="Search...."><i class="fa fa-search position-absolute" style="top: 50%;right: 5px;transform: translateY(-50%);"></i>
                                </div>
                                <div id="device_list">

                                </div>
                            </div>

                            {{--<div class="border p-2" style="width: 17%;">
                                <label>Manufacturer Filter</label>
                                <div class="row" id="manufacture" style="height: 87%;">
                                    <div class="col-md-12 manufacture multi-select-filter">
                                        <select class="form-control list-filter" name="manufacturer[]" id="manufacturer_filter" multiple>
                                            @if(count($manufacturers ?? []) > 0)
                                                @foreach($manufacturers as $mf)
                                                    <option value="{{ $mf->id }}">{{ $mf->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="border p-2" style="width: 17%;">
                                <label>Product Type Filter</label>
                                <div class="row" style="height: 87%;">
                                    <div class="col-md-12 prod-type multi-select-filter">
                                        <select class="form-control list-filter" name="product_type[]" id="product_type_filter" multiple>
                                            @if(count($productTypes ?? []) > 0)
                                                @foreach($productTypes as $pt)
                                                    <option value="{{ $pt->id }}">{{ $pt->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="border p-2" id="proSubType" style="display: none; width: 17%;">
                                <label>Product Sub Type Filter</label>
                                <div class="row" id="proSubTypeFilter">
                                    <div class="col-md-12 prod-sub-type multi-select-filter">
                                        <select class="form-control list-filter" name="product_sub_type[]" id="product_sub_type_filter" multiple>

                                        </select>
                                    </div>
                                </div>

                                <label>Interface Filter</label>
                                <div class="row" id="interfaceFilter">
                                    <div class="col-md-12 interface-filter multi-select-filter">
                                        <select class="form-control list-filter" name="interface[]" id="interface_filter" multiple>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="border p-2" id="proSubSubType" style="display: none; width: 17%;">
                                <label>Product Sub Sub Type Filter</label>
                                <div class="row">
                                    <div class="col-md-12 prod-sub-sub-type multi-select-filter">
                                        <select class="form-control list-filter" name="product_sub_sub_type[]" id="product_sub_sub_type_filter" multiple>

                                        </select>
                                    </div>
                                </div>
                            </div>--}}
                        </div>
                    </div>
                    <div class="col-md-12 pt-3">
                        <div style="width: 20%;">
                            <button type="button" id="generateFeed" class="btn btn-primary w-100">Generate Feed</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <div id="ajax-gallery" style="display: none;"></div>
    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertModalLabel"></h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"></div>
            </div>
        </div>
    </div>
@stop

@section('adminlte_js')
    <!-- Lightbox2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/jquery.multiselect.js') }}"></script>

    <script>
        let product_model = {};
        let product_models = {};
        let productDetail = {};
        let selected_device = {};
        function addToKey(key, value) {
            if (!selected_device[key]) {
                selected_device[key] = [];
            }
            selected_device[key].push(value);
        }
        let selected_carrier = {};
        function addCarrier(key, value) {
            if (!selected_carrier[key]) {
                selected_carrier[key] = [];
            }
            selected_carrier[key].push(value);
        }
        $(document).ready(function() {


            $('#productsTable').DataTable();
            $('#productListingTable').DataTable({});
            $('#listing_store').select2();

            $('#listing_store').on('select2:open', function () {
                // Wait for dropdown to render before focusing the search field
                setTimeout(function () {
                    document.querySelector('.select2-container--open .select2-search__field').focus();
                }, 0);
            });

            $(document).on('click', '#generateFeed', function() {
                if($('#listing_store').val() == ''){
                    alert('Please select store first.');
                    $('#listing_store').addClass('error');
                    return false;
                }else if(!$('input[name="feed_type"]').is(':checked')){
                    alert('Please select feed type');
                    return false;
                }else {
                    $('#listing_store').removeClass('error');
                    generateFeed();
                }
            });

            $("#manufacturer_filter, #product_type_filter, #brand_filter").gs_multiselect();

            let cnt = cnt2 = cnt3 = 0;
            $(document).on('click','.btn-val-add',function(){
                let ele = $(this);
                setTimeout(function () {

                    if (ele.parents('.prod-type').length) {

                        let product_types = [];
                        ele.parents('.prod-type').find('input[name="product_type[]"]').each(function () {
                            product_types.push($(this).val());
                        });
                        $.ajax({
                            url: "{{ route('listing.getproductsubtype') }}",
                            type: 'POST',
                            data: {
                                product_types: product_types
                            },
                            /* processData: false,
                             contentType: false,*/
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#proSubType').show();
                                    //console.log(0);
                                    if ($('#proSubTypeFilter').find('.default-drop-down').length <= 0) {
                                        $('#product_sub_type_filter').html(response.option);
                                        $('#product_sub_type_filter').gs_multiselect();
                                    } else {
                                        $('#product_sub_type_filter').html('');
                                        $('#proSubTypeFilter').find('.default-drop-down').html(response.option);
                                    }

                                    if ($('#interfaceFilter').find('.default-drop-down').length <= 0) {
                                        $('#interface_filter').html(response.ioption);
                                        $('#interface_filter').gs_multiselect();
                                    } else {
                                        $('#interface_filter').html('');
                                        $('#interfaceFilter').find('.default-drop-down').html(response.ioption);
                                    }

                                } else {
                                    clearMultiSelect('product_sub_type','prod-sub-type');
                                    //clearMultiSelect('product_sub_sub_type','prod-sub-sub-type');
                                    clearMultiSelect('interface','interface-filter');
                                    $('#proSubType').hide();
                                    $('#proSubSubType').hide();
                                }

                            },
                            error: function (xhr) {
                                /*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*/
                            }
                        });
                    }

                },300);
            });

            $(document).on('change','.list--filter',function(e){
                e.preventDefault();
                let ele = $(this);
                //console.log(ele);
                setTimeout(function () {

                    if (ele.parents('.prod-type').length) {

                        let product_types = [];
                        ele.parents('.prod-type').find('input[name="product_type[]"]').each(function () {
                            product_types.push($(this).val());
                        });
                        $.ajax({
                            url: "{{ route('listing.getproductsubtype') }}",
                            type: 'POST',
                            data: {
                                product_types: product_types
                            },
                            /* processData: false,
                             contentType: false,*/
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#proSubType').show();
                                    //console.log(0);
                                    if ($('#proSubTypeFilter').find('.default-drop-down').length <= 0) {
                                        $('#product_sub_type_filter').html(response.option);
                                        $('#product_sub_type_filter').gs_multiselect();
                                    } else {
                                        $('#product_sub_type_filter').html('');
                                        $('#proSubTypeFilter').find('.default-drop-down').html(response.option);
                                    }

                                    if ($('#interfaceFilter').find('.default-drop-down').length <= 0) {
                                        $('#interface_filter').html(response.ioption);
                                        $('#interface_filter').gs_multiselect();
                                    } else {
                                        $('#interface_filter').html('');
                                        $('#interfaceFilter').find('.default-drop-down').html(response.ioption);
                                    }

                                } else {
                                    clearMultiSelect('product_sub_type','prod-sub-type');
                                    //clearMultiSelect('product_sub_sub_type','prod-sub-sub-type');
                                    clearMultiSelect('interface','interface-filter');
                                    $('#proSubType').hide();
                                    $('#proSubSubType').hide();
                                }
                            },
                            error: function (xhr) {
                                /*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*/
                            }
                        });
                    }

                },300);
            });

            $(document).on('click','.btn-val-remove',function(){
                let ele = $(this);
                setTimeout(function () {
                    if (ele.parents('.prod-type').length) {

                        let product_types = [];
                        ele.parents('.prod-type').find('input[name="product_type[]"]').each(function () {
                            product_types.push($(this).val());
                        });
                        $.ajax({
                            url: "{{ route('listing.getproductsubtype') }}",
                            type: 'POST',
                            data: {
                                product_types: product_types
                            },
                            /* processData: false,
                             contentType: false,*/
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#proSubType').show();
                                    if ($('#proSubTypeFilter').find('.default-drop-down').length <= 0) {
                                        $('#product_sub_type_filter').html(response.option);
                                        $('#product_sub_type_filter').gs_multiselect();

                                    } else {
                                        $('#product_sub_type_filter').html('');
                                        $('#proSubTypeFilter').find('.default-drop-down').html(response.option);
                                    }

                                    if ($('#interfaceFilter').find('.default-drop-down').length <= 0) {
                                        $('#interface_filter').html(response.ioption);
                                        $('#interface_filter').gs_multiselect();
                                    } else {
                                        $('#interface_filter').html('');
                                        $('#interfaceFilter').find('.default-drop-down').html(response.ioption);
                                    }

                                } else {
                                    clearMultiSelect('product_sub_type','prod-sub-type');
                                    clearMultiSelect('product_sub_sub_type','prod-sub-sub-type');
                                    clearMultiSelect('interface','interface-filter');
                                    $('#proSubType').hide();
                                    $('#proSubSubType').hide();
                                }

                            },
                            error: function (xhr) {
                                /*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*/
                            }
                        });
                    }

                },300);

            });

            $('#product_code_filter').autocomplete({

                source: function (request, response) {

                    let product_code = [];
                    $('input[name="product_code[]"]').each(function () {
                        product_code.push($(this).val())
                    });

                    $.ajax({
                        type: "POST",
                        url: "{{ route('listing.search') }}",
                        data: {
                            term: request.term,
                            type: "product_code",
                            product_code: product_code,
                            store_id: $('#listing_store').val(),
                        },
                        //processData: false,
                        //contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },

                        success: function(data) {
                            actemp = data.details;
                            response(data.display);
                        },
                        delay: 100
                    });
                },
                minLength:1,
                select : function(event, ui){
                    $('#product_code_filter').val('');
                    $('#pro_code_list').append('<div class="border bg-blue d-flex justify-content-between p-1 mt-1"><input type="hidden" name="product_code[]" value="'+ui.item.value+'">'+ui.item.value+'<i class="fa fa-window-close float-right mt-1"></i></div>');
                    return false;
                }
            }).focus(function () {
                //$('#product_code_filter').keydown();
            });

            $('#device_series_filter').autocomplete({

                source: function (request, response) {
                    let device_series = [];
                    $('input[name="device_series[]"]').each(function () {
                        device_series.push($(this).val())
                    });
                    $.ajax({
                        type: "POST",
                        url: "{{ route('listing.search') }}",
                        data: {
                            term: request.term,
                            type: "device_series",
                            device_series: device_series,
                        },
                        //processData: false,
                        //contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {
                            actemp = data.details;
                            response(data.display);
                        },
                        delay: 100
                    });
                },
                minLength: 1,
                select : function(event, ui){
                    $('#device_series_filter').val('');
                    $('#device_series_list').append('<div class="border bg-blue d-flex justify-content-between p-1 mt-1"><input type="hidden" name="device_series[]" value="'+ui.item.value+'">'+ui.item.label+'<i class="fa fa-window-close float-right mt-1"></i></div>');

                    return false;
                }
            }).focus(function () {
                //$('#device_series_filter').keydown();
            });

            $('#device_filter').autocomplete({

                source: function (request, response) {
                    let device = [];
                    $('input[name="device[]"]').each(function () {
                        device.push($(this).val())
                    });
                    $.ajax({
                        type: "POST",
                        url: "{{ route('listing.search') }}",
                        data: {
                            term: request.term,
                            type: "device",
                            device: device
                        },
                        //processData: false,
                        //contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {
                            actemp = data.details;
                            response(data.display);
                        },
                        delay: 100
                    });
                },
                minLength: 1,
                select : function(event, ui){
                    $('#device_filter').val('');
                    $('#device_list').append('<div class="border bg-blue d-flex justify-content-between p-1 mt-1"><input type="hidden" name="device[]" value="'+ui.item.value+'">'+ui.item.label+' <i class="fa fa-window-close float-right mt-1"></i></div>');
                    return false;
                }
            }).focus(function () {
                //$('#device_filter').keydown();
            });


            $(document).on('click','.fa-window-close',function () {
                $(this).parent('.border').remove();
            });
        });



        function getStoreManufacturer(element){
            $.ajax({
                url: "{{ route('listing.getstoremanufacturer') }}",
                type: 'POST',
                data: {
                    store_id: $('#listing_store').val(),
                    "_token": '{{ csrf_token() }}'
                },
                success: function (response) {
                    // If success, remove the user row from the table
                    if (response.success) {
                        $('#use-store-brand').html(response.option);
                    }
                },
                error: function (xhr) {
                    // Handle error response
                    alert('Error: ' + xhr.responseJSON.error);
                }
            });
        }

        function generateFeed() {
            if($('#listing_store').val() === '') return;
            const form = document.querySelector("#frmproductfilter");
            let formData = new FormData(form);
            $("#ajax-preloader").show();
            if ($.fn.DataTable.isDataTable('#productsTable')) {
                $('#productsTable').DataTable().clear().destroy();
            }
            $('.total_sel_product').html('(Selected <b>0</b> item)');
            $.ajax({
                url: "{{ route('feed.generatefeed') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        window.location.href = '{{ route('feed.list') }}';
                    }
                },
                error: function (xhr) {
                    /*if (xhr.responseJSON?.error) {
                        $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                    }*/
                },
                complete: function () {
                    $("#ajax-preloader").hide();
                }
            });
        }

        function clearMultiSelect(elementClass,parentClass) {
            var $select = $('<select>', {
                class: 'form-control',
                name: elementClass + '[]',
                id: elementClass + '_filter',
                multiple: 'multiple'
            });

            $('.'+parentClass).html($select);
        }
    </script>
@stop
