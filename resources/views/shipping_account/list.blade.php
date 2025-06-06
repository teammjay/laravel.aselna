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
                                        <a href="{{ route('shipping_account.delete', $ship_acc->id) }}" class="btn btn-danger" title="Remove">
                                            <i class="fas fa-fw fa-trash"></i>
                                        </a>
                                    </td>
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
        let selAcc = null;
        $(document).ready(function() {

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

            $("#accountTable").dataTable();

        });

    </script>
@stop