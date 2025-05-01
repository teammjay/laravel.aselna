@extends('adminlte::page')

@section('title', 'Edit Product')

@section('content_header')
    <h1>{{ ($pageAt == 'Edit') ? 'Edit Product' : 'Add Product' }}</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <style>
        #frmtab8 .select2-container--default .select2-dropdown .select2-search__field:focus, #frmtab8 .select2-container--default .select2-search--inline .select2-search__field:focus {
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
        .ui-sortable tr:hover{
            cursor:move;
        }

        .ui-sortable-placeholder{
            background:#ccc !important;
            visibility: initial !important;
        }

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
                        <a class="nav-link" id="inventory-tab" data-toggle="pill" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                            Inventory
                        </a>
                    </li>
                    {{--<li class="nav-item">
                        <a class="nav-link" id="tier-pricing-tab" data-toggle="pill" href="#tab3" role="tab" aria-controls="tab3" aria-selected="false">
                            Tier Pricing
                        </a>
                    </li>--}}
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
                    <li class="nav-item">
                        <a class="nav-link" id="specification-tab" data-toggle="pill" href="#tab12" role="tab" aria-controls="tab12" aria-selected="false">
                            Specification
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="custom-tabs-one-tabContent">
                    <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="general-tab">
                        <form method="POST" id="frmtab1">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card-body border">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="product_title">Product Title</label>
                                                    <input type="text" class="form-control" id="product_title" name="product_title" value="{{ old('product_title',$product->product_title) }}" placeholder="Enter product title">
                                                    <span class="error-message text-danger" id="product_title-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="product_sku">Product Code</label>
                                                    <input type="text" class="form-control" id="product_sku" name="product_sku" value="{{ old('product_sku',$product->product_sku) }}" placeholder="Enter product code">
                                                    <span class="error-message text-danger" id="product_sku-error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        @php
                                            function renderCategories($productTypes, $product, $prefix = '') {
                                                foreach ($productTypes as $ptype) {
                                                    if($product->product_type == $ptype->id){
                                                        $sel = 'selected';
                                                    }else{
                                                        $sel = '';
                                                    }
                                                    echo '<option value="' . $ptype->id . '" '.$sel.'>' . $prefix . $ptype->name . '</option>';

                                                    if ($ptype->children) {
                                                        renderCategories($ptype->children, $product, $prefix . '-- ');
                                                    }
                                                }
                                            }
                                        @endphp
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="product_type">Product Type</label>
                                                    <select class="form-control select2" id="product_type" name="product_type">
                                                        <option>-- Product Type --</option>
                                                        @php
                                                            renderCategories($productTypes,$product);
                                                        @endphp
                                                        {{--@if(count($productTypes ?? []) > 0)
                                                            @foreach($productTypes as $ptype)
                                                                <option value="{{ $ptype->id }}" {{ $ptype->id == $product->product_type ? 'selected' : '' }}>{{ $ptype->name }}</option>
                                                            @endforeach
                                                        @endif--}}

                                                    </select>
                                                    <span class="error-message text-danger" id="product_type-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="product_condition">Product Condition</label>
                                                    <select class="form-control" id="product_condition_id" name="product_condition_id">
                                                        <option>-- Product Condition --</option>
                                                        @if(count($productConditions ?? []) > 0)
                                                            @foreach($productConditions as $pc)
                                                                <option value="{{ $pc->id }}" {{ $pc->id == $product->product_condition_id ? 'selected' : '' }}>{{ $pc->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="error-message text-danger" id="product_condition-error"></span>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="manufacturer_id">Product Manufacturer</label>
                                                    <select class="form-control" id="manufacturer_id" name="manufacturer_id">
                                                        <option>-- Product Manufacture --</option>
                                                        @if(count($manufacturers ?? []) > 0)
                                                            @foreach($manufacturers as $mf)
                                                                <option value="{{ $mf->id }}" {{ $mf->id == $product->manufacturer_id ? 'selected' : '' }}>{{ $mf->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <span class="error-message text-danger" id="manufacturer_id-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="product_status">Product Status</label>
                                                    <select class="form-control" id="product_status" name="product_status">
                                                        <option>-- Product Status --</option>
                                                        <option value="active" {{ "active" == $product->product_status ? 'selected' : '' }}> Active </option>
                                                        <option value="discontinued" {{ "discontinued" == $product->product_status ? 'selected' : '' }}> Discontinued </option>
                                                        <option value="out_of_stock" {{ "out_of_stock" == $product->product_status ? 'selected' : '' }}> Out of stock </option>
                                                    </select>
                                                    <span class="error-message text-danger" id="product_status-error"></span>
                                                </div>
                                            </div>


                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="product_status">Is Configurable? </label>
                                                    <input type="checkbox" name="configurable" id="configurable" class="custom-checkbox">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="product_status">SDOC Enable? </label>
                                                    <input type="checkbox" name="sdoc_enable_element" id="sdoc_enable_element" class="sdoc_enable__class" {{ $product->sdoc_enable == 1 ? 'checked' : '' }}>
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
                                                            <label for="product_description">Product Description</label>
                                                            <textarea class="form-control" id="product_description" name="product_description" rows="10" placeholder="Enter Description">{{ old('product_description',$product->product_description) }}</textarea>
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
                                                            <label for="product_price">Product Price</label>
                                                            <input type="text" class="form-control" id="product_price" name="product_price" value="{{ old('product_price',$product->product_price) }}" placeholder="00.00">
                                                            <span class="error-message text-danger" id="product_price-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="product_wholesale_price">Wholesale Price</label>
                                                            <input type="text" class="form-control" id="product_wholesale_price" name="product_wholesale_price" value="{{ old('product_wholesale_price',$product->product_wholesale_price) }}" placeholder="00.00">
                                                            <span class="error-message text-danger" id="product_wholesale_price-error"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="product_marketplace_price">Marketplace Price</label>
                                                            <input type="text" class="form-control" id="product_marketplace_price" name="product_marketplace_price" value="{{ old('product_marketplace_price',$product->product_marketplace_price) }}" placeholder="00.00">
                                                            <span class="error-message text-danger" id="product_marketplace_price-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="product_mrsp">Mrsp</label>
                                                            <input type="text" class="form-control" id="product_mrsp" name="product_mrsp" value="{{ old('product_mrsp',$product->product_mrsp) }}" placeholder="00.00">
                                                            <span class="error-message text-danger" id="product_mrsp-error"></span>
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
                                                    <label for="dimensions_width">Dimensions</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="dimensions_width" name="dimensions_width" value="{{ old('dimensions_width',$product->dimensions_width) }}" placeholder="">
                                                        <input type="text" class="form-control mr-1" id="dimensions_height" name="dimensions_height"  value="{{ old('dimensions_height',$product->dimensions_height) }}"placeholder="">
                                                        <input type="text" class="form-control mr-1" id="dimensions_length" name="dimensions_length"  value="{{ old('dimensions_length',$product->dimensions_length) }}"placeholder="">
                                                        <span>Inches</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="product_weight">Weight</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="product_weight" name="product_weight" value="{{ old('product_weight',$product->product_weight) }}" placeholder="">
                                                        <select class="form-control w-25" id="product_weight_unit" name="product_weight_unit">
                                                            <option value="kg" {{ "kg" == $product->product_weight_unit ? 'selected' : '' }}> Kg </option>
                                                            <option value="oz" {{ "oz" == $product->product_weight_unit ? 'selected' : '' }}> OZ </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="package_dimensions_width">Package Dimensions</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="package_dimensions_width" name="package_dimensions_width" value="{{ old('package_dimensions_width',$product->package_dimensions_width) }}" placeholder="">
                                                        <input type="text" class="form-control mr-1" id="package_dimensions_height" name="package_dimensions_height" value="{{ old('package_dimensions_height',$product->package_dimensions_height) }}" placeholder="">
                                                        <input type="text" class="form-control mr-1" id="package_dimensions_length" name="package_dimensions_length" value="{{ old('package_dimensions_length',$product->package_dimensions_length) }}" placeholder="">
                                                        <span>Inches</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="package__weight">Package Weight</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="package__weight" name="package_weight" value="{{ old('package_weight',$product->package_weight) }}" placeholder="">
                                                        <select class="form-control w-25" id="package_weight_unit" name="package_weight_unit">
                                                            <option value="kg" {{ "kg" == $product->package_weight_unit ? 'selected' : '' }}> Kg </option>
                                                            <option value="oz" {{ "oz" == $product->package_weight_unit ? 'selected' : '' }}> OZ </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="package__weight">Input Voltage</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="input__voltage" name="input_voltage_value" value="{{ old('input_voltage_value',$product->input_voltage_value) }}" placeholder="">
                                                        <select class="form-control w-25" id="input_voltage_unit" name="input_voltage_unit">
                                                            <option value="volts" {{ "volts" == $product->input_voltage_unit ? 'selected' : '' }}> Volts </option>
                                                            <option value="kilovolts" {{ "kilovolts" == $product->input_voltage_unit ? 'selected' : '' }}> Kilovolts </option>
                                                            <option value="microvolts" {{ "microvolts" == $product->input_voltage_unit ? 'selected' : '' }}> Microvolts </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="package__weight">Output Voltage</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="output__voltage" name="output_voltage_value" value="{{ old('output_voltage_value',$product->output_voltage_value) }}" placeholder="">
                                                        <select class="form-control w-25" id="output_voltage_unit" name="output_voltage_unit">
                                                            <option value="volts" {{ "volts" == $product->output_voltage_unit ? 'selected' : '' }}> Volts </option>
                                                            <option value="kilovolts" {{ "kilovolts" == $product->output_voltage_unit ? 'selected' : '' }}> Kilovolts </option>
                                                            <option value="microvolts" {{ "microvolts" == $product->output_voltage_unit ? 'selected' : '' }}> Microvolts </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="package__weight">Output Current</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="output__current" name="output_current_value" value="{{ old('output_current_value',$product->output_current_value) }}" placeholder="">
                                                        <select class="form-control w-25" id="output_current_unit" name="output_current_unit">
                                                            <option value="amps" {{ "amps" == $product->output_current_unit ? 'selected' : '' }}> Amps </option>
                                                            <option value="kiloamps" {{ "kiloamps" == $product->output_current_unit ? 'selected' : '' }}> Kiloamps </option>
                                                            <option value="milliamps" {{ "milliamps" == $product->output_current_unit ? 'selected' : '' }}> Milliamps </option>
                                                            <option value="microamps" {{ "microamps" == $product->output_current_unit ? 'selected' : '' }}> Microamps </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            {{--<div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="package__weight">Retail Bulk?</label>
                                                    <div class="d-flex">
                                                        <input type="text" class="form-control mr-1" id="package__weight" name="package_weight" value="{{ old('package_weight',$product->package_weight) }}" placeholder="">
                                                        <select class="form-control w-25" id="package_weight_unit" name="package_weight_unit">
                                                            <option value="kg" {{ "kg" == $product->package_weight_unit ? 'selected' : '' }}> Kg </option>
                                                            <option value="oz" {{ "oz" == $product->package_weight_unit ? 'selected' : '' }}> OZ </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>--}}
                                        </div>
                                    </div>
                                    <div class="card-body border mt-2">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label>Target Audiences</label>
                                                    <div class="d-flex">
                                                        @if(count($targetAudience ?? []) > 0)
                                                            @foreach($targetAudience as $ta)
                                                                <input type="checkbox" class="mr-1" name="target_aud" value="{{ $ta->name }}" {{ $ta->name == $product->target_aud ? 'checked' : '' }}><span class="mr-3">{{ $ta->name }}</span>
                                                            @endforeach
                                                        @endif

                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label for="product_color">Color</label>
                                                    <select class="custom-select" name="product_color" id="product_color">
                                                        <option></option>
                                                        @if(count($colors ?? []) > 0)
                                                            @foreach($colors as $color)
                                                                <option value="{{ $color->id }}" {{ $color->id == $product->product_color ? 'selected' : '' }}>{{ $color->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(1)">Save</button>
                                <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="inventory-tab">
                        <form method="POST" id="frmtab2">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card-body border">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="product_qty">Quantity</label>
                                                    <input type="text" class="form-control" id="product_qty" name="product_qty" value="{{ old('product_qty',$product->product_qty) }}" placeholder="100">
                                                    @error('product_qty')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="product_reorder_qty">Reorder Quantity</label>
                                                    <input type="text" class="form-control" id="product_reorder_qty" name="product_reorder_qty" value="{{ old('product_reorder_qty',$product->product_reorder_qty) }}" placeholder="100">
                                                </div>
                                            </div>
                                            {{--<div class="col-md-8">
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
                                            </div>--}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{--<div class="row mt-3">
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
                            </div>--}}
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(2)">Save</button>
                                <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    {{--<div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="tier-pricing-tab">
                        <form method="POST" id="frmtab3">
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
                                <button type="button" class="btn btn-success" onclick="saveTabData(3)">Save</button>
                                <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                                --}}{{--<button type="reset" class="btn btn-primary">Reset</button>--}}{{--
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>--}}
                    <div class="tab-pane fade" id="tab4" role="tabpanel" aria-labelledby="compatibility-tab">
                        <form method="POST" id="frmtab4">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            Interfaces
                                        </div>
                                        <div class="card-body">
                                            <select class="form-control select2 pro-interface" name="interfaces[]" multiple="multiple">
                                                @if(count($interfaces ?? []) > 0)
                                                    @foreach($interfaces as $interface)
                                                        <option value="{{ $interface->id }}" {{ in_array($interface->id,$productInterfaces) ? 'selected' : '' }}>{{ $interface->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
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
                                <button type="button" class="btn btn-success" onclick="saveTabData(4)">Save</button>
                                <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab5" role="tabpanel" aria-labelledby="features-tab">
                        <form method="POST" id="frmtab5">
                            <button type="button" class="btn btn-secondary" id="add_feature"> <i class="fa fa-plus-square"></i> Add</button>
                            <div class="card card-body border mt-2">
                                <table class="table table-bordered" id="features_table">
                                    <thead>
                                    <tr>
                                        <th data-column="0" data-order="asc">Value <span class="sort-icon float-right">▲</span></th>
                                        <th data-column="1" data-order="asc">Sort <span class="sort-icon float-right">▲</span></th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @if(count($keyFeatures ?? []) > 0)
                                            @foreach($keyFeatures as $features)
                                                <tr class="field-group">
                                                    <td><textarea class="form-control" name="features[]">{{ $features->value }}</textarea></td>
                                                    <td><input type="text" class="form-control" name="feature_sort_order[]" value="{{ $features->sort_order }}"></td>
                                                    <td><button type="button" class="btn btn-danger remove-feature"> <i class="fas fa-fw fa-trash"></i></button></td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td><textarea class="form-control" name="features[]"></textarea></td>
                                                <td><input type="text" class="form-control" name="feature_sort_order[]" value="1"></td>
                                                <td></td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(5)">Save</button>
                                <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab6" role="tabpanel" aria-labelledby="search-terms-tab">
                        <form method="POST" id="frmtab6">
                            <button type="button" class="btn btn-secondary" id="add_search_term"><i class="fa fa-plus-square"></i> Add</button>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <table class="table table-bordered" id="table_search_term">
                                        <thead>
                                            <tr>
                                                <th data-column="0" data-order="asc">Term <span class="sort-icon float-right">▲</span></th>
                                                <th data-column="1" data-order="asc">Sort Order <span class="sort-icon float-right">▲</span></th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(count($searchTerms ?? []) > 0)
                                                @foreach($searchTerms as $key)
                                                    <tr class="field-group">
                                                        <td width="70%"><input type="text" class="form-control" name="search_term[]" value="{{ $key->term }}"></td>
                                                        <td width="20%"><input type="text" class="form-control" name="search_sort_order[]" value="{{ $key->sort_order }}"></td>
                                                        <td width="10%"><button type="button" class="btn btn-danger remove-term"> <i class="fas fa-fw fa-trash"></i></button></td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td width="80%"><input type="text" class="form-control" name="search_term[]"></td>
                                                    <td width="20%"><input type="text" class="form-control" name="search_sort_order[]"></td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(6)">Save</button>
                                <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab7" role="tabpanel" aria-labelledby="images-tab">

                        <form id="imageUploadForm" enctype="multipart/form-data">
                            <div class="form-group col-md-4 d-flex">
                                <input class="form-control" type="file" name="images[]" id="images" accept="image/*" multiple>
                                <button class="btn btn-secondary" type="submit">Upload</button>
                            </div>
                        </form>

                        <div class="row">
                            <div class="col-md-8">
                                <table class="table table-bordered" id="sortable-table">
                                    <thead>
                                        <th></th>
                                        <th>Image</th>
                                        <th>Main Image</th>
                                        <th>Order</th>
                                        <th></th>
                                    </thead>
                                    <tbody>
                                        @if(count($productImages ?? []) > 0)
                                            @foreach($productImages as $images)
                                                <tr class="pro-img-{{ $images->id }}" data-id="{{ $images->id }}">
                                                    <td><i class="fa fa-sort" title="Drag and drop row up or down to sort"></i></td>
                                                    <td><img src="{{ isset($images->imageObj) ? asset('storage/product_images/'. $images->imageObj->name) : '' }}" alt="{{ $images->image }}" width="70" height="70"></td>
                                                    <td><input type="checkbox" name="main_image" class="set_main_image" value="{{ $images->id }}" {{ $images->main_image == 1 ? 'checked' : '' }}></td>
                                                    {{--<td><input type="text" class="form-control image-sort-order" readonly="" data-id="{{ $images->id }}" name="sort_order" value="{{ $images->sort_order }}"></td>--}}
                                                    <td>{{ $images->sort_order }}</td>
                                                    <td><button class="btn btn-danger delete-btn" data-id="{{ $images->id }}"> <i class="fas fa-fw fa-trash"></i></button></td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                        </div>

                        <div class="row mt-5">
                            <div class="col-sm-12">
                                <label>Additional Images</label>
                                <form id="additionalImageUploadForm" enctype="multipart/form-data">
                                    <div class="form-group col-md-4 d-flex">
                                        <input class="form-control" type="file" name="alternate_images[]" id="alternate_images" accept="image/*" multiple>
                                        <button class="btn btn-secondary" type="submit">Upload</button>
                                    </div>
                                </form>
                            </div>

                            <div class="col-md-8">
                                <table class="table table-bordered" id="sortable-alt-table">
                                    <thead>
                                    <th></th>
                                    <th>Image</th>
                                    <th>Order</th>
                                    <th></th>
                                    </thead>
                                    <tbody>
                                    @if(count($productAltImages ?? []) > 0)
                                        @foreach($productAltImages as $images)
                                            <tr class="pro-alt-img-{{ $images->id }}" data-id="{{ $images->id }}">
                                                <td><i class="fa fa-sort" title="Drag and drop row up or down to sort"></i></td>
                                                <td><img src="{{ asset('storage/product_additional_images/'. $images->imageObj->name) }}" alt="{{ $images->image }}" width="70" height="70"></td>
                                                <td>{{ $images->sort_order }}</td>
                                                <td><button class="btn btn-danger delete-alt-btn" data-id="{{ $images->id }}"> <i class="fas fa-fw fa-trash"></i></button></td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                    <div class="tab-pane fade" id="tab8" role="tabpanel" aria-labelledby="tags-tab">
                        <form method="POST" id="frmtab8">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-secondary" id="add_tag"><i class="fa fa-plus-square"></i> Add</button>
                                    <div class="form-group mt-3">
                                        {{--<textarea name="tags" class="form-control"  rows="10"></textarea>--}}
                                        <select class="form-control select2 pro-tags" name="tags[]" multiple="multiple">
                                            <option value="Apple" {{ in_array('Apple',$productTags) ? 'selected' : '' }}>Apple</option>
                                            <option value="Android" {{ in_array('Android',$productTags) ? 'selected' : '' }}>Android</option>
                                            <option value="Smart" {{ in_array('Smart',$productTags) ? 'selected' : '' }}>Smart</option>

                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(8)">Save</button>
                                <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab9" role="tabpanel" aria-labelledby="stores-tab">
                        <form method="POST" id="frmtab9">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <thead>
                                            <th>Store</th>
                                            <th>Active</th>
                                            <th>Store Products, Store Category</th>
                                        </thead>
                                        <tbody>
                                            @if(count($store_details ?? []) > 0)
                                                @foreach($store_details as $key=>$store)
                                                    <tr class="store_row" id="store_{{ $store['store_detail']->id }}" data-id="{{ $store['store_detail']->id }}">
                                                        <td>{{ $store['store_detail']->name }} {{ (!empty($store['store_detail']->storeType)) ? ' - '.$store['store_detail']->storeType->name : ''}}</td>
                                                        <td align="center">
                                                            <input type="checkbox" name="active" {{ ((isset($productStore) && in_array($store['store_detail']->id,$productStore)) || $pageAt != 'Edit') ? 'checked' : '' }} />
                                                            <input type="hidden" name="store_id" value="{{ $store['store_detail']->id }}">
                                                        </td>
                                                        <td>
                                                            <select class="form-control" name="store_type_category_id">
                                                                <option value=""></option>
                                                                @if(count($store['store_category'] ?? []) > 0)
                                                                    @foreach($store['store_category'] as $key2=>$sCat)
                                                                        <option value="{{ $sCat->id }}" {{ (isset($productStore[$sCat->id]) && $productStore[$sCat->id] == $store['store_detail']->id ) ? 'selected' : '' }}>{{ $sCat->name }}</option>
                                                                    @endforeach
                                                                @endif
                                                            </select>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif

                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            Store Detail
                                        </div>
                                        <div class="card-body" id="store_product_detail">
                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="col-form-label">Title</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="checkbox" name="overwrite_title" id="overwrite_title" class="mr-1"> <textarea class="form-control" name="title" id="store_product_title" placeholder="Store Name/ Title" readonly>{{ old('product_title',$product->product_title) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="col-form-label">Description</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="checkbox" id="overwrite_description" name="overwrite_description" class="mr-1"> <textarea class="form-control" name="description" id="store_product_description" placeholder="Description" rows="8" readonly>{{ old('product_description',$product->product_description) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="col-form-label">Price</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="checkbox" id="overwrite_price" name="overwrite_price" class="mr-1"> <input type="text" class="form-control" name="price" id="store_product_price" placeholder="00.00" value="{{ old('product_price',$product->product_price) }}" readonly>
                                                </div>
                                            </div>
                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="col-form-label">Compare at Price</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="checkbox" id="overwrite_compare_at_price" name="overwrite_compare_at_price" class="mr-1"> <input type="text" class="form-control" name="compare_at_price" id="store_product_compare_at_price" value="{{ old('product_marketplace_price',$product->product_marketplace_price) }}" placeholder="00.00" readonly>
                                                </div>
                                            </div>
                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="col-form-label">Fbc Price</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="checkbox" id="overwrite_fulfillment_by_channel_price" name="overwrite_fulfillment_by_channel_price" class="mr-1"> <input type="text" class="form-control" name="fulfillment_by_channel_price" id="store_product_fbc_price" placeholder="00.00" readonly>
                                                </div>
                                            </div>
                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="col-form-label">Wholesale Price</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="checkbox" id="overwrite_wholesale_price" name="overwrite_wholesale_price" class="mr-1"> <input type="text" class="form-control" name="wholesale_price" id="store_wholesale_price" value="{{ old('product_wholesale_price',$product->product_wholesale_price) }}" placeholder="00.00" readonly>
                                                </div>
                                            </div>
                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="col-form-label">Msrp</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="checkbox" id="overwrite_msrp" name="overwrite_msrp" class="mr-1"> <input type="text" class="form-control" name="msrp" id="store_product_msrp" value="{{ old('product_mrsp',$product->product_mrsp) }}" placeholder="00.00" readonly>
                                                </div>
                                            </div>
                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="col-form-label">Sales Count</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="text" class="form-control" name="sales_count" id="store_product_sales_count" placeholder="0" readonly>
                                                </div>
                                            </div>
                                            <input type="hidden" name="manufacturer_id" value="{{ $product->manufacturer_id }}">
                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="col-form-label">Last Sale Date</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="text" class="form-control w-75 mr-1" name="last_sale_date" id="last_sale_date" readonly>
                                                    <input type="text" class="form-control w-25" name="last_sale_time" id="last_sale_time" readonly>
                                                </div>
                                            </div>

                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="col-form-label">Last Synch</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="text" class="form-control w-75 mr-1" name="last_synch_date" id="last_synch_date" readonly>
                                                    <input type="text" class="form-control w-25" name="last_synch_time" id="last_synch_time" readonly>
                                                </div>
                                            </div>

                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="fulfillment_type">Fullfillment Type</label>
                                                </div>
                                                <div class="col-md-9">
                                                    <select class="form-control" name="fulfillment_type" id="fulfillment_type">
                                                        <option value="10">Merchant</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="synch_price">Synch Price</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="checkbox" id="synch_price" name="synch_price">
                                                </div>
                                            </div>

                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="synch_inventory">Synch Inventory</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="checkbox" id="synch_inventory" name="synch_inventory">
                                                </div>
                                            </div>

                                            <div class="row mt-3 g-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="synch_item">Synch Item</label>
                                                </div>
                                                <div class="col-md-9 d-flex justify-between">
                                                    <input type="checkbox" id="synch_item" name="synch_item">
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(9)">Save</button>
                                <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab10" role="tabpanel" aria-labelledby="channel-tab">
                        <form method="POST" id="frmtab10">
                            <button type="button" class="btn btn-secondary" id="create_channel" data-toggle="modal" data-target="#addChannelModal" ><i class="fa fa-plus-square"></i> Create</button>
                            <table class="table table-bordered mt-2">
                                <thead>
                                <tr>
                                    <th>Channel</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                    @if(count($productStoreType ?? []) > 0)
                                        @foreach($productStoreType as $stype)
                                            <tr>
                                                <td>{{ $stype->name }}</td>
                                                <td>{{ ($stype->status == 1) ? 'Active' : 'Inactive' }}</td>
                                                <td>
                                                    <button type="button" id="edit_channel" data-toggle="modal" data-id="{{ $stype->id }}" data-target="#addChannelModal"  class="btn btn-primary">
                                                        <i class="fas fa-fw fa-pencil-alt"></i>
                                                    </button>
                                                    <button class="btn btn-danger delete-product-channel" data-id="{{ $stype->id }}"> <i class="fas fa-fw fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>

                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab11" role="tabpanel" aria-labelledby="spitex-tab">
                        <form method="POST" id="frmtab11">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title_template">Title Template</label>
                                        <textarea name="title_template" id="title_template" class="form-control"  rows="10">{{ $product->title_template }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="description_template">Description Template</label>
                                        <textarea name="description_template" id="description_template" class="form-control"  rows="10">{{ $product->description_template }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">Title Keywords</div>
                                        <div class="card-body">
                                            <button type="button" class="btn btn-secondary" id="add_title_keyword"><i class="fa fa-plus-square"></i> Add</button>
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <table class="table table-bordered" id="table_title_keyword">
                                                        <thead>
                                                        <tr>
                                                            <th data-column="0" data-order="asc">Keyword <span class="sort-icon float-right">▲</span></th>
                                                            <th data-column="1" data-order="asc">Priority <span class="sort-icon float-right">▲</span></th>
                                                            <th></th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        @if(count($titleKeywords ?? []) > 0)
                                                            @foreach($titleKeywords as $key)
                                                                <tr class="field-group">
                                                                    <td width="70%"><input type="text" class="form-control" name="title_keyword[]" value="{{ $key->keyword }}"></td>
                                                                    <td width="20%"><input type="text" class="form-control" name="title_priority[]" value="{{ $key->priority }}"></td>
                                                                    <td width="10%"><button type="button" class="btn btn-danger remove-keyword"> <i class="fas fa-fw fa-trash"></i></button></td>
                                                                </tr>
                                                            @endforeach
                                                        @else
                                                            <tr>
                                                                <td width="80%"><input type="text" class="form-control" name="title_keyword[]"></td>
                                                                <td width="20%"><input type="text" class="form-control" name="title_priority[]"></td>
                                                            </tr>
                                                        @endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(11)">Save</button>
                                <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="tab12" role="tabpanel" aria-labelledby="specification-tab">
                        <form method="POST" id="frmtab12">
                            <div class="form-group w-50">
                                <label>Store Type</label>
                                <select class="form-control" name="specification_store_type_id" id="specification_store_type_id">
                                    @if(count($storeTypes ?? []) > 0)
                                        @foreach($storeTypes as $sType)
                                            <option value="{{ $sType->id }}">{{ $sType->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div id="specification_list">

                            </div>

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(12)">Save</button>
                                <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
            <div id="ajax-gallery" style="display: none;"></div>
        </div>
        <!-- Add channel modal -->
        <div class="modal fade" id="addChannelModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addShippingModalLabel">Product Channel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="frmproductChannel">
                            <input type="hidden" name="id" id="product_channel_id">
                            <div class="mb-3">
                                <label for="store_type_id" class="col-form-label">Channel:</label>
                                <select class="form-control" name="store_type_id" id="store_type_id">
                                    @if(count($storeTypes ?? []) > 0)
                                        @foreach($storeTypes as $sType)
                                            <option value="{{ $sType->id }}">{{ $sType->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="col-form-label">Status:</label>
                                <select class="form-control" name="status" id="status">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="saveProductChannel()" id="saveProductChannel">Save</button>
                    </div>
                </div>
            </div>
        </div>

@stop
@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Lightbox2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script>
        var product_id = "{{ $product->product_id ?? '' }}"; // Passed from controller

        function saveTabData(tabId) {
            const form = document.querySelector("#frmtab"+ tabId);
            let formData = new FormData(form);
            formData.append('product_id', product_id);
            formData.append('tab', tabId);

            if(tabId == 1){
                formData.set('sdoc_enable', $('#sdoc_enable_element').is(':checked') ? 1 : 0);
            }

            if(tabId == 9){

                let store_id = $('.store_row.active').data('id');
                if($('.store_row.active').find('input[name="active"]').prop('checked')){
                    var status = 1;
                }else{
                    var status = 0;
                }

                var cat_id = $('.store_row.active').find('select[name="store_type_category_id"]').val();

                formData.set('store_type_category_id',cat_id);
                formData.set('store_id',store_id);
                formData.set('active',status);
            }

            $.ajax({
                type: 'POST',
                url: "{{ route('catalog.save') }}", // Adjust this URL to your endpoint
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
            formData.append('product_id', product_id);
            formData.set('product_status', 'active');
            formData.set('sdoc_enable', $('#sdoc_enable_element').is(':checked') ? 1 : 0);

            $.ajax({
                type: 'POST',
                url: "{{ route('catalog.final') }}", // Adjust this URL to your endpoint
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
                    window.location.href = "{{ route('catalog.list') }}";
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

        function saveProductChannel() {
            const form = document.querySelector("#frmproductChannel");
            let formData = new FormData(form);
            formData.append('product_id', '{{ $product->product_id }}');

            $.ajax({
                type: 'POST',
                url: "<?php echo e(route('catalog.saveproductchannel')); ?>", // Adjust this URL to your endpoint
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
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

        function loadSpecification(){

            $.ajax({
                url: "{{ route('catalog.getspecification') }}",
                type: 'POST',
                data: {
                    store_type_id : $('#specification_store_type_id').val(),
                    product_id : '{{ $product->product_id }}'
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if(response.html){
                        $('#specification_list').html(response.html);
                        $('.specification').select2();
                        $('.select2').on('select2:open', function () {
                            // Wait for dropdown to render before focusing the search field
                            setTimeout(function () {
                                document.querySelector('.select2-container--open .select2-search__field').focus();
                            }, 0);
                        });

                    }
                },
                error: function (xhr) {
                    if (xhr.responseJSON?.error) {
                        $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                    }
                }
            });
        }

        $(document).ready(function() {

            $('.select2').on('select2:open', function () {
                // Wait for dropdown to render before focusing the search field
                setTimeout(function () {
                    document.querySelector('.select2-container--open .select2-search__field').focus();
                }, 0);
            });

            $(document).ajaxStart(function () {
                $("#ajax-preloader").show(); // Show preloader when AJAX starts
            }).ajaxStop(function () {
                $("#ajax-preloader").hide(); // Hide preloader when AJAX stops
            });

            $('.pro-tags,.pro-interface').select2();
            $('#product_type').select2();

            $('#imageUploadForm').on('submit', function (e) {
                e.preventDefault();

                let formData = new FormData(this);
                formData.append('product_id', product_id);

                $.ajax({
                    url: "{{ route('catalog.upload') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        }
                    },
                    error: function (xhr) {

                    }
                });
            });

            $('#additionalImageUploadForm').on('submit', function (e) {
                e.preventDefault();

                let formData = new FormData(this);
                formData.append('product_id', product_id);

                $.ajax({
                    url: "{{ route('catalog.uploadaltimg') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        }
                    },
                    error: function (xhr) {
                        if (xhr.responseJSON?.error) {
                            $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                        }
                    }
                });
            });

            /* get channel detail*/

            $(document).on('click', '#create_channel', function() {
                $('#frmproductChannel').find('#store_type_id').removeAttr('disabled');
                $('#frmproductChannel').find('#status').val(1);
            });

            $(document).on('click', '#edit_channel', function() {
                $.ajax({
                    url: "{{ route('catalog.getproductchannel') }}",
                    type: 'POST',
                    data: {
                        id : $(this).data('id')
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            if(response.data){
                                $('#frmproductChannel').find('#store_type_id').val(response.data.store_type_id).attr('disabled',true);
                                $('#frmproductChannel').find('#status').val(response.data.status);
                                $('#frmproductChannel').find('#product_channel_id').val(response.data.id);
                            }
                        }
                    },
                    error: function (xhr) {
                        if (xhr.responseJSON?.error) {
                            $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                        }
                    }
                });
            });

            $(document).on('click', '.delete-product-channel', function() {

                $.ajax({
                    url: "{{ route('catalog.deleteproductchannel') }}",
                    type: 'POST',
                    data: {
                        id : $(this).data('id')
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            alert(response.message)
                        }
                    },
                    error: function (xhr) {
                        if (xhr.responseJSON?.error) {
                            $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                        }
                    }
                });
            });



            // set default image for prodcut
            $(document).on('click', '.set_main_image', function() {
                $('.set_main_image').prop('checked',false);
                $(this).prop('checked',true);
                $.ajax({
                    url: "{{ route('catalog.set_default') }}",
                    type: 'POST',
                    data: {
                        image_id : $(this).val(),
                        product_id : product_id
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            alert(response.message);
                        }
                    },
                    error: function (xhr) {
                        if (xhr.responseJSON?.error) {
                            $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                        }
                    }
                });
            });

            // change sort order for image of prodcut
            $(document).on('change', '.image-sort-order', function() {
                $('.set_main_image').prop('checked',false);
                $(this).prop('checked',true);
                $.ajax({
                    url: "{{ route('catalog.set_image_order') }}",
                    type: 'POST',
                    data: {
                        image_id : $(this).data('id'),
                        sort_order : $(this).val(),
                        product_id : product_id
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            alert(response.message);
                        }
                    },
                    error: function (xhr) {
                        if (xhr.responseJSON?.error) {
                            $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                        }
                    }
                });
            });

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


            // Add new term
            $('#add_search_term').click(function() {
                $('#table_search_term tbody').append(`
                    <tr class="field-group">
                        <td width="70%"><input type="text" class="form-control" name="search_term[]" value=""></td>
                        <td width="20%"><input type="text" class="form-control" name="search_sort_order[]" value=""></td>
                        <td width="10%"><button type="button" class="btn btn-danger remove-term"> <i class="fas fa-fw fa-trash"></i></button></td>
                     </tr>
                `);

            });

            // Remove search terms
            $(document).on('click', '.remove-term', function() {
                $(this).parents('tr.field-group').remove();
            });


            // Add new term
            $('#add_title_keyword').click(function() {
                $('#table_title_keyword tbody').append(`
                    <tr class="field-group">
                        <td width="70%"><input type="text" class="form-control" name="title_keyword[]" value=""></td>
                        <td width="20%"><input type="text" class="form-control" name="title_priority[]" value=""></td>
                        <td width="10%"><button type="button" class="btn btn-danger remove-keywoird"> <i class="fas fa-fw fa-trash"></i></button></td>
                     </tr>
                `);

            });

            // Remove search terms
            $(document).on('click', '.remove-keyword', function() {
                $(this).parents('tr.field-group').remove();
            });

            $(document).on('click', 'input[name="active"]', function() {
                if($(this).is(':checked')){
                    $(this).prop('checked',false);
                    $('.store_row').removeClass('active bg-blue');
                    $(this).parents('.store_row').addClass('active bg-blue');
                }else{
                    $(this).prop('checked',true);
                    $(this).parents('.store_row').removeClass('active bg-blue');
                }
            });



            // change sort order for image of prodcut
            $(document).on('click', '.store_row', function(e) {

                if ($(e.target).is('checkbox, a, select')) {
                    return;
                }

                $('.store_row').removeClass('active bg-blue');
                $(this).addClass('active bg-blue');

                if($(this).find('input[name="active"]').is(':checked')){
                    $(this).find('input[name="active"]').prop('checked',false);
                }else{
                    $(this).find('input[name="active"]').prop('checked',true);
                }


                var store_id = $(this).data('id');
                $.ajax({
                    url: "{{ route('catalog.getstoredetail') }}",
                    type: 'POST',
                    data: {
                        store_id : store_id,
                        product_id : product_id,
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            //alert(response.message);
                            //console.log(response.data);
                            if(response.data.title){
                                $('#store_product_title').val(response.data.title);
                                $('#store_product_description').val(response.data.description);
                                $('#store_product_price').val(response.data.price);
                                $('#store_product_compare_at_price').val(response.data.compare_at_price);
                                $('#store_product_fbc_price').val(response.data.fulfillment_by_channel_price);
                                $('#store_wholesale_price').val(response.data.wholesale_price);
                                $('#store_product_msrp').val(response.data.msrp);
                                $('#store_product_sales_count').val(response.data.sales_count);
                                $('#store_manufacturer_id').val(response.data.manufacturer_id);
                                if(response.data.last_sale_date){
                                    var parts = response.data.last_sale_date.split(" ");
                                    var date = parts[0]; // "2024-12-10"
                                    var time = parts[1]; // "14:30:00"
                                    $('#last_sale_date').val(date);
                                    $('#last_sale_time').val(time);
                                }

                                if(response.data.last_synch_date){
                                    var parts = response.data.last_synch_date.split(" ");
                                    var date = parts[0]; // "2024-12-10"
                                    var time = parts[1]; // "14:30:00"
                                    $('#last_synch_date').val(date);
                                    $('#last_synch_time').val(time);
                                }

                                if(response.data.synch_price == 1) {
                                    $('#synch_price').prop('checked', true);
                                }

                                if(response.data.synch_inventory == 1) {
                                    $('#synch_inventory').prop('checked', true);
                                }

                                if(response.data.synch_item == 1) {
                                    $('#synch_item').prop('checked', true);
                                }

                                if(response.data.overwrite_title == 1) {
                                    $('#overwrite_title').prop('checked', true);
                                    $('#store_product_title').removeAttr('readonly');
                                }
                                if(response.data.overwrite_description == 1) {
                                    $('#overwrite_description').prop('checked', true);
                                    $('#store_product_description').removeAttr('readonly');
                                }
                                if(response.data.overwrite_price == 1) {
                                    $('#overwrite_price').prop('checked', true);
                                    $('#store_product_price').removeAttr('readonly');
                                }
                                if(response.data.overwrite_compare_at_price == 1) {
                                    $('#overwrite_compare_at_price').prop('checked', true);
                                    $('#store_product_compare_at_price').removeAttr('readonly');
                                }
                                if(response.data.overwrite_fulfillment_by_channel_price == 1) {
                                    $('#overwrite_fulfillment_by_channel_price').prop('checked', true);
                                    $('#store_product_fbc_price').removeAttr('readonly');
                                }
                                if(response.data.overwrite_wholesale_price == 1) {
                                    $('#overwrite_wholesale_price').prop('checked', true);
                                    $('#store_wholesale_price').removeAttr('readonly');
                                }
                                if(response.data.overwrite_msrp == 1) {
                                    $('#overwrite_msrp').prop('checked', true);
                                    $('#store_product_msrp').removeAttr('readonly');
                                }
                            }else{
                                $('#store_product_title').val(response.data.product_title);
                                $('#store_product_description').val(response.data.product_description);
                                $('#store_product_price').val(response.data.product_price);
                                $('#store_product_compare_at_price').val(response.data.product_marketplace_price);
                                $('#store_product_fbc_price').val(response.data.fulfillment_by_channel_price);
                                $('#store_wholesale_price').val(response.data.product_wholesale_price);
                                $('#store_product_msrp').val(response.data.product_mrsp);
                                $('#store_product_sales_count').val('00.00');
                                $('#store_manufacturer_id').val(response.data.manufacturer_id);
                                $('#overwrite_title').prop('checked', false);
                                $('#overwrite_description').prop('checked', false);
                                $('#overwrite_price').prop('checked', false);
                                $('#overwrite_compare_at_price').prop('checked', false);
                                $('#overwrite_fulfillment_by_channel_price').prop('checked', false);
                                $('#overwrite_wholesale_price').prop('checked', false);
                                $('#overwrite_msrp').prop('checked', false);
                                $('#store_product_detail').find('input[type="text"]').attr('readonly',true);
                            }
                        }
                    },
                    error: function (xhr) {
                        /*if (xhr.responseJSON?.error) {
                            $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                        }*/
                    }
                });
            });

            $(document).on('click', '#store_product_detail [type="checkbox"]', function() {

                console.log($(this));
                let nextSibling = $(this).next();
                if($(this).prop('checked')){
                    nextSibling.removeAttr('readonly');
                }else{
                    nextSibling.attr('readonly','true');
                }
            });

            /* feature table sort*/

            $('#features_table th').click(function() {
                const table = $('#features_table');
                const rows = table.find('tbody > tr').toArray();
                const columnIndex = $(this).data('column');
                const order = $(this).data('order');
                const isAscending = order === 'asc';

                rows.sort((a, b) => {

                    let cellA = '';
                    let cellB = '';

                    if(columnIndex == 0) {
                        cellA = $(a).children('td').eq(columnIndex).find('textarea').val();
                        cellB = $(b).children('td').eq(columnIndex).find('textarea').val();
                    }else{
                        cellA = $(a).children('td').eq(columnIndex).find('input').val();
                        cellB = $(b).children('td').eq(columnIndex).find('input').val();
                    }

                    return isAscending
                        ? cellA.localeCompare(cellB, undefined, { numeric: true })
                        : cellB.localeCompare(cellA, undefined, { numeric: true });
                });

                $(this).data('order', isAscending ? 'desc' : 'asc');

                // Update sort icons
                $('th .sort-icon').text('▲'); // Reset all icons
                $(this).find('.sort-icon').text(isAscending ? '▼' : '▲'); // Set current icon

                // Append sorted rows back to the table body
                table.find('tbody').append(rows);
            });

            /* table_search_term sort*/

            $('#table_search_term th').click(function() {
                const table = $('#table_search_term');
                const rows = table.find('tbody > tr').toArray();
                const columnIndex = $(this).data('column');
                const order = $(this).data('order');
                const isAscending = order === 'asc';

                rows.sort((a, b) => {

                    const cellA = $(a).children('td').eq(columnIndex).find('input').val();
                    const cellB = $(b).children('td').eq(columnIndex).find('input').val();

                    return isAscending
                        ? cellA.localeCompare(cellB, undefined, { numeric: true })
                        : cellB.localeCompare(cellA, undefined, { numeric: true });
                });

                $(this).data('order', isAscending ? 'desc' : 'asc');

                // Update sort icons
                $('th .sort-icon').text('▲'); // Reset all icons
                $(this).find('.sort-icon').text(isAscending ? '▼' : '▲'); // Set current icon

                // Append sorted rows back to the table body
                table.find('tbody').append(rows);
            });

            /* table_title_keyword sort*/

            $('#table_title_keyword th').click(function() {
                const table = $('#table_title_keyword');
                const rows = table.find('tbody > tr').toArray();
                const columnIndex = $(this).data('column');
                const order = $(this).data('order');
                const isAscending = order === 'asc';

                rows.sort((a, b) => {

                    let cellA = $(a).children('td').eq(columnIndex).find('input').val();
                    let cellB = $(b).children('td').eq(columnIndex).find('input').val();

                    if(columnIndex == 1) {
                        // Convert to numbers if possible
                        let numA = !isNaN(parseFloat(cellA)) ? parseFloat(cellA) : cellA;
                        let numB = !isNaN(parseFloat(cellB)) ? parseFloat(cellB) : cellB;

                        if (typeof numA === 'number' && typeof numB === 'number') {
                            return isAscending ? numA - numB : numB - numA;
                        }
                    }

                    return isAscending
                        ? cellA.localeCompare(cellB, undefined, { numeric: true })
                        : cellB.localeCompare(cellA, undefined, { numeric: true });
                });

                $(this).data('order', isAscending ? 'desc' : 'asc');

                // Update sort icons
                $('th .sort-icon').text('▲'); // Reset all icons
                $(this).find('.sort-icon').text(isAscending ? '▼' : '▲'); // Set current icon

                // Append sorted rows back to the table body
                table.find('tbody').append(rows);
            });

            $("#sortable-table tbody").sortable({
                cursor: "move",
                update: function (event, ui) {
                    let order = [];
                    $("#sortable-table tbody tr").each(function (index, element) {
                        order.push($(element).data("id"));
                    });

                    $.ajax({
                        url: "{{ route('catalog.imageorder') }}",
                        method: "POST",
                        data: {
                            order: order,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (response) {
                            if (response.success) {
                                //alert("Order updated successfully!");
                            }
                        }
                    });
                }
            });

            $(document).on('click', '.delete-btn', function() {
                var imageId = $(this).data('id');
                var token = '{{ csrf_token() }}'; // CSRF token for security

                // Confirm the deletion
                if (confirm("Are you sure you want to delete this image?")) {
                    $.ajax({
                        url: "{{ url('catalog/deleteimg') }}",
                        type: 'post',
                        data: {
                            image_id: imageId,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                $('.pro-img-' + imageId).remove();
                                alert(response.success);
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }
            });

            $(document).on('click', '.delete-alt-btn', function() {
                var imageId = $(this).data('id');
                var token = '{{ csrf_token() }}'; // CSRF token for security

                // Confirm the deletion
                if (confirm("Are you sure you want to delete this image?")) {
                    $.ajax({
                        url: "{{ url('catalog/deletealtimg') }}",
                        type: 'post',
                        data: {
                            image_id: imageId,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                $('.pro-alt-img-' + imageId).remove();
                                alert(response.success);
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }
            });

            $("#sortable-alt-table tbody").sortable({
                cursor: "move",
                update: function (event, ui) {
                    let order = [];
                    $("#sortable-alt-table tbody tr").each(function (index, element) {
                        order.push($(element).data("id"));
                    });

                    $.ajax({
                        url: "{{ route('catalog.altimageorder') }}",
                        method: "POST",
                        data: {
                            order: order,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (response) {
                            if (response.success) {
                                //alert("Order updated successfully!");
                            }
                        }
                    });
                }
            });

            $(document).on('click','.mainImage',function () {

                const galleryDiv = $('#ajax-gallery'); // Reference to the hidden gallery div
                galleryDiv.empty();

                const anchor = $('<a>')
                    .attr('href', $(this).attr('src'))
                    .attr('data-lightbox', 'gallery')
                    .attr('data-title', 'Gallery Image');

                const img = $('<img>')
                    .attr('src', $(this).attr('src'))
                    .css('display', 'none'); // Hidden thumbnails

                anchor.append(img);
                galleryDiv.append(anchor);
                galleryDiv.find('a:first').trigger('click');

            });

            // Add new field group
            $(document).on('click', '#add_specification', function() {

                $.ajax({
                    url: "{{ route('catalog.getspecificationoption') }}",
                    method: "POST",
                    data: {
                        store_type_id: $('#specification_store_type_id').val(),
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {

                        var html = '<tr class="field-group">';
                        html += '<td><select class="form-control select2 specification" name="specification[]">'+response.option+'</select></td>';
                        html += '<td class="field_option">';
                        if(response.field == 'select'){
                            html += '<select class="form-control" name="specification_value[]">';
                            html += response.valueoption;
                            html += '</select>';
                        }else{
                            html += '<textarea class="form-control" name="specification_value[]">';
                            html += response.valueoption;
                            html += '</textarea>';
                        }
                        html += '</td></tr>';


                        $('#specification_table tbody').append(html);

                        $('.specification').select2();
                        $('.select2').on('select2:open', function () {
                            // Wait for dropdown to render before focusing the search field
                            setTimeout(function () {
                                document.querySelector('.select2-container--open .select2-search__field').focus();
                            }, 0);
                        });
                    }
                });



            });

            // Remove field group
            $(document).on('click', '.remove-specification', function() {
                $(this).parents('tr.field-group').remove();
            });

            loadSpecification();

            $(document).on('change', '#specification_store_type_id', function() {
                loadSpecification();
            });

            $(document).on('change', 'select[name="specification[]"]', function() {
                var element = $(this);
                $.ajax({
                    url: "{{ route('catalog.getfieldoption') }}",
                    method: "POST",
                    data: {
                        field_name: $(this).val(),
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        var htmloption = '';
                        if(response.field == 'select'){
                            htmloption += '<select class="form-control" name="specification_value[]">';
                            htmloption += response.option;
                            htmloption += '</select>';
                        }else{
                            htmloption += '<textarea class="form-control" name="specification_value[]">';
                            htmloption += response.option;
                            htmloption += '</textarea>';
                        }

                        element.parents('tr').find('.field_option').html(htmloption);
                    }
                });
            });

            var hash = window.location.hash;

            if (hash) {
                var $tabLink = $('a[href="' + hash + '"]');

                if ($tabLink.length) {
                    // Deactivate all tabs and tab contents
                    $('.nav-link').removeClass('active');
                    $('.tab-pane').removeClass('active show');

                    // Activate selected tab and tab content
                    $tabLink.addClass('active');
                    $(hash).addClass('active show');
                }
            }

            // When user clicks a tab, update the URL hash
            $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
                var target = $(e.target).attr("href");
                window.location.hash = target;
            });

        });
    </script>
@stop
