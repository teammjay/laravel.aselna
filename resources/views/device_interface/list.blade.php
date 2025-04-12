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
        </tbody>
    </table>
@Stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#deviceInterfaceTable').DataTable(
                {
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: "{{ route('device-interface-data') }}",
                    columns: [
                        {
                            data: null, name: 'device_name',
                            render: function (data, type, row) {
                                return row.device.name;
                            }
                        },
                        {
                            data: null, name: 'interface_name',
                            render: function (data, type, row) {
                                return row.interfaces.name;
                            }
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            render: function (data, type, row) {
                                return '<a href="{{ route('device_interface.edit', ':id') }}" class="btn btn-primary">Edit</a>'.replace(':id', row.id) +
                                    '<a href="{{ route('device_interface.delete', ':id') }}" class="btn btn-danger">Delete</a>'.replace(':id', row.id);
                            }
                        },
                    ]
                }
            );
        });
    </script>
@Stop
