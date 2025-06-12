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
    <h1>Create Listing</h1>
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
            <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="select-product-tab" data-toggle="tab" data-target="#tab1" role="tab" aria-controls="tab1" aria-selected="true">
                        Select Product(s)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="product-update-tab" data-toggle="tab" data-target="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                        Product Update
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="select-device-tab" data-toggle="tab" data-target="#tab3" role="tab" aria-controls="tab3" aria-selected="false">
                        Select Device(s)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="listing-tab" data-toggle="tab" data-target="#tab4" role="tab" aria-controls="tab4" aria-selected="false">
                        Listing
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="summary-tab" data-toggle="tab" data-target="#tab5" role="tab" aria-controls="tab5" aria-selected="false">
                        Processing Summary
                    </button>
                </li>

            </ul>
            <div class="tab-content" id="custom-tabs-one-tabContent">
                <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="select-product-tab">

                    <div class="row mt-3 mb-3">
                        <form id="frmproductfilter">
                            <div class="col-md-12">
                                <div class="d-flex" style="gap:5px;">
                                    <div class="border p-2">
                                        <button type="button" id="searchBtn" class="btn btn-primary w-100">Search</button>
                                        <select class="form-control mt-3 mb-3" name="store_id" id="listing_store">
                                            <option value="">--</option>
                                            @if(count($stores ?? []) > 0)
                                                @foreach($stores as $store)
                                                    <option value="{{ $store->id }}" data-storetype="{{ (!empty((array)$store->storeType)) ? $store->storeType->name : '' }}">{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="form-group mb-1 mt-1">
                                            <input type="checkbox" name="never_listed_product" id="never_listed_product" checked> <span>Never Listed Product</span>
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="checkbox" name="additional_image_available" id="additional_image_available"> <span>Additional Image Available</span>
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="checkbox" name="exclude_oem" id="exclude_oem"> <span>Exclude Oem</span>
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="checkbox" name="bundle_product" id="bundle_product"> <span>Bundle Product</span>
                                        </div>

                                    </div>
                                    <div class="border p-2" style="width: 13%;">
                                        <label>Product Code Filter</label>
                                        <div class="form-group d-flex position-relative">
                                            <input type="text" class="form-control" id="product_code_filter" placeholder="Search...."><i class="fa fa-search position-absolute" style="top: 50%;right: 5px;transform: translateY(-50%);"></i>
                                        </div>
                                        <div id="pro_code_list">

                                        </div>
                                    </div>
                                    <div class="border p-2" style="width: 10%;">
                                        <label>Device Series</label>
                                        <div class="form-group d-flex position-relative">
                                            <input type="text" class="form-control" id="device_series_filter" placeholder="Search...."><i class="fa fa-search position-absolute" style="top: 50%;right: 5px;transform: translateY(-50%);"></i>
                                        </div>
                                        <div id="device_series_list">

                                        </div>
                                    </div>
                                    <div class="border p-2 " style="width: 10%;">
                                        <label>Device Filter</label>
                                        <div class="form-group d-flex position-relative">
                                            <input type="text" class="form-control" id="device_filter" placeholder="Search...."><i class="fa fa-search position-absolute" style="top: 50%;right: 5px;transform: translateY(-50%);"></i>
                                        </div>
                                        <div id="device_list">

                                        </div>
                                    </div>
                                    <div class="border p-2" style="width: 17%;">
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
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div class="col-md-12">
                            <div class="">
                                <div class="card-header pl-0 pr-0">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <input type="checkbox" id="selected_product"> <label for="selected_product">Selected Products Only</label>
                                            <button class="btn btn-default" id="select_all_product">Select All</button>
                                            <span class="total_sel_product"></span>
                                        </div>
                                        <div class="float-right" id="total_search_product"></div>
                                    </div>
                                </div>
                                <div class="card-body pl-0 pr-0">
                                    <div class="table-responsive" style="max-height: 500px; overflow: auto;">
                                        <table id="productsTable" class="table table-bordered table-striped">
                                            <thead>
                                            <tr>
                                                <th>Image</th>
                                                <th>Product Code</th>
                                                <th>Product Type</th>
                                                <th>Title</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Interface</th>
                                                <th>Created At</th>
                                            </tr>
                                            </thead>
                                            <tbody id="search-product-list">

                                            {{--@include('catalog.product_list', ['products' => $products])--}}

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="product-update-tab">
                    <div class="row mt-3 mb-3">

                        <div class="col-md-12">
                            <div class="d-flex" style="gap:5px;">
                                <div class="border p-2">
                                    <label>Title Generation</label>
                                    <div class="form-group d-flex position-relative align-items-center w-75">
                                        <input type="radio" class="ml-3 mr-2" name="title_generate" value="3" checked> <span>3</span>
                                        <input type="radio" class="ml-3 mr-2" name="title_generate" value="2"> <span>2</span>
                                        <input type="radio" class="ml-3 mr-2" name="title_generate" value="1"> <span>1</span>
                                        <button type="button" class="btn btn-secondary ml-3" id="generate_title">Generate</button>
                                    </div>

                                </div>
                                <div class="border p-2">
                                    <label>Keyword Generation</label>
                                    <div class="d-flex justify-content-between" style="gap:5px;">
                                        <div class="border p-2">
                                            <label>Short</label>
                                            <div class="form-group d-flex position-relative mb-0 align-items-center w-75">
                                                <input type="radio" class="ml-2 mr-2" name="short_keyword" value="1" checked> <span>1</span>
                                                <input type="radio" class="ml-2 mr-2" name="short_keyword" value="2"> <span>2</span>
                                                <input type="radio" class="ml-2 mr-2" name="short_keyword" value="3"> <span>3</span>
                                            </div>
                                        </div>
                                        <div class="border p-2">
                                            <label>Long</label>
                                            <div class="form-group d-flex position-relative mb-0 align-items-center w-75">
                                                <input type="radio" class="ml-2 mr-2" name="long_keyword" value="all" checked> <span>All</span>
                                                <input type="radio" class="ml-2 mr-2" name="long_keyword" value="1"> <span>1</span>
                                                <input type="radio" class="ml-2 mr-2" name="long_keyword" value="2"> <span>2</span>
                                                <input type="radio" class="ml-2 mr-2" name="long_keyword" value="3"> <span>3</span>
                                                <input type="radio" class="ml-2 mr-2" name="long_keyword" value="4"> <span>4</span>
                                                <input type="radio" class="ml-2 mr-2" name="long_keyword" value="5"> <span>5</span>
                                                <input type="radio" class="ml-2 mr-2" name="long_keyword" value="6"> <span>6</span>
                                                <input type="radio" class="ml-2 mr-2" name="long_keyword" value="7"> <span>7</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="d-flex pb-2">
                                                <input type="text" id="keyword_seperator" value="," class="form-control" style="width: 30px;">
                                                <button type="button" class="btn btn-secondary ml-3" id="generate_keyword">Generate</button>
                                            </div>
                                            <input type="checkbox" name="append_front" id="append_front" checked> Append Front
                                        </div>
                                    </div>
                                </div>
                                <div class="border p-2">
                                    <label>Replace / Clear</label>
                                    <div class="d-flex justify-content-between" style="gap:5px;">
                                        <div class="border p-2">
                                            <div class="form-group d-flex position-relative mb-0 align-items-center w-75">
                                                <input type="radio" class="ml-2 mr-2" name="replace_field" value="all" checked> <span>All</span>
                                                <input type="radio" class="ml-2 mr-2" name="replace_field" value="title"> <span>Title</span>
                                                <input type="radio" class="ml-2 mr-2" name="replace_field" value="short"> <span>Short</span>
                                                <input type="radio" class="ml-2 mr-2" name="replace_field" value="long"> <span>Long</span>
                                                <input type="text" class="form-control ml-2 mr-2" name="search_input" id="search_input" placeholder="Search">
                                                <input type="text" class="form-control ml-2 mr-2" name="replace_value" id="replace_value" placeholder="Replace">
                                                <button type="button" class="btn btn-secondary ml-3" id="replace">Replace</button>
                                            </div>
                                            {{--<div class="form-group d-flex align-items-center mt-3">

                                            </div>--}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="">
                                <div class="card-header d-flex align-items-center justify-content-between pr-0 pl-0">
                                    <div class="d-flex align-items-center">
                                        <label>Bundle Sperator</label> <input type="text" class="form-control ml-3" name="bundle_seperator" value="w" style="width: 150px;">
                                        <span class="total_product"></span>
                                    </div>
                                    <div class="float-right d-flex align-items-center" style="gap:15px;">
                                        <div class="d-flex align-items-center" style="gap:15px;">
                                            <select class="form-control" id="store_category">

                                            </select>
                                            <button class="btn btn-secondary" id="apply_all">Apply to all</button>
                                        </div>
                                        <div class="d-flex align-items-center" style="gap:15px;">
                                            <select id="price_type" class="form-control">
                                                <option value="fixed">Fixed</option>
                                                <option value="percentage">Percentage</option>
                                            </select>
                                            <input type="text" class="form-control" id="price_to_increase" placeholder="Price to increase">
                                            <button class="btn btn-secondary" id="apply_price">Apply to selected</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pr-0 pl-0">
                                    <div class="table-responsive" id="productsTable2_wrapper" style="max-height: 500px; overflow: auto;">


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade position-relative" id="tab3" role="tabpanel" aria-labelledby="select-device-tab">
                    <div class="row mt-3 mb-3">
                        <div class="col-md-12">
                            <div class="d-flex" style="gap:10px;">
                                <div class="border p-2" style="width: 17%;">
                                    <label>Generation Type</label>
                                    <div class="form-group align-items-center">
                                        @if(count($listingGenerationTypes ?? []) > 0)
                                            @php
                                                $count = 0;
                                            @endphp
                                            @foreach($listingGenerationTypes as $key=>$ltype)
                                                <div class="form-group mb-1">
                                                    <input type="radio" class="mr-1" {{ ($ltype->name == 'Free Group') ? 'checked' : '' }} id="generate_type_{{ $ltype->id }}" name="generate_type" value="{{ $ltype->id }}" data-name="{{ $ltype->name }}"> <label class="mb-0" for="generate_type_{{ $ltype->id }}">{{ $ltype->name }}</label>
                                                </div>
                                                @php
                                                    $count++;
                                                @endphp
                                            @endforeach
                                        @endif
                                        <div class="form-group mb-1 inc_carrier_option">
                                            <input type="checkbox" class="mr-1 mt-1" name="include_carrier" id="include_carrier"><label for="include_carrier">Include carrier</label>
                                        </div>
                                    </div>
                                    <div class="border p-2">
                                        <div class="form-group mb-0 align-items-center">
                                            <div class="form-group d-flex align-items-baseline mb-1">
                                                <input type="radio" class="ml-2 mr-2" name="listing_type" value="all" checked> <span>All</span>
                                            </div>
                                            <div class="form-group d-flex align-items-baseline mb-1">
                                                <input type="radio" class="ml-2 mr-2" name="listing_type" value="Neverlistedfortype"> <span>Never listed for type</span>
                                            </div>
                                            <div class="form-group d-flex align-items-baseline mb-1">
                                                <input type="radio" class="ml-2 mr-2" name="listing_type" value="neverlisted"> <span>Neverlisted</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="border p-2" style="max-height: 290px;overflow-y: auto;">
                                        <label>Device Type</label>
                                        @if(count($deviceTypes ?? []) > 0)
                                            @foreach($deviceTypes as $dType)
                                                <div class="form-group d-flex align-items-baseline mb-1">
                                                    <input type="checkbox" class="ml-2 mr-2" name="device_type[]" value="{{ $dType->id }}"> <span>{{ $dType->name }}</span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div class="border p-2 mt-2" id="combo_option">
                                        <div class="form-group d-flex align-items-baseline mb-1">
                                            <input type="checkbox" class="ml-2 mr-2" name="brand_combo" id="brand_combo" value=""> <span> Brand Combo</span>
                                        </div>
                                        <div class="form-group d-flex align-items-baseline mb-1">
                                            <input type="checkbox" class="ml-2 mr-2" name="carrier_combo" id="carrier_combo" value=""> <span>Carrier Combo</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex" style="gap:10px;">
                                        <div class="border p-2" style="width: 26.33%;">
                                            <label>Brand Filter</label>
                                            <div class="row">
                                                <div class="col-md-12 pro-brand multi-select-filter">
                                                    <select class="form-control list-filter" id="brand_filter" multiple style="height: 225px;">
                                                        @if(count($brands ?? []) > 0)
                                                            @foreach($brands as $brand)
                                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="border p-2" style="width: 20%;">
                                            <label>Rating Filter</label>
                                            <div class="row">
                                                <div class="col-md-12 pro-rating multi-select-filter">
                                                    <select class="form-control list-filter" id="rating_filter" multiple style="height: 235px;">
                                                        @if(count($deviceRating ?? []) > 0)
                                                            @foreach($deviceRating as $deviceR)
                                                                <option value="{{ $deviceR->id }}">{{ $deviceR->value }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="border p-2" style="width: 20%;">
                                            <label>Popularity</label>
                                            <div class="row">
                                                <div class="col-md-12 pro-popular multi-select-filter">
                                                    <select class="form-control list-filter" id="popularity_filter" multiple style="height: 235px;">
                                                        <option value="1">1</option>
                                                        <option value="2">2</option>
                                                        <option value="3">3</option>
                                                        <option value="4">4</option>
                                                        <option value="5">5</option>
                                                        <option value="6">6</option>
                                                        <option value="7">7</option>
                                                        <option value="8">8</option>
                                                        <option value="9">9</option>
                                                        <option value="1">10</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="border p-2 carr-filter" style="width: 27%; display:none;">
                                            <label>Carrier</label>
                                            <div class="row">
                                                <div class="col-md-12 pro-carrier multi-select-filter">
                                                    <select class="form-control list-filter" id="carrier_filter" multiple style="height: 235px;">
                                                        @if(count($carriers ?? []) > 0)
                                                            @foreach($carriers as $carrier)
                                                                <option value="{{ $carrier->id }}">{{ $carrier->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="d-flex" style="gap:10px;">
                                        <div class="form-group mb-1 mt-3">
                                            <label>Brand</label>
                                            <select class="form-control" name="brand" id="use-store-brand">
                                                <option value=""></option>
                                                <option value="use-store">Use store as brand</option>
                                            </select>
                                        </div>
                                        <div>
                                            <div class="form-group mb-1 mt-3">
                                                <label>Title Template</label>
                                                <div class="d-flex">
                                                    <input type="text" class="form-control" name="title_template" id="title_template" style="width: 85%;">
                                                    <button type="button" class="btn btn-default" id="updateAll">Update All</button>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center mb-1 mt-3">
                                                <span>Brand Seperator </span>
                                                <input type="text" name="brand_seperator" class="form-control ml-2 mr-5" id="brand_seperator" value=" , " style="width: 40px;">
                                                <span>Group Seperator </span>
                                                <input type="text" name="group_seperator" class="form-control ml-2 mr-5" id="group_seperator" value=" , " style="width: 40px;">
                                                <span>Device Seperator </span>
                                                <input type="text" name="device_seperator" class="form-control ml-2 mr-5" id="device_seperator" value=" / " style="width: 40px;">
                                                <input type="checkbox" name="compress" id="compress" checked> <span> Compress</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mt-5">
                            <div class="row pr-0 pl-0">
                                <div class="col-md-5">
                                    <div class="card">
                                        <div class="card-header">
                                            Products <span class="total_product"></span>
                                        </div>
                                        <div class="card-body">
                                            <div style="max-height: 538px; overflow: auto">
                                                <table id="productsTable3" class="table table-bordered table-striped">
                                                    <thead>
                                                    <tr>
                                                        <th>Image</th>
                                                        <th>Product Code</th>
                                                        <th>Devices</th>
                                                        <th>Listed</th>
                                                        <th>Listed Type</th>
                                                        <th>Img Avail.</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="product-list-3">

                                                    {{--@include('catalog.product_list', ['products' => $products])--}}

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="card">
                                        <div class="card-header">
                                            Target Listing Details
                                            <span class="float-right" id="total_listing_product"></span>
                                        </div>
                                        <div class="card-body">
                                            <div>
                                                <button type="button" class="btn btn-default mr-2" id="select_all"><i class="fa fa-list"></i> Select All</button>
                                                <button type="button" class="btn btn-default mr-2" id="select_none"><i class="fa fa-ban"></i> Select None</button>
                                                <input type="checkbox" id="selected_device_only"> <span>Selected Device Only</span>
                                            </div>
                                            <div style="max-height: 500px; overflow: scroll">
                                                <table id="productListingTable" class="table table-bordered table-striped mt-3">
                                                    <thead>
                                                    <tr class="sticky-top bg-white" style="top:-1px;">
                                                        <th></th>
                                                        <th>Carrier</th>
                                                        <th>Product Code</th>
                                                        <th>Brand</th>
                                                        <th>Type</th>
                                                        <th>modelName</th>
                                                        <th>Listed count</th>
                                                        <th>Listed type count</th>
                                                        <th>Rating</th>
                                                        <th>Popularity</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="listing_list">
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab4" role="tabpanel" aria-labelledby="listing-tab">
                    <div class="row pt-2">
                        <div class="col-md-12">
                            <div class="p-2">
                                <label>Replace / Clear</label>
                                <div class="d-flex justify-content-between" style="gap:5px;">
                                    <div class="border p-2">
                                        <div class="form-group d-flex align-items-center mt-3">
                                            <input type="text" class="form-control" name="title_input_from" id="title_input_from" placeholder="Search">
                                            <input type="text" class="form-control" name="title_value_to_replace" id="title_value_to_replace" placeholder="Replace">
                                            <button type="button" class="btn btn-secondary ml-3" id="final_replace">Replace</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row  pt-5">
                        <div class="col-md-9">
                            <div>
                                <span class="float-right" id="total_generate_product"></span>
                            </div>
                            <form id="frmFinalListingProducts">
                                <input type="hidden" name="generation_type_id" id="generation_type_id">
                                <table id="listingTable" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Code</th>
                                        <th>SKU</th>
                                        <th>Title</th>
                                    </tr>
                                    </thead>
                                    <tbody id="listing-product">

                                    {{--@include('catalog.product_list', ['products' => $products])--}}

                                    </tbody>
                                </table>
                            </form>
                        </div>
                        <div class="col-md-3 ">
                            <div class="sticky-top" style="height: 100vh; overflow: auto;">
                                <div>
                                    <span class="float-right" id="total_generate_models"></span>
                                </div>
                                <table id="modelTable" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Brand</th>
                                        <th>Model</th>
                                        <th>Carrier</th>
                                    </tr>
                                    </thead>
                                    <tbody id="model_list">

                                    {{--@include('catalog.product_list', ['products' => $products])--}}

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab5" role="tabpanel" aria-labelledby="summary-tab">
                </div>
                <div class="text-center sticky-bottom">
                    <div class="card-footer footer-btn">
                        <button type="button" class="btn btn-warning lbtn" id="prevBtn">Prev</button>
                        <button type="button" class="btn btn-success lbtn" id="nextBtn">Next</button>
                        <a href="{{ route('listing.list') }}" class="btn btn-secondary lbtn">Cancel</a>
                        {{--<a href="{{ route('listing.list') }}" class="btn btn-warning donebtn" style="display: none;">Done</a>--}}
                        <button type="button" class="btn btn-warning donebtn" id="doneBtn" style="display: none;">Done</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="ajax-gallery" style="display: none;"></div>
    </div>
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
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('[data-toggle="tab"]');
            let currentIndex = 0;

            const updateTabs = (index) => {
                const tab = new bootstrap.Tab(tabs[index]);
                tab.show();
            };

            // Function to activate a tab
            const activateTab = (index) => {
                tabs.forEach((tab, i) => {
                    tab.disabled = i !== index; // Disable all other tabs
                });

                //console.log(index);
                const tabInstance = new bootstrap.Tab(tabs[index]);
                //console.log(tabInstance);
                tabInstance.show();

                // Enable/Disable Previous and Next buttons
                document.getElementById('prevBtn').disabled = index === 0;
                document.getElementById('nextBtn').disabled = index === tabs.length - 1;
            };

            document.getElementById('nextBtn').addEventListener('click', () => {
                if(currentIndex < 3) {
                    $('#nextBtn').html('Next');
                }

                if (currentIndex < tabs.length - 1) {
                    if(currentIndex == 0){
                        let selected_product = [];
                        $('input[name="products[]"]:checked').each(function () {
                            selected_product.push($(this).val())
                        });
                        if (selected_product.length === 0) {
                            alert('Please select product to proceed.');
                        }else{
                            followSecondStep();
                            currentIndex++;
                            activateTab(currentIndex);
                            $('html, body').animate({ scrollTop: 0 }, 'fast');
                        }
                    }else if(currentIndex == 1){
                        /* check channel category selected or not*/
                        let flag = false;
                        let product_errors = false;
                        $('.store-type-category').each(function () {
                            var storeTypeName = $(this).data('store-type-name').toLowerCase();

                            var notAllowedStore = ['shopify'];
                            //
                            if($(this).val() == '' && !notAllowedStore.includes(storeTypeName)){
                                $(this).addClass('error');
                                flag = true;
                            }
                        });
                        $('.no_of_errors').each(function () {
                            console.log($(this).data('error'));
                            if($(this).data('error') != '0'){
                                product_errors = true;
                            }
                        });

                        if(flag){
                            alert('Please select channel category for products');
                            return false;
                        }

                        if(product_errors) {
                            alert('Please resolve errors of products to proceed.');
                            return false;
                        }
                        followThirdStep();
                        currentIndex++;
                        activateTab(currentIndex);
                        $('html, body').animate({ scrollTop: 0 }, 'fast');
                        let include_carrier = 0;
                        $('input[name="include_carrier"]').prop('checked',false);
                        loadTitleTemplate();
                    }else if(currentIndex == 2){

                        $('.bd-example').show();
                        let product_ids = [];
                        $('input[name="selected_list_product[]"]').each(function () {
                            product_ids.push($(this).val())
                        });

                        let device_type = [];
                        $('input[name="device_type[]"]:checked').each(function () {
                            device_type.push($(this).val())
                        });

                        let brand = [];
                        $("#brand_filter option").each(function()
                        {
                            brand.push($(this).val())
                        });
                        let rating = [];
                        $("#rating_filter option").each(function()
                        {
                            rating.push($(this).val())
                        });
                        let popularity = [];
                        $("#popularity_filter option").each(function()
                        {
                            popularity.push($(this).val())
                        });

                        let carriers = [];
                        $("#carrier_filter option").each(function()
                        {
                            carriers.push($(this).val())
                        });

                        let store_id = $('#listing_store').val();
                        //let title_template = $('#title_template').val();
                        let brand_seperator = $('#brand_seperator').val();
                        let group_seperator = $('#group_seperator').val();
                        let device_seperator = $('#device_seperator').val();
                        let compress = 0;
                        if($('#compress').is(':checked')){
                            compress = 1;
                        }
                        let generate_type = $('input[name="generate_type"]:checked').val();
                        let generate_type_name = $('input[name="generate_type"]:checked').data('name');

                        let title_template = [];
                        $('input[name="product_title_template[]"]').each(function () {
                            title_template.push($(this).val())
                        });

                        let devices = [];
                        $('input[name="device[]"]').each(function()
                        {
                            devices.push($(this).val())
                        });

                        let deviceSeries = [];
                        $('input[name="device_series[]"]').each(function()
                        {
                            deviceSeries.push($(this).val())
                        });

                        let store_type = $('#listing_store option:selected').data('storetype');

                        let include_carrier = 0;
                        if($('input[name="include_carrier"]').is(':checked')){
                            include_carrier = 1;
                        }

                        let brand_combo = 0;
                        if($('input[name="brand_combo"]').is(':checked')){
                            brand_combo = 1;
                        }

                        let carrier_combo = 0;
                        if($('input[name="carrier_combo"]').is(':checked')){
                            carrier_combo = 1;
                        }

                        if($('#title_template').val() == ''){
                            alert('Please select title template first and update.');
                            return false;
                        }

                        $("#ajax-preloader").show();
                        $('#total_generate_models').html('0 rows');
                        $.ajax({
                            url: "{{ route('listing.generateproductlisting') }}",
                            type: 'POST',
                            data: {
                                product_id:product_ids,
                                device_type:device_type,
                                devices:devices,
                                device_series:deviceSeries,
                                brand:brand,
                                rating:rating,
                                popularity:popularity,
                                carriers:carriers,
                                store_id:store_id,
                                title_template:title_template,
                                compress:compress,
                                generate_type:generate_type,
                                generate_type_name:generate_type_name,
                                brand_seperator:brand_seperator,
                                group_seperator:group_seperator,
                                device_seperator:device_seperator,
                                selected_device:selected_device,
                                selected_carrier:selected_carrier,
                                product_detail:productDetail,
                                store_type:store_type,
                                include_carrier:include_carrier,
                                carrier_combo:carrier_combo,
                                brand_combo:brand_combo,
                                manufacturer_id:$('#use-store-brand').val(),
                            },
                            /* processData: false,
                             contentType: false,*/
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#listing-product').html(response.html);
                                    $('#total_generate_product').html(response.totalproduct +' rows');
                                    $('.bd-example').hide();
                                    $('#generation_type_id').val(response.generate_type);
                                    product_model = response.models;
                                    product_models = response.modelDetail;
                                    $('#model_list').html('');
                                    $('#nextBtn').html('Finish');
                                    currentIndex++;
                                    activateTab(currentIndex);
                                    $('html, body').animate({ scrollTop: 0 }, 'fast');
                                }

                                if(response.error){
                                    alert(response.message);
                                }
                            },
                            error: function (xhr) {
                                if (xhr.responseJSON.message) {
                                    alert(xhr.responseJSON.message);
                                }
                            },
                            complete: function () {
                                $("#ajax-preloader").hide();
                            }
                        });

                    }else if(currentIndex == 3){
                        //$('.bd-example').show();
                        var flag = false;
                        $('.titleLength').each(function () {
                            var len =  $(this).data('length');
                            var maxlen =  $(this).data('maxlength');
                            if(len > maxlen){
                                flag = true;
                            }
                        });
                        console.log(flag);
                        if(flag){
                            alert('The title length is greater than maximum length so product can not generated');
                            return false;
                        }else {
                            const form = document.querySelector("#frmFinalListingProducts");
                            let formData = new FormData(form);
                            formData.append('store_id', $('#listing_store').val());
                            formData.append('product_models', JSON.stringify(product_models));
                            let store_type = $('#listing_store option:selected').data('storetype');
                            formData.append('store_type', store_type);
                            let include_carrier = 0;
                            if($('input[name="include_carrier"]').is(':checked')){
                                include_carrier = 1;
                            }
                            formData.append('include_carrier', include_carrier);
                            let brand_combo = 0;
                            if($('input[name="brand_combo"]').is(':checked')){
                                brand_combo = 1;

                            }
                            formData.append('brand_combo', brand_combo);
                            let carrier_combo = 0;
                            if($('input[name="carrier_combo"]').is(':checked')){
                                carrier_combo = 1;
                            }
                            formData.append('carrier_combo', carrier_combo);
                            formData.append('manufacturer_id', $('#use-store-brand').val());
                            $("#ajax-preloader").show();
                            $.ajax({
                                type: 'POST',
                                url: "{{ route('listing.savelistingproducts') }}", // Adjust this URL to your endpoint
                                data: formData,
                                processData: false,
                                contentType: false,
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                dataType: 'json',
                                success: function (response) {
                                    if (response.success) {
                                        // Display a success message
                                        alert(response.message);
                                        currentIndex++;
                                        activateTab(currentIndex);
                                        if (response.summary) {
                                            $('#tab5').html(response.summary);
                                        }
                                        $('.lbtn').hide();
                                        $('.donebtn').show();
                                        //location.href='{{ route('listing.list') }}'
                                    } else if (response.error) {
                                        alert('Something went wrong! Please try again later.');
                                        location.reload();
                                    }
                                },
                                error: function (xhr) {
                                    if (xhr.responseJSON.message) {
                                        if (xhr.responseJSON.message == 'Attempt to read property "upc_code" on null') {
                                            alert("Upc code missign for selected store. Please generate upc code and try again.");
                                        } else {
                                            alert(xhr.responseJSON.message);
                                        }
                                    }
                                },
                                complete: function () {
                                    $("#ajax-preloader").hide();
                                }
                            });
                        }
                    }else{
                        currentIndex++;
                        activateTab(currentIndex);
                        $('html, body').animate({ scrollTop: 0 }, 'fast');
                    }
                }
            });

            // Handle Previous button click
            document.getElementById('prevBtn').addEventListener('click', () => {
                $('#nextBtn').html('Next');
                if (currentIndex > 0) {
                    currentIndex--;
                    activateTab(currentIndex);
                    $('html, body').animate({ scrollTop: 0 }, 'fast');
                }
            });

            document.getElementById('doneBtn').addEventListener('click', () => {
                $('#nextBtn').html('Next');
                $('.lbtn').show();
                $('.donebtn').hide();
                currentIndex = 0;
                activateTab(0);
            });

            // Initialize by activating the first tab
            activateTab(currentIndex);

        });

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

            /*$('#listing_store').on('select2:select', function (e) {
                /!* check selected store upc exist or not*!/
                var data = e.params.data;
                var store_id = data.id;
                $.ajax({
                    url: "{{ route('listing.checkupcexist') }}",
                    type: 'POST',
                    data: {
                        store_id:store_id,
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if(response.success){
                            $('#alertModal').find('.modal-header').removeClass('bg-danger').removeClass('bg-info');
                            if(response.upc_cnt == 0){
                                $('#alertModal').find('.modal-header').addClass('bg-danger');
                                $('#alertModalLabel').html('Alert');
                                $('#alertModal').find('.modal-body').html('Upc code not available for selected store. Please generate upc code first!');
                            }else{
                                $('#alertModal').find('.modal-header').addClass('bg-info');
                                $('#alertModalLabel').html('Information');
                                $('#alertModal').find('.modal-body').html(response.upc_cnt+' upc code available for selected store. If you require more please generate!');
                            }
                            $('#alertModal').modal('show');
                        }
                    },
                    error: function (xhr) {
                        /!*if (xhr.responseJSON?.error) {
                            $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                        }*!/
                    },
                    complete: function () {
                        $("#ajax-preloader").hide();
                    }
                });
            });*/

            $(document).on('click', '#searchBtn', function() {
                if($('#listing_store').val() == ''){
                    alert('Please select store first.');
                    $('#listing_store').addClass('error');
                    return false;
                }else {
                    $('#listing_store').removeClass('error');
                    loadProducts();
                }
            });

            $("#manufacturer_filter, #product_type_filter, #brand_filter, #rating_filter, #popularity_filter, #carrier_filter").gs_multiselect();

            $(document).on('click', '#selected_product', function() {
                /*if($(this).is(':checked')){

                }else{
                    $('.selectproRow').removeClass('bg-blue');
                    $('.proCheck').prop('checked',true);
                }*/

                $('.selectproRow').removeClass('bg-blue');
                $('.proCheck').prop('checked',false);

                //let totalCnt = $('input[name="products[]"]:checked').length;
                $('.total_sel_product').html('(Selected <b>0</b> item)');
            });

            $(document).on('click', '#select_all_product', function() {
                $('#selected_product').prop('checked',true);
                $('.proCheck').each(function () {
                    $(this).prop('checked',true);
                    $(this).parents('.selectproRow').addClass('bg-blue');
                });

                let totalCnt = $('input[name="products[]"]:checked').length;
                $('.total_sel_product').html('(Selected <b>'+totalCnt+'</b> item)');
            });



            $(document).on('click', '.selectproRow', function(e) {
                let ele = $(this);

                if ($(e.target).is('checkbox, a, img')) {
                    return;
                }
                if($('#selected_product').is(':checked')){
                    if($(this).find('.proCheck').is(':checked')){
                        ele.removeClass('bg-blue');
                        $(this).find('.proCheck').prop('checked',false);
                    }else{
                        ele.addClass('bg-blue');
                        $(this).find('.proCheck').prop('checked',true);
                    }
                }else{
                    ele.removeClass('bg-blue');
                    $(this).find('.proCheck').prop('checked',false);
                }

                let totalCnt = $('input[name="products[]"]:checked').length;
                $('.total_sel_product').html('(Selected <b>'+totalCnt+'</b> item)');
            });

            $(document).on('click', '.sprolistRow', function() {
                let ele = $(this);
                $('.sprolistRow').removeClass('bg-blue');
                $('.pro-id').prop('checked',false);
                if(ele.find('.pro-id').is(':checked')){
                    ele.removeClass('bg-blue');
                    ele.find('.pro-id').prop('checked',false);
                }else{
                    ele.addClass('bg-blue');
                    ele.find('.pro-id').prop('checked',true);
                    var title_template = ele.find('input[name="product_title_template[]"]').val();
                    if(title_template) {
                        $('#title_template').val(title_template);
                    }
                }
                loadListingProduct(1);
            });

            $(document).on('click', '#updateAll', function() {
                $("#ajax-preloader").show();
                var selected_template = $('#title_template').val();
                $('input[name="product_title_template[]"]').val(selected_template);
                setTimeout(function(){
                    $("#ajax-preloader").hide();
                },1000);

            });

            $(document).on('click', 'input[name="device_type[]"]', function() {
                loadListingProduct(0);
            });

            $(document).on('click', 'input[name="include_carrier"]', function() {

                if($(this).is(':checked')){
                    $('.carr-filter').show();
                }else{
                    $('.carr-filter').hide();
                }
                loadListingProduct(0);
            });



            $(document).on('click', '#select_all', function() {
                $('.targetRow').find('.sel-device').prop('checked',true);

                $('.targetRow').find('.sel-device').each(function () {
                    let product_id = $(this).data('proid');
                    addToKey(product_id, $(this).val());
                    var carrier_id = $(this).data('carrierid');
                    if(carrier_id != ''){
                        addCarrier(product_id,carrier_id);
                    }
                });

                $('.targetRow').addClass('bg-blue');
            });

            $(document).on('click', '#select_none', function() {
                $('.targetRow').find('.sel-device').prop('checked',false);
                $('.targetRow').find('.sel-device').each(function () {
                    let product_id = $(this).data('proid');
                    var nArr = selected_device[product_id];
                    const newArray = nArr.reduce((acc, item) => {
                        if (item !== $(this).val()) acc.push(item);
                        return acc;
                    }, []);

                    selected_device[product_id] = [];

                    if(selected_carrier.length) {
                        var cArr = selected_carrier[product_id];
                        const newcArray = cArr.reduce((acc, item) => {
                            if (item !== $(this).val()) acc.push(item);
                            return acc;
                        }, []);

                        selected_carrier[product_id] = [];
                    }
                });
                $('.targetRow').removeClass('bg-blue');
            });

            $(document).on('click', '#selected_device_only', function() {
                if($(this).is(':checked')){
                    $('.targetRow').find('.sel-device').prop('checked',false);
                }else{
                    $('.targetRow').find('.sel-device').prop('checked',true);
                }
            });

            $(document).on('click','.errorModal button', function() {
                if($(this).data('bs-dismiss') === 'modal') {
                    var $modal = $(this).closest('.modal');
                    var modalId = $modal.attr('id');
                    console.log(modalId);
                    $('#' + modalId).modal('hide');
                }
            })

            $(document).on('click', '.targetRow', function() {
                let ele = $(this);

                if(!$('#selected_device_only').is(':checked')){
                    return false;
                }
                if(ele.find('.sel-device').is(':checked')){
                    ele.removeClass('bg-blue');
                    ele.find('.sel-device').prop('checked',false);
                    valueToRemove = ele.find('.sel-device').val();
                    var product_id = ele.find('.sel-device').data('proid');
                    var nArr = selected_device[product_id];
                    const newArray = nArr.reduce((acc, item) => {
                        if (item !== valueToRemove) acc.push(item);
                        return acc;
                    }, []);

                    selected_device[product_id] = newArray;

                    if(selected_carrier.length) {
                        var ncArr = selected_carrier[product_id];
                        const newcArray = ncArr.reduce((acc, item) => {
                            if (item !== valueToRemove) acc.push(item);
                            return acc;
                        }, []);

                        selected_carrier[product_id] = newcArray;
                    }
                }else{
                    ele.addClass('bg-blue');
                    ele.find('.sel-device').prop('checked',true);
                    //selected_device.push(ele.find('.sel-device').val());
                    var product_id = ele.find('.sel-device').data('proid');
                    addToKey(product_id, ele.find('.sel-device').val());
                    var carrier_id = ele.find('.sel-device').data('carrierid');
                    if(carrier_id != ''){
                        addCarrier(product_id,carrier_id);
                    }
                    //console.log('add-'+selected_device);
                }
            });

            $(document).on('click', '.listingRow', function() {
                let ele = $(this);
                $('.listingRow').removeClass('bg-blue');
                ele.addClass('bg-blue');
                let key = ele.data('key');
                let proid = ele.data('proid');
                let selModel = product_model[proid];
                let selModels = product_models[proid];
                //console.log(selModel);
                let mlist = '';
                for(i in selModel[key]){
                    let brand = selModels[key][i];
                    let cname = '';
                    if(brand.carrier_name){
                        cname = brand.carrier_name;
                    }
                    mlist += '<tr><td>' + brand['brand_name'] + '</td><td>'+selModel[key][i]+'</td><td>'+cname+'</td></tr>';
                }
                $('#model_list').html(mlist);
                $('#total_generate_models').html(selModel[key].length +' rows');
            });

            $(document).on('click', '#final_replace', function() {
                let searchTerm = $('#title_input_from').val();
                let replaceText = $('#title_value_to_replace').val();

                $('.listingRow').each(function () {
                    $(this).find('textarea').each(function () {
                        var currentvalue = $(this).val();
                        var updatedString = currentvalue.replace(new RegExp(searchTerm, "g"), replaceText);
                        $(this).val(updatedString);
                    });
                });
            });
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
                                if($('#listing_store').val() != '') {
                                    loadProducts();
                                }
                            },
                            error: function (xhr) {
                                /*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*/
                            }
                        });
                    }

                    /*if (ele.parents('.prod-sub-type').length) {

                        let product_types = [];
                        ele.parents('.prod-sub-type').find('input[name="product_sub_type[]"]').each(function () {
                            product_types.push($(this).val());
                        });
                        $.ajax({
                            url: "{{ route('listing.getproductsubtype') }}",
                            type: 'POST',
                            data: {
                                product_types: product_types
                            },
                            /!* processData: false,
                             contentType: false,*!/
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#proSubSubType').show();
                                    if ($('#proSubSubType').find('.default-drop-down').length <= 0) {
                                        $('#product_sub_sub_type_filter').html(response.option);
                                        $('#product_sub_sub_type_filter').gs_multiselect();
                                    } else {
                                        $('#product_sub_sub_type_filter').html('');
                                        $('#proSubSubType').find('.default-drop-down').html(response.option);
                                    }

                                    if ($('#interfaceFilter').find('.default-drop-down').length <= 0) {
                                        $('#interface_filter').html(response.ioption);
                                        $('#interface_filter').gs_multiselect();
                                    } else {
                                        $('#interface_filter').html('');
                                        $('#interfaceFilter').find('.default-drop-down').html(response.ioption);
                                    }

                                } else {
                                    clearMultiSelect('product_sub_sub_type','prod-sub-sub-type');
                                    clearMultiSelect('interface','interface-filter');
                                    $('#proSubSubType').hide();
                                }
                                if($('#listing_store').val() != '') {
                                    loadProducts();
                                }
                            },
                            error: function (xhr) {
                                /!*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*!/
                            }
                        });
                    }*/

                    if (ele.parents('.prod-sub-type').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if(ele.parents('.interface-filter').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if(ele.parents('.prod-sub-sub-type').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if(ele.parents('.prod-sub-sub-type').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if(ele.parents('.manufacture').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if (ele.parents('.pro-brand').length || ele.parents('.pro-rating').length || ele.parents('.pro-popular').length || ele.parents('.pro-carrier').length) {
                        loadListingProduct(0);
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
                                if($('#listing_store').val() != '') {
                                    loadProducts();
                                }
                            },
                            error: function (xhr) {
                                /*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*/
                            }
                        });
                    }

                    /*if (ele.parents('.prod-sub-type').length) {

                        let product_types = [];
                        ele.parents('.prod-sub-type').find('input[name="product_sub_type[]"]').each(function () {
                            product_types.push($(this).val());
                        });
                        $.ajax({
                            url: "{{ route('listing.getproductsubtype') }}",
                            type: 'POST',
                            data: {
                                product_types: product_types
                            },
                            /!* processData: false,
                             contentType: false,*!/
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#proSubSubType').show();
                                    if ($('#proSubSubType').find('.default-drop-down').length <= 0) {
                                        $('#product_sub_sub_type_filter').html(response.option);
                                        $('#product_sub_sub_type_filter').gs_multiselect();
                                    } else {
                                        $('#product_sub_sub_type_filter').html('');
                                        $('#proSubSubType').find('.default-drop-down').html(response.option);
                                    }

                                    if ($('#interfaceFilter').find('.default-drop-down').length <= 0) {
                                        $('#interface_filter').html(response.ioption);
                                        $('#interface_filter').gs_multiselect();
                                    } else {
                                        $('#interface_filter').html('');
                                        $('#interfaceFilter').find('.default-drop-down').html(response.ioption);
                                    }

                                } else {
                                    clearMultiSelect('product_sub_sub_type','prod-sub-sub-type');
                                    clearMultiSelect('interface','interface-filter');
                                    $('#proSubSubType').hide();
                                }
                                if($('#listing_store').val() != '') {
                                    loadProducts();
                                }
                            },
                            error: function (xhr) {
                                /!*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*!/
                            }
                        });
                    }*/

                    if (ele.parents('.prod-sub-type').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if(ele.parents('.interface-filter').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if(ele.parents('.prod-sub-sub-type').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if(ele.parents('.manufacture').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if (ele.parents('.pro-brand').length || ele.parents('.pro-rating').length || ele.parents('.pro-popular').length || ele.parents('.pro-carrier').length) {
                        loadListingProduct(0);
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

                                if($('#listing_store').val() != '') {
                                    loadProducts();
                                }
                            },
                            error: function (xhr) {
                                /*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*/
                            }
                        });
                    }

                    /*if (ele.parents('.prod-sub-type').length) {
                        let product_types = [];
                        ele.parents('.prod-sub-type').find('input[name="product_sub_type[]"]').each(function () {
                            product_types.push($(this).val());
                        });
                        $.ajax({
                            url: "{{ route('listing.getproductsubtype') }}",
                            type: 'POST',
                            data: {
                                product_types: product_types
                            },
                            /!* processData: false,
                             contentType: false,*!/
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#proSubSubType').show();
                                    if ($('#proSubSubType').find('.default-drop-down').length <= 0) {
                                        $('#product_sub_sub_type_filter').html(response.option);
                                        $('#product_sub_sub_type_filter').gs_multiselect();
                                    } else {
                                        $('#product_sub_sub_type_filter').html('');
                                        $('#proSubSubType').find('.default-drop-down').html(response.option);
                                    }

                                    if ($('#interfaceFilter').find('.default-drop-down').length <= 0) {
                                        $('#interface_filter').html(response.ioption);
                                        $('#interface_filter').gs_multiselect();
                                    } else {
                                        $('#interface_filter').html('');
                                        $('#interfaceFilter').find('.default-drop-down').html(response.ioption);
                                    }

                                } else {
                                    clearMultiSelect('product_sub_sub_type','prod-sub-sub-type');
                                    clearMultiSelect('interface','interface-filter');
                                    $('#proSubSubType').hide();
                                }
                                loadProducts();
                            },
                            error: function (xhr) {
                                /!*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*!/
                            }
                        });
                    }*/


                    if (ele.parents('.prod-sub-type').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if(ele.parents('.interface-filter').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if(ele.parents('.prod-sub-sub-type').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if(ele.parents('.prod-sub-sub-type').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if(ele.parents('.manufacture').length) {
                        if($('#listing_store').val() != '') {
                            loadProducts();
                        }
                    }

                    if (ele.parents('.pro-brand').length || ele.parents('.pro-rating').length || ele.parents('.pro-popular').length || ele.parents('.pro-carrier').length) {
                        loadListingProduct(0);
                    }


                },300);

            });

            /*$(document).on('change','.default-drop-down',function(e){
                e.preventDefault();
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
                            /!* processData: false,
                             contentType: false,*!/
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

                                if($('#listing_store').val() != '') {
                                    loadProducts();
                                }
                            },
                            error: function (xhr) {
                                /!*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*!/
                            }
                        });
                    }

                    /!*if (ele.parents('.prod-sub-type').length) {
                        let product_types = [];
                        ele.parents('.prod-sub-type').find('input[name="product_sub_type[]"]').each(function () {
                            product_types.push($(this).val());
                        });
                        $.ajax({
                            url: "{{ route('listing.getproductsubtype') }}",
                            type: 'POST',
                            data: {
                                product_types: product_types
                            },
                            /!* processData: false,
                             contentType: false,*!/
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#proSubSubType').show();
                                    if ($('#proSubSubType').find('.default-drop-down').length <= 0) {
                                        $('#product_sub_sub_type_filter').html(response.option);
                                        $('#product_sub_sub_type_filter').gs_multiselect();
                                    } else {
                                        $('#product_sub_sub_type_filter').html('');
                                        $('#proSubSubType').find('.default-drop-down').html(response.option);
                                    }

                                    if ($('#interfaceFilter').find('.default-drop-down').length <= 0) {
                                        $('#interface_filter').html(response.ioption);
                                        $('#interface_filter').gs_multiselect();
                                    } else {
                                        $('#interface_filter').html('');
                                        $('#interfaceFilter').find('.default-drop-down').html(response.ioption);
                                    }

                                } else {
                                    clearMultiSelect('product_sub_sub_type','prod-sub-sub-type');
                                    clearMultiSelect('interface','interface-filter');
                                    $('#proSubSubType').hide();
                                }
                                loadProducts();
                            },
                            error: function (xhr) {
                                /!*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*!/
                            }
                        });
                    }*!/

                    if(ele.parents('.prod-sub-sub-type').length) {
                        loadProducts()
                    }

                    if(ele.parents('.interface-filter').length) {
                        loadProducts()
                    }

                    if (ele.parents('.pro-brand').length || ele.parents('.pro-rating').length || ele.parents('.pro-popular').length) {
                        loadListingProduct(0);
                    }

                },300);

            });*/

            /*$(document).on('change', '#product_type_filter', function() {
                let product_type = $(this).val();
                $.ajax({
                    url: "{{ route('listing.getproductsubtype') }}",
                    type: 'POST',
                    data: {
                        product_types:product_type
                    },
                   /!* processData: false,
                    contentType: false,*!/
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            $('#proSubType').show();
                            if(cnt == 0) {
                                $('#product_sub_type_filter').html(response.option);
                                $('#product_sub_type_filter').gs_multiselect();
                                cnt++;
                            }else{
                                $('#product_sub_type_filter').html('');
                                $('#proSubType').find('.default-drop-down').html(response.option);
                            }
                        }else{
                            $('#proSubType').hide();
                        }
                    },
                    error: function (xhr) {
                        /!*if (xhr.responseJSON?.error) {
                            $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                        }*!/
                    }
                });
            });*/


            $(document).on('click', '#generate_keyword', function() {
                generateKeywords();
            });

            $(document).on('click', '#generate_title', function() {
                generateTitleKeywords();
            });

            $(document).on('click', '#replace', function() {
                let searchTerm = $('#search_input').val();
                let replaceText = $('#replace_value').val();
                let replaceField = $('input[name="replace_field"]:checked').val();

                //console.log(replaceField);
                $('.sproRow').each(function () {
                    let ele = $(this);
                    if(replaceField == 'all'){
                        ele.find('textarea').each(function () {
                            var currentvalue = $(this).val();
                            var updatedString = currentvalue.replace(new RegExp(searchTerm, "g"), replaceText);
                            $(this).val(updatedString);
                        });
                    }else if(replaceField == 'title'){
                        var currentvalue = ele.find('.pro-title').val();
                        var updatedString = currentvalue.replace(new RegExp(searchTerm, "g"), replaceText);
                        ele.find('.pro-title').val(updatedString);
                    }else if(replaceField == 'short'){
                        var currentvalue = ele.find('.pro-shortkey').val();
                        var updatedString = currentvalue.replace(new RegExp(searchTerm, "g"), replaceText);
                        ele.find('.pro-shortkey').val(updatedString);
                    }else if(replaceField == 'long'){
                        var currentvalue = ele.find('.pro-longkey').val();
                        var updatedString = currentvalue.replace(new RegExp(searchTerm, "g"), replaceText);
                        ele.find('.pro-longkey').val(updatedString);
                    }

                });
            });


            $(document).on('input', '.pro-title', function() {
                var val = $(this).val();
                var id = $(this).data('id');

                $('.tLength-'+id).html(val.length);
            });

            $(document).on('input', '.pro-shortkey', function() {
                var val = $(this).val();
                var id = $(this).data('id');

                $('.sLength-'+id).html(val.length);
            });

            $(document).on('input', '.pro-longkey', function() {
                var val = $(this).val();
                var id = $(this).data('id');

                $('.lLength-'+id).html(val.length);
            });

            $(document).on('input ', '.pro-list-title', function() {
                var ele = $(this);
                var val = $(this).val();
                var maxlen = $(this).parent().find('.titleLength').data('maxlength');

                $(this).parent().find('.titleLength').attr('data-length',val.length);
                $(this).parent().find('.titleLength').removeClass('error').removeClass('success');
                if(val.length > maxlen){
                    $(this).parent().find('.titleLength').addClass('error').html('<i class="fa fa-ban"></i> '+val.length +' / '+ maxlen);
                }else{
                    $(this).parent().find('.titleLength').addClass('success').html('<i class="fa fa-check-circle"></i> '+val.length +' / '+ maxlen);
                }
            });




            $(document).on('click', '.price-overwrite', function() {
                if($(this).is(':checked')){
                    $(this).parent().find('input[type="text"]').removeAttr('readonly');
                }else {
                    $(this).parent().find('input[type="text"]').attr('readonly',true);
                }
            });

            $(document).on('click', 'input[name="generate_type"]', function() {
                let type = $(this).val();
                $('#title_template').val('');
                let typeName = $('input[name="generate_type"]:checked').data('name');

                if(typeName == 'Product Only' || typeName == 'Title Group' ){
                    alert('This selected generate type is under development meanwhile please use other.');
                    return false;
                }
                if(typeName == 'Carrier Group' || typeName == 'All Carriers'){
                    $('#include_carrier').prop('checked',true);
                    $('#include_carrier').attr('disabled',true);
                    $('.carr-filter').show();
                }else{
                    $('#include_carrier').removeAttr('disabled');
                    $('.carr-filter').hide();
                }

                let store_type = $('#listing_store option:selected').data('storetype');

                $.ajax({
                    url: "{{ route('listing.gettitletemplate') }}",
                    type: 'POST',
                    data: {
                        type:type,
                        typeName:typeName,
                        store_type:store_type
                    },
                    /* processData: false,
                     contentType: false,*/
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            //$('#title_template').html(response.option);
                            let data = response.data;
                            if(response.display != '') {
                                $('#title_template').val(response.option);
                                $('input[name="product_title_template[]"]').val(response.option);

                                $('#title_template').autocomplete({

                                    source: function (request, response) {
                                        actemp = data.details;
                                        response(data.display);
                                    },
                                    minLength: 0,
                                    select: function (event, ui) {
                                        $('#title_template').val(ui.item.label);
                                        return false;
                                    }
                                }).focus(function () {
                                    $('#title_template').keydown();
                                });
                            }else{
                                alert('This selected generate type is under development meanwhile please use other.');
                                return false;
                            }
                        }
                    },
                    error: function (xhr) {
                        /*if (xhr.responseJSON?.error) {
                            $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                        }*/
                    }
                });

                loadListingProduct(0);
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
                    if($('#listing_store').val() != '') {
                        loadProducts();
                    }
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
                    if($('#listing_store').val() != '') {
                        loadProducts();
                    }
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
                    if($('#listing_store').val() != '') {
                        loadProducts();
                    }
                    return false;
                }
            }).focus(function () {
                //$('#device_filter').keydown();
            });


            $(document).on('click','.fa-window-close',function () {
                $(this).parent('.border').remove();
                if($('#listing_store').val() != '') {
                    loadProducts();
                }
            });

            $(document).on('click','.mainImage',function () {
                const productId = $(this).data('product-id'); // Get the product ID
                const galleryDiv = $('#ajax-gallery'); // Reference to the hidden gallery div

                // Fetch images via AJAX
                $.ajax({
                    url: '{{ route('catalog.getimages') }}',
                    method: 'POST',
                    data:{
                        productId : productId,
                        "_token": '{{ csrf_token() }}'
                    },
                    success: function (images) {
                        galleryDiv.empty(); // Clear any existing content

                        // Append images to the gallery div
                        $.each(images, function (index, image) {
                            const anchor = $('<a>')
                                .attr('href', image)
                                .attr('data-lightbox', 'gallery')
                                .attr('data-title', 'Gallery Image');

                            const img = $('<img>')
                                .attr('src', image)
                                .css('display', 'none'); // Hidden thumbnails

                            anchor.append(img);
                            galleryDiv.append(anchor);
                        });

                        // Trigger Lightbox by simulating a click on the first image
                        if (images.length > 0) {
                            galleryDiv.find('a:first').trigger('click');
                        } else {
                            alert('No images found for this product.');
                        }
                    },
                    error: function () {
                        alert('Failed to load images.');
                    }
                });
            });

            $(document).on('change','.store-type-category',function () {
                var store_product_id = $(this).data('spid');
                var product_id = $(this).data('pid');
                var category_id = $(this).val();
                $.ajax({
                    url: "{{ route('listing.updatestoreproduct') }}",
                    type: 'POST',
                    data: {
                        category_id:category_id,
                        id: store_product_id,
                        store_id: $('#listing_store').val(),
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.issues && response.issues.length > 0) {
                            var buttonId = 'errorButton-' + product_id;
                            $('button#' + buttonId).removeClass('btn-success').addClass('btn-danger').text(response.issues.length +' Errors').data('error',''+response.issues.length);
                            $('#errorModal-' +product_id + ' .modal-body').html(response.issue_table_html);
                        } else {
                            var buttonId = 'errorButton-' + product_id;
                            $('button#' + buttonId).removeClass('btn-danger').addClass('btn-success').text('0 Issues').data('error','0');
                            $('#errorModal-' +product_id + ' .modal-body').html('');
                        }
                    },
                    error: function (xhr) {
                    },
                    complete: function () {
                        $("#ajax-preloader").hide();
                    }
                });
            });

            $(document).on('click','#apply_all',function () {
                var category_id = $('#store_category').val();
                let selected_product = [];
                $('input[name="selected_product[]"]').each(function () {
                    selected_product.push($(this).val());
                });

                let selected_store_product = [];
                $('input[name="selected_store_product[]"]').each(function () {
                    selected_store_product.push($(this).val());
                });

                $.ajax({
                    url: "{{ route('listing.updateallstoreproduct') }}",
                    type: 'POST',
                    data: {
                        category_id:category_id,
                        product_ids: selected_product,
                        store_product_ids: selected_store_product,
                        store_id: $('#listing_store').val(),
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        for (let i = 0; i < selected_product.length; i++) {
                            var product_id = selected_product[i];

                            if (response.issues.product_id && response.issues.product_id.length > 0) {
                                var buttonId = 'errorButton-' + product_id;
                                $('button#' + buttonId).removeClass('btn-success').addClass('btn-danger').text(response.issues.product_id.length +' Errors').data('error',''+response.issues.product_id.length);
                                $('#errorModal-' +product_id + ' .modal-body').html(response.issue_table_html);
                            } else {
                                var buttonId = 'errorButton-' + product_id;
                                $('button#' + buttonId).removeClass('btn-danger').addClass('btn-success').text('0 Issues').data('error','0');
                                $('#errorModal-' +product_id + ' .modal-body').html('');
                            }
                        }

                        $('.store-type-category').each(function(){
                            $(this).val(category_id).trigger('change');
                        });

                        //$('.store-type-category').select2();

                    },
                    error: function (xhr) {
                    },
                    complete: function () {
                        $("#ajax-preloader").hide();
                    }
                });

            });

            $(document).on('click','#apply_price',function () {
                var new_price = $('#price_to_increase').val();
                var price_type = $('#price_type').val();

                if(price_type == 'fixed'){

                    $('.sproRow').each(function () {
                        let ele = $(this);
                        if(ele.find('.price-overwrite').is(':checked')) {
                            var currentvalue = ele.find('.pro-price').val();
                            var newvalue = parseFloat(currentvalue) + parseFloat(new_price);
                            ele.find('.pro-price').val(parseFloat(newvalue).toFixed(2));
                        }
                    });

                }else{

                    $('.sproRow').each(function () {
                        let ele = $(this);
                        if(ele.find('.price-overwrite').is(':checked')) {
                            var currentvalue = ele.find('.pro-price').val();
                            var newPrice = (parseFloat(currentvalue) * new_price) / 100;
                            var newvalue = parseFloat(currentvalue) + parseFloat(newPrice);
                            ele.find('.pro-price').val(parseFloat(newvalue).toFixed(2));
                        }
                    });
                }
            });

            $(document).on('click','#select_all_pricebox',function () {

                $('.sproRow').each(function () {
                    if($(this).find('.price-overwrite').is(':checked')){
                        $(this).find('.price-overwrite').prop('checked',false);
                        $(this).parent().find('input[type="text"]').attr('readonly',true);
                    }else{
                        $(this).find('.price-overwrite').prop('checked',true);
                        $(this).parent().find('input[type="text"]').removeAttr('readonly');
                    }
                });
            });

        });

        function loadTitleTemplate() {
            let type = $('input[name="generate_type"]:checked').val();
            let typeName = $('input[name="generate_type"]:checked').data('name');
            $('#title_template').val('');
            if(typeName == 'Product Only' || typeName == 'Title Group'){
                alert('This selected generate type is under development meanwhile please use other.');
                return false;
            }
            let store_type = $('#listing_store option:selected').data('storetype');
            $.ajax({
                url: "{{ route('listing.gettitletemplate') }}",
                type: 'POST',
                data: {
                    type:type,
                    typeName:typeName,
                    store_type:store_type
                },
                /* processData: false,
                 contentType: false,*/
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        //$('#title_template').html(response.option);
                        let data = response.data;
                        if(response.option != '') {
                            $('#title_template').val(response.option);
                        }else{
                            $("#title_template").val($("#title_template option:first").val());
                        }

                        $('#title_template').autocomplete({

                            source: function (request, response) {
                                actemp = data.details;
                                response(data.display);
                            },
                            minLength: 0,
                            select : function(event, ui){
                                $('#title_template').val(ui.item.label);

                                var selected_template = ui.item.label;
                                $('.pro-id').each(function () {
                                    if($(this).is(':checked')){
                                        $(this).parent().find('input[name="product_title_template[]"]').val(selected_template);
                                    }
                                });
                                return false;
                            }
                        }).focus(function () {
                            $('#title_template').keydown();
                        });
                    }
                },
                error: function (xhr) {
                    /*if (xhr.responseJSON?.error) {
                        $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                    }*/
                }
            });
        }

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

        function loadProducts() {
            if($('#listing_store').val() === '') return;
            const form = document.querySelector("#frmproductfilter");
            let formData = new FormData(form);
            $("#ajax-preloader").show();
            if ($.fn.DataTable.isDataTable('#productsTable')) {
                $('#productsTable').DataTable().clear().destroy();
            }
            $('.total_sel_product').html('(Selected <b>0</b> item)');
            $.ajax({
                url: "{{ route('listing.getproducts') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.html) {
                        $('#search-product-list').html(response.html);
                        $('#total_search_product').html(response.totalRecords+' rows');

                        if($('#selected_product').is(':checked')){
                            $('.selectproRow').removeClass('bg-blue');
                            $('.proCheck').prop('checked',false);
                        }else{
                            $('.selectproRow').removeClass('bg-blue');
                            $('.proCheck').prop('checked',true);
                        }
                        if(response.totalRecords > 0) {
                            var proTable = $('#productsTable').DataTable({
                                "lengthChange": false,
                                "autoWidth": false,
                                "processing": false,
                                "serverSide": false,
                                "searching": false,
                                "paging": false,
                                "info": false,
                                "order": [[7, "desc"]],
                                columnDefs: [
                                    {
                                        targets: [0],
                                        orderable: false
                                    }
                                ]
                            });
                        }

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

        function followSecondStep() {

            let selected_product = [];
            $('input[name="products[]"]:checked').each(function () {
                selected_product.push($(this).val())
            });

            $('.total_product').html('(<b>'+selected_product.length+'</b> item)');
            $('#ajax-preloader').show();
            $.ajax({
                url: "{{ route('listing.getselectedproducts') }}",
                type: 'POST',
                data: {
                    product_ids:selected_product,
                    store_id:$('#listing_store').val(),
                    step:2
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.html) {
                        $('#productsTable2_wrapper').html(response.html);
                        generateKeywords();
                        $('.store-type-category').select2();
                        if(response.storeCategories){
                            $('#store_category').html(response.storeCategories);
                            $('#store_category').select2();
                        }
                        //generateTitleKeywords();
                        $(document).on('click', '.btn-danger', function () {
                            const targetModal = $(this).data('bs-target');
                            $(targetModal).modal('show');
                        });
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

        function followThirdStep(){
            let selected_product = [];
            let store_type = $('#listing_store option:selected').data('storetype');
            if(store_type == 'Ebay' || store_type == 'eBay'){
                $('input[data-name="Combined Devices"]').parent().hide();
                $('#combo_option').show();
                $('.inc_carrier_option').show();
            }else{
                $('#combo_option').hide();
                $('.inc_carrier_option').hide();
            }
            getStoreManufacturer();
            productDetail = {};
            $('input[name="selected_store_product[]"]').each(function () {
                var pid = $(this).val();
                var pro_id = $('.pid-'+pid).val();
                selected_product.push(pro_id);
                var title = $('.title-'+pro_id).val();
                var short_keyword = $('.shortKey-'+pro_id).val();
                var long_keyword = $('.longKey-'+pro_id).val();
                var price = $('.price-'+pro_id).val();
                var store_type_category_id = $('.stc-'+pro_id).val();
                if($('.po-'+pro_id).is(':checked')) {
                    var price_overwrite = 1;
                }else{
                    var price_overwrite = 0;
                }
                productDetail[pid] = {};
                productDetail[pid]['product_id'] = pro_id;
                productDetail[pid]['title'] = title;
                productDetail[pid]['short_keyword'] = short_keyword;
                productDetail[pid]['long_keyword'] = long_keyword;
                productDetail[pid]['price'] = price;
                productDetail[pid]['price_overwrite'] = price_overwrite;
                productDetail[pid]['store_type_category_id'] = store_type_category_id;
            });

            $("#ajax-preloader").show();
            $.ajax({
                url: "{{ route('listing.getselectedproducts') }}",
                type: 'POST',
                data: {
                    product_ids:selected_product,
                    store_id:$('#listing_store').val(),
                    product_detail:JSON.stringify(productDetail),
                    step:3
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.html) {
                        $('#product-list-3').html(response.html);
                        var selected_template = $('#title_template').val();
                        $('input[name="product_title_template[]"]').val(selected_template);
                        loadListingProduct(0);
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


        function generateKeywords(){
            let short_key_length = $('input[name="short_keyword"]:checked').val();
            let long_key_length = $('input[name="long_keyword"]:checked').val();
            let keyword_seperator = $('#keyword_seperator').val();
            let append_front = 0;
            if($('#append_front').is(':checked')){
                let append_front = 1;
            }
            let selected_product = [];
            $('input[name="selected_product[]"]').each(function () {
                selected_product.push($(this).val());
            });
            $.ajax({
                url: "{{ route('listing.getkeywords') }}",
                type: 'POST',
                data: {
                    short_key_length:short_key_length,
                    long_key_length:long_key_length,
                    keyword_seperator:keyword_seperator,
                    append_front:append_front,
                    selected_product:selected_product,
                },
                /* processData: false,
                 contentType: false,*/
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        if(response.short_keyword){
                            let sObject = response.short_keyword;
                            let lObject = response.long_keyword;
                            for (let key in sObject) {
                                $('.shortKey-'+key).val(sObject[key]);
                                $('.sLength-'+key).html(sObject[key].length);
                            }

                            for (let key in lObject) {
                                $('.longKey-'+key).val(lObject[key]);
                                $('.lLength-'+key).html(lObject[key].length);
                            }
                        }
                    }
                },
                error: function (xhr) {
                    /*if (xhr.responseJSON?.error) {
                        $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                    }*/
                }
            });
        }

        function generateTitleKeywords(){
            let title_length = $('input[name="title_generate"]:checked').val();
            let selected_product = [];
            $('input[name="selected_product[]"]').each(function () {
                selected_product.push($(this).val());
            });
            $.ajax({
                url: "{{ route('listing.gettitlekeywords') }}",
                type: 'POST',
                data: {
                    title_length:title_length,
                    selected_product:selected_product,
                    store_id:$('#listing_store').val(),
                },
                /* processData: false,
                 contentType: false,*/
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        if(response.title_keyword){
                            let sObject = response.title_keyword;
                            for (let key in sObject) {
                                $('.title-'+key).val(sObject[key]);
                                $('.tLength-'+key).html(sObject[key].length);
                            }

                        }
                    }
                },
                error: function (xhr) {
                    /*if (xhr.responseJSON?.error) {
                        $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                    }*/
                }
            });
        }

        function loadListingProduct(single){
            //let product_id = ele.find('.pro-id').val();
            let product_ids = [];
            if(single){

                product_ids.push($('input[name="selected_list_product[]"]:checked').val());
            }else {
                $('input[name="selected_list_product[]"]').each(function () {
                    product_ids.push($(this).val())
                });
            }
            let device_type = [];
            $('input[name="device_type[]"]:checked').each(function () {
                device_type.push($(this).val())
            });

            let is_selected = 0;
            if($('#selected_device_only').is(':checked')) {
                is_selected = 1;
                /*$('input[name="listing_product_id[]"]:checked').each(function () {
                    selected_device.push($(this).val())
                });*/
            }

            let brand = [];
            $("#brand_filter option").each(function()
            {
                brand.push($(this).val())
            });
            let rating = [];
            $("#rating_filter option").each(function()
            {
                rating.push($(this).val())
            });
            let popularity = [];
            $("#popularity_filter option").each(function()
            {
                popularity.push($(this).val())
            });

            let carriers = [];
            $("#carrier_filter option").each(function()
            {
                carriers.push($(this).val())
            });



            let devices = [];
            $('input[name="device[]"]').each(function()
            {
                devices.push($(this).val())
            });

            let deviceSeries = [];
            $('input[name="device_series[]"]').each(function()
            {
                deviceSeries.push($(this).val())
            });

            let include_carrier = 0;
            if($('input[name="include_carrier"]').is(':checked')){
                include_carrier = 1;
            }

            $("#ajax-preloader").show();
            if ($.fn.DataTable.isDataTable('#productListingTable')) {
                $('#productListingTable').DataTable().clear().destroy();
            }
            let generate_type_name = $('input[name="generate_type"]:checked').data('name');
            $.ajax({
                url: "{{ route('listing.getcompatibledevice') }}",
                type: 'POST',
                data: {
                    product_id:product_ids,
                    device_type:device_type,
                    devices:devices,
                    device_series:deviceSeries,
                    brand:brand,
                    rating:rating,
                    popularity:popularity,
                    carriers:carriers,
                    selected_device:selected_device,
                    is_selected:is_selected,
                    include_carrier:include_carrier,
                    generate_type_name:generate_type_name
                },
                /* processData: false,
                 contentType: false,*/
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        $('#listing_list').html(response.html);
                        $('#total_listing_product').html(response.total+' rows');
                        $('.total_sel_product').html('(Selected <b>0</b> item)');
                        if(response.total > 0) {
                            if(include_carrier == 1){
                                var sortCol = 1;
                            }else{
                                var sortCol = 3;
                            }
                            var proListTable = $('#productListingTable').DataTable({
                                "lengthChange": false,
                                "autoWidth": false,
                                "processing": false,
                                "serverSide": false,
                                "searching": false,
                                "paging": false,
                                "info": false,
                                "order": [[sortCol, "asc"]],
                                columnDefs: [
                                    {
                                        targets: [0],
                                        orderable: false
                                    }
                                ]
                            });
                        }


                        if(response.total == 0){
                            $('#nextBtn').attr('disabled',true);
                        }else{
                            $('#nextBtn').removeAttr('disabled');
                        }
                    }else{
                        $('#listing_list').html('');
                        $('#total_listing_product').html('0 rows');
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
