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
                                <option value="">--</option>
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
        </div>
    </div>

    <div class="table-responsive">
        <table id="shipmentTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th></th>
                <th>Image</th>
                <th>Status</th>
                <th>Carrier Service</th>
                <th>Store</th>
                <th>Order Id</th>
                <th>Order Date</th>
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
                    <button type="button" class="btn btn-primary" id="shipThis"><i class="fa fa-shipping-fast"></i> Ship This </button>
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
                    <button type="button" class="btn btn-primary" id="shipThis"><i class="fa fa-check"></i> Ok </button>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                    <button type="button" class="btn btn-primary" id="saveShipping"><i class="fa fa-check"></i> Ok </button>
                </div>
            </div>
        </div>
    </div>
    <div id="ajax-gallery" style="display: none;"></div>
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
                ele.find('.bulk-edit').attr('disabled',true).hide();
                ele.find('.prVal').show();
            }else {
                ele.addClass('bg-blue');
                ele.find('.pro-id').prop('checked', true);
            }
        });

        $(document).on('click','#refresh_list',function () {
            loadDatatables();
        });

        $(document).on('click','#mark_as_shipped',function () {
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
                url: "{{ route('shipment.cancelshipment') }}",
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
                        alert('Records updated successfully!!');
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

        $(document).on('change','#carrier_id',function () {
            var ele = $(this);
            getShippingCarrierService(ele);
        });

        $(document).on('change','#carrier_name',function () {
            var ele = $(this);
            getShippingCarrierService(ele);
        });

        loadDatatables();
        loadServiceCarrier();

        $(document).on('change','#carrier_name',function () {
            loadServiceCarrier();
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

        $("#shipmentTable").dataTable();

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
        if(type == 'selected'){

            $('input[name="shipment_id[]"]:checked').each(function () {
                selected_shipment.push($(this).val())
            });
        }else{
            $('input[name="shipment_id[]"]').each(function () {
                selected_shipment.push($(this).val())
            });
        }

        if(selected_shipment.length == 0){
            alert('Please select record first');
            return false;
        }
        var carrier_id;
        if($('#carrier_id').val() != ''){
            needUpdate = true;
            carrier_id = $('#carrier_id').val();
        }

        var carrier_service_id;
        if($('#carrier_service_id').val() != ''){
            needUpdate = true;
            carrier_service_id = $('#carrier_service_id').val();
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

    function loadServiceCarrier(){
        var carrier = $('#carrier_name').val();
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

        $("#shipmentTable").dataTable().fnDestroy();

        var table = $('#shipmentTable').DataTable({
            "iDisplayLength": 20,
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
        });

        $( '#shipmentTable_wrapper').removeClass( 'form-inline' );

        $.fn.dataTable.ext.errMode = 'throw';
    }
</script>
@stop


