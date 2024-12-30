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
            <th>Image</th>
            <th>Device Name</th>
            <th>Brand Name</th>
            <th>Device Type</th>
            <th>Status</th>
            <th>Last modified by</th>
            <th>Last modified at</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        {{--        @foreach($devices as $device)--}}
        {{--            <tr>--}}
        {{--                <td>--}}
        {{--                    <img src="{{ asset('storage/device_images/'.$device->image) }}" alt="{{ $device->name }}"--}}
        {{--                         width="50">--}}
        {{--                </td>--}}
        {{--                <td>{{$device->name}}</td>--}}
        {{--                <td>{{ isset($device->brand) ? $device->brand->name : '' }}</td>--}}
        {{--                <td>{{ isset($device->deviceType) ? $device->deviceType->name : '' }}</td>--}}
        {{--                <td>{{ ucfirst($device->status) }}</td>--}}
        {{--                <td>{{ $device->updated_by }}</td>--}}
        {{--                <td>{{ $device->updated_at->format('d M Y g:i A') }}</td>--}}
        {{--                <td>--}}
        {{--                    <a href="{{ route('devices.edit', ['id' => $device->id]) }}" class="btn btn-primary">Edit</a>--}}
        {{--                    <a href="{{ route('devices.delete', ['id' => $device->id]) }}" class="btn btn-danger">Delete</a>--}}
        {{--                </td>--}}
        {{--            </tr>--}}
        {{--        @endforeach--}}
        </tbody>
    </table>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#deviceTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('device-data') }}',
                    data: function (d) {
                        d.name = $('#name').val();
                    }
                },
                columns: [
                    {
                        data: '', name: 'image', render: function (data, type, row) {
                            return '<img src="{{ asset('storage/device_images/') }}/' + row.image + '" alt="' + row.name + '" width="50">';
                        }
                    },
                    {data: 'name', name: 'name'},
                    {data: 'brand.name', name: 'brand.name'},
                    {data: 'device_type.name', name: 'device_type.name'},
                    {data: 'status', name: 'status'},
                    {data: 'updated_by', name: 'updated_by'},
                    {
                        data: 'updated_at', name: 'updated_at', render: function (data) {
                            return moment(data).format('DD MMM YYYY hh:mm A');
                        }
                    },
                    {
                        data: 'action', name: 'action', orderable: false, searchable: false,
                        render: function (data, type, row) {
                            return '<a href="{{ route('devices.edit', ['id' => ':id']) }}'.replace(':id','') + row.id + '" class="btn btn-primary">Edit</a>' +
                                '<a href="{{ route('devices.delete', ['id' => ':id']) }}'.replace(':id','') + row.id + '" class="btn btn-danger">Delete</a>';
                        }
                    }
                ]
            });
        });
    </script>
@stop
