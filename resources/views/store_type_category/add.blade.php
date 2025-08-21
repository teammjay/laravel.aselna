@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($store_type_category) ? 'Edit Store Type Category' : 'Add Store Type Category' }}</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
       
        .select2-dropdown {
            z-index: 9999;
        }
        .select2-container {
            width: 100% !important;
            /*margin: 15px 0;*/
        }

        .select2-container--default .select2-selection--single {
            border: 1px solid #ced4da;
            padding: .46875rem .75rem;
            height: calc(2.25rem + 2px);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow b{
            top:70%;
        }
    </style>
@stop

@section('content')
    <form
        action="{{ isset($store_type_category) ? route('store_type_category.update',['id' => $store_type_category['id']]) : route('store_type_category.store') }}"
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
                                   placeholder="Enter Code" value="{{ old('code', $store_type_category['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">MarketPlace Code</label>
                            <input type="text" class="form-control @error('marketplace_code') is-invalid @enderror" id="marketplace_code"
                                   name="marketplace_code"
                                   placeholder="Enter Code" value="{{ old('marketplace_code', $store_type_category['marketplace_code'] ?? '') }}">
                            @if ($errors->has('marketplace_code'))
                                <span class="text-danger">{{ $errors->first('marketplace_code') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Name" value="{{ old('name', $store_type_category['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="store_type_id">Store Type</label>
                            <select class="form-control @error('store_type_id') is-invalid @enderror" id="store_type_id" name="store_type_id">
                                <option value="">--</option>
                                @if(count($store_types ?? []) > 0)
                                    @foreach($store_types as $sType)
                                        <option value="{{ $sType->id }}" @if((isset($store_type_category) && $store_type_category['store_type_id'] === $sType->id)) selected @endif >{{ $sType->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('store_type_category_id'))
                                <span class="text-danger">{{ $errors->first('store_type_category_id') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="parent_id">Parent</label>
                            <select class="form-control @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                                <option value="">--</option>
                                @if(count($store_type_categories ?? []) > 0)
                                    @foreach($store_type_categories as $sCat)
                                        <option value="{{ $sCat->id }}" @if((isset($store_type_category) && $store_type_category['parent_id'] === $sCat->id)) selected @endif >{{ $sCat->name }}</option>
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
                            <label for="store_type_code">Store Type Code</label>
                            <input type="text" class="form-control" id="store_type_code"
                                   name="store_type_code"
                                   placeholder="Enter store type code" value="{{ old('store_type_code', $store_type_category['store_type_code'] ?? '') }}">
                        </div>
                    </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($store_type_category))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop

@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {

            $('#parent_id').select2();
        });
    </script>
@stop
