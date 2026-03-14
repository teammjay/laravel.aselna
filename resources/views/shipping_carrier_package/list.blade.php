@extends('adminlte::page')

@section('title', 'Shipping Carrier Package')

@section('content_header')
    <h1>Shipping Carrier Package</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('shipping_carrier_package.create') }}" class="btn btn-primary">
                Add Shipping Carrier Package
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="shippingCarrierPackageTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Non Machinable</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($shipping_carrier_packages as $sCarrier)
                    <tr>
                        <td>{{ $sCarrier->name }}</td>
                        <td>{{ ($sCarrier->non_machinable == 1) ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('shipping_carrier_package.edit', $sCarrier->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('shipping_carrier_package.delete', $sCarrier->id) }}" class="btn btn-danger" title="Remove">
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
            $('#shippingCarrierPackageTable').DataTable({
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