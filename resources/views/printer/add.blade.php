@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($printers) ? 'Edit Printer' : 'Add Printer' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($printers) ? route('printer.update',['id' => $printers['id']]) : route('printer.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="printer_client_id">Printer Client</label>
                            <select class="form-control" name="printer_client_id" id="printer_client_id">
                                <option value="">--</option>
                                @if(count($printer_clients ?? []) > 0)
                                    @foreach($printer_clients as $pclient)
                                        <option value="{{ $pclient->id }}" {{ (isset($printers['printer_client_id']) && $pclient->id == $printers['printer_client_id']) ? 'selected' : '' }}>{{ $pclient->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('printer_client_id'))
                                <span class="text-danger">{{ $errors->first('printer_client_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                   placeholder="Enter Name" value="{{ old('name', $printers['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="label_format">Label Format</label>
                            <input type="text" class="form-control @error('label_format') is-invalid @enderror" id="label_format"
                                   name="label_format"
                                   placeholder="Label Format" value="{{ old('label_format', $printers['label_format'] ?? '') }}">
                            @if ($errors->has('label_format'))
                                <span class="text-danger">{{ $errors->first('label_format') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dpi">DPI</label>
                            <input type="text" class="form-control @error('dpi') is-invalid @enderror" id="dpi"
                                   name="dpi"
                                   placeholder="DPI" value="{{ old('dpi', $printers['dpi'] ?? '') }}">
                            @if ($errors->has('dpi'))
                                <span class="text-danger">{{ $errors->first('dpi') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <input type="checkbox" class="@error('status') is-invalid @enderror" id="status"
                                   name="status" {{ (isset($printers['status']) && $printers['status'] == 1 ) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <input type="hidden" name="printer_type" value="0">
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($printers))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
