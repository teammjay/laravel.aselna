@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($logistic_center) ? 'Edit Logistic Center' : 'Add Logistic Center' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($logistic_center) ? route('logistic_center.update',['id' => $logistic_center['id']]) : route('logistic_center.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="size">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Shipping Carrier Name" value="{{ old('name', $logistic_center['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="size">Client</label>
                            <select class="form-control" name="client_id" id="client_id">
                                <option value=""></option>
                                <option value="">Aselna</option>
                            </select>
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="size">Center Type</label>
                            <select class="form-control" name="center_type" id="center_type">
                                <option value=""></option>
                                <option value="1">Aselna</option>
                            </select>
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($logistic_center))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
