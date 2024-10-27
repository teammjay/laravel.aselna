@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($deviceType) ? 'Edit Device Type' : 'Add Device Type' }}</h1>
@stop

@section('content')
    <form action="{{ isset($deviceType) ? route('device_types.update',['id' => $deviceType['id']]) : route('device_types.store') }}" method="POST">
    @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input @error('code') is-invalid @enderror"
                                   @if(isset($deviceType) && $deviceType->status === 'active') checked @endif name="status" id="status">
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
                            <label for="name">Device Type Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                   placeholder="Enter brand name"  value="{{ $deviceType['name'] ?? '' }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code"
                                placeholder="Enter code" value="{{ $deviceType['code'] ?? '' }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes1">Notes 1</label>
                            <textarea class="form-control @error('notes1') is-invalid @enderror" id="notes1" name="notes1"
                                      placeholder="Enter Notes">{{ $deviceType['notes1'] ?? '' }}</textarea>
                            @if ($errors->has('notes1'))
                                <span class="text-danger">{{ $errors->first('notes1') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-center">
            <button type="submit" class="btn btn-success">Submit</button>
            @if(!isset($deviceType))
                <button type="reset" class="btn btn-primary">Reset</button>
            @endif
        </div>
    </form>
@stop
