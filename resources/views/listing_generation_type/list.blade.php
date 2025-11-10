@extends('adminlte::page')

@section('title', 'Listing Generation Type')

@section('content_header')
    <h1>Generation Type</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('listing_generation_type.create') }}" class="btn btn-primary">
                Add Generation Type
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="generationTypeTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Singleton</th>
                    <th>Allow Duplicates</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($listing_generation_types as $ltype)
                    <tr>
                        <td>{{ $ltype->id }}</td>
                        <td>{{ $ltype->code }}</td>
                        <td>{{ $ltype->name }}</td>
                        <td>{{ ($ltype->singleton == 1) ? 'Yes' : 'No'  }}</td>
                        <td>{{ ($ltype->allow_duplicates == 1) ? 'Yes' : 'No'  }}</td>
                        <td>
                            <a href="{{ route('listing_generation_type.edit', $ltype->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('listing_generation_type.delete', $ltype->id) }}" class="btn btn-danger" title="Remove">
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
            $('#generationTypeTable').DataTable({
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