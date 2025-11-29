@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Shipping Account Transaction')

@section('content_header')
    <h1>Shipping Account Transaction</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-dropdown .select2-search__field:focus, .select2-container--default .select2-search--inline .select2-search__field:focus {
            outline: 0;
            border: none;
        }
        .select2-container{width:100% !important;}
        .select2-container .select2-selection--single{
            height: calc(2.25rem + 2px);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: auto;
            position: absolute;
            top: 50%;
            right: 1px;
            width: 20px;
            transform: translateY(-50%);
        }
    </style>
@stop

@section('content')
    @csrf
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}</div>
    @endif

    {{--<div class="row">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-cog"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">Completed</span>
                    <span class="info-box-number">
                          @if($states->status == 'completed')
                              {{ $states->status }}
                          @endif
                  <small>%</small>
                </span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
        <!-- /.col -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-thumbs-up"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">Pending</span>
                    <span class="info-box-number">41,410</span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
        <!-- /.col -->

        <!-- fix for small devices only -->
        <div class="clearfix hidden-md-up"></div>

        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-shopping-cart"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">Cancelled</span>
                    <span class="info-box-number">760</span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>

    </div>--}}

    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form">

                <div class="row pt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-4 text-right">
                                Shipping Account
                            </div>
                            <div class="col-8">
                                <select class="form-control" name="">
                                    <option value="">--</option>
                                    @if(count($shipping_account ?? []) > 0)
                                        @foreach($shipping_account as $sa)
                                            <option value="{{ $sa->id }}">{{ $sa->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-4 text-right">
                                Tracking Number
                            </div>
                            <div class="col-8">
                                <input type="text" name="tracking_number" id="tracking_number" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-4 text-right">
                                Transaction Date
                            </div>
                            <div class="col-8">
                                <input type="text" name="transaction_date" id="transaction_date" class="form-control">
                            </div>
                        </div>
                    </div>



                </div>

                <div class="row pt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-4 text-right">
                                Transaction ID
                            </div>
                            <div class="col-8">
                                <input type="text" name="transaction_id" id="transaction_id" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-4 text-right">
                                Cancelled Date
                            </div>
                            <div class="col-8">
                                <input type="text" name="cancelled_date" id="cancelled_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-4 text-right">
                                Address
                            </div>
                            <div class="col-8">
                                <input type="text" name="address" id="address" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row pt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-4 text-right">
                                Store
                            </div>
                            <div class="col-8">
                                <select class="form-control" name="store_id" id="store_id">
                                    <option value="">-- All Stores --</option>
                                    @if(count($stores ?? []) > 0)
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}">{{ $store->name }} - {{ $store->storeType->name ?? '' }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-4 text-right">
                                Created Date
                            </div>
                            <div class="col-8">
                                <input type="text" name="created_at" id="created_at" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-4 text-right">
                               Status
                            </div>
                            <div class="col-8">
                                <select class="form-control" name="status" id="status">
                                    <option value=""></option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row pt-3">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="form-control btn btn-primary">Apply Filter</button>
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

    <div class="row my-3">
        <div class="col-md-2">
            <button class="btn btn-primary" id="cancleLabel">
                Void
            </button>
            <button class="btn btn-secondary" id="reprintLabel">
                Re-print
            </button>
        </div>

    </div>


    <div class="table-responsive">
        <table id="transactionsTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Shipping Account</th>
                <th>Tracking Number</th>
                <th>Transaction Date</th>
                <th>Transaction ID</th>
                <th>Store</th>
                <th>Amount</th>
                <th>Address</th>
                <th>Status</th>
                <th>Cancelled</th>
                <th>Cancelled Date</th>
                <th>Created Date</th>
            </tr>
            </thead>
            <tbody id="transaction-list">
{{--                @if(count($shipping_account_transactions ?? []) > 0)--}}
{{--                    @foreach($shipping_account_transactions as $ship_transaction)--}}
{{--                        <tr data-id="{{ $ship_transaction->id }}">--}}
{{--                            <td>{{ $ship_transaction->shippingAccount->name }}<input type="checkbox" data-id="{{ $ship_transaction->id }}" name="transaction_id[]" style="visibility: hidden;" value="{{ $ship_transaction->tracking_number }}"></td>--}}
{{--                            <td>{{ $ship_transaction->tracking_number }}</td>--}}
{{--                            <td>{{ $ship_transaction->transaction_date }}</td>--}}
{{--                            <td>{{ $ship_transaction->transaction_id }}</td>--}}
{{--                            <td>{{ $ship_transaction->amount }}</td>--}}
{{--                            <td>{{ $ship_transaction->address }}</td>--}}
{{--                            <td><input type="checkbox" name="canceled" disabled @if($ship_transaction->canceled == 1) checked @endif /></td>--}}
{{--                            <td>{{ $ship_transaction->canceled_date }}</td>--}}
{{--                            <td>{{ $ship_transaction->created_at }}</td>--}}
{{--                        </tr>--}}
{{--                    @endforeach--}}
{{--                @endif--}}
            </tbody>
        </table>
    </div>
@stop

@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        var accountTransactionTable = null;
        $(document).ready(function() {

            $('#store_id').select2();

            $('.card-header').on('click', function () {
                if ($(this).find('.fa').hasClass('fa-angle-down')) {
                    $(this).find('.fa').removeClass('fa-angle-down');
                    $(this).find('.fa').addClass('fa-angle-up');
                } else {
                    $(this).find('.fa').removeClass('fa-angle-up');
                    $(this).find('.fa').addClass('fa-angle-down');
                }
                $('.card-body').toggle();
            });
            $('#created_at, #transaction_date, #cancelled_date').datepicker({
                dateFormat: 'yy-mm-dd',
            });

            $(document).on('click','#transaction-list tr',function (e) {
                let ele = $(this);

                if ($(e.target).is('input, textarea, select, checkbox, a, i, button, img')) {
                    return;
                }

                if (ele.find('input[name="canceled"]').is(':checked')) {
                    return;
                }
                //$('#account-list tr').removeClass('active bg-blue');
                if(!ele.hasClass('active bg-blue')){
                    ele.addClass('active bg-blue');
                    ele.find('input[name="transaction_id[]"]').prop('checked',true);
                }else{
                    ele.removeClass('active bg-blue');
                    ele.find('input[name="transaction_id[]"]').prop('checked',false);
                }

            });

            $(document).on('click','#cancleLabel',function () {
                var transaction_ids  = [];
                $('input[name="transaction_id[]"]:checked').each(function (e) {
                    transaction_ids.push($(this).val());
                });
                var ids  = [];
                $('input[name="transaction_id[]"]:checked').each(function (e) {
                    ids.push($(this).data('id'));
                });

                if (confirm("Are you sure you want to void this label?")) {

                    $.ajax({
                        url: "{{ route('shipping_account_transactions.cancellabel') }}",
                        type: 'POST',
                        data: {
                            ids: ids,
                            transaction_ids: transaction_ids,
                            account_id: '{{ $account_id }}',
                            "_token": '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            // If success, remove the user row from the table

                            if (response.success) {
                                if(response.successCnt > 0 && response.failCnt == 0){
                                    alert('All labels voided successfully.');
                                }else if(response.successCnt == 0 && response.failCnt > 0){
                                    alert('All labels voided failed.' + ' Message : '+response.message);
                                }else if(response.successCnt > 0 && response.failCnt > 0){
                                    alert(response.successCnt + ' labels voided and '+ response.successCnt + ' failed. Message:'+ response.message);
                                }
                                location.reload();
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }
            });


            accountTransactionTable = $("#transactionsTable").DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                stateSave: true,
                ajax: {
                    // csrf
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: '{{ route('shipping_account_transactions.filter') }}',
                    type: 'POST',
                    data: function (d) {
                        var formData = $('#filter-form').serializeArray();
                        $.each(formData, function (index, field) {
                            d[field.name] = field.value;
                        });

                        sessionStorage.setItem("account_transaction_filters", JSON.stringify(formData));
                    }
                },
                "order": [[ 10, "desc" ]],
                columns: [
                    {data: 'shipping_account', name: 'shipping_account',orderable: false, searchable: false},
                    {data: 'tracking_number', name: 'tracking_number'},
                    {data: 'transaction_date', name: 'transaction_date'},
                    {data: 'transaction_id', name: 'transaction_id'},
                    {data: 'store', name: 'store',orderable: false},
                    {data: 'amount', name: 'amount'},
                    {data: 'address', name: 'address'},
                    {data: 'status', name: 'status'},
                    {data: 'canceled', name: 'canceled',
                        render: function (data, type, row) {
                            return `<input type="checkbox" name="canceled" disabled ${data ? 'checked' : ''} />`;
                        }
                    },
                    {data: 'canceled_date', name: 'canceled_date'},
                    {data: 'created_at', name: 'created_at'}
                ],
                createdRow: function (row, data, dataIndex) {
                    // Assuming `data.id` holds the transaction ID
                    $(row).attr('data-id', data.tracking_number);
                },
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
                }
            });

            $('#filter-form').on('submit', function (e) {
                e.preventDefault(); // Prevent default form submission
                accountTransactionTable.ajax.reload();
            });

            document.getElementById("resetForm").addEventListener("click", function () {
                sessionStorage.removeItem("account_transaction_filters");
            });



            $('#reprintLabel').on('click', function () {
                var selectedTransactions = $('#transaction-list tr.active');
                var transaction_id = [];
                selectedTransactions.each(function () {
                    var id = $(this).data('id');
                    if (id) {
                        transaction_id.push(id);
                    }
                });
                if (transaction_id.length === 0) {
                    alert('Please select a transaction to reprint.');
                    return;
                }
                $.ajax({
                    url: "{{ route('shipping_account_transactions.reprintlabel') }}",
                    type: 'POST',
                    data: {
                        transaction_id: transaction_id,
                        account_id: '{{ $account_id }}',
                        "_token": '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        alert(response.message);
                        if (response.success) {
                            location.reload();
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });
            });

            {{--function loadTransactions(){--}}

            {{--    if ($.fn.DataTable.isDataTable('#transactionsTable')) {--}}
            {{--        $('#transactionsTable').DataTable().clear().destroy();--}}
            {{--    }--}}

            {{--    const form = document.querySelector("#filter-form");--}}
            {{--    let formData = new FormData(form);--}}

            {{--    $.ajax({--}}
            {{--        url: "{{ route('shipping_account_transactions.filter') }}",--}}
            {{--        type: 'POST',--}}
            {{--        data: formData,--}}
            {{--        processData: false,--}}
            {{--        contentType: false,--}}
            {{--        headers: {--}}
            {{--            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')--}}
            {{--        },--}}
            {{--        success: function (response) {--}}
            {{--            if (response.html) {--}}
            {{--                $('#transaction-list').html(response.html);--}}

            {{--                if(response.totalRecords > 0) {--}}
            {{--                    var proTable = $('#transactionsTable').DataTable({--}}
            {{--                        "lengthChange": false,--}}
            {{--                        "autoWidth": false,--}}
            {{--                        "processing": false,--}}
            {{--                        "serverSide": false,--}}
            {{--                        "searching": false,--}}
            {{--                        "paging": true,--}}
            {{--                        "info": false,--}}
            {{--                        "order": [[8, "desc"]],--}}
            {{--                        /*columnDefs: [--}}
            {{--                            {--}}
            {{--                                targets: [0],--}}
            {{--                                orderable: false--}}
            {{--                            }--}}
            {{--                        ]*/--}}
            {{--                    });--}}
            {{--                }--}}

            {{--            }--}}
            {{--        },--}}
            {{--        error: function (xhr) {--}}
            {{--            /*if (xhr.responseJSON?.error) {--}}
            {{--                $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');--}}
            {{--            }*/--}}
            {{--        },--}}
            {{--        complete: function () {--}}
            {{--            $("#ajax-preloader").hide();--}}
            {{--        }--}}
            {{--    });--}}
            {{--}--}}
        });

        document.addEventListener("DOMContentLoaded", function () {
            let savedFilters = JSON.parse(sessionStorage.getItem("account_transaction_filters"));
            if (savedFilters) {
                console.log(savedFilters);
                Object.keys(savedFilters).forEach(function (key) {

                    console.log(savedFilters[key]);
                    let input = $('#filter-form [name="'+savedFilters[key].name+'"]');
                    if (input) {
                        input.val(savedFilters[key].value)
                    }

                    if( savedFilters[key].name == 'store_id'){
                        $('#listing_store').val(savedFilters[key].value);
                    }

                });

                //accountTransactionTable.ajax.reload();
            }
        });

    </script>
@stop
