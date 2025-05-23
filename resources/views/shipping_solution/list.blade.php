@extends('adminlte::page')

@section('title', 'Shipping Solution')

@section('content_header')
    <h1>Shipping Solution</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('shipping_solution.create') }}" class="btn btn-primary">
                Add Shipping Solution
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="shippingSolutionTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Production Service Url</th>
                    <th>Sandbox Service Url</th>
                    <th>Shipment Estimate Available</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($shipping_solutions as $solution)
                    <tr>
                        <td>{{ $solution->name }}</td>
                        <td>{{ $solution->code }}</td>
                        <td>{{ $solution->production_service_url }}</td>
                        <td>{{ $solution->sandbox_service_url }}</td>
                        <td>{{ $solution->shipment_estimate_available }}</td>
                        <td>
                            <a href="{{ route('shipping_solution.edit', $solution->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('shipping_solution.delete', $solution->id) }}" class="btn btn-danger" title="Remove">
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
            $('#shippingSolutionTable').DataTable();
        });
    </script>
@stop