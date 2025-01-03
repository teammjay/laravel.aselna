@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($product_color) ? 'Edit Product Color' : 'Add Product Color' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($product_color) ? route('product_color.update',['id' => $product_color['id']]) : route('product_color.store') }}"
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
                                   placeholder="Enter Product Color Code" value="{{ old('code', $product_color['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Product Color Name" value="{{ old('name', $product_color['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="product_type_id">Product Type</label>
                            <select class="form-control" name="product_type_id" id="product_type_id">
                                <option value="">--</option>
                                @if(count($product_types ?? []) > 0)
                                    @foreach($product_types as $ptype)
                                        <option value="{{ $ptype->id }}" {{ (isset($product_color['product_type_id']) && $ptype->id == $product_color['product_type_id']) ? 'selected' : '' }}>{{ $ptype->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('parent_id'))
                                <span class="text-danger">{{ $errors->first('parent_id') }}</span>
                            @endif
                        </div>
                    </div>

                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('product_color.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($product_color))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
