@php use App\Constants\OrderStatus; @endphp
@extends('adminlte::page')

@section('title', 'Order Detail')

@section('content_header')
    <h1>Order Detail</h1>
@stop

@section('adminlte_css')

@stop

@section('content')

    @csrf
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Order Summary
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="order_id">Order Id</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="order_id" name="order_id"
                                           value="{{ old('order_id',$orderDetail->order_id) }}" readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="store">Store</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="hidden" class="form-control" id="store_id" name="store_id"
                                           value="{{ old('store_id',$orderDetail->store_id) }}">
                                    <input type="text" class="form-control" id="store"
                                           value="{{ $orderDetail->store->name }} - {{ $orderDetail->store->storeType->name }}"
                                           readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="order_date">Order Date</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="order_date" name="order_date"
                                           value="{{ old('order_date',$orderDetail->order_date) }}" readonly>
                                </div>
                            </div>

                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="order_status">Order Status</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="order_status" name="order_status"
                                           value="{{ old('order_status', OrderStatus::getOrderStatusName($orderDetail->order_status)) }}"
                                           readonly>
                                </div>
                            </div>

                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="customer">Customer</label>
                                </div>
                                <div class="col-md-9">
                                    @if(isset($orderDetail->customer))
                                        <a href="javascript:void(0);" class="text-blue" id="edit_customer" data-id="{{ $orderDetail->customer->id }}">
                                            {{ $orderDetail->customer->full_name }}
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="buyer_name">Buyer Name</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="buyer_first_name"
                                           name="buyer_first_name"
                                           value="{{ old('buyer_first_name',$orderDetail->buyer_first_name) }}"
                                           readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="buyer_last_name">Buyer Last Name</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="buyer_last_name" name="buyer_last_name"
                                           value="{{ old('buyer_last_name',$orderDetail->buyer_last_name) }}" readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="buyer_email">Buyer Email</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="buyer_email" name="buyer_email"
                                           value="{{ old('buyer_email',$orderDetail->buyer_email) }}" readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="buyer_user_id">Buyer User Id</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="buyer_user_id" name="buyer_user_id"
                                           value="" readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="order_total">Order Total</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="order_total" name="order_total"
                                           value="{{ old('order_total',$orderDetail->order_total) }}" readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="purchase_order_id">Purchase Order Id</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="purchase_order_id"
                                           name="purchase_order_id"
                                           value="{{ old('purchase_order_id',$orderDetail->purchase_order_id) }}"
                                           readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="order_risk_score">Order risk score</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="order_risk_score"
                                           name="order_risk_score"
                                           value="{{ old('order_risk_score',$orderDetail->order_risk_score) }}"
                                           readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="order_risk_message">Order risk message</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="order_risk_message"
                                           name="order_risk_message"
                                           value="{{ old('order_risk_message',$orderDetail->order_risk_message) }}"
                                           readonly>
                                </div>
                            </div>

                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="contigent">Contingent</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="checkbox" id="contigent"
                                           name="contigent" {{ ($orderDetail->contigent == 1) ? 'checked'  : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">

            <div class="card">
                <div class="card-header">
                    Order Summary
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="order_id">Shipping Address</label>
                                </div>
                                <div class="col-md-9">
                                    @if(isset($orderDetail->customerAddress))
                                        <a href="javascript:void(0);" class="text-blue" id="edit_address" data-id="{{ $orderDetail->customerAddress->id }}">
                                            {{ $orderDetail->customerAddress->address_combined }} {{$orderDetail->customerAddress->city}} {{$orderDetail->customerAddress->state}}
                                            {{$orderDetail->customerAddress->country}} {{$orderDetail->customerAddress->zip_code}}
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="requested_shipment">Requested Shipment</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="requested_shipment_service"
                                           name="requested_shipment_service"
                                           value="{{ old('requested_shipment_service',$orderDetail->requested_shipment_service) }}"
                                           readonly>
                                </div>
                            </div>
                            <div class="row mt-3 g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="requested_shipment">Requested Shipment</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="requested_shipment_carrier"
                                           name="requested_shipment_carrier"
                                           value="{{ old('requested_shipment_carrier',$orderDetail->requested_shipment_carrier) }}"
                                           readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Operations
                </div>
                <div class="card-body">
                    <textarea class="form-control" rows="10"></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    Order Items
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="itemTable" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>Thumbnail</th>
                                <th>Sku</th>
                                <th>Store Sku</th>
                                <th>Code</th>
                                <th>Quantity</th>
                                <th>Item Price</th>
                                <th>Id1</th>
                                <th>Id2</th>
                                <th>Title</th>
                            </tr>
                            </thead>
                            <tbody id="order-item-list">
                            @if(count($orderDetail->orderItem ?? []) > 0)
                                @foreach($orderDetail->orderItem as $item)
                                    <tr>
                                        <td>
                                            @if(isset($item->product) && $item->product->product_image_url != '')
                                                <div class="main-image">
                                                    <img
                                                        src="{{  asset('storage/product_images/'. $item->product->product_image_url) }}"
                                                        alt="{{  $item->product->product_image_url }}" class="mainImage"
                                                        width="70" height="70"
                                                        data-product-id="{{ $item->product->product_id }}">
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $item->sku }}</td>
                                        <td>{{ $item->store_sku }}</td>
                                        <td>{{ (isset($item->product)) ? $item->product->product_sku : '' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->item_price }}</td>
                                        <td>{{ $item->id1 }}</td>
                                        <td>{{ $item->id2 }}</td>
                                        <td>{{ $item->title }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9">No item found</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mark as edit postal address modal -->
    <div class="modal fade" id="editCustomerModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">Customer Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                    <button type="button" class="btn btn-primary" id="saveCustomer"><i class="fa fa-check"></i> Ok </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mark as edit postal address modal -->
    <div class="modal fade" id="editPostalAddressModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPostalAddressModalLabel">Postal Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary closeaddressbtn" data-dismiss="modal">Cancle</button>
                    <button type="button" class="btn btn-primary" id="saveAddress"><i class="fa fa-check"></i> Ok </button>
                </div>
            </div>
        </div>
    </div>
@stop
@section('adminlte_js')
    <script>

        $(document).ready(function () {
            $(document).on('click', '#edit_address', function () {
                var address_id = $(this).data('id');
                if (address_id != '' && address_id != null) {
                    $.ajax({
                        url: "{{ route('shipment.getshippingaddress') }}",
                        type: 'POST',
                        data: {
                            address_id: address_id,
                            "_token": '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                $('#editPostalAddressModal').find('.modal-body').html(response.html);
                                $('#editPostalAddressModal').modal('show');
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }
            });

            $(document).on('click', '#saveAddress', function () {

                const form = document.querySelector("#frmEditAddress");
                let formData = new FormData(form);
                $.ajax({
                    url: "{{ route('shipment.saveaddress') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            $('#edit_address').html(response.address);
                            $('#editPostalAddressModal').modal('hide');

                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });
            });

            $(document).on('click', '#edit_customer', function () {
                var customer_id = $(this).data('id');
                if (customer_id != '' && customer_id != null) {
                    $.ajax({
                        url: "{{ route('order.getcustomerdetail') }}",
                        type: 'POST',
                        data: {
                            customer_id: customer_id,
                            "_token": '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                $('#editCustomerModal').find('.modal-body').html(response.html);
                                $('#editCustomerModal').modal('show');
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }
            });

            $(document).on('click', '#saveCustomer', function () {

                const form = document.querySelector("#frmEditCustomer");
                let formData = new FormData(form);
                $.ajax({
                    url: "{{ route('order.savecustomer') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            $('#edit_customer').html(response.name);
                            $('#editCustomerModal').modal('hide');

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
