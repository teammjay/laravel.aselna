@extends('adminlte::page')

@section('title', 'Specification Values')

@section('content_header')
    <h1>Specification Values</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('specification_values.create') }}" class="btn btn-primary">
                Add Specification Value
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="specificationValuesTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Field Name</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($specification_infos as $spec_value)
                    <tr>
                        <td>{{ $spec_value->field_name }}</td>
                        <td>
                            <a href="{{ route('specification_values.edit', $spec_value->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('specification_values.delete', $spec_value->id) }}" class="btn btn-danger" title="Remove">
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
            $('#specificationValuesTable').DataTable({
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
