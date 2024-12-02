@extends('adminlte::page')

@section('title', 'Interface')

@section('content_header')
    <h1>Interface Devices</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('device_interface.create') }}" class="btn btn-primary">
                Add Interface Device
            </a>
        </div>
    </div>
    <table id="deviceInterfaceTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Device Name</th>
            <th>Interface Name</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($deviceInterfaces as $deviceInterface)
            <tr>
                <td>{{$deviceInterface->device->name}}</td>
                <td>{{$deviceInterface->interfaces->name}}</td>
                <td>
                    <a href="{{ route('device_interface.edit', $deviceInterface->id) }}" class="btn btn-primary">
                        Edit
                    </a>
                    <a href="{{ route('device_interface.delete', $deviceInterface->id) }}" class="btn btn-danger">
                        Delete
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@Stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#deviceInterfaceTable').DataTable();
        });
    </script>
@Stop
