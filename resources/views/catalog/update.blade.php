@extends('adminlte::page')

@section('title', 'Edit Product')

@section('content_header')
    <h1>Edit Product</h1>
@stop

@section('content')
    <form action="{{ route('catalog.update', $product->product_id) }}" method="POST">
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
                                        <input type="text" class="form-control" id="product_title" name="product_title" value="{{ old('product_title', $product->product_title) }}" placeholder="Enter product title">
                                        @error('product_title')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Product Code</label>
                                        <input type="text" class="form-control" id="product_sku" name="product_sku" value="{{ old('product_sku', $product->product_sku) }}" placeholder="Enter product code">
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
                                            <option value="1"  {{ 1 == $product->product_type ? 'selected' : '' }}>Leather Case</option>
                                        </select>
                                        @error('product_type')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Product Price</label>
                                        <input type="text" class="form-control" id="product_price" name="product_price" value="{{ old('product_price', $product->product_price) }}" placeholder="Enter product price">
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
                                            <option value="1" {{ 1 == $product->product_manufacturer ? 'selected' : '' }}>HCO</option>
                                        </select>
                                        @error('product_manufacture')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="description">Product Description</label>
                                        <textarea class="form-control" id="product_description" name="product_description" placeholder="Enter Description">{{ old('product_description', $product->product_description) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <button type="submit" class="btn btn-success">Submit</button>
                            <button type="reset" class="btn btn-primary">Reset</button>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="inventory-tab">
                        <div class="card card-primary">

                        </div>
                    </div>
                </div>
            </div>
        </div>


    </form>
@stop