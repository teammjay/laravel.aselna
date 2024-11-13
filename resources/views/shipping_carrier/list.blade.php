@extends('adminlte::page')

@section('title', 'Catalog')

@section('content_header')
    <h1>Shipping Carrier</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('device_screen.create') }}" class="btn btn-primary">
                Add Shipping Carrier
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="shippingCarrierTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($shipping_carriers as $sCarrier)
                    <tr>
                        <td>{{ $sCarrier->name }}</td>
                        <td>{{ $sCarrier->code }}</td>
                        <td>
                            <a href="{{ route('shipping_carrier.edit', $sCarrier->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('shipping_carrier.delete', $sCarrier->id) }}" class="btn btn-danger" title="Remove">
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
            $('#shippingCarrierTable').DataTable();
        });
    </script>
@stop