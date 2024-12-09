@extends('adminlte::page')


@section('content_header')
    <h1>{{ isset($storeCategory) ? 'Edit Store Type' : 'Add Store Type' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($storeCategory) ? route('store_category.update',['id' => $storeCategory['id']]) : route('store_category.store') }}"
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
                                   placeholder="Enter Code" value="{{ old('code', $storeCategory['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="store_type_id">Select Channel</label>
                        <select class="form-control @error('store_type_id') is-invalid @enderror"
                                id="store_type_id"
                                name="store_type_id">
                            <option value="">Select channel</option>
                            @foreach($storeType as $storeTypeInd)
                                <option value="{{ $storeTypeInd->id }}"
                                        @if(isset($storeCategory) && $storeCategory['store_type_id'] == $storeTypeInd->id) selected @endif>{{ $storeTypeInd->name }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('store_type_id'))
                            <span class="text-danger">{{ $errors->first('store_type_id') }}</span>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Category Title</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter name" value="{{ old('name', $storeCategory['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($storeCategory))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
