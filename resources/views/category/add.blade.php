@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($category) ? 'Edit Category' : 'Add Category' }}</h1>
@stop

@section('content')
    <form action="{{ isset($category) ? route('categories.edit', ['id' => $category['id']]) : route('categories.store') }}" method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input @error('status') is-invalid @enderror"
                               @if((isset($category) && $category->status === 'active') || old('status') == 'on') checked
                               @endif name="status"
                               id="status">
                        <label class="form-check-label" for="status">Status</label>
                        @error('status')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Category Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Device Name" value="{{ old('name', $category['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Code</label>
                            <input type="text" class="form-control @error('ccode') is-invalid @enderror" id="code"
                                   name="code"
                                   placeholder="Enter Code" value="{{ old('code', $category['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Order</label>
                            <input type="text" class="form-control @error('order') is-invalid @enderror" id="order"
                                   name="order"
                                   placeholder="Enter Order" value="{{ old('order', $category['order'] ?? '') }}">
                            @if ($errors->has('order'))
                                <span class="text-danger">{{ $errors->first('order') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($category))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
