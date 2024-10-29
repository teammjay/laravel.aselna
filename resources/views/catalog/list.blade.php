@extends('adminlte::page')

@section('title', 'Catalog')

@section('content_header')
    <h1>Catalog</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('catalog.create') }}" class="btn btn-secondary">
                Add Catalog
            </a>
        </div>
    </div>
    <div class="card card-secondary">
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
    </div>
    <table id="productsTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Image</th>
            <th>Code</th>
            <th>Type</th>
            <th>Title</th>
            <th>Status</th>
            <th>Price</th>
            <th>Manufacturer</th>
            <th>Color</th>
        </tr>
        </thead>
        <tbody id="product-list">

            @include('catalog.product_list', ['products' => $products])

        </tbody>
    </table>
    <div id="pagination">
        @include('catalog.pagination', ['products' => $products])
    </div>
@stop

@section('adminlte_js')

<script>
    $(document).ready(function() {
        $('#filter-form').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            var perPage =  '{{ $perPage }}';
            var page =  1;
            $.ajax({
                url: "{{ route('catalog.filter') }}?page=" + page + "&perPage=" + perPage,
                method: 'GET',
                data: $(this).serialize(),
                success: function(response) {
                    // Update the product list and pagination
                    $('#product-list').html(response.html);
                    $('#pagination').html(response.pagination);
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                }
            });
        });

        // Handle pagination links click
        $(document).on('click', '.pagination a', function(event) {
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            loadProductsWithPage(page);
        });

        function loadProductsWithPage(page) {
            var perPage = 1;
            $.ajax({
                url: "{{ route('catalog.filter') }}?page=" + page + "&perPage=" + perPage,
                data: $('#filter-form').serialize(),
                success: function(response) {
                    $('#product-list').html(response.html);
                    $('#pagination').html(response.pagination);
                }
            });
        }

        $(document).on('click', '.delete-btn', function() {
            var productId = $(this).data('id');
            var token = '{{ csrf_token() }}'; // CSRF token for security

            // Confirm the deletion
            if (confirm("Are you sure you want to delete this product?")) {
                $.ajax({
                    url: "{{ url('catalog/destroy') }}/" + productId,   // Laravel route for deletion
                    type: 'DELETE',
                    data: {
                        "_token": token
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            $('#product-' + productId).remove();
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


