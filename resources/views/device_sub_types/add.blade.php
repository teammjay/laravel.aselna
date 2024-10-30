@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($deviceSubType) ? 'Edit Device Sub Type' : 'Add Device Sub Type' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($deviceSubType) ? route('device_sub_types.update',['id' => $deviceSubType['id']]) : route('device_sub_types.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input @error('code') is-invalid @enderror"
                                   @if(isset($deviceSubType) && $deviceSubType->status === 'active') checked @endif name="status" id="status">
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
                            <label for="name">Device Sub Type Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Device sub type" value="{{ $deviceSubType['name'] ?? '' }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="device_type_id">Device Type</label>
                            <select class="form-control @error('device_type_id') is-invalid @enderror" id="device_type_id"
                                    name="device_type_id">
                                <option value="">Select Device Type</option>
                                @foreach($deviceTypes as $deviceType)
                                    <option value="{{ $deviceType->id }}"
                                            @if(isset($deviceSubType) && $deviceSubType['device_type_id'] == $deviceType->id) selected @endif>{{ $deviceType->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('device_type_id'))
                                <span class="text-danger">{{ $errors->first('device_type_id') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes1">Notes 1</label>
                            <textarea class="form-control @error('notes1') is-invalid @enderror" id="notes1" name="notes1"
                                      placeholder="Enter Notes">{{ $deviceSubType['notes1'] ?? '' }}</textarea>
                            @if ($errors->has('notes1'))
                                <span class="text-danger">{{ $errors->first('notes1') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code"
                                   placeholder="Enter code" value="{{ $deviceSubType['code'] ?? '' }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-center">
            <button type="submit" class="btn btn-success">Submit</button>
            @if(!isset($deviceSubType))
                <button type="reset" class="btn btn-primary">Reset</button>
            @endif
        </div>
    </form>
@endsection
