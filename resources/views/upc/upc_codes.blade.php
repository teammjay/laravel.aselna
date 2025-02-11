@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>UPC codes</h1>
@stop

@section('content')
    <table id="upcTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Company prefix</th>
            <th>Product number</th>
            <th>UPC</th>
            <th>used</th>
            <th>Sequence ID</th>
            <th>Store</th>
        </tr>
        </thead>
        <tbody>
        @foreach($upcCodes as $upcCode)
            <tr>
                <td>{{$upcCode->company_prefix}}</td>
                <td>{{$upcCode->product_number}}</td>
                <td>{{$upcCode->upc_code}}</td>
                <td>{{$upcCode->is_used ? 'Used' : 'Not Used'}}</td>
                <td>{{$upcCode->sequence_id}}</td>
                <td>{{$upcCode->storeData->name}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#upcTable').DataTable();
        } );
    </script>
@stop
