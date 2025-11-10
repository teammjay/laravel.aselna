@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Shipping Account Purchase')

@section('content_header')
    <h1>Shipping Account Purchase</h1>
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
                                <select class="form-control" name="shipping_account">
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
                                Amount
                            </div>
                            <div class="col-8">
                                <input type="text" name="amount" id="amount" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row pt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-4 text-right">
                                Purchase Date
                            </div>
                            <div class="col-8">
                                <input type="text" name="purchase_date" id="filter_purchase_date" class="form-control">
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
            <button type="button" class="btn btn-secondary" id="check_balance">
                Check balance & add credit
            </button>
        </div>

    </div>


    <div class="table-responsive">
        <table id="purchaseTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Shuipping Account</th>
                <th>Amount</th>
                <th>Purchase Date</th>
                <th>Errors</th>
                <th>Created Date</th>
            </tr>
            </thead>
            <tbody id="purchase_list">
            </tbody>
        </table>
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
        var selAcc = '{{ $account_id }}';
        $(document).ready(function() {
            var accountPurchaseTable = null;

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
                            accountPurchaseTable.ajax.reload();
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });
            });

            $(document).on('click','#purchase_list tr',function (e) {
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

            accountPurchaseTable = $("#purchaseTable").DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                stateSave: true,
                ajax: {
                    // csrf
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: '{{ route('shipping_account_purchases.filter') }}',
                    type: 'POST',
                    data: function (d) {
                        var formData = $('#filter-form').serializeArray();
                        $.each(formData, function (index, field) {
                            d[field.name] = field.value;
                        });

                        sessionStorage.setItem("account_purchase_filters", JSON.stringify(formData));
                    }
                },
                "order": [[ 4, "desc" ]],
                columns: [
                    {data: 'shipping_account', name: 'shipping_account',orderable: false, searchable: false},
                    {data: 'amount', name: 'amount'},
                    {data: 'purchase_date', name: 'purchase_date'},
                    {data: 'errors', name: 'errors'},
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
                accountPurchaseTable.ajax.reload();
            });

            document.getElementById("resetForm").addEventListener("click", function () {
                sessionStorage.removeItem("account_purchase_filters");
            });


        });

        document.addEventListener("DOMContentLoaded", function () {
            let savedFilters = JSON.parse(sessionStorage.getItem("account_purchase_filters"));
            if (savedFilters) {
                console.log(savedFilters);
                Object.keys(savedFilters).forEach(function (key) {

                    //console.log(savedFilters[key]);
                    let input = $('#filter-form [name="'+savedFilters[key].name+'"]');
                    if (input) {
                        input.val(savedFilters[key].value)
                    }


                });
            }
        });

    </script>
@stop
