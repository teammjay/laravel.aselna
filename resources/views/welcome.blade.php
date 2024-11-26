@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <select class="form-control" id="listing_store">
                <option value="-">Select Store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }} - {{ $store->marketplace_name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-bordered table-striped table-hover" id="listingTable">
                <thead>
                <tr>
                    <th>Id</th>
                    <th>Sku</th>
                    <th>Action</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#listingTable').DataTable();
        });
    </script>
@stop
