@extends('adminlte::page')

@section('title', 'Manufacturer')

@section('content_header')
    <h1>Manufacturer</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('manufacturer.create') }}" class="btn btn-primary">
                Add Manufacturer
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="manufacturerTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    {{--<th>ID</th>--}}
                    <th>Name</th>
                    <th>Code</th>
                    <th>Notes</th>
                    <th>Thumbnail</th>
                    <th>Sort Order</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($manufacturers as $manufacture)
                    <tr>
                        {{--<td>{{ $manufacture->id }}</td>--}}
                        <td>{{ $manufacture->name }}</td>
                        <td>{{ $manufacture->code }}</td>
                        <td>{{ $manufacture->notes }}</td>
                        <td></td>
                        <td>{{ $manufacture->sort_order }}</td>
                        <td>
                            <a href="{{ route('manufacturer.edit', $manufacture->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('manufacturer.delete', $manufacture->id) }}" class="btn btn-danger" title="Remove">
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
            $('#manufacturerTable').DataTable({
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