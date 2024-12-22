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
    <div class="card card-secondary">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" name="name" id="name" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="marketplace_name">Marketplace Name:</label>
                            <input type="text" name="marketplace_name" id="marketplace_name" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="store_status">Status:</label>
                            <select class="custom-select" name="store_status" id="store_status">
                                <option value=""> -- </option>
                                <option value="1"> Active </option>
                                <option value="0"> Closed </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="store_status">Is authorized?</label>
                            <select class="custom-select" name="authorized" id="authorized">
                                <option value=""> -- </option>
                                <option value="1"> Yes </option>
                                <option value="0"> No </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="store_type">Store Type:</label>
                            <select class="form-control" name="store_type" id="store_type">
                                <option value=""> -- </option>
                                @if(count($store_types ?? []) > 0)
                                    @foreach($store_types as $stype)
                                        <option value="{{ $stype->id }}">{{ $stype->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="form-control btn btn-primary">Filter</button>
                        </div>

                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="reset" class="form-control btn btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
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
                <th></th>
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

            $('.card-header').on('click',function () {
                if($(this).find('.fa').hasClass('fa-angle-down')){
                    $(this).find('.fa').removeClass('fa-angle-down');
                    $(this).find('.fa').addClass('fa-angle-up');
                }else{
                    $(this).find('.fa').removeClass('fa-angle-up');
                    $(this).find('.fa').addClass('fa-angle-down');
                }
                $('.card-body').toggle();
            });

            /*$('#storeTable').DataTable({});*/

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

            $('#filter-form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                var perPage =  '{{ $perPage }}';
                var page =  1;
                $.ajax({
                    url: "{{ route('stores.filter') }}?page=" + page + "&perPage=" + perPage,
                    method: 'GET',
                    data: $(this).serialize(),
                    success: function(response) {
                        // Update the product list and pagination
                        $('#store-list').html(response.html);
                        $('#pagination').html(response.pagination);
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                    }
                });
            });

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

            $(document).on('click', '.authorize', function() {
                let ele = $(this);
                let store_id= $(this).data('id');
                let is_authorize = 0;
                if($(this).is(':checked')){
                    is_authorize = 1
                }

                $.ajax({
                    url: "{{ route('store.update') }}",   // Laravel route for deletion
                    type: 'POST',
                    data: {
                        id:store_id,
                        field:'authorized',
                        value:is_authorize,
                        "_token": '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            ele.prop('checked',true);
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });
            });


        });
    </script>
@stop


