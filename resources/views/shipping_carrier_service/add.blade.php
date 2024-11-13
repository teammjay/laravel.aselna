@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($shipping_carrier_service) ? 'Edit Shipping Carrier' : 'Add Shipping Carrier' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($shipping_carrier_service) ? route('shipping_carrier_service.update',['id' => $shipping_carrier_service['id']]) : route('shipping_carrier_service.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Shipping Carrier Name" value="{{ old('name', $shipping_carrier_service['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="generic_service_method">Generic Method</label>
                            <select class="form-control @error('code') is-invalid @enderror" id="generic_service_method" name="generic_service_method">
                                <option value="1">Dumy Method</option>

                            </select>
                            @if ($errors->has('generic_service_method'))
                                <span class="text-danger">{{ $errors->first('generic_service_method') }}</span>
                            @endif
                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="shipping_carrier_id">Shipping Carrier</label>
                            <select class="form-control @error('code') is-invalid @enderror" id="shipping_carrier_id" name="shipping_carrier_id">
                                <option value="">--</option>
                                @if(count($shipping_carriers ?? []) > 0)
                                    @foreach($shipping_carriers as $sCarrier)
                                        <option value="{{ $sCarrier->id }}" @if((isset($shipping_carrier_service) && $shipping_carrier_service['shipping_carrier_id'] === $sCarrier->id)) selected @endif >{{ $sCarrier->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('shipping_carrier_id'))
                                <span class="text-danger">{{ $errors->first('shipping_carrier_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label>&nbsp;</label>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-3">
                                <label for="included_insurance">Included Insurance</label>
                            </div>
                            <div class="col-md-3">
                                <input type="checkbox" name="included_insurance" id="included_insurance" @if((isset($shipping_carrier_service) && $shipping_carrier_service['included_insurance'] === 1)) checked @endif >
                                @if ($errors->has('included_insurance'))
                                    <span class="text-danger">{{ $errors->first('included_insurance') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($shipping_carrier_service))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
