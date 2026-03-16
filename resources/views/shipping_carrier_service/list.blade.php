@extends('adminlte::page')

@section('title', 'Shipping Carrier Service')

@section('content_header')
    <h1>Shipping Carrier Service</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('shipping_carrier_service.create') }}" class="btn btn-primary">
                Add Shipping Carrier Service
            </a>
        </div>
    </div>
    {{--<div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-4">
                    <div class="row">
                        <div class="col-4">
                            Name
                        </div>
                        <div class="col-2">
                            <select class="custom-select" id="shipping_carrier_id" name="shipping_carrier_id">
                                <option value="=">=</option>

                            </select>
                        </div>
                        <div class="col-6">
                            <input type="text" name="name" id="name" class="form-control">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>--}}
    <table id="shippingCarrierServiceTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Name</th>
            <th>Shipping Carrier</th>
            <th>Included Insurance</th>
            <th>Generic Service Method</th>
            <th>Delivery Time</th>
            <th>Earliest Delivery Days</th>
            <th>Latest Delivery Days</th>
            <th>Service Level</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($shipping_carrier_services as $services)
            <tr>
                <td>{{ $services->name }}</td>
                <td>{{ $services->ShippingCarrier->name }}</td>
                <td>{{ $services->included_insurance == 1 ? 'Yes' : 'No' }}</td>
                <td>{{ $services->generic_service_method }}</td>
                <td>{{ $services->delivery_time != '' ? $services->delivery_time : 0 }}</td>
                <td>{{ $services->earliest_delivery_days != '' ? $services->earliest_delivery_days : 'N/A' }}</td>
                <td>{{ $services->latest_delivery_days != '' ? $services->latest_delivery_days : 'N/A' }}</td>
                <td>{{ $services->service_level != '' ? $services->service_level : 'N/A' }}</td>
                <td>
                    <a href="{{ route('shipping_carrier_service.edit', $services->id) }}" class="btn btn-primary" title="Edit">
                        <i class="fas fa-fw fa-pencil-alt"></i>
                    </a>
                    <a href="{{ route('shipping_carrier_service.delete', $services->id) }}" class="btn btn-danger" title="Remove">
                        <i class="fas fa-fw fa-trash"></i>
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@stop

@section('adminlte_js')
    <script>
        $(document).ready(function () {
            $('#shippingCarrierServiceTable').DataTable({
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
