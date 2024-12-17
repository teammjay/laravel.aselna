@extends('adminlte::page')

@section('title', 'Carriers')

@section('content_header')
    <h1>Carriers</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('carrier.create') }}" class="btn btn-primary">
                Add carrier
            </a>
        </div>
    </div>

    <table id="carrierTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Sort Order</th>
            <th>Last modified by</th>
            <th>Last modified at</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($carriers as $carrier)
            <tr>
                <td>{{$carrier->name}}</td>
                <td>{{$carrier->code}}</td>
                <td>{{$carrier->sort_order}}</td>
                <td>{{ $carrier->updated_by }}</td>
                <td>{{ $carrier->updated_at }}</td>
                <td>
                    <a href="{{ route('carrier.edit', ['id' => $carrier->id]) }}" class="btn btn-primary" title="Edit"><i class="fas fa-fw fa-pencil-alt"></i></a>
                    <a href="{{ route('carrier.delete', ['id' => $carrier->id]) }}" class="btn btn-danger" title="Remove"><i class="fas fa-fw fa-trash"></i></a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#carrierTable').DataTable({});
        });
    </script>
@stop
