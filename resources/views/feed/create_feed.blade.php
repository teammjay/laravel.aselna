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
                                    <option value="">- All Store -</option>
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

                            <div class="border p-2" style="width: 20%;">
                                <label>Included Ratings</label>
                                <div class="row">
                                    <div class="col-md-12 pro-rating multi-select-filter">
                                        <select class="form-control list-filter" name="included_ratings[]" id="included_ratings_filter" multiple>
                                            @if(count($deviceRatings ?? []) > 0)
                                                @foreach($deviceRatings as $deviceR)
                                                    <option value="{{ $deviceR->id }}">{{ $deviceR->value }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="border p-2" style="width: 20%;">
                                <label>Excluded Ratings</label>
                                <div class="row">
                                    <div class="col-md-12 pro-rating multi-select-filter">
                                        <select class="form-control list-filter" name="excluded_ratings[]" id="excluded_ratings_filter" multiple>
                                            @if(count($deviceRatings ?? []) > 0)
                                                @foreach($deviceRatings as $deviceR)
                                                    <option value="{{ $deviceR->id }}">{{ $deviceR->value }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
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
                        <div class="d-flex" style="gap:10px;">
                            <div style="width: 20%;">
                                <button type="button" id="generateFeed" class="btn btn-primary w-100">Generate Feed</button>
                            </div>
                            <div style="width: 30%;">
                                <div class="input-group">
                                    <input type="text" id="config_name" class="form-control" placeholder="Configuration Name">
                                    <div class="input-group-append">
                                        <button type="button" id="saveConfiguration" class="btn btn-success">Save Configuration</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="col-md-12 mt-5">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Saved Configurations</h3>
                            <div style="width: 250px;">
                                <select id="table_store_filter" class="form-control select2">
                                    <option value="">All Stores</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="configTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Store</th>
                                        <th>Feed Type</th>
                                        <th>File Type</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="config-list">
                                    <tr>
                                        <td colspan="6" class="text-center">Loading configurations...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

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

            $('#listing_store').on('change', function() {
                let storeId = $(this).val();
                $('#table_store_filter').val(storeId).trigger('change.select2');
            });

            $('#table_store_filter').select2().on('change', function() {
                loadConfigurations();
            });

            $(document).on('click', '.generate-config', function() {
                let id = $(this).data('id');
                if (!confirm('Are you sure you want to generate feed directly from this configuration?')) return;

                $.ajax({
                    url: "/configuration/generate/" + id,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            });

            $(document).on('click', '#saveConfiguration', function() {
                let name = $('#config_name').val();
                let store_id = $('#listing_store').val();
                if (name == '') {
                    alert('Please enter a configuration name');
                    return;
                }
                if (store_id == '') {
                    alert('Please select a store');
                    return;
                }

                const form = document.querySelector("#frmproductfilter");
                let formData = new FormData(form);
                formData.append('name', name);

                $.ajax({
                    url: "{{ route('feed.saveconfiguration') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            loadConfigurations();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            });

            $(document).on('click', '.load-config', function() {
                let config = $(this).data('config');
                $('#config_name').val(config.name);

                // Set store and trigger change for Select2
                if (config.store_id) {
                    $('#listing_store').val(config.store_id).trigger('change');
                }

                $('input[name="feed_type"][value="' + config.feed_type + '"]').prop('checked', true);
                $('#file_type').val(config.file_type);

                // Clear existing lists
                $('#pro_code_list, #device_series_list, #device_list').html('');

                // Load dynamic filters
                if (config.filters) {
                    if (config.filters.product_code) {
                        config.filters.product_code.forEach(val => {
                            $('#pro_code_list').append('<div class="border bg-blue d-flex justify-content-between p-1 mt-1"><input type="hidden" name="product_code[]" value="' + val + '">' + val + '<i class="fa fa-window-close float-right mt-1"></i></div>');
                        });
                    }
                    if (config.filters.device_series) {
                        config.filters.device_series.forEach(val => {
                            $('#device_series_list').append('<div class="border bg-blue d-flex justify-content-between p-1 mt-1"><input type="hidden" name="device_series[]" value="' + val + '">' + val + '<i class="fa fa-window-close float-right mt-1"></i></div>');
                        });
                    }
                    if (config.filters.device) {
                        config.filters.device.forEach(val => {
                            $('#device_list').append('<div class="border bg-blue d-flex justify-content-between p-1 mt-1"><input type="hidden" name="device[]" value="' + val + '">' + val + ' <i class="fa fa-window-close float-right mt-1"></i></div>');
                        });
                    }

                    // For multiselects, we need to set values and trigger refresh
                    if (config.filters.included_ratings) {
                        // $('#included_ratings_filter').val(config.filters.included_ratings);
                        // We need a way to refresh gs_multiselect, but it doesn't seem to have a standard refresh.
                        // However, we can re-initialize or find the checkboxes.
                        // Given the plugin, let's try to manually check the checkboxes in the dropdown if possible,
                        // or re-trigger the change event if the plugin supports it.
                        // $('#included_ratings_filter').trigger('change');
                        refreshGsMultiselect('included_ratings_filter', config.filters.included_ratings);
                    }
                    if (config.filters.excluded_ratings) {
                        // $('#excluded_ratings_filter').val(config.filters.excluded_ratings);
                        // $('#excluded_ratings_filter').trigger('change');
                        refreshGsMultiselect('excluded_ratings_filter', config.filters.excluded_ratings);
                    }
                    // if (config.filters.manufacturer) {
                    //     $('#manufacturer_filter').val(config.filters.manufacturer);
                    //     $('#manufacturer_filter').trigger('change');
                    //     refreshGsMultiselect('manufacturer_filter', config.filters.manufacturer);
                    // }
                    // if (config.filters.product_type) {
                    //     $('#product_type_filter').val(config.filters.product_type);
                    //     $('#product_type_filter').trigger('change');
                    //     refreshGsMultiselect('product_type_filter', config.filters.product_type);
                    // }
                    // if (config.filters.brand) {
                    //     $('#brand_filter').val(config.filters.brand);
                    //     $('#brand_filter').trigger('change');
                    //     refreshGsMultiselect('brand_filter', config.filters.brand);
                    // }
                }
            });

            $(document).on('click', '.delete-config', function() {
                if (!confirm('Are you sure you want to delete this configuration?')) return;
                let id = $(this).data('id');
                $.ajax({
                    url: "/feed/configuration/" + id,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            loadConfigurations();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            });

            function loadConfigurations() {
                let store_id = $('#table_store_filter').val();

                $.ajax({
                    url: "{{ route('feed.getconfigurations') }}",
                    type: 'GET',
                    data: { store_id: store_id },
                    success: function(response) {
                        if (response.success) {
                            let html = '';
                            if (response.data.length > 0) {
                                response.data.forEach(config => {
                                    html += '<tr>';
                                    html += '<td>' + config.name + '</td>';
                                    let storeInfo = 'Unknown';
                                    if (config.store) {
                                        storeInfo = config.store.name;
                                        if (config.store.store_type) {
                                            storeInfo += ' - ' + config.store.store_type.name;
                                        }
                                    }
                                    html += '<td>' + storeInfo + '</td>';
                                    html += '<td>' + config.feed_type + '</td>';
                                    html += '<td>' + config.file_type + '</td>';
                                    html += '<td>' + config.created_at + '</td>';
                                    html += '<td>';
                                    html += '<button class="btn btn-sm btn-info load-config mr-1" data-config=\'' + JSON.stringify(config) + '\'>Load</button>';
                                    html += '<button class="btn btn-sm btn-primary generate-config mr-1" data-id="' + config.id + '">Generate</button>';
                                    html += '<button class="btn btn-sm btn-danger delete-config" data-id="' + config.id + '">Delete</button>';
                                    html += '</td>';
                                    html += '</tr>';
                                });
                            } else {
                                html = '<tr><td colspan="6" class="text-center">No saved configurations found</td></tr>';
                            }
                            $('#config-list').html(html);
                        }
                    }
                });
            }
            loadConfigurations();

            function refreshGsMultiselect(id, values) {
                let $originalSelect = $('#' + id);
                let $table = $originalSelect.closest('table');
                if ($table.length === 0) return;

                let $fromSel = $table.find('select.default-drop-down');
                let $mc = $table.find('td').eq(1); // middle cell
                let $fb = $mc.find('.btn-val-add');
                let $btr_all = $mc.find('.btn-all-val-remove'); // the << button

                // 1. Clear current selection: click the "<<" button
                $btr_all.click();

                // The plugin uses a 300ms timeout for the "<<" button.
                setTimeout(function() {
                    // 2. Select the desired values in fromSel
                    $fromSel.find('option').each(function() {
                        if (values.indexOf($(this).val()) !== -1) {
                            $(this).prop('selected', true);
                        } else {
                            $(this).prop('selected', false);
                        }
                    });

                    // 3. Click the ">" button to move them to toSel
                    $fb.click();
                }, 500);
            }

            $("#manufacturer_filter, #product_type_filter, #brand_filter, #included_ratings_filter, #excluded_ratings_filter").gs_multiselect();

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
