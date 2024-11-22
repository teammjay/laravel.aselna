@extends('adminlte::page')

@section('title', 'Listing')
@section('adminlte_css_pre')
    <style>
        #product_type_filter option, #manufacturer_filter option {
        border-bottom: 1px solid #eaeaea;
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
                    <a class="nav-link active" id="select-product-tab" data-toggle="pill" href="#tab1" role="tab" aria-controls="tab1" aria-selected="true">
                        Select Product(s)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="product-update-tab" data-toggle="pill" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                        Product Update
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="select-device-tab" data-toggle="pill" href="#tab3" role="tab" aria-controls="tab3" aria-selected="false">
                        Select Device(s)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="listing-tab" data-toggle="pill" href="#tab4" role="tab" aria-controls="tab4" aria-selected="false">
                        Listing
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="summary-tab" data-toggle="pill" href="#tab5" role="tab" aria-controls="tab5" aria-selected="false">
                        Processing Summary
                    </a>
                </li>

            </ul>
            <div class="tab-content" id="custom-tabs-one-tabContent">
                <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="select-product-tab">

                    <div class="row mt-3 mb-3">

                        <div class="col-md-12">
                            <div class="d-flex justify-content-between" style="gap:5px;">
                                <div class="border p-2">
                                    <button type="button" class="btn btn-primary w-100">Search</button>
                                    <select class="form-control mt-3 mb-3" id="listing_store">
                                        @if(count($stores ?? []) > 0)
                                            @foreach($stores as $store)
                                                <option value="{{ $store->id }}">{{ $store->name.' - '.$store->marketplace_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="form-group mb-1">
                                        <input type="checkbox" id="never_listed_product" checked> Never Listed Product
                                    </div>
                                    <div class="form-group mb-1">
                                        <input type="checkbox" id="additional_image_available"> Additional Image Available
                                    </div>
                                    <div class="form-group mb-1">
                                        <input type="checkbox" id="exclude_oem"> Exclude Oem
                                    </div>
                                    <div class="form-group mb-1">
                                        <input type="checkbox" id="bundle_product"> Bundle Product
                                    </div>

                                </div>
                                <div class="border p-2">
                                    <label>Product Code Filter</label>
                                    <div class="form-group d-flex position-relative">
                                        <input type="text" class="form-control" id="product_code_filter" placeholder="Search...."><i class="fa fa-search position-absolute" style="top: 50%;right: 5px;transform: translateY(-50%);"></i>
                                    </div>

                                </div>
                                <div class="border p-2">
                                    <label>Device Series</label>
                                    <div class="form-group d-flex position-relative">
                                        <input type="text" class="form-control" id="device_series_filter" placeholder="Search...."><i class="fa fa-search position-absolute" style="top: 50%;right: 5px;transform: translateY(-50%);"></i>
                                    </div>

                                </div>
                                <div class="border p-2 ">
                                    <label>Device Filter</label>
                                    <div class="form-group d-flex position-relative">
                                        <input type="text" class="form-control" id="device_filter" placeholder="Search...."><i class="fa fa-search position-absolute" style="top: 50%;right: 5px;transform: translateY(-50%);"></i>
                                    </div>

                                </div>
                                <div class="border p-2">
                                    <label>Manufacturer Filter</label>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <select class="form-control" id="manufacturer_filter" multiple>
                                                @if(count($manufacturers ?? []) > 0)
                                                    @foreach($manufacturers as $mf)
                                                        <option value="{{ $mf->id }}">{{ $mf->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="border p-2">
                                    <label>Product Type Filter</label>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <select class="form-control" id="product_type_filter" multiple>
                                                @if(count($productTypes ?? []) > 0)
                                                    @foreach($productTypes as $pt)
                                                        <option value="{{ $pt->id }}">{{ $pt->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="">
                                <div class="card-header">
                                    <input type="checkbox" id="seleced_product"> Selected Products Only
                                </div>
                                <div class="card-body">
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
                                        <tbody id="product-list">

                                        {{--@include('catalog.product_list', ['products' => $products])--}}

                                        </tbody>
                                    </table>
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
                                                <button type="button" class="btn btn-secondary ml-3" id="generate_title">Generate</button>
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
                                                <input type="text" class="form-control" name="search_input" placeholder="Search">
                                                <input type="text" class="form-control" name="replace_value" placeholder="Replace">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="">
                                <div class="card-header d-flex align-items-center">
                                    <label>Bundle Sperator</label> <input type="text" class="form-control ml-3" name="bundle_seperator" value="w" style="width: 150px;">
                                </div>
                                <div class="card-body">
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
                                        <tbody id="product-list">

                                        {{--@include('catalog.product_list', ['products' => $products])--}}

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="select-device-tab">
                    <div class="row mt-3 mb-3">

                        <div class="col-md-12">
                            <div class="d-flex" style="gap:10px;">
                                <div class="border p-2">
                                    <label>Generation Type</label>
                                    <div class="form-group align-items-center">
                                        <div class="form-group mb-1">
                                            <input type="radio" class="mr-1" name="generate_type" value="product-only" checked> <span>Product Only</span>
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="radio" class="mr-1" name="generate_type" value="single-device"> <span>Single Device</span>
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="radio" class="mr-1" name="generate_type" value="carrier-group"> <span>Carrier Group</span>
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="radio" class="mr-1" name="generate_type" value="brand-group"> <span>Brand Group</span>
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="radio" class="mr-1" name="generate_type" value="title-group"> <span>Title Group</span>
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="radio" class="mr-1" name="generate_type" value="free-group"> <span>Free Group</span>
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="radio" class="mr-1" name="generate_type" value="all-carrier"> <span>All Carrier</span>
                                        </div>
                                        <div class="form-group mb-1">
                                            <input type="radio" class="mr-1" name="generate_type" value="combined-device"> <span>Combined Device</span>
                                        </div>

                                        <div class="form-group mb-1">
                                            <input type="checkbox" class="mr-1" name="popular_brand" value="popular-brand"> <span>Popular Brand Only</span>
                                        </div>
                                    </div>
                                    <div class="border p-2">
                                        <div class="form-group mb-0 align-items-center">
                                            <div class="form-group mb-1">
                                                <input type="radio" class="ml-2 mr-2" name="listing_type" value="all" checked> <span>All</span>
                                            </div>
                                            <div class="form-group mb-1">
                                                <input type="radio" class="ml-2 mr-2" name="listing_type" value="Neverlistedfortype"> <span>Neverlistedfortype</span>
                                            </div>
                                            <div class="form-group mb-1">
                                                <input type="radio" class="ml-2 mr-2" name="listing_type" value="neverlisted"> <span>Neverlisted</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="border p-2">
                                    <label>Device Type</label>
                                    @if(count($deviceTypes ?? []) > 0)
                                        @foreach($deviceTypes as $dType)
                                            <div class="form-group mb-1">
                                                <input type="checkbox" class="ml-2 mr-2" name="device_type" value="{{ $dType->id }}"> <span>{{ $dType->name }}</span>
                                            </div>
                                        @endforeach
                                    @endif


                                </div>
                                <div>
                                    <div class="border p-2">
                                        <label>Brand Filter</label>
                                        <div class="row">
                                            <div class="col-md-12">
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
                                    <div class="form-group mb-1 mt-3">
                                        <label>Brand</label>
                                        <select class="form-control" name="brand">
                                            <option value="use-store">Use store as brand</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex" style="gap:10px;">
                                        <div class="border p-2">
                                            <label>Rating Filter</label>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <select class="form-control" id="rating_filter" multiple style="height: 225px;">
                                                        <option value="1">10</option>
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

                                        <div class="border p-2">
                                            <label>Popularity</label>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <select class="form-control" id="popularity_filter" multiple style="height: 225px;">
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
                                    <div class="form-group mb-1 mt-3">
                                        <label>Title Template</label>
                                        <select class="form-control" name="title_template">
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center mb-1 mt-3">
                                        <span>Brand Seperator </span>
                                        <input type="text" name="brand_seperator" class="form-control ml-2 mr-5" id="brand_seperator" value="," style="width: 30px;">
                                        <span>Group Seperator </span>
                                        <input type="text" name="group_seperator" class="form-control ml-2 mr-5" id="group_seperator" value="," style="width: 30px;">
                                        <span>Device Seperator </span>
                                        <input type="text" name="device_seperator" class="form-control ml-2 mr-5" id="device_seperator" value="," style="width: 30px;">
                                        <input type="checkbox" name="compress" id="compress" checked> <span> Compress</span>
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
                                                <tbody id="product-list">

                                                {{--@include('catalog.product_list', ['products' => $products])--}}

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="card">
                                        <div class="card-header">
                                            Target Listing Details
                                        </div>
                                        <div class="card-body">
                                            <table id="productListingTable" class="table table-bordered table-striped">
                                                <thead>
                                                <tr>
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
                                                <tbody id="listing-list">

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
                <div class="tab-pane fade" id="tab4" role="tabpanel" aria-labelledby="listing-tab">


                </div>
                <div class="tab-pane fade" id="tab5" role="tabpanel" aria-labelledby="summary-tab">
                </div>
            </div>
        </div>
    </div>

@stop

@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>


        $(document).ready(function() {


        });
    </script>
@stop
