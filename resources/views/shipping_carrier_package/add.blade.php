@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($shipping_carrier_package) ? 'Edit Shipping Carrier Package' : 'Add Shipping Carrier Package' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($shipping_carrier_package) ? route('shipping_carrier_package.update',['id' => $shipping_carrier_package['id']]) : route('shipping_carrier_package.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="size">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"  name="name"
                                   placeholder="Enter Shipping Carrier Package Name" value="{{ old('name', $shipping_carrier_package['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dimensions_length">Dimensions Length</label>
                            <input type="text" class="form-control" id="dimensions_length" name="dimensions_length" placeholder="Enter Dimention Length" value="{{ old('dimensions_length', $shipping_carrier_package['dimensions_length'] ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="shipping_carrier_id">Shipping Carrier</label>
                            <select class="form-control" id="shipping_carrier_id" name="shipping_carrier_id">
                                <option value=""></option>
                                @if(count($shippingCarrier ?? []) > 0)
                                    @foreach($shippingCarrier as $sc)
                                        <option value="{{ $sc->id }}" @if(isset($shipping_carrier_package['shipping_carrier_id']) && $sc->id == $shipping_carrier_package['shipping_carrier_id']) selected @endif>{{ $sc->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dimensions_width">Dimensions Length</label>
                            <input type="text" class="form-control" id="dimensions_width" name="dimensions_width" placeholder="Enter Dimention Width" value="{{ old('dimensions_width', $shipping_carrier_package['dimensions_width'] ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dimensions_length_unit">Dimensions Length Unit</label>
                            <input type="text" class="form-control" id="dimensions_length_unit" name="dimensions_length_unit" value="{{ old('dimensions_length_unit', $shipping_carrier_package['dimensions_length_unit'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dimensions_height">Dimensions Height</label>
                            <input type="text" class="form-control" id="dimensions_height" name="dimensions_height" placeholder="Enter Dimention Height" value="{{ old('dimensions_height', $shipping_carrier_package['dimensions_height'] ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="non_machinable">Non Machinable</label>
                            <input type="checkbox" class="@error('non_machinable') is-invalid @enderror" id="non_machinable"
                                   name="non_machinable" {{ (isset($shipping_carrier_package['non_machinable']) && $shipping_carrier_package['non_machinable'] == 1 ) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($shipping_carrier_package))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
