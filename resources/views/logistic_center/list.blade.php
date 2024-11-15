@extends('adminlte::page')

@section('title', 'Logistic Center')

@section('content_header')
    <h1>Logistic Center</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('logistic_center.create') }}" class="btn btn-primary">
                Add Logistic Center
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="logisticCenterTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($logistic_centers as $lcenter)
                    <tr>
                        <td>{{ $lcenter->name }}</td>
                        <td>
                            <a href="{{ route('logistic_center.edit', $lcenter->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('logistic_center.delete', $lcenter->id) }}" class="btn btn-danger" title="Remove">
                                <i class="fas fa-fw fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script>
        $(document).ready(function () {
            $('#logisticCenterTable').DataTable();
        });
    </script>
@stop