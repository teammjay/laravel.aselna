@extends('adminlte::page')

@section('title', 'Add Product')

@section('content_header')
    <h1>Add Product</h1>
@stop

@section('content')
    <form action="{{ route('catalog.store') }}" method="POST">
        @csrf
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="general-tab" data-toggle="pill" href="#tab1" role="tab" aria-controls="tab1" aria-selected="true">
                           General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="inventory-tab" data-toggle="pill" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                            Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tier-pricing-tab" data-toggle="pill" href="#tab3" role="tab" aria-controls="tab3" aria-selected="false">
                            Tier Pricing
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="compatibility-tab" data-toggle="pill" href="#tab4" role="tab" aria-controls="tab4" aria-selected="false">
                            Compatibility
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="custom-tabs-one-tabContent">
                    <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="general-tab">

                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Product Title</label>
                                            <input type="text" class="form-control" id="product_title" name="product_title" placeholder="Enter product title">
                                            @error('product_title')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Product Code</label>
                                            <input type="text" class="form-control" id="product_sku" name="product_sku" placeholder="Enter product code">
                                            @error('product_sku')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Product Type</label>
                                            <select class="form-control" id="product_type" name="product_type">
                                                <option>-Product Type-</option>
                                                <option value="1">Leather Case</option>
                                            </select>
                                            @error('product_type')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Product Price</label>
                                            <input type="text" class="form-control" id="product_price" name="product_price" placeholder="Enter product price">
                                            @error('product_price')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Product Manufacture</label>
                                            <select class="form-control" id="product_manufacturer" name="product_manufacturer">
                                                <option>-Product Manufacturer-</option>
                                                <option value="1">HCO</option>
                                            </select>
                                            @error('product_manufacture')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="description">Product Description</label>
                                            <textarea class="form-control" id="product_description" name="product_description" placeholder="Enter Description"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                    </div>
                    <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="inventory-tab">
                        <div class="card card-primary">

                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="tier-pricing-tab">
                        <div class="card card-primary">

                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab4" role="tabpanel" aria-labelledby="compatibility-tab">
                        <div class="card card-primary">

                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <button type="reset" class="btn btn-primary">Reset</button>
                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>


    </form>
@stop