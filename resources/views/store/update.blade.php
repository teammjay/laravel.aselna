@extends('adminlte::page')

@section('title', 'Add / Edit Store')

@section('content_header')
    <h1>{{ ($action == 'Edit') ? 'Edit Store' : 'Add Store' }}</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <style>
        .nav-link {
            padding: 0.5rem 0.9rem !important;
        }
        ul.manufacturer-list{list-style: none;}
        ul.manufacturer-list li{display: inline-block; width:12%; margin-bottom: 10px;}
        .select2-container--default .select2-dropdown .select2-search__field:focus, .select2-container--default .select2-search--inline .select2-search__field:focus {
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
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__display{color:#333;}
    </style>
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
                    <a class="nav-link" id="devices-tab" data-toggle="pill" href="#tab12" role="tab" aria-controls="tab12" aria-selected="false">
                        Devices
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="properties-tab" data-toggle="pill" href="#tab13" role="tab" aria-controls="tab13" aria-selected="false">
                        Properties
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="manufacturer-tab" data-toggle="pill" href="#tab14" role="tab" aria-controls="tab14" aria-selected="false">
                        Manufacturer
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
                                        <label for="store_id">ID</label>
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
                                        <span class="error-message text-danger" id="name-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="store_type_id">Store Type</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-control" name="store_type_id" id="store_type_id">
                                            @if(count($store_types ?? []) > 0)
                                                @foreach($store_types as $stype)
                                                    <option value="{{ $stype->id }}" {{ $stype->id == $store->store_type_id ? 'selected' : '' }}>{{ $stype->name }}</option>
                                                @endforeach
                                            @endif
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
                                        <span class="error-message text-danger" id="marketplace_name-error"></span>
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
                                        <span class="error-message text-danger" id="store_url-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="sku_generation_type">Sku Generation Type</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-control" name="sku_generation_type" id="sku_generation_type">
                                            <option value=""></option>
                                            <option value="1" {{ ($store->sku_generation_type == 1) ? 'selected' : '' }}>Sequence</option>
                                            <option value="2" {{ ($store->sku_generation_type == 2) ? 'selected' : '' }}>Hash ID</option>
                                            <option value="3" {{ ($store->sku_generation_type == 3) ? 'selected' : '' }}>Product Code With Hash ID</option>
                                            <option value="4" {{ ($store->sku_generation_type == 4) ? 'selected' : '' }}>Product Code</option>
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
                                        <input type="checkbox" id="wholesale" name="wholesale" {{ ($store->wholesale == 1) ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="auto_generate_listings">Auto generate listing</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="auto_generate_listings" name="auto_generate_listings" {{ ($store->auto_generate_listings == 1) ? 'checked' : '' }}>
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
                                            @if(count($listing_templates ?? []) > 0)
                                                @foreach($listing_templates as $ltemplate)
                                                    <option value="{{ $ltemplate->id }}" {{ $ltemplate->id == $store->listing_template_id ? 'selected' : '' }}>{{ $ltemplate->name }}</option>
                                                @endforeach
                                            @endif
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
                                            <option value="0" {{ ($store->upc_usage_type == 0) ? 'selected' : '' }}>None</option>
                                            <option value="1" {{ ($store->upc_usage_type == 1) ? 'selected' : '' }}>Storeownupc</option>
                                            <option value="2" {{ ($store->upc_usage_type == 2) ? 'selected' : '' }}>Upcpool</option>
                                            <option value="3" {{ ($store->upc_usage_type == 3) ? 'selected' : '' }}>Product</option>
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
                                        <input type="checkbox" id="test_store" name="test_store" {{ ($store->test_store == 1) ? 'checked' : '' }}>
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
                                        <input type="checkbox" id="ad_account" name="ad_account" {{ ($store->ad_account == 1) ? 'checked' : '' }}>
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
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="auto_submit_listing">Auto Submit listing</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="auto_submit_listing" name="auto_submit_listing" {{ ($store->auto_submit_listing == 1) ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="re_submit_listing">Re Submit listing</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="re_submit_listing" name="re_submit_listing" {{ ($store->re_submit_listing == 1) ? 'checked' : '' }}>
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
                                        <input type="checkbox" id="download_listings" name="download_listings" {{ ($store->download_listings == 1) ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="download_buy_box">Download buy box</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="download_buy_box" name="download_buy_box" {{ ($store->download_buy_box == 1) ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="download_reviews">Download reviews</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="download_reviews" name="download_reviews" {{ ($store->download_reviews == 1) ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="generate_device_details">Generate device details</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="generate_device_details" name="generate_device_details" {{ ($store->generate_device_details == 1) ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="skip_device_pages">Skip device pages</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="skip_device_pages" name="skip_device_pages" {{ ($store->skip_device_pages == 1) ? 'checked' : '' }}>
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
                    <button type="button" class="btn btn-secondary" id="add_message" data-toggle="modal" data-target="#addMessageModal" ><i class="fa fa-plus-square"></i> Create</button>
                    <table class="table table-bordered mt-2">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Incoming Account</th>
                            <th>Outgoing Account</th>
                            <th>API Account</th>
                            <th>Active</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                            @if(count($storeMessageAccount ?? []) > 0)
                                @foreach($storeMessageAccount as $maccount)
                                    <tr class="field-group">
                                        <td>{{ $maccount->name }}</td>
                                        <td>{{ $maccount->email }}</td>
                                        <td>
                                            {{ (isset($maccount->incomingAccount)) ? 'Email: '.$maccount->incomingAccount->email : '' }}<br>
                                            {{ (isset($maccount->incomingAccount)) ? 'User ID: '.$maccount->incomingAccount->user_id : '' }}
                                        </td>
                                        <td>
                                            {{ (isset($maccount->outgoingAccount)) ? 'Email: '.$maccount->outgoingAccount->email : '' }}<br>
                                            {{ (isset($maccount->outgoingAccount)) ? 'User ID: '.$maccount->outgoingAccount->user_id : '' }}
                                        </td>
                                        <td>{{ $maccount->api_account == 1 ? 'Yes' : 'No' }}</td>
                                        <td>{{ $maccount->active == 1 ? 'Yes' : 'No' }}</td>
                                        <td>

                                            <button type="button" class="btn btn-primary edit-message-account" data-id="{{ $maccount->id }}" title="Edit"> <i class="fas fa-fw fa-pencil-alt"></i></button>
                                            <button type="button" class="btn btn-danger remove-message-account" data-id="{{ $maccount->id }}"> <i class="fas fa-fw fa-trash"></i></button></td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
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
                                        <input type="text" class="form-control" id="address_3" name="address_3" value="{{ old('address_3',$store->address_3) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="city">City</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="city" name="city" value="{{ old('city',$store->city) }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="country">Country</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="country" name="country" value="{{ old('country',$store->country) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="district">District</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="district" name="district" value="{{ old('district',$store->district) }}">
                                    </div>
                                </div><div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="state">State Or Region</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="state" name="state" value="{{ old('state',$store->state) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="postal_code">Postal Code</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ old('postal_code',$store->postal_code) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="country_code">Country Code</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="country_code" name="country_code" value="{{ old('country_code',$store->country_code) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="phone">Phone</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone',$store->phone) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="email">Email</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="email" name="email" value="{{ old('email',$store->email) }}">
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
                <div class="tab-pane fade" id="tab4" role="tabpanel" aria-labelledby="payment-tab">
                    <form method="POST" id="frmtab4">
                        <button type="button" class="btn btn-secondary" id="store_payment" data-toggle="modal" data-target="#exampleModal" ><i class="fa fa-plus-square"></i> Create</button>
                        <table class="table table-bordered mt-2">
                            <thead>
                            <tr>
                                <th>Payment Type</th>
                                <th>Payment Method</th>
                                <th>Email</th>
                                <th></th>
                            </tr>
                            </thead>
                        </table>

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(4)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab5" role="tabpanel" aria-labelledby="shipping-tab">
                    <form method="POST" id="frmtab5">
                        <button type="button" class="btn btn-secondary" id="create_shipping" data-toggle="modal" data-target="#addShippingModal" ><i class="fa fa-plus-square"></i> Create</button>
                        <table class="table table-bordered mt-2">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(count($storeShippingService ?? []) > 0)
                                    @foreach($storeShippingService as $shipping)
                                        <tr class="field-group">
                                            <td>{{ $shipping->name }}</td>
                                            <td><button type="button" class="btn btn-danger remove-shipping" data-id="{{ $shipping->id }}"> <i class="fas fa-fw fa-trash"></i></button></td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                        {{--<div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(5)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>--}}
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
                                        <input type="text" class="form-control" id="app_id" name="app_id" value="{{ old('app_id',$store->app_id) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="dev_id">Dev Id</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="dev_id" name="dev_id" value="{{ old('dev_id',$store->dev_id) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="certificate">Certificate</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="certificate" name="certificate" value="{{ old('certificate',$store->certificate) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="authorized">Authorized</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="authorized" name="authorized" {{ 1 == $store->authorized ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="seller_id">Seller ID</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="seller_id" name="seller_id" value="{{ old('seller_id',$store->seller_id) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="user_id">User ID</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="user_id" name="user_id" value="{{ old('user_id',$store->user_id) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="api_key">API key</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="api_key" name="api_key" value="{{ old('api_key',$store->api_key) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="password">Password</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="password" name="password" value="{{ old('password',$store->password) }}">
                                    </div>
                                </div>

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="access_key">Access Key</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="access_key" name="access_key" value="{{ old('access_key',$store->access_key) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="secret_key">Secret Key</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="secret_key" name="secret_key" value="{{ old('secret_key',$store->secret_key) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="app_name">App Name</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="app_name" name="app_name" value="{{ old('app_name',$store->app_name) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="app_version">App Version</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="app_version" name="app_version" value="{{ old('app_version',$store->app_version) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="service_url">Service Url</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="service_url" name="service_url" value="{{ old('service_url',$store->service_url) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="service_url">File system Host</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select type="text" class="form-control" id="file_system_id" name="file_system_id">
                                            <option value=""></option>
                                            @if(count($fileSystems ?? []) > 0)
                                                @foreach($fileSystems as $fs)
                                                    <option value="{{ $fs->id }}" {{ $fs->id == $store->file_system_id ? 'selected' : '' }}>{{ $fs->host }} ({{ $fs->url_base_path }})</option>
                                                @endforeach
                                            @endif
                                        </select>
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
                <div class="tab-pane fade" id="tab7" role="tabpanel" aria-labelledby="media-tab">
                    <form method="POST" id="frmtab7" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="image_base_path">Image Base Path</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="image_base_path" name="image_base_path" value="{{ old('image_base_path',$store->image_base_path) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="alter_image_files">Modify Image Files</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="alter_image_files" name="alter_image_files" {{ 1 == $store->alter_image_files ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="resize_images">Resize Images</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="resize_images" name="resize_images" value="{{ old('image_base_path',$store->image_base_path) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="apply_watermark">Apply Watermark</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="apply_watermark" name="apply_watermark" {{ 1 == $store->apply_watermark ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="watermark_image">Watermark Image</label>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="file" id="watermark_image" name="watermark_image" >
                                    </div>
                                    <div class="col-md-3">
                                        @if($store->watermark_image_id != '')
                                            <img src="{{ url('images/' . $store->watermark_image_id) }}" alt="{{ $store->watermark_image_id }}" width="70" height="70">
                                        @endif
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="watermark_transparency">Watermark Transparency</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="watermark_transparency" name="watermark_transparency" value="{{ old('watermark_transparency',$store->watermark_transparency) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="images_location">Images Location</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="images_location" name="images_location" value="{{ old('images_location',$store->images_location) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(7)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab8" role="tabpanel" aria-labelledby="pages-tab">
                        <button type="button" class="btn btn-secondary" id="add_pages" data-toggle="modal" data-target="#addPagesModal" ><i class="fa fa-plus-square"></i> Create</button>
                        <table class="table table-bordered mt-2">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>Page Type</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @if(count($storePages ?? []) > 0)
                                @foreach($storePages as $page)
                                    <tr class="field-group">
                                        <td>{{ $page->title }}</td>
                                        <td>{{ \App\Constants\PageTypes::getPageTypeName($page->page_type) }}</td>
                                        <td>
                                            <button type="button" class="btn btn-primary edit-page" data-id="{{ $page->id }}" title="Edit"> <i class="fas fa-fw fa-pencil-alt"></i></button>
                                            <button type="button" class="btn btn-danger remove-page" data-id="{{ $page->id }}" title="Remove"> <i class="fas fa-fw fa-trash"></i></button></td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                        {{--<div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(8)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            --}}{{--<button type="reset" class="btn btn-primary">Reset</button>--}}{{--
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>--}}
                </div>
                <div class="tab-pane fade" id="tab9" role="tabpanel" aria-labelledby="listingbar-tab">
                    {{--<form method="POST" id="frmtab9">

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(9)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            --}}{{--<button type="reset" class="btn btn-primary">Reset</button>--}}{{--
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>--}}
                </div>
                <div class="tab-pane fade" id="tab10" role="tabpanel" aria-labelledby="categories-tab">
                    <button type="button" class="btn btn-secondary" id="add_category" data-toggle="modal" data-target="#addCategoryModal" ><i class="fa fa-plus-square"></i> Create</button>
                    <button type="button" class="btn btn-secondary" id="copy_categories" data-toggle="modal" ><i class="fa fa-plus-square"></i> Copy Categories </button>
                    <button type="button" class="btn btn-secondary" id="sync_to_store" data-toggle="modal" ><i class="fa fa-plus-square"></i>Sync to Store</button>
{{--                    <button type="button" class="btn btn-secondary" id="add_category" data-toggle="modal" data-target="#selectCategoryModal" ><i class="fa fa-plus-square"></i> Add</button>--}}
                    <table class="table table-bordered mt-2">
                        <thead>
                        <tr>
                            <th>Code</th>
                            <th>Category Name</th>
                            <th>Url</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(count($storeCategories ?? []) > 0)
                            @foreach($storeCategories as $category)
                                <tr class="field-group">
                                    <td>{{ $category->code }}</td>
                                    <td>{{ $category->name }}</td>
                                    <td>{{ $category->url }}</td>
                                    <td>
                                        {{--<button type="button" class="btn btn-primary edit-page" data-id="{{ $page->id }}" title="Edit"> <i class="fas fa-fw fa-pencil-alt"></i></button>--}}
                                        <button type="button" class="btn btn-danger remove-category" data-id="{{ $category->id }}" title="Remove"> <i class="fas fa-fw fa-trash"></i></button></td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>

                </div>
                <div class="tab-pane fade" id="tab11" role="tabpanel" aria-labelledby="logistics-tab">
                    <button type="button" class="btn btn-secondary" id="add_logistic" data-toggle="modal" data-target="#addLogisticCenterModal" ><i class="fa fa-plus-square"></i> Create</button>
                    <table class="table table-bordered mt-2">
                        <thead>
                        <tr>
                            <th>Center Type</th>
                            <th>Logistic Center</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                            @if(count($storeLogisticCenters ?? []) > 0)
                                @foreach($storeLogisticCenters as $sCenter)
                                    <tr class="field-group">
                                        <td>{{ $sCenter->LogisticCenter->center_type }}</td>
                                        <td>{{ $sCenter->LogisticCenter->name }}</td>
                                        <td>
                                            {{--<button type="button" class="btn btn-primary edit-page" data-id="{{ $page->id }}" title="Edit"> <i class="fas fa-fw fa-pencil-alt"></i></button>--}}
                                            <button type="button" class="btn btn-danger remove-logistic-center" data-id="{{ $sCenter->id }}" title="Remove"> <i class="fas fa-fw fa-trash"></i></button></td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>

                </div>
                <div class="tab-pane fade" id="tab12" role="tabpanel" aria-labelledby="devices-tab">
                    <form method="POST" id="frmtab12">
                        <div class="row">
                            <div class="col-md-4">
                                @if(count($devices ?? []) > 0)
                                    @foreach($devices as $device)
                                        <div class="form-group">
                                            <input type="checkbox" name="devices[]" value="{{ $device->id }}" @if(is_array($storeDevices)){{ in_array($device->id,($storeDevices ?? [])) ? 'checked' : '' }} @endif>  {{ $device->name }}
                                        </div>

                                    @endforeach
                                @endif

                            </div>
                        </div>
                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(12)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab13" role="tabpanel" aria-labelledby="properties-tab">

                    <form method="POST" id="frmtab13">
                        <button type="button" class="btn btn-secondary" id="add_properties"> <i class="fa fa-plus-square"></i> Add</button>
                        <div class="card card-body border mt-2">
                            <table class="table table-bordered" id="properties_table">
                                <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Value</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if(count($propertyValues ?? []) > 0)
                                    @foreach($propertyValues as $pvalues)
                                        <tr class="field-group">
                                            <td>
                                                <select class="form-control" name="store_property_info_id[]">
                                                    <option value=""></option>
                                                    @if(count($propertyInfo ?? []) > 0)
                                                        @foreach($propertyInfo as $pinfo)
                                                            <option value="{{ $pinfo->id }}" {{ (isset($pvalues->store_property_info_id) && $pvalues->store_property_info_id == $pinfo->id) ? 'selected' : '' }}>{{ $pinfo->field_local_name }}</option>
                                                        @endforeach
                                                    @endif

                                                </select>
                                            </td>
                                            <td><input type="text" class="form-control" name="value[]" value="{{ $pvalues->value }}"></td>
                                            <td><button type="button" class="btn btn-danger remove-property"> <i class="fas fa-fw fa-trash"></i></button></td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td>
                                            <select class="form-control" name="store_property_info_id[]">
                                                <option value=""></option>
                                                @if(count($propertyInfo ?? []) > 0)
                                                    @foreach($propertyInfo as $pinfo)
                                                        <option value="{{ $pinfo->id }}">{{ $pinfo->field_local_name }}</option>
                                                    @endforeach
                                                @endif

                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control" name="value[]" value=""></td>
                                        <td></td>
                                    </tr>
                                @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(13)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>

                </div>

                <div class="tab-pane fade" id="tab14" role="tabpanel" aria-labelledby="manufacturer-tab">
                    <form method="POST" id="frmtab14">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label>Store Manufacturer</label>
                                    <select class="form-control store-manufacturer" name="manufacturer[]" multiple="multiple">
                                        @if(count($manufacturers ?? []) > 0)
                                            @foreach($manufacturers as $manufacturer)
                                                <option value="{{ $manufacturer->id }}" @if(is_array($storeManufacturers)){{ in_array($manufacturer->id,($storeManufacturers ?? [])) ? 'selected' : '' }}@endif>{{ $manufacturer->name }}</option>
                                            @endforeach
                                        @endif

                                    </select>
                                </div>
                               {{-- <ul class="manufacturer-list">
                                @if(count($manufacturers ?? []) > 0)
                                    @foreach($manufacturers as $manufacturer)
                                        <li>
                                            <input type="checkbox" name="devices[]" value="{{ $manufacturer->id }}" @if(is_array($storeManufacturers)){{ in_array($manufacturer->id,($storeManufacturers ?? [])) ? 'checked' : '' }} @endif>  {{ $manufacturer->name }}
                                        </li>

                                    @endforeach
                                @endif
                                </ul>--}}
                            </div>
                        </div>
                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(14)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('store.modal',['shipping_services'=> $shipping_services])
@stop

@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Lightbox2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script>
        var store_id = "{{ $store->id ?? '' }}"; // Passed from controller

        function saveStoreTabData(tabId) {
            const form = document.querySelector("#frmtab"+ tabId);
            let formData = new FormData(form);
            formData.append('store_id', store_id);
            formData.append('tab', tabId);

            $.ajax({
                type: 'POST',
                url: "{{ route('store.save') }}", // Adjust this URL to your endpoint
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                dataType:'json',
                success: function(response) {
                    // Display a success message
                    alert(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Validation error
                        var errors = xhr.responseJSON.errors;

                        // Clear previous errors
                        $('.error-message').text('');

                        // Loop through and display errors
                        $.each(errors, function(field, messages) {
                            $('#' + field + '-error').text(messages[0]); // Display the first error message
                        });
                    }
                }
            });

        }

        function finalizeForm() {

            const form = document.querySelector("#frmtab1");
            let formData = new FormData(form);
            formData.append('store_id', store_id);
            formData.set('store_status', 'active');

            $.ajax({
                type: 'POST',
                url: "{{ route('store.final') }}", // Adjust this URL to your endpoint
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                dataType:'json',
                success: function(response) {
                    // Display a success message
                    alert(response.message);
                    window.location.href = "{{ route('store.list') }}";
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Validation error
                        var errors = xhr.responseJSON.errors;

                        // Clear previous errors
                        $('.error-message').text('');

                        // Loop through and display errors
                        $.each(errors, function(field, messages) {
                            $('#' + field + '-error').text(messages[0]); // Display the first error message
                        });
                    }
                }
            });

        }

        function saveStoreShippingService() {
            const form = document.querySelector("#frmStoreShippingService");
            let formData = new FormData(form);
            formData.append('store_id', store_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('store.shippingservice') }}", // Adjust this URL to your endpoint
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                dataType:'json',
                success: function(response) {
                    // Display a success message
                    alert(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Validation error
                        var errors = xhr.responseJSON.errors;

                        // Clear previous errors
                        $('.error-message').text('');

                        // Loop through and display errors
                        $.each(errors, function(field, messages) {
                            $('#' + field + '-error').text(messages[0]); // Display the first error message
                        });
                    }
                }
            });

        }

        function saveStorePages() {
            const form = document.querySelector("#frmStorePages");
            let formData = new FormData(form);
            formData.append('store_id', store_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('store.pages') }}", // Adjust this URL to your endpoint
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                dataType:'json',
                success: function(response) {
                    // Display a success message
                    $('#addPagesModal').modal('hide');
                    alert(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Validation error
                        var errors = xhr.responseJSON.errors;

                        // Clear previous errors
                        $('.error-message').text('');

                        // Loop through and display errors
                        $.each(errors, function(field, messages) {
                            $("#frmStorePages").find('#' + field + '-error').text(messages[0]); // Display the first error message
                        });
                    }
                }
            });

        }

        function saveStoreCategory() {
            const form = document.querySelector("#frmStoreCategory");
            let formData = new FormData(form);
            formData.append('store_id', store_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('store.category') }}", // Adjust this URL to your endpoint
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                dataType:'json',
                success: function(response) {
                    // Display a success message
                    alert(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Validation error
                        var errors = xhr.responseJSON.errors;

                        // Clear previous errors
                        $('.error-message').text('');

                        // Loop through and display errors
                        $.each(errors, function(field, messages) {
                            $("#frmStoreCategory").find('#' + field + '-error').text(messages[0]); // Display the first error message
                        });
                    }
                }
            });

        }

        function saveStoreMessageAccount() {
            const form = document.querySelector("#frmStoreMessageAccount");
            let formData = new FormData(form);
            formData.append('store_id', store_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('store.messageaccount') }}", // Adjust this URL to your endpoint
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                dataType:'json',
                success: function(response) {
                    // Display a success message
                    alert(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Validation error
                        var errors = xhr.responseJSON.errors;

                        // Clear previous errors
                        $('.error-message').text('');

                        // Loop through and display errors
                        $.each(errors, function(field, messages) {
                            $("#frmStoreMessageAccount").find('#acc_' + field + '-error').text(messages[0]); // Display the first error message
                            $("#frmStoreMessageAccount").find('#' + field + '-error').text(messages[0]); // Display the first error message

                        });
                    }
                }
            });

        }

        function saveStoreLogisticCenter() {
            const form = document.querySelector("#frmStoreLogisticCenter");
            let formData = new FormData(form);
            formData.append('store_id', store_id);
            $.ajax({
                type: 'POST',
                url: "{{ route('store.logisticcenter') }}", // Adjust this URL to your endpoint
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                dataType:'json',
                success: function(response) {
                    // Display a success message
                    alert(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Validation error
                        var errors = xhr.responseJSON.errors;
                        // Clear previous errors
                        $('.error-message').text('');
                        // Loop through and display errors
                        $.each(errors, function(field, messages) {
                            $("#frmStoreLogisticCenter").find('#logistic_' + field + '-error').text(messages[0]);
                        });
                    }
                }
            });
        }

        $(document).ready(function() {

            $('.store-manufacturer').select2();
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
            $(document).on('click', '.remove-shipping', function() {
                var shippingId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion
                if (confirm("Are you sure you want to delete this shipping servie?")) {
                    $.ajax({
                        type: 'POST',
                        url: "{{ route('store.removeshipping') }}", // Adjust this URL to your endpoint
                        data: {
                            "shippingId": shippingId
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        dataType:'json',
                        success: function(response) {
                            // Display a success message
                            alert(response.message);
                            ele.parents('tr.field-group').remove();
                        },
                        error: function(xhr) {
                            alert(response.message);
                        }
                    });
                }
            });
            $(document).on('click', '.remove-page', function() {
                var pageId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion
                if (confirm("Are you sure you want to delete this page?")) {

                    $.ajax({
                        type: 'POST',
                        url: "{{ route('store.removepage') }}", // Adjust this URL to your endpoint
                        data: {
                            "pageId": pageId
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        dataType:'json',
                        success: function(response) {
                            // Display a success message
                            alert(response.message);
                            ele.parents('tr.field-group').remove();
                        },
                        error: function(xhr) {
                            alert(response.message);
                        }
                    });
                }
            });

            $(document).on('click', '.edit-message-account', function() {
                var accountId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion

                $.ajax({
                    type: 'POST',
                    url: "{{ route('store.getstoremessageaccount') }}",
                    data: {
                        "account_id": accountId
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    dataType:'json',
                    success: function(response) {
                        // Display a success message
                        if(response.data) {
                            $('#store_account_id').val(response.data.id);
                            $('#acc_name').val(response.data.name);
                            $('#acc_email').val(response.data.email);
                            if(response.data.api_account == 1){
                                $('#api_account').prop('checked',true);
                            }
                            if(response.data.active == 1){
                                $('#message_active').prop('checked',true);
                            }

                            if(response.incomingAccount){
                                $('#acc_in_id').val(response.incomingAccount.id);
                                $('#acc_in_protocol').val(response.incomingAccount.protocol);
                                $('#acc_in_email').val(response.incomingAccount.email);
                                $('#acc_in_user_id').val(response.incomingAccount.user_id);
                                $('#acc_in_password').val(response.incomingAccount.password);
                                $('#acc_in_server').val(response.incomingAccount.server);
                                $('#acc_in_port').val(response.incomingAccount.port);
                                $('#acc_in_ssl_port').val(response.incomingAccount.ssl_port);
                                $('#acc_in_tls_port').val(response.incomingAccount.tls_port);
                                $('#acc_in_folders').val(response.incomingAccount.folders);
                                $('#acc_in_fetch_limit').val(response.incomingAccount.fetch_limit);

                                if(response.incomingAccount.requires_ssl == 1){
                                    $('#acc_in_requires_ssl').prop('checked',true);
                                }
                                if(response.incomingAccount.requires_tls == 1){
                                    $('#acc_in_requires_tls').prop('checked',true);
                                }
                                if(response.incomingAccount.requires_authentication == 1){
                                    $('#acc_in_requires_authentication').prop('checked',true);
                                }

                            }

                            if(response.outgoingAccount){
                                $('#acc_out_id').val(response.outgoingAccount.id);
                                $('#acc_out_protocol').val(response.outgoingAccount.protocol);
                                $('#acc_out_email').val(response.outgoingAccount.email);
                                $('#acc_out_user_id').val(response.outgoingAccount.user_id);
                                $('#acc_out_password').val(response.outgoingAccount.password);
                                $('#acc_out_server').val(response.outgoingAccount.server);
                                $('#acc_out_port').val(response.outgoingAccount.port);
                                $('#acc_out_ssl_port').val(response.outgoingAccount.ssl_port);
                                $('#acc_out_tls_port').val(response.outgoingAccount.tls_port);

                                if(response.outgoingAccount.requires_ssl == 1){
                                    $('#acc_out_requires_ssl').prop('checked',true);
                                }
                                if(response.outgoingAccount.requires_tls == 1){
                                    $('#acc_out_requires_tls').prop('checked',true);
                                }
                                if(response.outgoingAccount.requires_authentication == 1){
                                    $('#acc_out_requires_authentication').prop('checked',true);
                                }
                            }

                            $('#addMessageModal').modal('show');
                        }
                    },
                    error: function(xhr) {
                        alert(response.message);
                    }
                });
            });

            $(document).on('click', '.edit-page', function() {
                var pageId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion

                $.ajax({
                    type: 'POST',
                    url: "{{ route('store.getstorepage') }}",
                    data: {
                        "pageId": pageId
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    dataType:'json',
                    success: function(response) {
                        // Display a success message
                        if(response.data) {
                            $('#page_id').val(response.data.id);
                            $('#page_type').val(response.data.page_type);
                            $('#page_title').val(response.data.title);
                            $('#page_text').val(response.data.text);
                            $('#addPagesModal').modal('show');
                        }
                    },
                    error: function(xhr) {
                        alert(response.message);
                    }
                });
            });
            $(document).on('click', '.remove-message-account', function() {
                var accId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion
                if (confirm("Are you sure you want to delete this message accounts?")) {

                    $.ajax({
                        type: 'POST',
                        url: "{{ route('store.removemessageaccount') }}", // Adjust this URL to your endpoint
                        data: {
                            "accId": accId
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        dataType:'json',
                        success: function(response) {
                            // Display a success message
                            alert(response.message);
                            ele.parents('tr.field-group').remove();
                        },
                        error: function(xhr) {
                            alert(response.message);
                        }
                    });
                }
            });
            $(document).on('click', '.remove-logistic-center', function() {
                var centerId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion
                if (confirm("Are you sure you want to delete this logistic center?")) {

                    $.ajax({
                        type: 'POST',
                        url: "{{ route('store.removelogisticcenter') }}", // Adjust this URL to your endpoint
                        data: {
                            "centerId": centerId
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        dataType:'json',
                        success: function(response) {
                            // Display a success message
                            alert(response.message);
                            ele.parents('tr.field-group').remove();
                        },
                        error: function(xhr) {
                            alert(response.message);
                        }
                    });
                }
            });

            $('#copy_categories').click(function() {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('store.copycategories') }}",
                    data: {
                        store_id: store_id
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    success: function(response) {
                        alert(response.message);
                        location.reload();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            alert(errors[Object.keys(errors)[0]][0]);
                        } else {
                            alert('An error occurred while copying categories');
                        }
                    }
                });
            });

            $('#sync_to_store').click(function() {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('store.synccategories') }}",
                    data: {
                        store_id: store_id
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    success: function(response) {
                        alert(response.message);
                        location.reload();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            alert(errors[Object.keys(errors)[0]][0]);
                        } else {
                            alert('An error occurred while syncing categories');
                        }
                    }
                });
            });

            // Add new field group
            $('#add_properties').click(function() {
                $('#properties_table tbody').append(`
                     <tr>
                                        <td>
                                            <select class="form-control" name="store_property_info_id[]">
                                                <option value=""></option>
                                                @if(count($propertyInfo ?? []) > 0)
                        @foreach($propertyInfo as $pinfo)
                    <option value="{{ $pinfo->id }}">{{ $pinfo->field_local_name }}</option>
                                                    @endforeach
                        @endif

                    </select>
                </td>
                <td><input type="text" class="form-control" name="value[]" value=""></td>
                <td><button type="button" class="btn btn-danger remove-property"> <i class="fas fa-fw fa-trash"></i></button></td>
                </tr>
                `);

            });

            // Remove field group
            $(document).on('click', '.remove-feature', function() {
                $(this).parents('tr.field-group').remove();
            });
        });
    </script>
@stop
