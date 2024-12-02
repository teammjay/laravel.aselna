@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>UPC Generate</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <div class="btn btn-primary" id="createUpcModalButton" data-toggle="modal" data-target="#createUpcModal">
                create UPC
            </div>
        </div>
    </div>
    <table id="upcTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Company prefix</th>
            <th>Start Product Number</th>
            <th>Number of Codes</th>
            <th>Stores</th>
        </tr>
        </thead>
        <tbody>
        @foreach($upcGenerate as $upcGenerate)
            <tr>
                <td>{{$upcGenerate->company_prefix}}</td>
                <td>{{$upcGenerate->product_number}}</td>
                <td>{{$upcGenerate->number_of_codes}}</td>
                <td>{{$upcGenerate->storeData->name}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div>
        <div class="modal fade" id="createUpcModal" tabindex="-1" role="dialog" aria-labelledby="createUpcModalLabel"
             aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="{{ route('upc_codes.generate') }}" id="generateUpcCode" method="post">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="createUpcModalLabel">Create UPC</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="p-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="store">Store</label>
                                        <select class="form-control @error('store') is-invalid @enderror"
                                                id="store"
                                                name="store" value="{{ old('store') }}">
                                            <option value="">Select Store</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="quantity">Company Prefix</label>
                                        <input type="text"
                                               class="form-control @error('company_prefix') is-invalid @enderror"
                                               id="company_prefix"
                                               name="company_prefix"
                                               placeholder="Enter Company Prefix" value="{{ old('company_prefix') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="quantity">Start product number</label>
                                        <input type="text"
                                               class="form-control @error('product_number') is-invalid @enderror"
                                               id="product_number"
                                               name="product_number"
                                               placeholder="Enter product number" value="{{ old('product_number') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="quantity">Number of codes</label>
                                        <input type="text"
                                               class="form-control @error('number_of_codes') is-invalid @enderror"
                                               id="number_of_codes"
                                               name="number_of_codes"
                                               placeholder="Enter number of codes" value="{{ old('number_of_codes') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#upcTable').DataTable();

            $('#store').one('click', function () {
                var loader = $('<option>Loading...</option>');
                var select = $('#store');
                select.append(loader);

                $.ajax({
                    url: '/api/stores', // Replace with your API endpoint
                    method: 'GET',
                    success: function (data) {

                        select.empty();
                        select.append('<option value="">Select Store</option>');
                        $.each(data, function (key, value) {
                            select.append('<option value="' + value.id + '">' + value.name + '</option>');
                        });
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching stores:', error);
                        select.empty();
                        select.append('<option>Error loading stores</option>');
                    }
                });
            });

            $('#generateUpcCode').on('submit', function (e) {
                e.preventDefault();
                var form = $(this);
                var data = form.serialize();
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: data,
                    success: function (response) {
                        console.log(response)
                        alert(response.message);
                        $('#createUpcModal').modal('hide');
                        location.reload();
                    },
                    error: function (xhr, status, error) {
                        console.error('Error generating UPC code:', error);
                    }
                });
            });
        });
    </script>
@stop
