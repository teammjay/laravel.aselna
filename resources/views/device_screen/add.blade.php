@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($device_screen) ? 'Edit Device Screen' : 'Add Device Screen' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($device_screen) ? route('device_screen.update',['id' => $device_screen['id']]) : route('device_screen.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input @error('status') is-invalid @enderror"
                                   @if(isset($device_screen) && $device_screen->status === 'active') checked
                                   @endif name="status" id="status">
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
                            <label for="size">Device Size</label>
                            <input type="text" class="form-control @error('size') is-invalid @enderror" id="size"
                                   name="size"
                                   placeholder="Enter Device Screen Size" value="{{ $device_screen['size'] ?? '' }}">
                            @if ($errors->has('size'))
                                <span class="text-danger">{{ $errors->first('size') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($device_screen))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
