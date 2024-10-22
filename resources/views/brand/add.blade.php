@extends('adminlte::page')

@section('title', 'Add Product')

@section('content_header')
    <h1>Add Brand</h1>
@stop

@section('content')
    <form action="{{ route('brand.store') }}" method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Brand Name</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   placeholder="Enter brand name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Order No</label>
                            <input type="text" class="form-control" id="order_no" name="order_no"
                                   placeholder="Enter order no">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="description">Notes 1</label>
                            <textarea class="form-control" id="notes1" name="notes1"
                                      placeholder="Enter Notes"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="description">Notes 2</label>
                            <textarea class="form-control" id="notes2" name="notes2"
                                      placeholder="Enter Notes"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="description">Notes 3</label>
                            <textarea class="form-control" id="notes3" name="notes3"
                                      placeholder="Enter Notes"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <button type="reset" class="btn btn-primary">Reset</button>
            </div>
        </div>
    </form>
@stop
