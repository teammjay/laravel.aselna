@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($product_target_audience) ? 'Edit Product Target Audience' : 'Add Product Target Audience' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($product_target_audience) ? route('product_target_audience.update',['id' => $product_target_audience['id']]) : route('product_target_audience.store') }}"
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
                                   placeholder="Enter Product Target Audience Name" value="{{ old('name', $product_target_audience['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('product_target_audience.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($product_target_audience))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
