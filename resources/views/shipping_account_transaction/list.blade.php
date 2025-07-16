@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Shipping Account Transaction')

@section('content_header')
    <h1>Shipping Account Transaction</h1>
@stop

@section('content')
    @csrf
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}</div>
    @endif


    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form">
                <input type="hidden" name="store_id" id="store_id">
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
                                Created Date
                            </div>
                            <div class="col-8">
                                <input type="text" name="created_at" id="created_at" class="form-control">
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
                            <button type="reset" class="form-control btn btn-secondary">Reset</button>
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
                <th>Shuipping Account</th>
                <th>Tracking Number</th>
                <th>Transaction Date</th>
                <th>Transaction ID</th>
                <th>Amount</th>
                <th>Address</th>
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
    <script>
        $(document).ready(function() {
            var accountTransactionTable = null;

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
                var transaction_id  = $('input[name="transaction_id[]"]:checked').val();
                var id  = $('input[name="transaction_id[]"]:checked').data('id');
                alert(transaction_id);
                $.ajax({
                    url: "{{ route('shipping_account_transactions.cancellabel') }}",
                    type: 'POST',
                    data: {
                        id: id,
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


            accountTransactionTable = $("#transactionsTable").DataTable({
                processing: true,
                serverSide: true,
                searching: false,
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
                    }
                },
                "order": [[ 2, "desc" ]],
                columns: [
                    {data: 'shipping_account', name: 'shipping_account',orderable: false, searchable: false},
                    {data: 'tracking_number', name: 'tracking_number'},
                    {data: 'transaction_date', name: 'transaction_date'},
                    {data: 'transaction_id', name: 'transaction_id'},
                    {data: 'amount', name: 'amount'},
                    {data: 'address', name: 'address'},
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
                    //$(row).attr('data-id', data.tracking_number);
                }
            });

            $('#filter-form').on('submit', function (e) {
                e.preventDefault(); // Prevent default form submission
                accountTransactionTable.ajax.reload();
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

    </script>
@stop
