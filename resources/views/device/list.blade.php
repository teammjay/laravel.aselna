@extends('adminlte::page')

@section('title', 'Device')

@section('content_header')
    <h1>Device </h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('devices.create') }}" class="btn btn-primary">
                Add Device
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
    <table id="deviceTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Device Name</th>
            <th>Status</th>
            <th>Last modified by</th>
            <th>Last modified at</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($devices as $device)
            <tr>
                <td>{{$device->name}}</td>
                <td>{{ ucfirst($device->status) }}</td>
                <td>{{ $device->updated_by }}</td>
                <td>{{ $device->updated_at->format('d M Y g:i A') }}</td>
                <td>
                    <a href="{{ route('devices.edit', ['id' => $device->id]) }}" class="btn btn-primary">Edit</a>
                    <a href="{{ route('devices.delete', ['id' => $device->id]) }}" class="btn btn-danger">Delete</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#deviceTable').DataTable({});
        });
    </script>
@stop
