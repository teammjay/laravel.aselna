@extends('adminlte::page')

@section('title', 'Printer Client')

@section('content_header')
    <h1>Printer Client</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('printer_client.create') }}" class="btn btn-primary">
                Add Printer Client
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="printerClientsTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Machine Key</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($printer_clients as $sType)
                    <tr>
                        <td>{{ $sType->name }}</td>
                        <td>{{ $sType->machine_key }}</td>
                        <td>{{ ($sType->active == 1) ? 'Active' : '' }}</td>
                        <td>
                            <a href="{{ route('printer_client.edit', $sType->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('printer_client.delete', $sType->id) }}" class="btn btn-danger" title="Remove">
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
            $('#printerClientsTable').DataTable();
        });
    </script>
@stop