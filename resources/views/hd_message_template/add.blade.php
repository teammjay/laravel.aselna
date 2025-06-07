@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($hd_message_template) ? 'Edit HD Message Template' : 'Add HD Message Template' }}</h1>
@stop

@section('adminlte_css')
    <style>
        .stores li {
            display: inline-block;
            width: 49%;
            margin-bottom: 10px;
        }

    </style>
@stop

@section('content')
    <form
        action="{{ isset($hd_message_template) ? route('hd_message_template.update',['id' => $hd_message_template['id']]) : route('hd_message_template.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">

                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter template name" value="{{ old('name', $hd_message_template['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="store_type_id">Store Type</label>
                            <select class="form-control" name="store_type_id" id="store_type_id">
                                <option value=""></option>
                                @if(count($store_types ?? []) > 0)
                                    @foreach($store_types as $stype)
                                        <option value="{{ $stype->id }}" {{ (isset($hd_message_template['store_type_id']) && $stype->id == $hd_message_template['store_type_id']) ? 'selected' : ''}}>{{ $stype->name }}</option>
                                    @endforeach
                                @endif

                            </select>
                            @if ($errors->has('store_type_id'))
                                <span class="text-danger">{{ $errors->first('store_type_id') }}</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="template_code">Template Code</label>
                            <textarea class="form-control @error('template_code') is-invalid @enderror" id="template_code" name="template_code" placeholder="Enter template code" rows="15">{{ old('template_code', $hd_message_template['template_code'] ?? '') }}</textarea>
                            @if ($errors->has('template_code'))
                                <span class="text-danger">{{ $errors->first('template_code') }}</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="print_to_file">Print to file</label>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" name="print_to_file" id="print_to_file" @if((isset($hd_message_template) && $hd_message_template['print_to_file'] === 1)) checked @endif >
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="replacement_order_shipped">Replacement Order Shipped</label>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" name="replacement_order_shipped" id="replacement_order_shipped" @if((isset($hd_message_template) && $hd_message_template['replacement_order_shipped'] === 1)) checked @endif >
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('hd_message_template.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($hd_message_template))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
