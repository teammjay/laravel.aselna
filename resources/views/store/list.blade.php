@extends('adminlte::page')

@section('title', 'Store')

@section('content_header')
    <h1>Store</h1>
@stop

@section('content')
    @csrf
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}</div>
    @endif
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
    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a> </h3>
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
                            <label for="store_status">Ad account?</label>
                            <select class="custom-select" name="ad_account" id="ad_account">
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
                            <button type="reset" id="resetForm" class="form-control btn btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table id="storeTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    {{--<th>Store ID</th>--}}
                    <th>Name</th>
                    <th>Marketplace Name</th>
                    <th>Store Type</th>
                    <th>Listing Type</th>
                    <th>Store Status</th>
                    <th>Authorized</th>
                    <th>Short Code</th>
                    <th>Upc usage type</th>
                    <th>Ad Account</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="store-list">
            @include('store.store_list', ['stores' => $stores])
            </tbody>
        </table>
    </div>
    <div id="pagination">
        {{--@include('store.pagination', ['stores' => $stores])--}}
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

            /*$('#storeTable').DataTable({
                stateSave:true,
                stateSaveCallback: function(settings, data) {
                    sessionStorage.setItem(
                        'DataTables_' + settings.sInstance,
                        JSON.stringify(data)
                    );
                },

                stateLoadCallback: function(settings) {
                    return JSON.parse(
                        sessionStorage.getItem('DataTables_' + settings.sInstance)
                    );
                },
                columnDefs: [
                    {
                        targets: [5,8,9],
                        orderable: false
                    }
                ]
            });*/

            //loadDatatables();

            // Handle pagination links click
            $(document).on('click', '.pagination a', function(event) {
                event.preventDefault();
                var page = $(this).attr('href').split('page=')[1];
                //loadProductsWithPage(page);
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
                loadDatatables();

            });

            loadDatatables();

            document.getElementById("resetForm").addEventListener("click", function () {
                sessionStorage.removeItem("store_filters");
            });

            /*$(document).on('click', '.delete-btn', function() {
                var storeId = $(this).data('id');
                var token = '{{ csrf_token() }}'; // CSRF token for security

                // Confirm the deletion
                if (confirm("Are you sure you want to delete this store?")) {

                    $('#deleteModal').find('.frmRecordDelete').attr('action', "{{ url('stores/destroy') }}/" + storeId);
                    $('#deleteModal').modal('show');

                    /!*$.ajax({
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
                    });*!/
                }
            });*/

            $(document).on('click', '.authorize', function() {
                let ele = $(this);
                let store_id= $(this).data('id');
                let is_authorize = 0;
                if($(this).is(':checked')){
                    is_authorize = 1
                }

                $.ajax({
                    url: "{{ route('store.update') }}",
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

            $(document).on('click', '.ad_account', function() {
                let ele = $(this);
                let store_id= $(this).data('id');
                let is_ad_account = 0;
                if($(this).is(':checked')){
                    is_ad_account = 1
                }

                $.ajax({
                    url: "{{ route('store.update') }}",
                    type: 'POST',
                    data: {
                        id:store_id,
                        field:'ad_account',
                        value:is_ad_account,
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

        document.addEventListener("DOMContentLoaded", function () {
            let savedFilters = JSON.parse(sessionStorage.getItem("store_filters"));
            if (savedFilters) {
                console.log(savedFilters);
                Object.keys(savedFilters).forEach(function (key) {

                    console.log(savedFilters[key]);
                    let input = $('#filter-form [name="'+savedFilters[key].name+'"]');
                    if (input) {
                        input.val(savedFilters[key].value)
                    }

                });

                loadDatatables();
            }
        });

        function loadDatatables() {
            var perPage =  '{{ $perPage }}';
            var page =  1;
            $('#storeTable').DataTable().clear().destroy();

            var formData = $('#filter-form').serializeArray();

            sessionStorage.setItem("store_filters", JSON.stringify(formData));
            $.ajax({
                url: "{{ route('stores.filter') }}?page=" + page + "&perPage=" + perPage,
                method: 'GET',
                data: formData,
                success: function(response) {
                    // Update the product list and pagination
                    $('#store-list').html(response.html);
                    $('#pagination').html(response.pagination);

                    $('#storeTable').DataTable({
                        stateSave:true,
                        stateSaveCallback: function(settings, data) {
                            sessionStorage.setItem(
                                'DataTables_' + settings.sInstance,
                                JSON.stringify(data)
                            );
                        },

                        stateLoadCallback: function(settings) {
                            return JSON.parse(
                                sessionStorage.getItem('DataTables_' + settings.sInstance)
                            );
                        },
                        columnDefs: [
                            {
                                targets: [5,8,9],
                                orderable: false
                            }
                        ]
                    });
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                }
            });
        }
    </script>
@stop


