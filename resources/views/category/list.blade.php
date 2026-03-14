@extends('adminlte::page')

@section('title', 'Customers')

@section('content_header')
    <h1>Category</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('categories.create') }}" class="btn btn-primary">
                Add Category
            </a>
        </div>
    </div>
    <table id="categoryTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Name</th>
            <th>Status</th>
            <th>Last modified by</th>
            <th>Last modified at</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($categories as $category)
            <tr>
{{--                Generate--}}
                <td>{{$category->name}}</td>
                <td>{{ ucfirst($category->status) }}</td>
                <td>{{ $category->updated_by }}</td>
                <td>{{ $category->updated_at->format('d M Y g:i A') }}</td>
                <td>
                    <a href="{{ route('categories.edit', ['id' => $category->id]) }}" class="btn btn-primary">Edit</a>
                    <a href="{{ route('categories.delete', ['id' => $category->id]) }}" class="btn btn-danger">Delete</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#categoryTable').DataTable({
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
