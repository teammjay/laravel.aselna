@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($printer_client) ? 'Edit Printer Client' : 'Add Printer Client' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($printer_client) ? route('printer_client.update',['id' => $printer_client['id']]) : route('printer_client.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                   placeholder="Enter Name" value="{{ old('name', $printer_client['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="machine_key">Machine Key</label>
                            <input type="text" class="form-control @error('machine_key') is-invalid @enderror" id="machine_key"
                                   name="machine_key"
                                   placeholder="Machine Key" value="{{ old('machine_key', $printer_client['machine_key'] ?? '') }}">
                            @if ($errors->has('machine_key'))
                                <span class="text-danger">{{ $errors->first('machine_key') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="active">Active</label>
                            <input type="checkbox" class="@error('active') is-invalid @enderror" id="active"
                                   name="active" {{ (isset($printer_client['active']) && $printer_client['active'] == 1 ) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <input type="hidden" name="connection_type" value="0">
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($printer_client))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
