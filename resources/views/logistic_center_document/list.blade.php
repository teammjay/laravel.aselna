@extends('adminlte::page')

@section('title', 'Logistic Center Documents')

@section('content_header')
    <h1>Logistic Center Documents</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('logistic_center_document.create') }}" class="btn btn-primary">
                Add Document Setting
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="documentTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Logistic Center</th>
                    <th>Document Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($documents as $document)
                    <tr>
                        <td>{{ $document->logisticCenter->name ?? 'N/A' }}</td>
                        <td>{{ \App\Constants\ShippingDocumentType::getName($document->document_type) }}</td>
                        <td>
                            @if($document->enabled)
                                <span class="badge badge-success">Enabled</span>
                            @else
                                <span class="badge badge-danger">Disabled</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('logistic_center_document.edit', $document->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('logistic_center_document.delete', $document->id) }}" class="btn btn-danger" title="Remove">
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
            $('#documentTable').DataTable({
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
                }
            });
        });
    </script>
@stop
