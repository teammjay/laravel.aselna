@extends('adminlte::page')

@section('title', 'Catalog')

@section('content_header')
    <h1>Brands</h1>
@stop

@section('content')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
        </div>
    </div>
    <table id="brandTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Brand Name</th>
            <th>Order No</th>
            <th>Notes 1</th>
            <th>Notes 2</th>
            <th>Notes 3</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

@stop

