@extends('adminlte::page')

@section('title', 'Store Type')

@section('content_header')
    <h1>Store Type</h1>
@stop

@section('content')
    @csrf
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}</div>
    @endif
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('store_type.create') }}" class="btn btn-primary">
                Add Store Type
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="storeTypeTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Product Qty</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($store_types as $sType)
                    <tr>
                        <td>{{ $sType->id }}</td>
                        <td>{{ $sType->code }}</td>
                        <td>{{ $sType->name }}</td>
                        <td>{{ $sType->product_qty }}</td>
                        <td>
                            <a href="{{ route('store_type.edit', $sType->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>

                            <button type="button" class="btn btn-danger delete-btn" data-module="store_type" data-id="{{ $sType->id }}" title="Remove"> <i class="fas fa-fw fa-trash"></i></button>

                            {{--<a href="{{ route('store_type.delete', $sType->id) }}" class="btn btn-danger" title="Remove">
                                <i class="fas fa-fw fa-trash"></i>
                            </a>--}}
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
            $('#storeTypeTable').DataTable();
        });
    </script>
@stop