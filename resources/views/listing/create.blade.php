@extends('adminlte::page')

@section('title', 'Listing')
@section('adminlte_css_pre')
    <link href="{{ asset('css/jquery.multiselect.css') }}" rel="stylesheet">

    <style>
        #product_type_filter option, #manufacturer_filter option, select[multiple] option {
            border-bottom: 1px solid #eaeaea;
        }
        #frmproductfilter .d-flex > .border {
            width: 14%;
        }
        .gap-5{gap:5px;}
        .proCheck{
            visibility: hidden;
        }
        .selectproRow:hover td {
            background-color: #ddf1ff;
            color:#000;
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
            z-index: 9999;
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
                                                    <option value="{{ $store->id }}">{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="form-group mb-1">
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
                                        <div class="row" style="height: 87%;">
                                            <div class="col-md-12 multi-select-filter">
                                                <select class="form-control" name="manufacturer[]" id="manufacturer_filter" multiple>
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
                                                <select class="form-control" name="product_type[]" id="product_type_filter" multiple>
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
                                        <div class="row">
                                            <div class="col-md-12 multi-select-filter">
                                                <select class="form-control" name="product_sub_type[]" id="product_sub_type_filter" multiple>

                                                </select>
                                            </div>
                                        </div>

                                        <label>Interface Filter</label>
                                        <div class="row">
                                            <div class="col-md-12 multi-select-filter">
                                                <select class="form-control" name="interface[]" id="interface_filter" multiple>
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
                                    <input type="checkbox" id="selected_product"> Selected Products Only
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
                            <div class="d-flex justify-content-between" style="gap:5px;">
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

                                                <button type="button" class="btn btn-secondary ml-3" id="replace">Replace</button>
                                            </div>
                                            <div class="form-group d-flex align-items-center mt-3">
                                                <input type="text" class="form-control" name="search_input" id="search_input" placeholder="Search">
                                                <input type="text" class="form-control" name="replace_value" id="replace_value" placeholder="Replace">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="">
                                <div class="card-header d-flex align-items-center pr-0 pl-0">
                                    <label>Bundle Sperator</label> <input type="text" class="form-control ml-3" name="bundle_seperator" value="w" style="width: 150px;">
                                </div>
                                <div class="card-body pr-0 pl-0">
                                    <div class="table-responsive" style="max-height: 500px; overflow: auto;">
                                        <table id="productsTable" class="table table-bordered table-striped">
                                            <thead>
                                            <tr>
                                                <th>Image</th>
                                                <th>Product Code</th>
                                                <th>Title</th>
                                                <th>Short Tail Keyword</th>
                                                <th>Long Tail Keyword</th>
                                                <th>Price</th>
                                                <th>Active</th>
                                                <th>Channel Category</th>
                                            </tr>
                                            </thead>
                                            <tbody id="selected_product_list">

                                            {{--@include('catalog.product_list', ['products' => $products])--}}

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade position-relative" id="tab3" role="tabpanel" aria-labelledby="select-device-tab">
                    {{--<div class="bd-example border" style="display: none;">
                        <div class="spinner-grow text-primary" role="status">
                            <span class="visually-hidden"></span>
                        </div>
                        <div class="spinner-grow text-secondary" role="status">
                            <span class="visually-hidden"></span>
                        </div>
                        <div class="spinner-grow text-success" role="status">
                            <span class="visually-hidden"></span>
                        </div>
                        <div class="spinner-grow text-danger" role="status">
                            <span class="visually-hidden"></span>
                        </div>
                        <div class="spinner-grow text-warning" role="status">
                            <span class="visually-hidden"></span>
                        </div>
                        <div class="spinner-grow text-info" role="status">
                            <span class="visually-hidden"></span>
                        </div>
                        <div class="spinner-grow text-light" role="status">
                            <span class="visually-hidden"></span>
                        </div>
                        <div class="spinner-grow text-dark" role="status">
                            <span class="visually-hidden"></span>
                        </div>
                    </div>--}}
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
                                                    <input type="radio" class="mr-1" {{ ($count == 0) ? 'checked' : '' }} id="generate_type_{{ $ltype->id }}" name="generate_type" value="{{ $ltype->id }}" data-name="{{ $ltype->name }}"> <label for="generate_type_{{ $ltype->id }}">{{ $ltype->name }}</label>
                                                </div>
                                                @php
                                                    $count++;
                                                @endphp
                                            @endforeach
                                        @endif

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
                                <div class="border p-2" style="width: 16%;">
                                    <label>Device Type</label>
                                    @if(count($deviceTypes ?? []) > 0)
                                        @foreach($deviceTypes as $dType)
                                            <div class="form-group d-flex align-items-baseline mb-1">
                                                <input type="checkbox" class="ml-2 mr-2" name="device_type[]" value="{{ $dType->id }}"> <span>{{ $dType->name }}</span>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                <div>
                                    <div class="d-flex" style="gap:10px;">
                                        <div class="border p-2" style="widows: 33.33%;">
                                            <label>Brand Filter</label>
                                            <div class="row">
                                                <div class="col-md-12 pro-brand multi-select-filter">
                                                    <select class="form-control" id="brand_filter" multiple style="height: 225px;">
                                                        @if(count($brands ?? []) > 0)
                                                            @foreach($brands as $brand)
                                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="border p-2" style="widows: 33.33%;">
                                            <label>Rating Filter</label>
                                            <div class="row">
                                                <div class="col-md-12 pro-rating multi-select-filter">
                                                    <select class="form-control" id="rating_filter" multiple style="height: 235px;">
                                                        <option value="10">10</option>
                                                        <option value="9">9</option>
                                                        <option value="8">8</option>
                                                        <option value="7">7</option>
                                                        <option value="6">6</option>
                                                        <option value="5">5</option>
                                                        <option value="4">4</option>
                                                        <option value="3">3</option>
                                                        <option value="2">2</option>
                                                        <option value="1">1</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="border p-2" style="widows: 33.33%;">
                                            <label>Popularity</label>
                                            <div class="row">
                                                <div class="col-md-12 pro-popular multi-select-filter">
                                                    <select class="form-control" id="popularity_filter" multiple style="height: 235px;">
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
                                    </div>
                                    <div class="d-flex" style="gap:10px;">
                                        <div class="form-group mb-1 mt-3">
                                            <label>Brand</label>
                                            <select class="form-control" name="brand">
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
                                                <input type="text" name="device_seperator" class="form-control ml-2 mr-5" id="device_seperator" value=" , " style="width: 40px;">
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
                                            Products
                                        </div>
                                        <div class="card-body">
                                            <div style="max-height: 538px; overflow: auto">
                                                <table id="productsTable" class="table table-bordered table-striped">
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

                                                    {{--@include('catalog.product_list', ['products' => $products])--}}

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
                                        <th>Model Name</th>
                                        <th>Name</th>
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
                        <a href="{{ route('listing.list') }}" class="btn btn-warning donebtn" style="display: none;">Done</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop

@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/jquery.multiselect.js') }}"></script>

    <script>
        let product_model = {};
        let product_models = {};
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
                        currentIndex++;
                        activateTab(currentIndex);
                        followThirdStep();
                        $('html, body').animate({ scrollTop: 0 }, 'fast');
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

                        let brand = $('#brand_filter').val();
                        let rating = $('#rating_filter').val();
                        let popularity = $('#popularity_filter').val();
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

                        $("#ajax-preloader").show();
                        $.ajax({
                            url: "{{ route('listing.generateproductlisting') }}",
                            type: 'POST',
                            data: {
                                product_id:product_ids,
                                device_type:device_type,
                                brand:brand,
                                rating:rating,
                                popularity:popularity,
                                store_id:store_id,
                                title_template:title_template,
                                compress:compress,
                                generate_type:generate_type,
                                generate_type_name:generate_type_name,
                                brand_seperator:brand_seperator,
                                group_seperator:group_seperator,
                                device_seperator:device_seperator,
                                selected_device:selected_device,
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
                            },
                            error: function (xhr) {
                                /*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*/
                                const message = xhr.responseJSON?.error || 'An unexpected error occurred. Please try again later.';
                                alert(message);
                            },
                            complete: function () {
                                $("#ajax-preloader").hide();
                            }
                        });

                    }else if(currentIndex == 3){
                        $('.bd-example').show();
                        const form = document.querySelector("#frmFinalListingProducts");
                        let formData = new FormData(form);
                        formData.append('store_id', $('#listing_store').val());
                        formData.append('product_models', JSON.stringify(product_models));
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
                            dataType:'json',
                            success: function(response) {
                                if(response.success){
                                    // Display a success message
                                    alert(response.message);
                                    currentIndex++;
                                    activateTab(currentIndex);
                                    if(response.summary) {
                                        $('#tab5').html(response.summary);
                                    }
                                    $('.lbtn').hide();
                                    $('.donebtn').show();
                                    //location.href='{{ route('listing.list') }}'
                                }else if(response.error){
                                    alert('Something went wrong! Please try again later.');
                                    location.reload();
                                }
                            },
                            error: function(xhr) {
                                const message = xhr.responseJSON?.error || 'An unexpected error occurred. Please try again later.';
                                alert(message);
                                //location.reload();
                            },
                            complete: function () {
                                $("#ajax-preloader").hide();
                            }
                        });
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
        $(document).ready(function() {

           /* $('#listing_store').select2({
                allowClear: true
            });*/
            $(document).on('click', '#searchBtn', function() {
                loadProducts();
            });

            $("#manufacturer_filter, #product_type_filter, #brand_filter, #rating_filter, #popularity_filter").gs_multiselect();

            $(document).on('click', '#selected_product', function() {
                if($(this).is(':checked')){
                    $('.proCheck').prop('checked',false);
                }else{
                    $('.proCheck').prop('checked',true);
                }
            });

            $(document).on('click', '.selectproRow', function() {
                let ele = $(this);
                if($('#selected_product').is(':checked')){
                    if($(this).find('.proCheck').is(':checked')){
                        ele.removeClass('bg-blue');
                        $(this).find('.proCheck').prop('checked',false);
                    }else{
                        ele.addClass('bg-blue');
                        $(this).find('.proCheck').prop('checked',true);
                    }
                }else{
                    $(this).find('.proCheck').prop('checked',true);
                }
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
                var selected_template = $('#title_template').val();
                $('input[name="product_title_template[]"]').val(selected_template);
            });


            /*$(document).on('click', '#brand_filter', function() {
                loadListingProduct(0);
            });*/

            $(document).on('click', 'input[name="device_type[]"]', function() {
                loadListingProduct(0);
            });

            $(document).on('click', '#select_all', function() {
                $('.targetRow').find('.sel-device').prop('checked',true);

                $('.targetRow').find('.sel-device').each(function () {
                    let product_id = $(this).data('proid');
                    addToKey(product_id, $(this).val());
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

                    selected_device[product_id] = newArray;
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



            $(document).on('click', '.targetRow', function() {
                let ele = $(this);
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
                    console.log('remove-'+selected_device);
                }else{
                    ele.addClass('bg-blue');
                    ele.find('.sel-device').prop('checked',true);
                    //selected_device.push(ele.find('.sel-device').val());
                    var product_id = ele.find('.sel-device').data('proid');
                    addToKey(product_id, ele.find('.sel-device').val());
                    console.log('add-'+selected_device);
                }
            });

            $(document).on('click', '.listingRow', function() {
                let ele = $(this);
                $('.listingRow').removeClass('bg-blue');
                ele.addClass('bg-blue');
                let key = ele.data('key');
                let selModel = product_model[key];

                let mlist = '';
                for(i in selModel){
                    mlist += '<tr><td>'+selModel[i]+'</td><td></td></tr>';
                }
                $('#model_list').html(mlist);
                $('#total_generate_models').html(selModel.length +' rows');
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
            let cnt = cnt2 = 0;
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
                                    console.log(0);
                                    if (cnt == 0) {
                                        $('#product_sub_type_filter').html(response.option);
                                        $('#product_sub_type_filter').gs_multiselect();
                                        cnt++;
                                    } else {
                                        $('#product_sub_type_filter').html('');
                                        $('#proSubType').find('.default-drop-down').html(response.option);
                                    }
                                } else {
                                    $('#proSubType').hide();
                                }
                            },
                            error: function (xhr) {
                                /*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*/
                            }
                        });
                    }

                    if (ele.parents('.pro-brand').length || ele.parents('.pro-rating').length || ele.parents('.pro-popular').length) {
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
                                    if (cnt2 == 0) {
                                        $('#product_sub_type_filter').html(response.option);
                                        $('#product_sub_type_filter').gs_multiselect();
                                        cnt2++;
                                    } else {
                                        $('#product_sub_type_filter').html('');
                                        $('#proSubType').find('.default-drop-down').html(response.option);
                                    }
                                } else {
                                    $('#proSubType').hide();
                                }
                            },
                            error: function (xhr) {
                                /*if (xhr.responseJSON?.error) {
                                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                                }*/
                            }
                        });
                    }

                    if (ele.parents('.pro-brand').length || ele.parents('.pro-rating').length || ele.parents('.pro-popular').length) {
                        loadListingProduct(0);
                    }

                },300);

            });

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

                console.log(replaceField);
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
                $.ajax({
                    url: "{{ route('listing.gettitletemplate') }}",
                    type: 'POST',
                    data: {
                        type:type
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
                            $('#title_template').val(response.option);
                            $('input[name="product_title_template[]"]').val(response.option);

                            $('#title_template').autocomplete({

                                source: function (request, response) {
                                    actemp = data.details;
                                    response(data.display);
                                },
                                minLength: 0,
                                select : function(event, ui){
                                    $('#title_template').val(ui.item.label);
                                    return false;
                                }
                            }).focus(function () {
                                $('#title_template').keydown();
                            });;
                        }
                    },
                    error: function (xhr) {
                        /*if (xhr.responseJSON?.error) {
                            $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                        }*/
                    }
                });
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
                    $('#pro_code_list').append('<div class="border bg-blue p-1 mt-1"><input type="hidden" name="product_code[]" value="'+ui.item.value+'">'+ui.item.value+'<i class="fa fa-window-close float-right mt-1"></i></div>');
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
                    $('#device_series_list').append('<div class="border bg-blue p-1 mt-1"><input type="hidden" name="device_series[]" value="'+ui.item.value+'">'+ui.item.label+'<i class="fa fa-window-close float-right mt-1"></i></div>');
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
                    $('#device_list').append('<div class="border bg-blue p-1 mt-1"><input type="hidden" name="device[]" value="'+ui.item.value+'">'+ui.item.label+' <i class="fa fa-window-close float-right mt-1"></i></div>');
                    return false;
                }
            }).focus(function () {
                //$('#device_filter').keydown();
            });


            $(document).on('click','.fa-window-close',function () {
                $(this).parent('.border').remove();
            });
        });

        function loadTitleTemplate() {
            let type = $('input[name="generate_type"]:checked').val();
            $('#title_template').val('');
            $.ajax({
                url: "{{ route('listing.gettitletemplate') }}",
                type: 'POST',
                data: {
                    type:type
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

        function loadProducts() {
            const form = document.querySelector("#frmproductfilter");
            let formData = new FormData(form);
            $("#ajax-preloader").show();
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
                        $('#selected_product_list').html(response.html);
                        generateKeywords();
                        //generateTitleKeywords();
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
            let productDetail = {};
            $('input[name="selected_store_product[]"]').each(function () {
                var pid = $(this).val();
                selected_product.push(pid);
                var pro_id = $('.pid-'+pid).val();
                var title = $('.title-'+pro_id).val();
                var short_keyword = $('.shortKey-'+pro_id).val();
                var long_keyword = $('.longKey-'+pro_id).val();
                var price = $('.price-'+pro_id).val();
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

            loadTitleTemplate();
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
            $("#ajax-preloader").show();
            $.ajax({
                url: "{{ route('listing.getcompatibledevice') }}",
                type: 'POST',
                data: {
                    product_id:product_ids,
                    device_type:device_type,
                    brand:brand,
                    rating:rating,
                    popularity:popularity,
                    selected_device:selected_device,
                    is_selected:is_selected,
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
    </script>
@stop
