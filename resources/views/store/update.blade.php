@extends('adminlte::page')

@section('title', 'Edit Store')

@section('content_header')
    <h1>Edit Store</h1>
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
                    <a class="nav-link" id="message-tab" data-toggle="pill" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                        Message
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="address-tab" data-toggle="pill" href="#tab3" role="tab" aria-controls="tab3" aria-selected="false">
                        Address
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="payment-tab" data-toggle="pill" href="#tab4" role="tab" aria-controls="tab4" aria-selected="false">
                        Payment
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="shipping-tab" data-toggle="pill" href="#tab5" role="tab" aria-controls="tab5" aria-selected="false">
                        Shipping
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="credential-tab" data-toggle="pill" href="#tab6" role="tab" aria-controls="tab6" aria-selected="false">
                        Credential
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="media-tab" data-toggle="pill" href="#tab7" role="tab" aria-controls="tab7" aria-selected="false">
                        Media
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pages-tab" data-toggle="pill" href="#tab8" role="tab" aria-controls="tab8" aria-selected="false">
                        Pages
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="listingbar-tab" data-toggle="pill" href="#tab9" role="tab" aria-controls="tab9" aria-selected="false">
                        Listing
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="categories-tab" data-toggle="pill" href="#tab10" role="tab" aria-controls="tab10" aria-selected="false">
                        Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="logistics-tab" data-toggle="pill" href="#tab11" role="tab" aria-controls="tab11" aria-selected="false">
                        Logistics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="devices-tab" data-toggle="pill" href="#tab11" role="tab" aria-controls="tab11" aria-selected="false">
                        Devices
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="custom-tabs-one-tabContent">
                <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="general-tab">
                    <form method="POST" id="frmtab1">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="id">ID</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="store_id" name="store_id" value="{{ old('id',$store->id) }}" placeholder="Enter product title" readonly="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="name">Name</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name',$store->name) }}" placeholder="Enter store name">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="store_type">Store Type</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-control" name="store_type_id" id="store_type_id">
                                            <option value="1" {{ 1 == $store->store_type_id ? 'selected' : '' }}>Ebay</option>
                                            <option value="2" {{ 2 == $store->store_type_id ? 'selected' : '' }}>Amazon</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="store_status">Store Status</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-control" name="store_status" id="store_status">
                                            <option value="1" {{ 1 == $store->store_status ? 'selected' : '' }}>Opened</option>
                                            <option value="0" {{ 0 == $store->store_status ? 'selected' : '' }}>Closed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="marketplace_name">Marketplace Name</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="marketplace_name" name="marketplace_name" value="{{ old('marketplace_name',$store->marketplace_name) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="brand_name">Brand Name</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="brand_name" name="brand_name" value="{{ old('brand_name',$store->brand_name) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="store_url">Store Url</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="store_url" name="store_url" value="{{ old('store_url',$store->store_url) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="sku_generation_type">Sku Generation Type</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-control" name="sku_generation_type" id="sku_generation_type">
                                            <option value="1">Product Code With Hash ID</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="profile_rate">Profile Rate</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="profile_rate" name="profile_rate" value="{{ old('profile_rate',$store->profile_rate) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="default_brand">Default Brand</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-control" name="default_brand" id="default_brand">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="short_code">Short Code</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="short_code" name="short_code" value="{{ old('short_code',$store->short_code) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="wholesale">Wholesale</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="wholesale" name="wholesale">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="auto_generate_listing">Auto generate listing</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="auto_generate_listing" name="auto_generate_listing">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="max_listing_inventory_quantity">Max listing inventory quantity</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="max_listing_inventory_quantity" name="max_listing_inventory_quantity" value="{{ old('max_listing_inventory_quantity',$store->max_listing_inventory_quantity) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="productaltimagenumber">Store.product altimage number</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="productaltimagenumber" name="productaltimagenumber" value="{{ old('productaltimagenumber',$store->productaltimagenumber) }}">
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="listing_template_id">Listing Templage</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-control" name="listing_template_id" id="listing_template_id">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="upc_usage_type">Upc usage type</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-control" name="upc_usage_type" id="upc_usage_type">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="upc_default_company_prefix">Upc default company prefix</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="upc_default_company_prefix" name="upc_default_company_prefix" value="{{ old('upc_default_company_prefix',$store->upc_default_company_prefix) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="working_hours">Working hours</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="working_hours" name="working_hours" value="{{ old('working_hours',$store->working_hours) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="test_store">Test store</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="test_store" name="test_store">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="default_listing_quantity">Default listing quantity</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="default_listing_quantity" name="default_listing_quantity" value="{{ old('default_listing_quantity',$store->default_listing_quantity) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="ad_account">Ad account</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="ad_account" name="ad_account">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="linked_store_id">Linked store</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="linked_store_id" name="linked_store_id" value="{{ old('linked_store_id',$store->linked_store_id) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="order_cutoff_time">Order cutoff time</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="order_cutoff_time" name="order_cutoff_time" value="{{ old('order_cutoff_time',$store->order_cutoff_time) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="logo_code">Logo code</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="logo_code" name="logo_code" value="{{ old('logo_code',$store->logo_code) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="logo_code_base64">Logo code base64</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="logo_code_base64" name="logo_code_base64" value="{{ old('logo_code_base64',$store->logo_code_base64) }}">
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="fulfilment_node_id">Fullfilment node id</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="fulfilment_node_id" name="fulfilment_node_id" value="{{ old('fulfilment_node_id',$store->fulfilment_node_id) }}">
                                    </div>
                                </div>

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="default_shipping_amount">Default shipping amount</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="default_shipping_amount" name="default_shipping_amount" value="{{ old('default_shipping_amount',$store->default_shipping_amount) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="free_shipping_amount">Free shipping amount</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="free_shipping_amount" name="free_shipping_amount" value="{{ old('free_shipping_amount',$store->free_shipping_amount) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="random_title_keyword_level">Random title keyword level</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="random_title_keyword_level" name="random_title_keyword_level" value="{{ old('random_title_keyword_level',$store->random_title_keyword_level) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="download_listings">Download listings</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="download_listings" name="download_listings">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="download_buy_box">Download buy box</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="download_buy_box" name="download_buy_box">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="download_reviews">Download reviews</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="download_reviews" name="download_reviews">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="generate_device_details">Generate device details</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="generate_device_details" name="generate_device_details">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="label_order_prefix">Label order prefix</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="label_order_prefix" name="label_order_prefix" value="{{ old('label_order_prefix',$store->label_order_prefix) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(1)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>

                </div>
                <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="message-tab">
                    <form method="POST" id="frmtab2">

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(2)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="address-tab">
                    <form method="POST" id="frmtab3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="first_name">First Name</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name',$store->first_name) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="last_name">Last Name</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name',$store->last_name) }}">
                                    </div>
                                </div><div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="company">Company</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="company" name="company" value="{{ old('company',$store->company) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="address_1">Address Line1</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="address_1" name="address_1" value="{{ old('address_1',$store->address_1) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="address_2">Address Line2</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="address_2" name="address_2" value="{{ old('address_2',$store->address_2) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="address_3">Address Line3</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="address_3" name="address_3" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="city">City</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="city" name="city" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="country">Country</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="country" name="country" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="district">District</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="district" name="district" value="">
                                    </div>
                                </div><div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="state">State Or Region</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="state" name="state" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="postal_code">Postal Code</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="country_code">Country Code</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="country_code" name="country_code" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="phone">Phone</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="phone" name="phone" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="email">Email</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="email" name="email" value="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(3)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab4" role="tabpanel" aria-labelledby="compatibility-tab">
                    <form method="POST" id="frmtab4">


                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(4)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab5" role="tabpanel" aria-labelledby="features-tab">
                    <form method="POST" id="frmtab5">

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(5)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab6" role="tabpanel" aria-labelledby="credential-tab">
                    <form method="POST" id="frmtab6">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="app_id">App Id</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="app_id" name="app_id" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="dev_id">Dev Id</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="dev_id" name="dev_id" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="certificate">Certificate</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="certificate" name="certificate" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="authorized">Authorized</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="authorized" name="authorized" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="user_id">User ID</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="user_id" name="user_id" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="api_key">API key</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="api_key" name="api_key" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="password">Password</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="password" name="password" value="">
                                    </div>
                                </div>

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="access_key">Access Key</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="access_key" name="access_key" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="secret_key">Secret Key</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="secret_key" name="secret_key" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="app_name">App Name</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="app_name" name="app_name" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="app_version">App Version</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="app_version" name="app_version" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="service_url">Service Url</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="service_url" name="service_url" value="">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(6)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab7" role="tabpanel" aria-labelledby="images-tab">
                </div>
                <div class="tab-pane fade" id="tab8" role="tabpanel" aria-labelledby="tags-tab">
                    <form method="POST" id="frmtab8">

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(8)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab9" role="tabpanel" aria-labelledby="stores-tab">
                    <form method="POST" id="frmtab9">

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(9)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab10" role="tabpanel" aria-labelledby="channel-tab">
                    <form method="POST" id="frmtab10">

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(10)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab11" role="tabpanel" aria-labelledby="spitex-tab">
                    <form method="POST" id="frmtab11">

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(11)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab12" role="tabpanel" aria-labelledby="spitex-tab">
                    <form method="POST" id="frmtab12">

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(12)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        var store_id = "{{ $store->id ?? '' }}"; // Passed from controller

        function saveStoreTabData(tabId) {
            const form = document.querySelector("#frmtab"+ tabId);
            let formData = new FormData(form);
            //formData.append('store_id', store_id);
            formData.append('tab', tabId);

            fetch('{{ route('store.save') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    if (data.errors) {

                        // Display validation errors
                        Object.keys(data.errors).forEach((field) => {
                            let errorMessage = data.errors[field][0];
                            document.querySelector(`#${field}`).classList.add('is-invalid');
                            document.querySelector(`#${field} ~ .text-danger`).innerText = errorMessage;
                        });
                    } else {
                        alert(data.message);
                        location.reload();
                    }
                });
        }

        function finalizeForm() {

            const form = document.querySelector("#frmtab1");
            let formData = new FormData(form);
            formData.append('store_id', store_id);
            formData.set('store_status', 'active');

            fetch('{{ route('store.final') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Store saved successfully!');
                        window.location.href = "{{ route('store.list') }}";
                    } else {
                        alert('Error submiting the record');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        $(document).ready(function() {

            $('.pro-tags').select2();



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


            // Add new device
            $('#add_title_keyword').click(function() {
                $('#table_title_keywords tbody').append(`
                    <tr class="field-group">
                        <td width="70%"><input type="text" class="form-control" name="title_keyword[]" value=""></td>
                        <td width="20%"><input type="text" class="form-control" name="title_priority[]" value=""></td>
                        <td width="10%"><button type="button" class="btn btn-danger remove-keyword"> <i class="fas fa-fw fa-trash"></i></button></td>
                     </tr>
                `);

            });

            // Remove retail price group
            $(document).on('click', '.remove-keyword', function() {
                $(this).parents('tr.field-group').remove();
            });

        });
    </script>
@stop
