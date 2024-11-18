@extends('adminlte::page')

@section('title', 'Customers')

@section('content_header')
    <h1>Sub Category</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('categories_sub.create') }}" class="btn btn-primary">
                Add Sub Category
            </a>
        </div>
    </div>
    <table id="subcategoryTable" class="table table-bordered table-striped">
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
        @foreach($subcategories as $subcategory)
            <tr>
                <td>{{$subcategory->name}}</td>
                <td>{{ ucfirst($subcategory->status) }}</td>
                <td>{{ $subcategory->updated_by }}</td>
                <td>{{ $subcategory->updated_at->format('d M Y g:i A') }}</td>
                <td>
                    <a href="{{ route('categories_sub.edit', ['id' => $subcategory->id]) }}" class="btn btn-primary">Edit</a>
                    <a href="{{ route('categories_sub.delete', ['id' => $subcategory->id]) }}" class="btn btn-danger">Delete</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#subcategoryTable').DataTable({});
        });
    </script>
@stop
