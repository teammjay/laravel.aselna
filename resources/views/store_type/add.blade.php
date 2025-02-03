@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($store_types) ? 'Edit Store Type' : 'Add Store Type' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($store_types) ? route('store_type.update',['id' => $store_types['id']]) : route('store_type.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code"
                                   name="code"
                                   placeholder="Enter Code" value="{{ old('code', $store_types['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Name" value="{{ old('name', $store_types['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="max_title_length">Max. Title Length</label>
                            <input type="text" class="form-control @error('max_title_length') is-invalid @enderror" id="max_title_length"
                                   name="max_title_length"
                                   placeholder="Max. Title Length" value="{{ old('max_title_length', $store_types['max_title_length'] ?? '') }}">
                            @if ($errors->has('max_title_length'))
                                <span class="text-danger">{{ $errors->first('max_title_length') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="product_qty">Total Product Quantity</label>
                            <input type="text" class="form-control @error('product_qty') is-invalid @enderror" id="product_qty"
                                   name="product_qty"
                                   placeholder="Product Quantity" value="{{ old('product_qty', $store_types['product_qty'] ?? '') }}">
                            @if ($errors->has('product_qty'))
                                <span class="text-danger">{{ $errors->first('product_qty') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="max_variation_name_length">Max. Variation Name Length</label>
                            <input type="text" class="form-control @error('max_variation_name_length') is-invalid @enderror" id="max_variation_name_length"
                                   name="max_variation_name_length"
                                   placeholder="Max. Variation Name Length" value="{{ old('max_variation_name_length', $store_types['max_variation_name_length'] ?? '') }}">
                            @if ($errors->has('max_variation_name_length'))
                                <span class="text-danger">{{ $errors->first('max_variation_name_length') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="max_listing_variants">Max. Listing Variants</label>
                            <input type="text" class="form-control @error('max_listing_variants') is-invalid @enderror" id="max_listing_variants"
                                   name="max_listing_variants"
                                   placeholder="Max. Listing Variants" value="{{ old('max_listing_variants', $store_types['max_listing_variants'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="max_random_title_length">Max. Random Title Length</label>
                            <input type="text" class="form-control @error('max_random_title_length') is-invalid @enderror" id="max_random_title_length"
                                   name="max_random_title_length"
                                   placeholder="Max. Random Title Length" value="{{ old('max_random_title_length', $store_types['max_random_title_length'] ?? '') }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="min_image_width">Min. Image Width</label>
                            <input type="text" class="form-control @error('min_image_width') is-invalid @enderror" id="min_image_width"
                                   name="min_image_width"
                                   placeholder="Min. Image Width" value="{{ old('min_image_width', $store_types['min_image_width'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="min_image_height">Min Image Height</label>
                            <input type="text" class="form-control @error('min_image_height') is-invalid @enderror" id="min_image_height"
                                   name="min_image_height"
                                   placeholder="Min Image Height" value="{{ old('min_image_height', $store_types['min_image_height'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="order_shipment_control_backward_days">Order Shipment Control Backward Days</label>
                            <input type="text" class="form-control @error('order_shipment_control_backward_days') is-invalid @enderror" id="order_shipment_control_backward_days"
                                   name="order_shipment_control_backward_days"
                                   placeholder="Order Shipment Control Backward Days" value="{{ old('order_shipment_control_backward_days', $store_types['order_shipment_control_backward_days'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="acknowledge_order">acknowledge order</label>
                            <input type="checkbox" class="@error('acknowledge_order') is-invalid @enderror" id="acknowledge_order"
                                   name="acknowledge_order" {{ (isset($store_types['acknowledge_order']) && $store_types['acknowledge_order'] == 1 ) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="create_image_per_listing">create image per listing</label>
                            <input type="checkbox" class="@error('create_image_per_listing') is-invalid @enderror" id="create_image_per_listing"
                                   name="create_image_per_listing" {{ ( isset($store_types['create_image_per_listing']) && $store_types['create_image_per_listing'] == 1) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($store_types))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
