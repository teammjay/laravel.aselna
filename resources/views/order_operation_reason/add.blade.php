@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($order_operation_reason) ? 'Edit Order Operation Reason' : 'Add Order Operation Reason' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($order_operation_reason) ? route('order_operation_reason.update',['id' => $order_operation_reason['id']]) : route('order_operation_reason.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="channel_code">Channel Code</label>
                            <input type="text" class="form-control @error('channel_code') is-invalid @enderror" id="channel_code"
                                   name="channel_code"
                                   placeholder="Enter Channel Code" value="{{ old('channel_code', $order_operation_reason['channel_code'] ?? '') }}">
                            @if ($errors->has('channel_code'))
                                <span class="text-danger">{{ $errors->first('channel_code') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Name" value="{{ old('name', $order_operation_reason['name'] ?? '') }}">
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
                                        <option value="{{ $sType->id }}" @if((isset($order_operation_reason) && $order_operation_reason['store_type_id'] === $sType->id)) selected @endif >{{ $sType->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('order_operation_reason_id'))
                                <span class="text-danger">{{ $errors->first('order_operation_reason_id') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($order_operation_reason))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
