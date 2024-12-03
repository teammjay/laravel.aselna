@extends('adminlte::page')

@section('title', 'Interface')

@section('content_header')
    <h1>Product Devices</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('product_interface.create') }}" class="btn btn-primary">
                Add Product Device
            </a>
        </div>
    </div>
    <table id="productInterfaceTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Product Name</th>
            <th>Interface Name</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($productInterfaces as $productInterface)
            <tr>
                <td>{{$productInterface->product->product_title}}</td>
                <td>{{$productInterface->interfaces->name}}</td>
                <td>
                    <a href="{{ route('product_interface.edit', $productInterface->id) }}" class="btn btn-primary">
                        Edit
                    </a>
                    <a href="{{ route('product_interface.delete', $productInterface->id) }}" class="btn btn-danger">
                        Delete
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#productInterfaceTable').DataTable();
        });
    </script>
@stop
