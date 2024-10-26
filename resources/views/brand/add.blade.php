@extends('adminlte::page')

@section('title', 'Add Product')

@section('content_header')
    <h1>{{ isset($brand) ? 'Edit Brand' : 'Add Brand' }}</h1>
@stop

@section('content')
    <form action="{{ isset($brand) ? route('brand.update',['id' => $brand['id']]) : route('brand.store') }}" method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input @error('code') is-invalid @enderror"  @if($brand->status === 'active') checked @endif name="status" id="status">
                            <label class="form-check-label" for="status">Status</label>
                            @error('status')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Brand Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                   placeholder="Enter brand name"  value="{{ $brand['name'] ?? '' }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code"
                                   placeholder="Enter code" value="{{ $brand['code'] ?? '' }}">
                            @error('code')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="order_no">Order No</label>
                            <input type="text" class="form-control @error('order_no') is-invalid @enderror" id="order_no" name="order_no"
                                   placeholder="Enter order no" value="{{ $brand['order_no'] ?? '' }}">
                            @error('order_no')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
{{--                Add Image Upload Sectoin here--}}
{{--                <div class="row">--}}
{{--                    <div class="col-md-6">--}}
{{--                        <div class="form-group">--}}
{{--                            <label for="image">Image</label>--}}
{{--                            <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image">--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes1">Notes 1</label>
                            <textarea class="form-control @error('notes1') is-invalid @enderror" id="notes1" name="notes1"
                                      placeholder="Enter Notes">{{ $brand['notes1'] ?? '' }}</textarea>
                            @if ($errors->has('notes1'))
                                <span class="text-danger">{{ $errors->first('notes1') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes2">Notes 2</label>
                            <textarea class="form-control @error('notes2') is-invalid @enderror" id="notes2" name="notes2"
                                      placeholder="Enter Notes">{{ $brand['notes2'] ?? '' }}</textarea>
                            @if ($errors->has('notes2'))
                                <span class="text-danger">{{ $errors->first('notes2') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes3">Notes 3</label>
                            <textarea class="form-control @error('notes3') is-invalid @enderror" id="notes3" name="notes3"
                                      placeholder="Enter Notes">{{ $brand['notes3'] ?? '' }}</textarea>
                            @if ($errors->has('notes3'))
                                <span class="text-danger">{{ $errors->first('notes3') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($brand))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
