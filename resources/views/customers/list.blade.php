@extends('adminlte::page')

@section('title', 'Customers')

@section('content_header')
    <h1>Customers</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                Add Customer
            </a>
        </div>
    </div>
    <table id="customerTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Customer Name</th>
            <th>Address</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#customerTable').DataTable({});
        });
    </script>
@stop

