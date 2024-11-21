@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($product_type) ? 'Edit Product Type' : 'Add Product Type' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($product_type) ? route('product_type.update',['id' => $product_type['id']]) : route('product_type.store') }}"
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
                                   placeholder="Enter Product Type Code" value="{{ old('code', $product_type['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Product Type Name" value="{{ old('name', $product_type['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="parent_id">Parent</label>
                            <select class="form-control" name="parent_id" id="parent_id">
                                <option value="">--</option>
                                @if(count($product_types ?? []) > 0)
                                    @foreach($product_types as $ptype)
                                        <option value="{{ $ptype->id }}" {{ (isset($product_type['parent_id']) && $ptype->id == $product_type['parent_id']) ? 'selected' : '' }}>{{ $ptype->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('parent_id'))
                                <span class="text-danger">{{ $errors->first('parent_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="singular_name">Singular Name</label>
                            <input type="text" class="form-control @error('singular_name') is-invalid @enderror" id="singular_name"
                                   name="singular_name"
                                   placeholder="Enter Product Singular Name" value="{{ old('singular_name', $product_type['singular_name'] ?? '') }}">
                        </div>

                        <div class="form-group">
                            <label for="simple_name">Simple Name</label>
                            <input type="text" class="form-control @error('simple_name') is-invalid @enderror" id="simple_name"
                                   name="simple_name"
                                   placeholder="Enter Product Simple Name" value="{{ old('simple_name', $product_type['simple_name'] ?? '') }}">
                        </div>

                        <div class="form-group">
                            <label for="sort_order">Sort Order</label>
                            <input type="text" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order"
                                   name="sort_order"
                                   placeholder="1" value="{{ old('sort_order', $product_type['sort_order'] ?? '') }}">
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <button type="submit" class="btn btn-secondary">Cancel</button>
                @if(!isset($product_type))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
