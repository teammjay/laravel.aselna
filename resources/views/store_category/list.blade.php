@extends('adminlte::page')

@section('title', 'Store Type Category')

@section('content_header')
    <h1>Store Category</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-2">
            <a href="{{ route('store_category.create') }}" class="btn btn-secondary">
                Add new store category
            </a>
        </div>
    </div>
    <table id="storeTypeCatgeory" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($storeCategories as $storeCategory)
            <tr>
                <td>{{ $storeCategory->id }}</td>
                <td>{{ $storeCategory->name }}</td>
                <td>
                    <a href="{{ route('store_category.edit', $storeCategory->id) }}" class="btn btn-primary">
                        Edit
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@stop

@section('adminlte_js')
    <script>
        $(document).ready(function () {
            $('#storeTypeCatgeory').DataTable({
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
