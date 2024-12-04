@extends('adminlte::page')

@section('title', 'Store')

@section('content_header')
    <h1>Store</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-2">
            <a href="{{ route('store.create') }}" class="btn btn-secondary">
                Add new store
            </a>
        </div>
        <div class="col-md-2">
            <a href="{{ route('customers.create') }}" class="btn btn-outline-info">
                Create Customer
            </a>
        </div>
    </div>
    {{--<div class="card card-secondary">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        <div class="card-body">
            <form id="filter-form">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" name="name" id="name" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="price_min">Min Price:</label>
                            <input type="number" name="price_min" id="price_min" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="price_max">Max Price:</label>
                            <input type="number" name="price_max" id="price_max" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="price_max">Status:</label>
                            <select class="custom-select" name="status" id="status">
                                <option> -- </option>
                                <option value="active"> Active </option>
                                <option value="discontinued"> Discontinued </option>
                                <option value="out_of_stock"> Out of stock </option>
                                <option value="draft"> Draft </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="form-control btn btn-primary">Filter</button>
                        </div>

                    </div>
                </div>


            </form>
        </div>
    </div>--}}
    <table id="storeTable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Store ID</th>
                <th>Marketplace Name</th>
                <th>Name</th>
                <th>Store Type</th>
                <th>Listing Type</th>
                <th>Store Status</th>
                <th>Authorized</th>
                <th>Short Code</th>
                <th>Upc usage type</th>
            </tr>
        </thead>
        <tbody id="store-list">
        @include('store.store_list', ['stores' => $stores])
        </tbody>
    </table>
    <div id="pagination">
        @include('store.pagination', ['stores' => $stores])
    </div>

@stop

@section('adminlte_js')

    <script>
        $(document).ready(function() {
            // Handle pagination links click
            $(document).on('click', '.pagination a', function(event) {
                event.preventDefault();
                var page = $(this).attr('href').split('page=')[1];
                loadProductsWithPage(page);
            });

            function loadProductsWithPage(page) {
                var perPage = '{{ $perPage }}';
                $.ajax({
                    url: "{{ route('stores.filter') }}?page=" + page + "&perPage=" + perPage,
                    data: $('#filter-form').serialize(),
                    success: function(response) {
                        $('#store-list').html(response.html);
                        $('#pagination').html(response.pagination);
                    }
                });
            }

            $(document).on('click', '.delete-btn', function() {
                var storeId = $(this).data('id');
                var token = '{{ csrf_token() }}'; // CSRF token for security

                // Confirm the deletion
                if (confirm("Are you sure you want to delete this store?")) {
                    $.ajax({
                        url: "{{ url('stores/destroy') }}/" + storeId,   // Laravel route for deletion
                        type: 'GET',
                        data: {
                            "_token": token
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                $('#store-' + storeId).remove();
                                alert(response.success);
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }
            });
        });
    </script>
@stop


