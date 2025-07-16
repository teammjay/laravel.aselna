@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Shipment')

@section('content_header')
    <h1>Shipment</h1>
@stop

@section('adminlte_css')
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link href="{{ asset('css/jquery.datetimepicker.css') }}" rel="stylesheet">

    <style>
        .select2-container--default .select2-dropdown .select2-search__field:focus, .select2-container--default .select2-search--inline .select2-search__field:focus {
            outline: 0;
            border: none;
        }

        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
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

        table th, table td, .column-no-whitespace {
            white-space: nowrap;
        }
        .bulk-edit{display: none; width: 150px;}
        table th, .column-no-whitespace{
            white-space: nowrap;
        }
        .main-image img {
            /*width: 300px;
            height: 300px;*/
            object-fit: cover;
            cursor: pointer;
        }

        .error{border-color:red;}

        #shipmentTable thead th {
            position: sticky;
            top: 0;
            background: white; /* Or your table's background */
            z-index: 10;
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
    <div class="row my-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    Options
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <input type="checkbox" name="ship_options" value="same_address"> <span>Combine Same Address</span>
                    </div>
                    <div class="form-group">
                        <input type="checkbox" name="ship_options" value="add_insurance"> <span>Add Insurance</span>
                    </div>
                    <div class="form-group">
                        <input type="checkbox" name="ship_options" value="stop_on_error"> <span>Stop On Error</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    Ship From
                </div>
                <div class="card-body">
                    @if(count($shipFrom ?? []) > 0)
                        @foreach($shipFrom as $lc)
                            <div class="form-group mb-1">
                                <input type="radio" name="ship_from" value="{{ $lc->id }}"> <span>{{ $lc->name }}</span>
                            </div>
                        @endforeach
                    @endif

                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    Shipping Service
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="d-flex align-items-center justify-content-between">
                            <label for="carrier_id" class="m-0">Carrier</label>
                            <select class="form-control w-75" id="carrier_id">
                                @if(count($shippingCarrier ?? []) > 0)
                                    @foreach($shippingCarrier as $sCarrier)
                                        <option value="{{ $sCarrier->id }}">{{ $sCarrier->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="d-flex align-items-center justify-content-between">
                            <label for="carrier_service_id" class="m-0">Service</label>
                            <select class="form-control w-75" id="carrier_service_id">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="d-flex align-items-center justify-content-between">
                            <label for="carrier_service_id" class="m-0">Carrier Package</label>
                            <select class="form-control" id="carrier_package" name="carrier_package">
                                <option value="">--</option>
                                @if(count($shippingCarrierPackage ?? []) > 0)
                                    @foreach($shippingCarrierPackage as $sCarrierPackage)
                                        <option value="{{ $sCarrierPackage->id }}">{{ $sCarrierPackage->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    Shipping Service
                </div>
                <div class="card-body">
                    <button class="btn btn-default w-100" id="update_selected">Update Selected</button>
                    <button class="btn btn-default mt-2 w-100" id="update_all">Update All</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row my-3">
        <div class="col-md-2">
            <select class="form-control mt-3 mb-3" name="store_id" id="listing_store">
                <option value="">All Stores</option>
                @if(count($stores ?? []) > 0)
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-md-10 d-flex align-items-center" style="gap:10px;">
            <button type="button" class="btn btn-default" id="refresh_list">
                <i class="fa fa-fw fa-spinner"></i> Refresh
            </button>
            <button type="button" class="btn btn-default" id="ship_all">
                <i class="fa fa-fw fa-shipping-fast"></i> Ship All
            </button>
            <button type="button" class="btn btn-default" id="ship_selected">
                <i class="fa fa-fw fa-shipping-fast"></i> Ship Selected
            </button>
            <button type="button" class="btn btn-default" id="mark_as_shipped">
                Mark As Shipped
            </button>
            <button type="button" class="btn btn-default" id="cancel_selected">
                Cancel Selected
            </button>

            <span id="total_selected">( 0 selected )</span>
        </div>
    </div>
    <div class="row">
        <div class="col-md-1" id="totalRecords"></div>
        <div class="col-md-3">
            <input type="checkbox" id="select_deselect_shipment"> <label for="selected_product">Select/Deselect All</label>
        </div>
    </div>
    <div class="table-responsive pb-5" style="max-height: 500px; overflow: auto;">
        <table id="shipmentTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th></th>
                <th>Image</th>
                <th>Status</th>
                <th>Carrier Package</th>
                <th>Carrier Service</th>
                <th>Store</th>
                <th>Order Id</th>
                <th>Order Date</th>
                <th>Shipment Date (expct)</th>
                <th>Product Code</th>
                <th>Weight</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>City</th>
            </tr>
            </thead>
            <tbody id="shipment-list">
            </tbody>
        </table>
    </div>
    <div id="ajax-gallery" style="display: none;"></div>

    <!-- Shipping Detail modal -->
    <div class="modal fade" id="shippingDetailModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                {{--<div class="modal-header">
                    <h5 class="modal-title" id="shippingDetailModalLabel">Price Update Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>--}}
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                    <button type="button" class="btn btn-primary" id="updateShipment"><i class="fa fa-sticky-note"></i> Update </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mark as shipped modal -->
    <div class="modal fade" id="markAsShippedModal">
        <div class="modal-dialog modal-dialog-centered" style="max-width:800px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="markAsShippedModalLabel">Shipment Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                    <button type="button" class="btn btn-primary" id="mark_shipped"><i class="fa fa-check"></i> Ok </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mark as cancel shipment modal -->
    <div class="modal fade" id="cancelShipmentModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelShipmentModalLabel">Cancel Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                    <button type="button" class="btn btn-primary" id="cancelOrder"><i class="fa fa-check"></i> Ok </button>
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
    <!-- Lightbox2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="{{ asset('js/jquery.datetimepicker.full.js') }}"></script>

    <script>
    $(document).ready(function() {
        $('#listing_store').select2();
        //$('#markAsShippedModal').modal('show');
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
        $('#created_at').datepicker({
            dateFormat:'yy-mm-dd',

        });

        $(document).on('click','#shipment-list tr',function (e) {
            let ele = $(this);

            if ($(e.target).is('input, textarea, select, checkbox, a, i, button, img')) {
                return;
            }
            //$('#product-list tr').removeClass('bg-blue');
            //$('.pro-id').prop('checked',false);
            if(ele.find('.pro-id').is(':checked')){
                ele.removeClass('bg-blue');
                ele.find('.pro-id').prop('checked', false);
            }else {
                ele.addClass('bg-blue');
                ele.find('.pro-id').prop('checked', true);
            }

            let totalCnt = $('input[name="shipment_id[]"]:checked').length;
            $('#total_selected').html('( <b>'+totalCnt+'</b> selected )');
        });

        $(document).on('click','#refresh_list',function () {
            loadDatatables();
        });

        $(document).on('click','#mark_as_shipped',function () {
            if($('input[name="shipment_id[]"]:checked').length > 1){
                alert('Please select one shipment to make "Mark as Shipped"');
                $('input[name="shipment_id[]"]').prop('checked',false);
                $('input[name="shipment_id[]"]').parents('tr').removeClass('bg-blue');
                return false;
            }
            var shipment_id = $('input[name="shipment_id[]"]:checked').val();
            if(shipment_id != '' && shipment_id != null){
                $.ajax({
                    url: "{{ route('shipment.getshippingdetail') }}",
                    type: 'POST',
                    data: {
                        operation_id: shipment_id,
                        mark_as_ship:1,
                        "_token": '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            $('#markAsShippedModal').find('.modal-body').html(response.html);
                            $('#date').datetimepicker({
                                format:'Y-m-d H:i',
                                /* minDate: 'now', */
                                step:10,
                                onSelectTime: function(dp,$input) {
                                    var sel_date = $input.val();
                                    $('#date').val(sel_date);
                                }
                            });
                            $('#markAsShippedModal').modal('show');

                            getShippingCarrierService($('#carrier_name_ms'));
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });
            }else{
                alert('Please select shipment first');
            }
        });

        $(document).on('click','#cancel_selected',function () {
            var shipment_id = $('input[name="shipment_id[]"]:checked').val();
            if(shipment_id != '' && shipment_id != null){
                $.ajax({
                    url: "{{ route('shipment.getcancelshipmentdetail') }}",
                    type: 'POST',
                    data: {
                        operation_id: shipment_id,
                        "_token": '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            $('#cancelShipmentModal').find('.modal-body').html(response.html);
                            $('#canceldate').datetimepicker({
                                format:'Y-m-d H:i',
                                /* minDate: 'now', */
                                step:10,
                                onSelectTime: function(dp,$input) {
                                    var sel_date = $input.val();
                                    $('#canceldate').val(sel_date);
                                }
                            });
                            $('#cancelShipmentModal').modal('show');
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });
            }else{
                alert('Please select shipment first');
            }
        });



        $('#filter-form').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            loadDatatables();
        });

        $(document).on('change','#listing_store',function () {
            $('#store_id').val($(this).val());
            loadDatatables();
        });

        $(document).on('click','.show-detail',function () {
            var operation_id = $(this).data('id');
            $.ajax({
                url: "{{ route('shipment.getshippingdetail') }}",
                type: 'POST',
                data: {
                    operation_id: operation_id,
                    "_token": '{{ csrf_token() }}'
                },
                success: function (response) {
                    // If success, remove the user row from the table
                    if (response.success) {
                        $('#shippingDetailModal').find('.modal-body').html(response.html);
                        $('#ship_date').datetimepicker({
                            format:'Y-m-d H:i',
                            minDate: 'now',
                            step:10,
                            onSelectTime: function(dp,$input) {
                                var sel_date = $input.val();
                                $('#ship_date').val(sel_date);
                            }
                        });
                        $('#shippingDetailModal').modal('show');

                        loadServiceCarrier($('#carrier_name').val());
                    }
                },
                error: function (xhr) {
                    // Handle error response
                    alert('Error: ' + xhr.responseJSON.error);
                }
            });
        });

        $(document).on('click','#cancelOrder',function () {

            const form = document.querySelector("#frmCancelShipment");
            let formData = new FormData(form);
            $.ajax({
                url: "{{ route('shipment.cancelorder') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
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

        $(document).on('click','#update_selected',function () {
            updateRecord('selected');
        });

        $(document).on('click','#update_all',function () {
            updateRecord('all');
        });

        getShippingCarrierService($('#carrier_id'));

        $(document).on('change','#carrier_id',function () {
            var ele = $(this);
            getShippingCarrierService(ele);
        });

        $(document).on('change','#carrier_name, #carrier_name_ms',function () {
            var ele = $(this);
            getShippingCarrierService(ele);
        });


        loadDatatables();
        loadServiceCarrier($('#carrier_name').val());

        $(document).on('change','#carrier_name, #carrier_name_ms',function () {
            loadServiceCarrier($(this).val());
        });

        $(document).on('click','.mainImage',function () {
            const productId = $(this).data('product-id'); // Get the product ID
            const galleryDiv = $('#ajax-gallery'); // Reference to the hidden gallery div

            // Fetch images via AJAX
            $.ajax({
                url: '{{ route('catalog.getimages') }}',
                method: 'POST',
                data:{
                    productId : productId,
                    "_token": '{{ csrf_token() }}'
                },
                success: function (images) {
                    galleryDiv.empty(); // Clear any existing content

                    // Append images to the gallery div
                    $.each(images, function (index, image) {
                        const anchor = $('<a>')
                            .attr('href', image)
                            .attr('data-lightbox', 'gallery')
                            .attr('data-title', 'Gallery Image');

                        const img = $('<img>')
                            .attr('src', image)
                            .css('display', 'none'); // Hidden thumbnails

                        anchor.append(img);
                        galleryDiv.append(anchor);
                    });

                    // Trigger Lightbox by simulating a click on the first image
                    if (images.length > 0) {
                        galleryDiv.find('a:first').trigger('click');
                    } else {
                        alert('No images found for this product.');
                    }
                },
                error: function () {
                    alert('Failed to load images.');
                }
            });
        });

        $(document).on('click','#edit_address',function () {
            var address_id = $(this).data('id');
            if(address_id != '' && address_id != null){
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


        $(document).on('click','#saveAddress',function () {

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
                        $('body').addClass('modal-open');

                    }
                },
                error: function (xhr) {
                    // Handle error response
                    alert('Error: ' + xhr.responseJSON.error);
                }
            });
        });

        $(document).on('click','.closeaddressbtn',function () {
            setTimeout(function(){
                $('body').addClass('modal-open');
            },500);
        });

        $(document).on('click','#mark_shipped',function () {

            const form = document.querySelector("#frmMarkasShipped");
            let formData = new FormData(form);

            const trackingNumber = formData.get("tracking_number");
            const label_generation = formData.get("label_generation");

            if(label_generation.trim() != 1) {
                $('#tracking_number').removeClass('error');
                if (!trackingNumber || trackingNumber.trim() === "") {
                    $('#tracking_number').addClass('error');
                    alert("Tracking number is required.");
                    return;
                }
            }

            const orderId = formData.get("order_id");

            // if (isNaN(orderId) || parseInt(orderId) <= 0) {
            //     $('#order_id').addClass('error');
            //     alert("Please enter a valid Order ID.");
            //     return;
            // }

            $.ajax({
                url: "{{ route('shipment.markshipped') }}",
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
                        alert('Order marked as shipped successfully!!');
                        location.reload();
                    }
                     else {
                         alert(response.message);
                    }
                },
                error: function (xhr) {
                    // Handle error response
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function (key, messages) {
                            alert(key + ': ' + messages[0]);
                        });
                    }
                    /*const response = xhr.responseJSON;
                    if ( response.errors) {
                        alert(response.message);
                    } else {
                        alert('Error: ' + response.message);
                    }*/
                }
            });
        });



        $(document).keydown(function(e) {
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault(); // prevent the default browser "Select All"
                $('#shipment-list tr').addClass('bg-blue');
                $('#shipment-list tr').find('.pro-id').prop('checked', true);

                let totalCnt = $('input[name="shipment_id[]"]:checked').length;
                $('#total_selected').html('( <b>'+totalCnt+'</b> selected )');
            }
        });


        $(document).on('click','#ship_all',function () {
            let shipment_ids = [];
            $('input[name="shipment_id[]"]').each(function () {
                shipment_ids.push($(this).val())
            });

            if(shipment_ids.length === 0){
                alert('Please select shipment first.');
                return false;
            }

            // Create a modal for progress display
            if (!$('#progressModal').length) {
                $('body').append(`
                    <div class="modal fade" id="progressModal" data-backdrop="static" data-keyboard="false">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Processing Shipments</h5>
                                </div>
                                <div class="modal-body">
                                    <div class="progress mb-3">
                                        <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                    </div>
                                    <div id="progress-status" class="text-center">Preparing to process shipments...</div>
                                    <div id="progress-detail" class="mt-3">
                                        <ul class="list-group" id="progress-items"></ul>
                                    </div>
                                </div>
                                <div class="modal-footer" style="display:none">
                                    <button type="button" class="btn btn-success" data-dismiss="modal">Complete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Reset and show modal
            $('#progressModal .progress-bar').css('width', '0%').attr('aria-valuenow', 0).text('0%');
            $('#progress-status').text('Preparing to process shipments...');
            $('#progress-items').empty();
            $('#progressModal .modal-footer').hide();
            $('#progressModal').modal('show');

            // Start the processing by making the initial request
            $.ajax({
                url: "{{ route('shipment.shipselected') }}",
                type: 'POST',
                data: {
                    shipment_ids: shipment_ids,
                    "_token": '{{ csrf_token() }}'
                },
                global: false,
                success: function (response) {
                    if (response.success) {
                        const process_id = response.process_id;
                        const total_count = response.total_count;
                        const shipment_ids = response.shipment_ids;

                        $('#progress-status').text(`Processing 0/${total_count} shipments`);

                        // Process each shipment sequentially
                        processShipmentsSequentially(process_id, shipment_ids, total_count, 0);
                    } else {
                        $('#progressModal').modal('hide');
                        alert(response.message || 'Failed to process shipments');
                    }
                },
                error: function (xhr) {
                    $('#progressModal').modal('hide');
                    const response = xhr.responseJSON;
                    if (response && response.errors) {
                        alert(response.message);
                    } else {
                        alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
                    }
                }
            });
        });

        $(document).on('click','#ship_selected',function () {
            let shipment_ids = [];
            $('input[name="shipment_id[]"]:checked').each(function () {
                shipment_ids.push($(this).val())
            });

            if(shipment_ids.length === 0){
                alert('Please select shipment first.');
                return false;
            }

            // Create a modal for progress display
            if (!$('#progressModal').length) {
                $('body').append(`
                    <div class="modal fade" id="progressModal" data-backdrop="static" data-keyboard="false">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Processing Shipments</h5>
                                </div>
                                <div class="modal-body">
                                    <div class="progress mb-3">
                                        <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                    </div>
                                    <div id="progress-status" class="text-center">Preparing to process shipments...</div>
                                    <div id="progress-detail" class="mt-3">
                                        <ul class="list-group" id="progress-items"></ul>
                                    </div>
                                </div>
                                <div class="modal-footer" style="display:none">
                                    <button type="button" class="btn btn-success" data-dismiss="modal">Complete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }

            // Reset and show modal
            $('#progressModal .progress-bar').css('width', '0%').attr('aria-valuenow', 0).text('0%');
            $('#progress-status').text('Preparing to process shipments...');
            $('#progress-items').empty();
            $('#progressModal .modal-footer').hide();
            $('#progressModal').modal('show');

            // Start the processing by making the initial request
            $.ajax({
                url: "{{ route('shipment.shipselected') }}",
                type: 'POST',
                data: {
                    shipment_ids: shipment_ids,
                    "_token": '{{ csrf_token() }}'
                },
                global: false,
                success: function (response) {
                    if (response.success) {
                        const process_id = response.process_id;
                        const total_count = response.total_count;
                        const shipment_ids = response.shipment_ids;

                        $('#progress-status').text(`Processing 0/${total_count} shipments`);

                        // Process each shipment sequentially
                        processShipmentsSequentially(process_id, shipment_ids, total_count, 0);
                    } else {
                        $('#progressModal').modal('hide');
                        alert(response.message || 'Failed to process shipments');
                    }
                },
                error: function (xhr) {
                    $('#progressModal').modal('hide');
                    const response = xhr.responseJSON;
                    if (response && response.errors) {
                        alert(response.message);
                    } else {
                        alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
                    }
                }
            });
        });

        // Function to process shipments one by one
        function processShipmentsSequentially(process_id, shipment_ids, total_count, current_index) {
            if (current_index >= shipment_ids.length) {
                // All done
                let progress = 100;
                $('#progress-status').text(`Completed processing all ${total_count} shipments`);
                $('#progress-bar').css('width', `${progress}%`).attr('aria-valuenow', progress).text(`${progress}%`);
                $('#progressModal .modal-footer').show();
                return;
            }

            const shipmentId = shipment_ids[current_index];

            // Update the progress UI
            const progress = Math.round(((current_index) / total_count) * 100);
            console.log(progress)
            $('#progress-status').text(`Processing ${current_index+1}/${total_count} shipments`);
            $('#progress-bar').css('width', `${progress}%`).attr('aria-valuenow', progress).text(`${progress}%`);

            // check for 5 items only remove previous items
            // if (current_index >= 5) {
            //     $('#progress-items li').slice(0, current_index - 4).remove();
            // }

            // Add item to list
            $('#progress-items').append(`
                <li class="list-group-item d-flex justify-content-between align-items-center" id="item-${shipmentId}">
                    Order #${shipmentId}
                    <span class="badge badge-warning">Processing</span>
                </li>
            `);

            // Make the API call to process this shipment
            $.ajax({
                url: "{{ route('shipment.shipselected') }}",
                type: 'POST',
                data: {
                    process_id: process_id,
                    order_id: shipmentId,
                    "_token": '{{ csrf_token() }}'
                },
                global: false,
                success: function (response) {
                    // Update the item status in the list
                    if (response.success) {
                        $(`tr[data-id="${shipmentId}"]`).remove();

                        $(`#item-${shipmentId}`).remove();
                    } else {
                        $(`#item-${shipmentId} .badge`)
                            .removeClass('badge-warning')
                            .addClass('badge-danger')
                            .text('Failed');
                    }

                    // Process the next item
                    processShipmentsSequentially(process_id, shipment_ids, total_count, current_index + 1);
                },
                error: function (xhr) {
                    // Mark this item as failed
                    $(`#item-${shipmentId} .badge`)
                        .removeClass('badge-warning')
                        .addClass('badge-danger')
                        .text('Failed');

                    // Continue with next item
                    processShipmentsSequentially(process_id, shipment_ids, total_count, current_index + 1);
                }
            });
        }

        $(document).on('click','#updateShipment',function () {

            const form = document.querySelector("#frmShippingDetail");
            let formData = new FormData(form);

            const carrier_package = formData.get("carrier_package");
            const label_generation = formData.get("label_generation");

            if (!carrier_package || carrier_package.trim() === "") {
                alert("Carrier package is required.");
                return;
            }


            $.ajax({
                url: "<?php echo e(route('shipment.updateshipment')); ?>",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                },
                success: function (response) {
                    // If success, remove the user row from the table
                    if (response.success) {
                        alert('Shipped Updated successfully!!');
                        location.reload();
                    }
                    else {
                        alert(response.message);
                    }
                },
                error: function (xhr) {
                    // Handle error response
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function (key, messages) {
                            alert(key + ': ' + messages[0]);
                        });
                    }
                    /*const response = xhr.responseJSON;
                    if ( response.errors) {
                        alert(response.message);
                    } else {
                        alert('Error: ' + response.message);
                    }*/
                }
            });
        });

        $(document).on('click', '#select_deselect_shipment', function() {

            var ele = $(this);
            if(ele.is(':checked')){
                $('.pro-id').each(function () {
                    $(this).prop('checked', true);
                    $(this).parents('tr').addClass('bg-blue');
                });
            }else {

                $('.pro-id').each(function () {
                    $(this).prop('checked', false);
                    $(this).parents('tr').removeClass('bg-blue');
                });
            }

            let totalCnt = $('input[name="shipment_id[]"]:checked').length;
            $('#total_selected').html('( <b>'+totalCnt+'</b> selected )');
        });
    });

    function getShippingCarrierService(element){
        $.ajax({
            url: "{{ route('shipment.getshippingcarrier') }}",
            type: 'POST',
            data: {
                carrier_id: element.val(),
                "_token": '{{ csrf_token() }}'
            },
            success: function (response) {
                // If success, remove the user row from the table
                if (response.success) {
                    $('#carrier_service_id').html(response.option);
                    $('#shipping_carrier_service').html(response.option);
                }
            },
            error: function (xhr) {
                // Handle error response
                alert('Error: ' + xhr.responseJSON.error);
            }
        });
    }

    function updateRecord(type){
        var needUpdate = false;
        var selected_shipment = [];
        if(type === 'selected'){

            $('input[name="shipment_id[]"]:checked').each(function () {
                selected_shipment.push($(this).val())
            });
        }else{
            $('input[name="shipment_id[]"]').each(function () {
                selected_shipment.push($(this).val())
            });
        }

        if(selected_shipment.length === 0){
            alert('Please select record first');
            return false;
        }
        var carrier_id;
        if($('#carrier_id').val() !== ''){
            needUpdate = true;
            carrier_id = $('#carrier_id').val();
        }

        var carrier_service_id;
        if($('#carrier_service_id').val() !== ''){
            needUpdate = true;
            carrier_service_id = $('#carrier_service_id').val();
        }

        var carrier_package;
        if($('#carrier_package').val() !== ''){
            needUpdate = true;
            carrier_package = $('#carrier_package').val();
        }

        var ship_from;
        if($('input[name="ship_from"]').is(':checked')){
            needUpdate = true;
            ship_from = $('input[name="ship_from"]:checked').val();
        }

        var ship_option;
        if($('input[name="ship_options"]').is(':checked')){
            needUpdate = true;
            ship_option = $('input[name="ship_options"]:checked').val();
        }

        if(!needUpdate){
            alert('Noting to ppdate. Please select option to update.');
            return false;
        }

        $.ajax({
            url: "{{ route('shipment.updateshippingdetails') }}",
            type: 'POST',
            data: {
                selected_shipment: selected_shipment,
                carrier_id: carrier_id,
                carrier_service_id: carrier_service_id,
                ship_from: ship_from,
                ship_option: ship_option,
                carrier_package: carrier_package,
                "_token": '{{ csrf_token() }}'
            },
            success: function (response) {
                // If success, remove the user row from the table
                if (response.success) {
                    alert('Records updated successfully!!');
                    location.reload();
                }
            },
            error: function (xhr) {
                // Handle error response
                alert('Error: ' + xhr.responseJSON.error);
            }
        });
    }

    function loadServiceCarrier(carrier){
        $.ajax({
            url: "{{ route('shipment.getshippingcarrier') }}",
            type: 'POST',
            data: {
                carrier_id:carrier,
                "_token": '{{ csrf_token() }}'
            },
            success: function (response) {
                // If success, remove the user row from the table
                if (response.success) {
                    $('#shipment_carrier').html(response.option);
                }
            },
            error: function (xhr) {
                // Handle error response
                alert('Error: ' + xhr.responseJSON.error);
            }
        });
    }

    function loadDatatables(){

        //$("#shipmentTable").dataTable().fnDestroy();
        $("#ajax-preloader").show();
        if ($.fn.DataTable.isDataTable('#shipmentTable')) {
            $('#shipmentTable').DataTable().clear().destroy();
        }
        $.ajax({
            url: "{{ route('shipment.filter') }}",
            type: 'POST',
            data: {
                'store_id':$('#listing_store').val()
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.html) {
                    $('#shipment-list').html(response.html);

                    if(response.totalRecords > 0) {
                        var proTable = $('#shipmentTable').DataTable({
                            "iDisplayLength": 100,
                            "lengthChange": true,
                            "autoWidth": false,
                            "processing": false,
                            "serverSide": false,
                            "searching": true,
                            "paging": false,
                            "info": false,
                            "order": [[7, "desc"]],
                            columnDefs: [
                                {
                                    targets: [0,1],
                                    orderable: false
                                }
                            ],
                            drawCallback: function(settings) {
                                let info = this.api().page.info();
                                //$('#shipmentTable_wrapper').find('.row div:first').html('Total : <b>' + info.recordsDisplay + '</b>');
                                $('#totalRecords').html('Total : <b>' + info.recordsDisplay + '</b>');
                            }
                        });

                        //$('#shipmentTable_wrapper').find('.row div:first').html('Total : <b>' + response.totalRecords + '</b>');
                    }

                }
            },
            error: function (xhr) {
                /*if (xhr.responseJSON?.error) {
                    $('#responseMessage').html('<p style="color:red;">' + xhr.responseJSON.error.image[0] + '</p>');
                }*/
            },
            complete: function () {
                $("#ajax-preloader").hide();
            }
        });

        /*var table = $('#shipmentTable').DataTable({
            "iDisplayLength": 100,
            "info": true,
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "searching": true,
            "aLengthMenu": [20, 25, 50, 100],
            'ajax': {
                url: "{{ route('shipment.filter') }}",
                type: "POST",
                //dataType:'json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                data: function(d) {
                    // Create a FormData object
                    var formData = $('#filter-form').serializeArray(); // Serialize form inputs

                    // Add form data to the DataTable's request payload
                    $.each(formData, function(index, field) {
                        d[field.name] = field.value; // Append form data to the DataTables request
                    });

                    d['store_id'] = $('#listing_store').val();

                    var columnValues = [];
                    if (table) {
                        table.rows({ search: 'applied' }).every(function(rowIdx) {
                            var rowData = this.data();
                            columnValues.push(rowData.id);
                        });
                    }

                    // Add to the request payload
                    d.columnValues = columnValues;
                },
                //processData: false,
                //contentType: false
            },
            "order": [[ 6, "desc" ]],
            'columns': [
                { data: 'action'},
                { data: 'image'},
                { data: 'status' },
                { data: 'carrier_service' },
                { data: 'store' },
                { data: 'order_id' },
                { data: 'order_date' },
                { data: 'product_code' },
                { data: 'weight' },
                { data: 'first_name' },
                { data: 'last_name' },
                { data: 'city' }
            ],
            columnDefs: [
                {
                    targets: 3, // The column index
                    className: 'column-no-whitespace' // The class to add
                },
                {
                    targets: [0,1,3,7], // Index of the column you want to disable sorting for
                    orderable: false // Disable sorting
                }
            ]
        });*/

        //$( '#shipmentTable_wrapper').removeClass( 'form-inline' );

        //$.fn.dataTable.ext.errMode = 'throw';
    }
</script>
@stop
