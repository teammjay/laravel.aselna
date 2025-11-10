@extends('adminlte::page')

@section('title', 'Order Operation Reason')

@section('content_header')
    <h1>Order Operation Reason</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('order_operation_reason.create') }}" class="btn btn-primary">
                Add Order Operation Reason
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="orderOperationReasonTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Store Type</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($order_operation_reasons as $op_reason)
                    <tr>
                        <td>{{ $op_reason->channel_code }}</td>
                        <td>{{ $op_reason->name }}</td>
                        <td>{{ isset($op_reason->storeType->name) ? $op_reason->storeType->name : '' }}</td>
                        <td>
                            <a href="{{ route('order_operation_reason.edit', $op_reason->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('order_operation_reason.delete', $op_reason->id) }}" class="btn btn-danger" title="Remove">
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
            $('#orderOperationReasonTable').DataTable({
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
