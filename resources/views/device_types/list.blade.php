@extends('adminlte::page')

@section('title', 'Catalog')

@section('content_header')
    <h1>Device Types</h1>
@stop

@section('content')
    @csrf
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}</div>
    @endif
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('device_types.create') }}" class="btn btn-primary">
                Add Device Type
            </a>
        </div>
    </div>
    <div class="card card-primary">
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
                            {{--                            <select class="custom-select" id="product_code_operator" name="product_code_operator">--}}
                            {{--                                <option value="=">=</option>--}}
                            {{--                                <option value="contains">contains</option>--}}
                            {{--                                <option value="starts_with">starts with</option>--}}
                            {{--                                <option value="ends_with">ends with</option>--}}
                            {{--                            </select>--}}
                        </div>
                        <div class="col-6">
                            <input type="text" name="name" id="name" class="form-control">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <table id="deviceTypeTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Device Type Name</th>
            <th>Status</th>
            <th>Last modified by</th>
            <th>Last modified at</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($deviceTypes as $deviceType)
            <tr>
                <td>{{ $deviceType->name }}</td>
                <td>{{ ucfirst($deviceType->status) }}</td>
                <td>{{ $deviceType->updated_by }}</td>
                <td>{{ $deviceType->updated_at->format('d M Y g:i A') }}</td>
                <td>
                    <a href="{{ route('device_types.edit', $deviceType->id) }}" class="btn btn-primary">
                        Edit
                    </a>
                    <button type="button" class="btn btn-danger delete-btn" data-module="device_types" data-id="{{ $deviceType->id }}" title="Remove">Delete</button>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>

@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#deviceTypeTable').DataTable(
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
