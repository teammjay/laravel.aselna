@extends('adminlte::page')

@section('title', 'Product Condition')

@section('content_header')
    <h1>Product Condition</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('product_condition.create') }}" class="btn btn-primary">
                Add Product Condition
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="productConditionTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($product_conditions as $pcondition)
                    <tr>
                        <td>{{ $pcondition->id }}</td>
                        <td>{{ $pcondition->code }}</td>
                        <td>{{ $pcondition->name }}</td>
                        <td>
                            <a href="{{ route('product_condition.edit', $pcondition->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('product_condition.delete', $pcondition->id) }}" class="btn btn-danger" title="Remove">
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
            $('#productConditionTable').DataTable();
        });
    </script>
@stop