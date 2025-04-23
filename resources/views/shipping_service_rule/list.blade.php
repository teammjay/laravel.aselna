@extends('adminlte::page')

@section('title', 'Shipping Service Rule')

@section('content_header')
    <h1>Shipping Service Rule</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('shipping_service_rule.create') }}" class="btn btn-primary">
                Add Shipping Service Rule
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <div class="table-responsive">
                <table id="shippingServiceRuleTable" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Weigth Filter</th>
                        <th>Min. Weigth</th>
                        <th>Max. Weigth</th>
                        <th>Price Filter</th>
                        <th>Min. Price</th>
                        <th>Max. Price</th>
                        <th>Quantity Operator</th>
                        <th>Min. Quantity</th>
                        <th>Max. Quantity</th>
                        <th>Package Type Filter</th>
                        <th>Signature Confirmation</th>
                        <th>Color</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($shipping_service_rules as $srule)
                        <tr>
                            <td>{{ $srule->priority }}</td>
                            <td>{{ \App\Constants\Operators::getOperatorsName($srule->weight_operator) }}</td>
                            <td>{{ $srule->min_weight_value }}</td>
                            <td>{{ $srule->max_weight_value }}</td>
                            <td>{{ \App\Constants\Operators::getOperatorsName($srule->price_operator) }}</td>
                            <td>{{ $srule->min_price }}</td>
                            <td>{{ $srule->max_price }}</td>
                            <td>{{ \App\Constants\Operators::getOperatorsName($srule->quantity_operator) }}</td>
                            <td>{{ $srule->min_quantity }}</td>
                            <td>{{ $srule->max_quantity }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                <a href="{{ route('shipping_service_rule.edit', $srule->id) }}" class="btn btn-primary" title="Edit">
                                    <i class="fas fa-fw fa-pencil-alt"></i>
                                </a>
                                <a href="{{ route('shipping_service_rule.delete', $srule->id) }}" class="btn btn-danger" title="Remove">
                                    <i class="fas fa-fw fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script>
        $(document).ready(function () {
            $('#shippingServiceRuleTable').DataTable();
        });
    </script>
@stop