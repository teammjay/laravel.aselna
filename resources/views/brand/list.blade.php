@extends('adminlte::page')

@section('title', 'Catalog')

@section('content_header')
    <h1>Brands</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('brand.create') }}" class="btn btn-primary">
                Add Brand
            </a>
        </div>
    </div>
    <div class="card card-primary">
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
{{--                            <select class="custom-select" id="product_code_operator" name="product_code_operator">--}}
{{--                                <option value="=">=</option>--}}
{{--                                <option value="contains">contains</option>--}}
{{--                                <option value="starts_with">starts with</option>--}}
{{--                                <option value="ends_with">ends with</option>--}}
{{--                            </select>--}}
                        </div>
                        <div class="col-6">
                            <input type="text" name="name" id="name" class="form-control">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <table id="brandTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Brand Name</th>
            <th>Order No</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($brands as $brand)
            <tr>
                <td>{{ $brand->name }}</td>
                <td>{{ $brand->order_no }}</td>
                <td>
                    <a href="{{ route('brand.edit', $brand->id) }}" class="btn btn-primary">
                        Edit
                    </a>
                    <a href="{{ route('brand.delete', $brand->id) }}" class="btn btn-danger">
                        Delete
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

@stop

