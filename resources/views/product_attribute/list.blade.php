@extends('adminlte::page')

@section('title', 'Product Attribute')

@section('content_header')
    <h1>Product Attribute</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('product_attribute.create') }}" class="btn btn-primary">
                Add Product Attribute
            </a>
        </div>
    </div>
    <table id="productAttributeTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Name</th>
            <th>Simple Attribute Name</th>
            <th>Created By</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($product_attributes as $pAttr)
            <tr>
                <td>{{$pAttr->name}}</td>
                <td>{{$pAttr->simple_attribute_name}}</td>
                <td>{{ $pAttr->created_by }}</td>
                <td>{{ $pAttr->created_at }}</td>
                <td>
                    <a href="{{ route('product_attribute.edit', ['id' => $pAttr->id]) }}" class="btn btn-primary" title="Edit"><i class="fas fa-fw fa-pencil-alt"></i></a>
                    <a href="{{ route('product_attribute.delete', ['id' => $pAttr->id]) }}" class="btn btn-danger" title="Remove"><i class="fas fa-fw fa-trash"></i></a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#productAttributeTable').DataTable({});
        });
    </script>
@stop
