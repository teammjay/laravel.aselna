@extends('adminlte::page')

@section('title', 'Page Type')

@section('content_header')
    <h1>Page Type</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('page_type.create') }}" class="btn btn-primary">
                Add Page Type
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="pageTypeTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($page_types as $sCarrier)
                    <tr>
                        <td>{{ $sCarrier->id }}</td>
                        <td>{{ $sCarrier->type }}</td>
                        <td>
                            <a href="{{ route('page_type.edit', $sCarrier->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('page_type.delete', $sCarrier->id) }}" class="btn btn-danger" title="Remove">
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
            $('#pageTypeTable').DataTable({
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