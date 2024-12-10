@extends('adminlte::page')

@section('title', 'store Type Category')

@section('content_header')
    <h1>store Type Category</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('store_type_category.create') }}" class="btn btn-primary">
                Add Store Type Category
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="storeTypeCategoryTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Parent</th>
                    <th>Store Type</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($store_type_categories as $sType)
                    <tr>
                        <td>{{ $sType->id }}</td>
                        <td>{{ $sType->code }}</td>
                        <td>{{ $sType->name }}</td>
                        <td>{{ isset($sType->parent->name) ? $sType->parent->name : '' }}</td>
                        <td>{{ isset($sType->storetype->name) ? $sType->storetype->name : '' }}</td>
                        <td>
                            <a href="{{ route('store_type_category.edit', $sType->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('store_type_category.delete', $sType->id) }}" class="btn btn-danger" title="Remove">
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
            $('#storeTypeCategoryTable').DataTable();
        });
    </script>
@stop