@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($device_rating) ? 'Edit Device Rating' : 'Add Device Rating' }}</h1>
@stop

@section('content')
    <form action="{{ isset($device_rating) ? route('device_ratings.update',['id' => $device_rating['id']]) : route('device_ratings.store') }}" method="POST">
    @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input @error('status') is-invalid @enderror"
                                   @if(isset($device_rating) && $device_rating->status === 'active') checked @endif name="status" id="status">
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
                            <label for="name">Device Rating</label>
                            <input type="text" class="form-control @error('value') is-invalid @enderror" id="value"
                                   name="value"
                                   placeholder="Enter Device Rating" value="{{ $device_rating['value'] ?? '' }}">
                            @if ($errors->has('value'))
                                <span class="text-danger">{{ $errors->first('value') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($device_rating))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop

