@extends('adminlte::page')

@section('title', 'Catalog')

@section('content_header')
    <h1>Device Series</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('device_series.create') }}" class="btn btn-primary">
                Add Device Series
            </a>
        </div>
    </div>
    {{--<div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-4">
                    <div class="row">
                        <div class="col-4">
                            Name
                        </div>
                        <div class="col-2">
                            --}}{{--                            <select class="custom-select" id="product_code_operator" name="product_code_operator">--}}{{--
                            --}}{{--                                <option value="=">=</option>--}}{{--
                            --}}{{--                                <option value="contains">contains</option>--}}{{--
                            --}}{{--                                <option value="starts_with">starts with</option>--}}{{--
                            --}}{{--                                <option value="ends_with">ends with</option>--}}{{--
                            --}}{{--                            </select>--}}{{--
                        </div>
                        <div class="col-6">
                            <input type="text" name="name" id="name" class="form-control">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>--}}
    <table id="deviceSeriesTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Device Series Name</th>
            <th>Status</th>
            <th>Last modified by</th>
            <th>Last modified at</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($device_series as $deviceSerie)
            <tr>
                <td>{{ $deviceSerie->name }}</td>
                <td>{{ ucfirst($deviceSerie->status) }}</td>
                <td>{{ $deviceSerie->updated_by }}</td>
                <td>{{ $deviceSerie->updated_at->format('d M Y g:i A') }}</td>
                <td>
                    <a href="{{ route('device_series.edit', $deviceSerie->id) }}" class="btn btn-primary">
                        Edit
                    </a>
                    <a href="{{ route('device_series.delete', $deviceSerie->id) }}" class="btn btn-danger">
                        Delete
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
@stop
@section('js')
<script>
    $(document).ready(function () {
        $('#deviceSeriesTable').DataTable(
            {
                "processing": true,
                'searching': true,
                "paging": true,
                "stateSave":true,
                stateSaveCallback: function(settings, data) {
                    sessionStorage.setItem(
                        'DataTables_' + settings.sInstance,
                        JSON.stringify(data)
                    );
                },

                stateLoadCallback: function(settings) {
                    return JSON.parse(
                        sessionStorage.getItem('DataTables_' + settings.sInstance)
                    );
                },
            }
        );
    });
</script>
@stop
