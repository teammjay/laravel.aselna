@extends('adminlte::page')

@section('title', 'Listing Template')

@section('content_header')
    <h1>Listing Template</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('listing_template.create') }}" class="btn btn-primary">
                Add Listing Template
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="listingTemplateTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($listing_templates as $ltemplate)
                    <tr>
                        <td>{{ $ltemplate->id }}</td>
                        <td>{{ $ltemplate->name }}</td>
                        <td>{{ $ltemplate->created_by  }}</td>
                        <td>{{ $ltemplate->created_at  }}</td>
                        <td>
                            <a href="{{ route('listing_template.edit', $ltemplate->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('listing_template.delete', $ltemplate->id) }}" class="btn btn-danger" title="Remove">
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
            $('#listingTemplateTable').DataTable({
                "dom": '<"datatable-top"lfip>rt<"datatable-bottom"ip>',
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