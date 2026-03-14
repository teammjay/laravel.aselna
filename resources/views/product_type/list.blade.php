@extends('adminlte::page')

@section('title', 'Product Type')

@section('content_header')
    <h1>Product Type</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('product_type.create') }}" class="btn btn-primary">
                Add Product Type
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="productTypeTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Parent</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($product_types as $ptype)
                    <tr>
                        <td>{{ $ptype->id }}</td>
                        <td>{{ $ptype->code }}</td>
                        <td>{{ $ptype->name }}</td>
                        <td>{{ isset($ptype->parent->name) ? $ptype->parent->name : '' }}</td>
                        <td>
                            <a href="{{ route('product_type.edit', $ptype->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('product_type.delete', $ptype->id) }}" class="btn btn-danger" title="Remove">
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
            $('#productTypeTable').DataTable({
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