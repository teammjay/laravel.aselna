@extends('adminlte::page')

@section('title', 'Add Product')

@section('content_header')
    <h1>Add Product</h1>
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
                    <li class="nav-item">
                        <a class="nav-link" id="features-tab" data-toggle="pill" href="#tab5" role="tab" aria-controls="tab5" aria-selected="false">
                            Key Features
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="search-terms-tab" data-toggle="pill" href="#tab6" role="tab" aria-controls="tab6" aria-selected="false">
                            Search Terms
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="images-tab" data-toggle="pill" href="#tab7" role="tab" aria-controls="tab7" aria-selected="false">
                            Images
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tags-tab" data-toggle="pill" href="#tab8" role="tab" aria-controls="tab8" aria-selected="false">
                            Tags
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="stores-tab" data-toggle="pill" href="#tab9" role="tab" aria-controls="tab9" aria-selected="false">
                            Stores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="channel-tab" data-toggle="pill" href="#tab10" role="tab" aria-controls="tab10" aria-selected="false">
                            Channel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="spintex-tab" data-toggle="pill" href="#tab11" role="tab" aria-controls="tab11" aria-selected="false">
                            Spintex
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="custom-tabs-one-tabContent">
                    <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="general-tab">
                        <form method="POST" id="frmtab1">
                            <input type="hidden" name="tab" value="1">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card-body border">
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
                                                        <option></option>
                                                        <option value="1">Leather Case</option>
                                                    </select>
                                                    @error('product_type')
                                                    <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="name">Product Condition</label>
                                                    <select class="form-control" id="product_condition" name="product_condition">
                                                        <option></option>
                                                        <option value="new">New</option>
                                                    </select>
                                                    @error('product_type')
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
                                                        <option></option>
                                                        <option value="1">HCO</option>
                                                    </select>
                                                    @error('product_manufacture')
                                                    <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="name">Product Status</label>
                                                    <select class="form-control" id="product_status" name="product_status">
                                                        <option></option>
                                                        <option value="active"> Active </option>
                                                        <option value="discontinued"> Discontinued </option>
                                                        <option value="out_of_stock"> Out of stock </option>
                                                    </select>
                                                    @error('product_status')
                                                    <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>


                                        </div>
                                    </div>
                                    <div class="row pt-2">
                                        <div class="col-md-12">

                                            <div class="card-body border">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label for="description">Product Description</label>
                                                            <textarea class="form-control" id="product_description" name="product_description" rows="10" placeholder="Enter Description"></textarea>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                    <div class="row pt-2">
                                        <div class="col-md-12">

                                            <div class="card-body border">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="name">Product Price</label>
                                                            <input type="text" class="form-control" id="product_price" name="product_price" placeholder="00.00">
                                                            @error('product_price')
                                                            <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="name">Wholesale Price</label>
                                                            <input type="text" class="form-control" id="product_wholesale_price" name="product_wholesale_price" placeholder="00.00">
                                                            @error('product_wholesale_price')
                                                            <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="name">Marketplace Price</label>
                                                            <input type="text" class="form-control" id="product_marketplace_price" name="product_marketplace_price" placeholder="00.00">
                                                            @error('product_marketplace_price')
                                                            <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="name">Mrsp</label>
                                                            <input type="text" class="form-control" id="product_mrsp" name="product_mrsp" placeholder="00.00">
                                                            @error('product_mrsp')
                                                            <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card-body border">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="name">Dimensions</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="dimensions_width" name="dimensions_width" placeholder="">
                                                        <input type="text" class="form-control mr-1" id="dimensions_height" name="dimensions_height" placeholder="">
                                                        <input type="text" class="form-control mr-1" id="dimensions_length" name="dimensions_length" placeholder="">
                                                        <span>Inches</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="name">Weight</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="product_weight" name="product_weight" placeholder="">
                                                        <select class="form-control w-25" id="product_weight_unit" name="product_weight_unit">
                                                            <option value="kg"> Kg </option>
                                                            <option value="oz"> OZ </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="name">Package Dimensions</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="package_dimensions_width" name="package_dimensions_width" placeholder="">
                                                        <input type="text" class="form-control mr-1" id="package_dimensions_height" name="package_dimensions_height" placeholder="">
                                                        <input type="text" class="form-control mr-1" id="package_dimensions_length" name="package_dimensions_length" placeholder="">
                                                        <span>Inches</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="name">Package Weight</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="package__weight" name="package__weight" placeholder="">
                                                        <select class="form-control w-25" id="package_weight_unit" name="package_weight_unit">
                                                            <option value="kg"> Kg </option>
                                                            <option value="oz"> OZ </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body border mt-2">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="name">Target Audionces</label>
                                                    <div class="d-flex">
                                                        <input type="checkbox" class="mr-1" name="target_aud" value="unisex"><span class="mr-3">Unisex</span>
                                                        <input type="checkbox" class="mr-1" name="target_aud" value="men"><span class="mr-3">Men</span>
                                                        <input type="checkbox" class="mr-1" name="target_aud" value="women"><span class="mr-3">Women</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="name">Color</label>
                                                    <select class="form-control" name="product_color" id="product_color">
                                                        <option></option>
                                                        <option value="red">Red</option>
                                                        <option value="green">Green</option>
                                                        <option value="blue">Blue</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData('1')">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>

                    </div>
                    <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="inventory-tab">
                        <form method="POST" id="frmtab2">
                            <input type="hidden" name="tab" value="2">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card-body border">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="name">Quantity</label>
                                                    <input type="text" class="form-control" id="product_qty" name="product_qty" placeholder="100">
                                                    @error('product_qty')
                                                    <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="name">Reorder Quantity</label>
                                                    <input type="text" class="form-control" id="product_reorder_qty" name="product_reorder_qty" placeholder="100">
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="name">Pick Location</label>
                                                    <select class="form-control" name="product_pick_location" id="product_pick_location">
                                                        <option></option>
                                                    </select>

                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="name">Issue Inventory Location</label>
                                                    <select class="form-control" name="issue_inventory_location" id="issue_inventory_location">
                                                        <option></option>
                                                    </select>

                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="name">Receive Inventory Location</label>
                                                    <select class="form-control" name="receive_inventory_location" id="receive_inventory_location">
                                                        <option></option>
                                                    </select>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="card card-primary">
                                        <div class="card-header">
                                            Inventory
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-bordered">
                                                <thead>
                                                <tr>
                                                    <th>Code</th>
                                                    <th>Label</th>
                                                    <th>Quantity on hand</th>
                                                    <th>Quantity allocated</th>
                                                    <th>Quantity available</th>
                                                </tr>
                                                </thead>
                                                <tbody>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData('2')">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="tier-pricing-tab">
                        <form method="POST" id="frmtab3">
                            <input type="hidden" name="tab" value="3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            Retail
                                        </div>
                                        <div class="card-body">
                                            <button class="btn btn-secondary" type="button" id="add_retail"><i class="fa fa-plus-square"></i> Add</button>
                                            <table class="table table-bordered mt-2" id="table_retail">
                                                <thead>
                                                    <tr>
                                                        <th>Quantity</th>
                                                        <th>Price</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><input type="text" class="form-control" name="retail_qty[]"></td>
                                                    <td><input type="text" class="form-control" name="retail_price[]"></td>
                                                    <td></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header">
                                            Wholesale
                                        </div>
                                        <div class="card-body">
                                            <button class="btn btn-secondary" type="button" id="add_wholesale"><i class="fa fa-plus-square"></i> Add</button>
                                            <table class="table table-bordered mt-2" id="table_wholesale">
                                                <thead>
                                                <tr>
                                                    <th>Quantity</th>
                                                    <th>Price</th>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><input type="text" class="form-control" name="wholesale_qty[]"></td>
                                                    <td><input type="text" class="form-control" name="wholesale_price[]"></td>
                                                    <td></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData('3')">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab4" role="tabpanel" aria-labelledby="compatibility-tab">
                        <form method="POST" id="frmtab4">
                            <input type="hidden" name="tab" value="4">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            Retail
                                        </div>
                                        <div class="card-body">
                                            <button class="btn btn-secondary" type="button" id="add_retail"><i class="fa fa-plus-square"></i> Add</button>
                                            <table class="table table-bordered mt-2" id="table_retail">
                                                <thead>
                                                <tr>
                                                    <th>Quantity</th>
                                                    <th>Price</th>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><input type="text" class="form-control" name="retail_qty[]"></td>
                                                    <td><input type="text" class="form-control" name="retail_price[]"></td>
                                                    <td></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            Incompatible Device
                                        </div>
                                        <div class="card-body">
                                            <button class="btn btn-secondary" type="button" id="add_device"><i class="fa fa-plus-square"></i> Add</button>
                                            <table class="table table-bordered mt-2" id="table_incompatible_device">
                                                <thead>
                                                <tr>
                                                    <th>Model Name</th>
                                                    <th>Name</th>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td><input type="text" class="form-control" name="model_name[]"></td>
                                                    <td><input type="text" class="form-control" name="device_name[]"></td>
                                                    <td></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData('4')">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab5" role="tabpanel" aria-labelledby="features-tab">
                        <form method="POST" id="frmtab5">
                            <input type="hidden" name="tab" value="5">
                            <button type="button" class="btn btn-secondary" id="add_feature"> <i class="fa fa-plus-square"></i> Add</button>
                            <div class="card card-body border mt-2">
                                <table class="table table-bordered" id="features_table">
                                    <thead>
                                        <tr>
                                            <th>Value</th>
                                            <th>Sort</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><textarea class="form-control" name="features[]"></textarea></td>
                                            <td><input type="text" class="form-control" name="feature_sort_order[]" value="1"></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData('5')">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab6" role="tabpanel" aria-labelledby="search-terms-tab">
                        <form method="POST" id="frmtab5">
                            <input type="hidden" name="tab" value="6">
                            <button type="button" class="btn btn-secondary" id="add_search_terms"><i class="fa fa-plus-square"></i> Add</button>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <td width="80%"><input type="text" class="form-control" name="search_term"></td>
                                                <td width="20%"><input type="text" class="form-control" name="search_term_number"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData('6')">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab7" role="tabpanel" aria-labelledby="images-tab">
                    </div>
                    <div class="tab-pane fade" id="tab8" role="tabpanel" aria-labelledby="tags-tab">
                        <form method="POST" id="frmtab8">
                            <input type="hidden" name="tab" value="8">
                            <button type="button" class="btn btn-secondary" id="add_tag"><i class="fa fa-plus-square"></i> Add</button>
                            <div class="form-group mt-3">
                                <textarea name="tags" class="form-control"  rows="10"></textarea>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData('7')">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab9" role="tabpanel" aria-labelledby="stores-tab">
                        <form method="POST" id="frmtab9">
                            <input type="hidden" name="tab" value="9">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <thead>
                                            <th>Store</th>
                                            <th>Active</th>
                                            <th>Store Products, Store Category</th>
                                        </thead>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData('8')">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab10" role="tabpanel" aria-labelledby="channel-tab">
                        <form method="POST" id="frmtab10">
                            <input type="hidden" name="tab" value="10">
                            <button type="button" class="btn btn-secondary" id="create_channel"><i class="fa fa-plus-square"></i> Create</button>
                            <table class="table table-bordered mt-2">
                                <thead>
                                <tr>
                                    <th>Channel</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                                </thead>
                            </table>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData('9')">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab11" role="tabpanel" aria-labelledby="spitex-tab">
                        <form method="POST" id="frmtab10">
                            <input type="hidden" name="tab" value="10">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Title Template</label>
                                        <textarea name="title_template" class="form-control"  rows="10"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Description Template</label>
                                        <textarea name="description_template" class="form-control"  rows="10"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData('tab10')">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
