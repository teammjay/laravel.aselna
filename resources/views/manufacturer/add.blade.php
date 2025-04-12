@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($manufacturer) ? 'Edit Manufacturer' : 'Add Manufacturer' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($manufacturer) ? route('manufacturer.update',['id' => $manufacturer['id']]) : route('manufacturer.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Manufacturer Name" value="{{ old('name', $manufacturer['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code"
                                   name="code"
                                   placeholder="Enter Manufacturer Code" value="{{ old('name', $manufacturer['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="client_id">Client</label>
                            <select class="form-control @error('code') is-invalid @enderror" id="client_id" name="client_id">
                                <option value="92d178fc-a647-11ef-944f-00e04c3c886d">hakan - fonus</option>
                            </select>
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <input type="text" class="form-control @error('notes') is-invalid @enderror" id="notes"
                                   name="notes"
                                   placeholder="Enter Notes" value="{{ old('notes', $manufacturer['notes'] ?? '') }}">

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sort_order">Sort Order</label>
                            <input type="text" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order"
                                   name="sort_order"
                                   placeholder="Sort Order" value="{{ old('sort_order', $manufacturer['sort_order'] ?? '') }}">
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($manufacturer))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
