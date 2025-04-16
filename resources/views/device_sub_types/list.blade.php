@extends('adminlte::page')

@section('title', 'Catalog')

@section('content_header')
    <h1>Device Sub Types</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('device_sub_types.create') }}" class="btn btn-primary">
                Add Device Sub Type
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
    <table id="deviceSubTypeTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Device Sub Type Name</th>
            <th>Status</th>
            <th>Last modified by</th>
            <th>Last modified at</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($deviceSubTypes as $deviceSubType)
            <tr>
                <td>{{ $deviceSubType->name }}</td>
                <td>{{ ucfirst($deviceSubType->status) }}</td>
                <td>{{ $deviceSubType->updated_by }}</td>
                <td>{{ $deviceSubType->updated_at->format('d M Y g:i A') }}</td>
                <td>
                    <a href="{{ route('device_sub_types.edit', $deviceSubType->id) }}" class="btn btn-primary">
                        Edit
                    </a>
                    <a href="{{ route('device_sub_types.delete', $deviceSubType->id) }}" class="btn btn-danger">
                        Delete
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@stop



@section('js')
    <script>
        $(document).ready(function () {
            $('#deviceSubTypeTable').DataTable(
                {
                    "processing": true,
                    'searching': true,
                    "paging": true,
                }
            );
        });
    </script>
@stop
