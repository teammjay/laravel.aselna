@extends('adminlte::page')

@section('title', 'Users')

@section('content_header')
    <h1>Users</h1>
@stop

@section('content')
    {{--<div class="card card-default">
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
                            <button type="reset" class="form-control btn btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>--}}
    <div class="table-responsive">
        <table id="userTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    {{--<th>Store ID</th>--}}
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="user-list">
                @include('user.user_list', ['users' => $users])
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

            $('#userTable').DataTable({});


            $('#filter-form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                if ($.fn.DataTable.isDataTable('#userTable')) {
                    $('#userTable').DataTable().clear().destroy();
                }
                $.ajax({
                    url: "{{ route('user.filter') }}",
                    method: 'GET',
                    data: $(this).serialize(),
                    success: function(response) {
                        // Update the product list and pagination
                        $('#user-list').html(response.html);
                        //$('#pagination').html(response.pagination);

                        $('#userTable').DataTable({});
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                    }
                });
            });

            $(document).on('click', '.delete-btn', function() {
                var user_id = $(this).data('id');
                var token = '{{ csrf_token() }}'; // CSRF token for security

                // Confirm the deletion
                if (confirm("Are you sure you want to delete this store?")) {
                    $.ajax({
                        url: "{{ url('user/destroy') }}/" + user_id,   // Laravel route for deletion
                        type: 'GET',
                        data: {
                            "_token": token
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                $('#user-' + user_id).remove();
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
    </script>
@stop


