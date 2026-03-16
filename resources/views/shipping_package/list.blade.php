@extends('adminlte::page')

@section('title', 'Shipping Package')

@section('content_header')
    <h1>Shipping Carrier Package</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('shipping_package.create') }}" class="btn btn-primary">
                Add Shipping Package
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="shippingPackageTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Dimentions</th>
                    <th>Weigth</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($shipping_packages as $sPackage)
                    <tr>
                        <td>{{ $sPackage->name }}</td>
                        <td>{{ $sPackage->dimensions_width.' x '.$sPackage->dimensions_length.' x '.$sPackage->dimensions_height.' '.\App\Constants\LengthUnit::getUnitName($sPackage->dimensions_length_unit ?? 0) }}</td>
                        <td>{{ $sPackage->weight_value.' '.\App\Constants\WeightUnit::getUnitSelfName($sPackage->weight_weight_unit ?? 0) }}</td>
                        <td>
                            <a href="{{ route('shipping_package.edit', $sPackage->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('shipping_package.delete', $sPackage->id) }}" class="btn btn-danger" title="Remove">
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
            $('#shippingPackageTable').DataTable({
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