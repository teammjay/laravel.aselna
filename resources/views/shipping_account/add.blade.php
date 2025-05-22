@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($shipping_account) ? 'Edit Shipping Account' : 'Add Shipping Account' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($shipping_account) ? route('shipping_account.update',['id' => $shipping_account['id']]) : route('shipping_account.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"  name="name"
                                   placeholder="Enter Shipping Carrier Package Name" value="{{ old('name', $shipping_account['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code"  name="code"
                                   placeholder="Enter Shipping Solution Name" value="{{ old('name', $shipping_account['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="production_service_url">Production Service Url</label>
                            <input type="text" class="form-control" id="production_service_url"  name="production_service_url"
                                   placeholder="Enter Production Service Url" value="{{ old('production_service_url', $shipping_account['production_service_url'] ?? '') }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="production_service_url">Sandbox Service Url</label>
                            <input type="text" class="form-control" id="sandbox_service_url"  name="sandbox_service_url"
                                   placeholder="Enter Sandbox Service Url" value="{{ old('sandbox_service_url', $shipping_account['sandbox_service_url'] ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="shipment_estimate_available">Shipment Estimate Available</label>
                            <input type="checkbox" class="custom-control" name="shipment_estimate_available" id="shipment_estimate_available" @if((isset($shipping_account) && $shipping_account['shipment_estimate_available'] === 1)) checked @endif >
                        </div>
                    </div>
                </div>


            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($shipping_account))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop