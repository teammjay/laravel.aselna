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
    <div class="row my-3">
        <div class="col-md-2">
            <button class="btn btn-primary">
                Void
            </button>
        </div>

    </div>

    <div class="card card-primary">
        <div class="card-body">
            <div class="table-responsive">
                <table id="transactionsTable" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Shuipping Account</th>
                        <th>Tracking Number</th>
                        <th>Transaction Date</th>
                        <th>Transaction ID</th>
                        <th>Transaction Amount</th>
                        <th>Transaction Address</th>
                        <th>Cancelled</th>
                        <th>Cancelled Date</th>
                        <th>Created Date</th>
                    </tr>
                    </thead>
                    <tbody id="transaction-list">
                        @if(count($shipping_account_transactions ?? []) > 0)
                            @foreach($shipping_account_transactions as $ship_transaction)
                                <tr data-id="{{ $ship_transaction->id }}">
                                    <td>{{ $ship_transaction->shippingAccount->name }}</td>
                                    <td>{{ $ship_transaction->tracking_number }}</td>
                                    <td>{{ $ship_transaction->transaction_date }}</td>
                                    <td>{{ $ship_transaction->transaction_id }}</td>
                                    <td>{{ $ship_transaction->amount }}</td>
                                    <td>{{ $ship_transaction->address }}</td>
                                    <td><input type="checkbox" name="canceled" data-id="{{ $ship_acc->id }}" @if($ship_transaction->canceled == 1) checked @endif /></td>
                                    <td>{{ $ship_transaction->canceled_date }}</td>
                                    <td>{{ $ship_transaction->created_at }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script>
        $(document).ready(function() {

            $(document).on('click','#transaction-list tr',function (e) {
                let ele = $(this);

                if ($(e.target).is('input, textarea, select, checkbox, a, i, button, img')) {
                    return;
                }
                //$('#account-list tr').removeClass('active bg-blue');
                if(!ele.hasClass('active bg-blue')){
                    ele.addClass('active bg-blue');
                }else{
                    ele.removeClass('active bg-blue');
                }

            });


            $("#transactionsTable").dataTable();

        });

    </script>
@stop