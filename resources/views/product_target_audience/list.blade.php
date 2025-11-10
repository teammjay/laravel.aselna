@extends('adminlte::page')

@section('title', 'Product Target Audience')

@section('content_header')
    <h1>Product Target Audience</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('product_target_audience.create') }}" class="btn btn-primary">
                Add Product Target Audience
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="productTargetAudienceTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Created By</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($product_target_audiences as $ptype)
                    <tr>
                        <td>{{ $ptype->id }}</td>
                        <td>{{ $ptype->name }}</td>
                        <td>{{ $ptype->created_by }}</td>
                        <td>
                            <a href="{{ route('product_target_audience.edit', $ptype->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('product_target_audience.delete', $ptype->id) }}" class="btn btn-danger" title="Remove">
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
            $('#productTargetAudienceTable').DataTable({
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