@stop

@section('adminlte_js')
<script>
    $(document).ready(function() {

        function saveTabData(tabId) {
            let formData = new FormData(document.getElementById('frmtab' + tabId));

            fetch('{{ route('catalog.save') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Switch to the next tab
                       /* let nextTab = new bootstrap.Tab(document.querySelector(`#${tabId}-tab`).nextElementSibling);
                        nextTab.show();*/
                    } else {
                        alert('Error saving data for ' + tabId);
                    }
                });
        }

        // Add new field group
        $('#add_feature').click(function() {
            $('#features_table tbody').append(`
                    <tr class="field-group">
                        <td><textarea class="form-control" name="features[]"></textarea></td>
                        <td><input type="text" class="form-control" name="feature_sort_order[]" value=""></td>
                        <td><button type="button" class="btn btn-danger remove-feature"> <i class="fas fa-fw fa-trash"></i></button></td>
                    </tr>
                `);

        });

        // Remove field group
        $(document).on('click', '.remove-feature', function() {
            $(this).parents('tr.field-group').remove();
        });


        // Add new retail price
        $('#add_retail').click(function() {
            $('#table_retail tbody').append(`
                    <tr class="field-group">
                        <td><input type="text" class="form-control" name="retail_qty[]"></td>
                        <td><input type="text" class="form-control" name="retail_price[]"></td>
                        <td><button type="button" class="btn btn-danger remove-retail"> <i class="fas fa-fw fa-trash"></i></button></td>
                    </tr>
                `);

        });

        // Remove retail price group
        $(document).on('click', '.remove-retail', function() {
            $(this).parents('tr.field-group').remove();
        });

        // Add new retail price
        $('#add_wholesale').click(function() {
            $('#table_wholesale tbody').append(`
                    <tr class="field-group">
                        <td><input type="text" class="form-control" name="wholesale_qty[]"></td>
                        <td><input type="text" class="form-control" name="wholesale_price[]"></td>
                        <td><button type="button" class="btn btn-danger remove-wholesale"> <i class="fas fa-fw fa-trash"></i></button></td>
                    </tr>
                `);

        });

        // Remove retail price group
        $(document).on('click', '.remove-wholesale', function() {
            $(this).parents('tr.field-group').remove();
        });


        // Add new device
        $('#add_device').click(function() {
            $('#table_incompatible_device tbody').append(`
                    <tr class="field-group">
                        <td><input type="text" class="form-control" name="model_name[]"></td>
                        <td><input type="text" class="form-control" name="device_name[]"></td>
                        <td><button type="button" class="btn btn-danger remove-device"> <i class="fas fa-fw fa-trash"></i></button></td>
                    </tr>
                `);

        });

        // Remove retail price group
        $(document).on('click', '.remove-device', function() {
            $(this).parents('tr.field-group').remove();
        });

    });
</script>
@stop
