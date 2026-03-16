@extends('adminlte::page')

@section('title', 'Product Color')

@section('content_header')
    <h1>Product Color</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('product_color.create') }}" class="btn btn-primary">
                Add Product Color
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="productColorTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($product_colors as $pcolor)
                    <tr>
                        <td>{{ $pcolor->id }}</td>
                        <td>{{ $pcolor->code }}</td>
                        <td>{{ $pcolor->name }}</td>
                        <td>
                            <a href="{{ route('product_color.edit', $pcolor->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('product_color.delete', $pcolor->id) }}" class="btn btn-danger" title="Remove">
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
            $('#productColorTable').DataTable({
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