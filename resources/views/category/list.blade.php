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
            <th></th>
            <th></th>
            <th></th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#categoryTable').DataTable({});
        });
    </script>
@stop
