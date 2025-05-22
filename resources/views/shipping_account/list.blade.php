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

            <button type="button" class="btn btn-default" id="transactions">
                <i class="fa fa-fw fa-exchange"></i> Transactions
            </button>
            <button type="button" class="btn btn-default" id="check_balance">
                Check balance & add credit
            </button>
            <button type="button" class="btn btn-default" id="purchase">
                Purchase
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="accountTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th></th>
                <th>Name</th>
                <th>Shipping Solution</th>
                <th>Sandbox Account</th>
            </tr>
            </thead>
            <tbody id="account-list">
                @if(count($shipping_accounts ?? []) > 0)
                    @foreach($shipping_accounts as $ship_acc)
                        <tr>
                            <td></td>
                            <td>{{ $ship_acc->name }}</td>
                            <td>{{ $ship_acc->name }}</td>
                            <td><input type="checkbox" name="sandbox_account" data-id="{{ $ship_acc->id }}" @if($ship_acc->sandbox_account == 1) checked @endif /></td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
@stop

@section('adminlte_js')
    <script>
        $(document).ready(function() {

            $(document).on('click','#account-list tr',function (e) {
                let ele = $(this);

                if ($(e.target).is('input, textarea, select, checkbox, a, i, button, img')) {
                    return;
                }
                //$('#product-list tr').removeClass('bg-blue');
                //$('.pro-id').prop('checked',false);
                if(ele.find('.pro-id').is(':checked')){
                    ele.removeClass('bg-blue');
                    ele.find('.pro-id').prop('checked', false);
                    ele.find('.bulk-edit').attr('disabled',true).hide();
                    ele.find('.prVal').show();
                }else {
                    ele.addClass('bg-blue');
                    ele.find('.pro-id').prop('checked', true);
                }
            });

            $(document).on('click','#update_selected',function () {
                updateRecord('selected');
            });

            $(document).on('click','#update_all',function () {
                updateRecord('all');
            });

            $(document).on('change','#carrier_id',function () {
                var ele = $(this);
                getShippingCarrierService(ele);
            });

            $("#accountTable").dataTable();

        });

    </script>
@stop