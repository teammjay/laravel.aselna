@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($device_series) ? 'Edit Device Series' : 'Add Device Series' }}</h1>
@stop

@section('content')
    <form action="{{ isset($device_series) ? route('device_series.update',['id' => $device_series['id']]) : route('device_series.store') }}" method="POST">
    @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input @error('code') is-invalid @enderror"
                                   @if((isset($device_series) && $device_series->status === 'active') || old('status') == 'on') checked @endif name="status" id="status">
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
                            <label for="name">Device Series Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Device Series Name" value="{{ old('name' ,$device_series['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="slug">Device Series slug</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug"
                                   name="slug"
                                   placeholder="Enter Device series slug" value="{{ old('slug',$device_series['slug'] ?? '') }}">
                            @if ($errors->has('slug'))
                                <span class="text-danger">{{ $errors->first('slug') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($device_series))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
