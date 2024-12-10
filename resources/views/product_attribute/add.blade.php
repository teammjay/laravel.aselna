@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($product_attribute) ? 'Edit Product Attribute' : 'Add Product Attribute' }}</h1>
@stop

@section('content')
    <form action="{{ isset($product_attribute) ? route('product_attribute.update',['id' => $product_attribute['id']]) : route('product_attribute.store') }}"
          method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Attribute Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Attribute Name" value="{{ old('name', $product_attribute['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="simple_attribute_name">Simple Attribute Name</label>
                            <input type="text" class="form-control @error('simple_attribute_name') is-invalid @enderror" id="simple_attribute_name"
                                   name="simple_attribute_name"
                                   placeholder="Enter Simple Attribute Name" value="{{ old('simple_attribute_name', $product_attribute['simple_attribute_name'] ?? '') }}">
                            @if ($errors->has('simple_attribute_name'))
                                <span class="text-danger">{{ $errors->first('simple_attribute_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="multiple_attribute_name">Multiple Attribute Name</label>
                            <input type="text" class="form-control @error('multiple_attribute_name') is-invalid @enderror" id="multiple_attribute_name"
                                   name="multiple_attribute_name"
                                   placeholder="Enter Multiple Attribute Name" value="{{ old('multiple_attribute_name', $product_attribute['multiple_attribute_name'] ?? '') }}">
                            @if ($errors->has('multiple_attribute_name'))
                                <span class="text-danger">{{ $errors->first('multiple_attribute_name') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($product_attribute))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop