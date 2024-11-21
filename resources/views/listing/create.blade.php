@extends('adminlte::page')

@section('title', 'Listing')

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
                                    <div class="form-group">
                                        <input type="checkbox" id="never_listed_product" checked> Never Listed Product
                                    </div>
                                    <div class="form-group">
                                        <input type="checkbox" id="additional_image_available"> Additional Image Available
                                    </div>
                                    <div class="form-group">
                                        <input type="checkbox" id="exclude_oem"> Exclude Oem
                                    </div>
                                    <div class="form-group">
                                        <input type="checkbox" id="bundle_product"> Bundle Product
                                    </div>

                                </div>
                                <div class="border p-2">
                                    <label>Product Code Filter</label>
                                    <div class="form-group d-flex position-relative w-75">
                                        <input type="text" class="form-control" id="product_code_filter" placeholder="Search...."><i class="fa fa-search position-absolute" style="top: 50%;right: 5px;transform: translateY(-50%);"></i>
                                    </div>

                                </div>
                                <div class="border p-2">
                                    <label>Device Series</label>
                                    <div class="form-group d-flex position-relative w-75">
                                        <input type="text" class="form-control" id="device_series_filter" placeholder="Search...."><i class="fa fa-search position-absolute" style="top: 50%;right: 5px;transform: translateY(-50%);"></i>
                                    </div>

                                </div>
                                <div class="border p-2 ">
                                    <label>Device Filter</label>
                                    <div class="form-group d-flex position-relative w-75">
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


                </div>
                <div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="select-device-tab">

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
