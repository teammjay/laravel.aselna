@extends('adminlte::page')

@section('title', 'Printer')

@section('content_header')
    <h1>Printer</h1>
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
            <a href="{{ route('printer.create') }}" class="btn btn-primary">
                Add Printer
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="printersTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Printer Client</th>
                    <th>Name</th>
                    <th>Label Format</th>
                    <th>Status</th>
                    <th>DPI</th>
                    <th>Printer Type</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($printers as $sType)
                    <tr>
                        <td></td>
                        <td>{{ $sType->name }}</td>

                        <td>{{ $sType->label_format }}</td>
                        <td>{{ $sType->status }}</td>
                        <td>{{ $sType->dpi }}</td>
                        <td>{{ ($sType->printer_type == 0) ? 'Printer' : '' }}</td>
                        <td>
                            <a href="{{ route('printer.edit', $sType->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <button type="button" class="btn btn-danger delete-btn" data-module="printer" data-id="{{ $sType->id }}" title="Remove"> <i class="fas fa-fw fa-trash"></i></button>
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
            $('#printersTable').DataTable({
                stateSave:true,
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
            });
        });
    </script>
@stop
