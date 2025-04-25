@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($specification_info) ? 'Edit Specification Info' : 'Add Specification Info' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($specification_info) ? route('specification_info.update',['id' => $specification_info['id']]) : route('specification_info.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="field_name">Field Name</label>
                            <input type="text" class="form-control @error('field_name') is-invalid @enderror" id="field_name"
                                   name="field_name"
                                   placeholder="Field Name" value="{{ old('field_name', $specification_info['field_name'] ?? '') }}">
                            @if ($errors->has('field_name'))
                                <span class="text-danger">{{ $errors->first('field_name') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="field_local_name">Field Local Name</label>
                            <input type="text" class="form-control @error('field_local_name') is-invalid @enderror" id="field_local_name"
                                   name="field_local_name"
                                   placeholder="Field Local Name" value="{{ old('field_local_name', $specification_info['field_local_name'] ?? '') }}">
                            @if ($errors->has('field_local_name'))
                                <span class="text-danger">{{ $errors->first('field_local_name') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="store_type_id">Store Type</label>
                            <select class="form-control" name="store_type_id" id="store_type_id">
                                <option value="">-- Store Type --</option>
                                @if(count($store_types ?? []) > 0)
                                    @foreach($store_types as $stype)
                                        <option value="{{ $stype->id }}" {{ (isset($specification_info['store_type_id']) && $stype->id == $specification_info['store_type_id']) ? 'selected' : '' }}>{{ $stype->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('store_type_id'))
                                <span class="text-danger">{{ $errors->first('store_type_id') }}</span>
                            @endif
                        </div>
                        {{--@php
                            function renderCategories($productTypes, $specification_info = [], $prefix = '') {
                                foreach ($productTypes as $ptype) {
                                    if(!empty($specification_info) && $specification_info['product_type_id'] == $ptype->id){
                                        $sel = 'selected';
                                    }else{
                                        $sel = '';
                                    }
                                    echo '<option value="' . $ptype->id . '" '.$sel.'>' . $prefix . $ptype->name . '</option>';

                                    if ($ptype->children) {
                                        renderCategories($ptype->children, $specification_info, $prefix . '-- ');
                                    }
                                }
                            }
                        @endphp
                        <div class="form-group">
                            <label for="product_type_id">Product Type</label>
                            <select class="form-control" name="product_type_id" id="product_type_id">
                                <option>-- Product Type --</option>
                                @php
                                    if(!empty($specification_info)){
                                        $sinfo = $specification_info;
                                    }else{
                                        $sinfo= [];
                                    }

                                    renderCategories($product_types,$sinfo);
                                @endphp
                            </select>
                            @if ($errors->has('product_type_id'))
                                <span class="text-danger">{{ $errors->first('product_type_id') }}</span>
                            @endif
                        </div>--}}

                        <div class="form-group">
                            <label for="required">Required</label><br>
                            <input type="checkbox" name="required" @if(isset($specification_info['required']) && $specification_info['required'] == 1) checked @endif>
                        </div>

                    </div>

                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('specification_info.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($specification_info))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
