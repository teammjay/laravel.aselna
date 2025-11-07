@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Account')

@section('content_header')
    <h1>Account</h1>
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
            <a href="{{ route('shipping_account.create') }}" class="btn btn-primary">
                Create
            </a>
        </div>
        <div class="col-md-10 d-flex align-items-center" style="gap:10px;">

            <button type="button" class="btn btn-default" id="transactions" disabled>
                <i class="fa fa-fw fa-exchange"></i> Transactions
            </button>
            <button type="button" class="btn btn-default" id="check_balance" disabled>
                Check balance & add credit
            </button>
            <button type="button" class="btn btn-default" id="purchase" disabled>
                Purchase
            </button>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-body">
            <div class="table-responsive">
                <table id="accountTable" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Shipping Solution</th>
                        <th>Sandbox Account</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody id="account-list">
                        @if(count($shipping_accounts ?? []) > 0)
                            @foreach($shipping_accounts as $ship_acc)
                                <tr data-id="{{ $ship_acc->id }}">
                                    <td>{{ $ship_acc->name }}</td>
                                    <td>{{ isset($ship_acc->shippingSolution) ? $ship_acc->shippingSolution->name : '' }}</td>
                                    <td><input type="checkbox" name="sandbox_account" data-id="{{ $ship_acc->id }}" @if($ship_acc->sandbox_account == 1) checked @endif /></td>
                                    <td>
                                        <a href="{{ route('shipping_account.edit', $ship_acc->id) }}" class="btn btn-primary" title="Edit">
                                            <i class="fas fa-fw fa-pencil-alt"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger delete-btn" data-module="shipping_account" data-id="{{ $ship_acc->id }}" title="Remove"> <i class="fas fa-fw fa-trash"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Purchase Credit Modal -->
    <div class="modal fade" id="purchaseCreditModal">
        <div class="modal-dialog modal-dialog-centered" style="max-width:800px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="makeRMAModalLabel">Account Credit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="order_id">Available Credit</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="available_credit" id="available_credit" value="" readonly="">
                                </div>
                            </div>

                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="order_id">Purchase Credit</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="purchase_credit" id="purchase_credit" value="">
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-4">
                                    <label for="order_id">Purchase Date</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="purchase_date" id="purchase_date" value="{{ date('Y-m-d H:i:s') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                    <button type="button" class="btn btn-primary" id="save_credit"><i class="fa fa-check"></i> Ok </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script>
        let selAcc = null;
        $(document).ready(function() {

            $('#purchase_date').datepicker({
                dateFormat:'yy-mm-dd',
                defaultDate: new Date(),
                minDate: new Date()
            });

            $(document).on('click','#account-list tr',function (e) {
                let ele = $(this);

                if ($(e.target).is('input, textarea, select, checkbox, a, i, button, img')) {
                    return;
                }

                if(!ele.hasClass('active bg-blue')){
                    $('#account-list tr').removeClass('active bg-blue');
                    ele.addClass('active bg-blue');
                }else{
                    ele.removeClass('active bg-blue');
                }

                selAcc = ele.data('id');

                $('#transactions').removeAttr('disabled');
                $('#check_balance').removeAttr('disabled');
                $('#purchase').removeAttr('disabled');
            });

            $(document).on('click','#transactions',function () {
                location.href = "{{ url('shipping_account_transactions') }}/" + selAcc;
            });

            $(document).on('click','#purchase',function () {
                location.href = "{{ url('shipping_account_purchases') }}/" + selAcc;
            });

            $("#accountTable").dataTable();

            $('#check_balance').on('click', function () {
                if (selAcc === null) {
                    alert('Please select account.');
                    return;
                }
                $.ajax({
                    url: "{{ route('shipping_account.getaccountcredit') }}",
                    type: 'POST',
                    data: {
                        account_id: selAcc,
                        "_token": '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            $('#available_credit').val(response.balance);
                            $('#purchase_credit').val(response.credit);
                            $('#purchaseCreditModal').modal('show');
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });
            });



            $('#save_credit').on('click', function () {
                if (selAcc === null) {
                    alert('Please select account.');
                    return;
                }

                var purchase_credit = $('#purchase_credit').val();
                var available_credit = $('#available_credit').val();

                var total_credit = parseFloat(purchase_credit) + parseFloat(available_credit);

                if(total_credit > 500){
                    alert('Your total credit more than 500');
                    return false;
                }

                $.ajax({
                    url: "{{ route('shipping_account.purchasecredit') }}",
                    type: 'POST',
                    data: {
                        account_id: selAcc,
                        credit_amount: $('#purchase_credit').val(),
                        purchase_date: $('#purchase_date').val(),
                        "_token": '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        alert(response.message);
                        if (response.success) {
                            $('#purchaseCreditModal').modal('hide');
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