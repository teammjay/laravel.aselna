@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'ALl Shipment')

@section('content_header')
    <h1>All Shipment</h1>
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
            border-color: #dee2e6;
        }

        .hide{display:none;}
        /*#shipping_section{min-height: 100vh;}*/
        .clickable-row:hover{cursor:pointer; background: #f0f8ff !important;}
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

    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form">
                <div class="row pt-3">
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-4 text-right">
                                Order ID
                            </div>
                            <div class="col-8">
                                <input type="text" name="order_id" id="order_id" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-4 text-right">
                                Order Date
                            </div>
                            <div class="col-8">
                                <input type="text" name="order_date" id="order_date" class="form-control" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-4 text-right">
                                First Name
                            </div>
                            <div class="col-8">
                                <input type="text" name="buyer_first_name" id="buyer_first_name" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-4 text-right">
                                Last Name
                            </div>
                            <div class="col-8">
                                <input type="text" name="buyer_last_name" id="buyer_last_name" class="form-control">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row pt-3">
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-4 text-right">
                                Purchase Order ID
                            </div>
                            <div class="col-8">
                                <input type="text" name="purchase_order_id" id="purchase_order_id" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-4 text-right">
                                SKU
                            </div>
                            <div class="col-8">
                                <input type="text" name="sku" id="sku" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-4 text-right">
                                Operation Status
                            </div>
                            <div class="col-8">
                                <select class="form-control" name="operation_status" id="operation_status">
                                    <option value="">--</option>
                                    <option value="0">Created</option>
                                    <option value="1">LabelCreated</option>
                                    <option value="2">Error</option>
                                    <option value="3">LabelPrinted</option>
                                    <option value="4">Estimated</option>
                                    <option value="6">Accepted</option>
                                    <option value="7">InTransit</option>
                                    <option value="8">Delivered</option>
                                    <option value="9">DeliveryException</option>
                                    <option value="10">ReturningToSender</option>
                                    <option value="11">ReturnedToSender</option>
                                    <option value="12">Canceled</option>
                                    <option value="13">MarkAsShipped</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row pt-3">
                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-4 text-right">
                                Tracking Number
                            </div>
                            <div class="col-8">
                                <input type="text" name="tracking_number" id="tracking_number" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-4 text-right">
                                Resubmit Count
                            </div>
                            <div class="col-8">
                                <input type="number" name="resubmit_count" id="resubmit_count" class="form-control">
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
            <select class="form-control mt-3 mb-3" name="store_id" id="listing_store">
                <option value="">- All Store -</option>
                @if(count($stores ?? []) > 0)
                    @foreach($stores as $store)
                        <option
                                value="{{ $store->id }}">{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-md-10 d-flex align-items-center" style="gap:10px;">
            <button type="button" class="btn btn-default" id="refresh_list">
                <i class="fa fa-fw fa-spinner"></i> Refresh
            </button>
            <button type="button" class="btn btn-default" id="resubmit_shipment" disabled="">
                <i class="fa fa-fw fa-shipping-fast"></i> Resubmit
            </button>

            <span id="total_selected">( 0 selected )</span>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="">
                <div class="table-responsive" style="">
                    <table id="shipmentTable" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            {{--<th></th>--}}
                            <th>Image</th>
                            <th>Resubmit Count</th>
                            <th>Status</th>
                            <th>Carrier Package</th>
                            <th>Carrier Service</th>
                            <th>Store</th>
                            <th>Purchase Order Id</th>
                            <th>Order Date</th>
                            <th>Product Code</th>
                            <th>Weight</th>
                            <th>Order Id</th>
                            <th>Shipment Date (expct)</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>State</th>
                        </tr>
                        </thead>
                        <tbody id="shipment-list">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {{--<div class="col-md-3">
            <div class="card">
                <div class="card-body" id="shipping_section">
                </div>
            </div>
        </div>--}}
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
                {{--<div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                    <button type="button" class="btn btn-primary" id="updateShipment"><i class="fa fa-sticky-note"></i> Update </button>
                </div>--}}
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
        let isProcessingCancelled = false;
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
        $('#order_date').datepicker({
            dateFormat:'yy-mm-dd',

        });

        let lastChecked = null;
        $(document).on('click','#shipment-list tr',function (e) {
            let ele = $(this);

            if (lastChecked && e.ctrlKey) {
                e.preventDefault();

                if(ele.find('.pro-id').is(':checked')){
                    ele.removeClass('bg-blue');
                    ele.find('.pro-id').prop('checked', false);
                }else {
                    ele.addClass('bg-blue');
                    ele.find('.pro-id').prop('checked', true);
                }

                let checkboxes = $(".pro-id");
                let start = checkboxes.index(ele.find('.proList'));
                let end = checkboxes.index(lastChecked);

                checkboxes.slice(Math.min(start, end), Math.max(start, end) + 1).prop("checked", lastChecked.checked).parents('tr').addClass('bg-blue');
            }
            lastChecked = ele.find('.pro-id');

            let totalCnt = $('input[name="shipment_id[]"]:checked').length;
            $('#total_selected').html('( <b>'+totalCnt+'</b> selected )');
            if(totalCnt > 0){
                $('#resubmit_shipment').removeAttr('disabled');
            }

        });

        /*$(document).on('mousedown','#shipment-list tr',function (e) {
            let ele = $(this);
            if (e.ctrlKey) {
                // Prevent text selection
                e.preventDefault();
            }else{

                $('#shipment-list tr').removeClass('bg-blue');
                $('.pro-id').prop('checked', false);
                ele.addClass('bg-blue');
                ele.find('.pro-id').prop('checked', true);
            }

            let totalCnt = $('input[name="shipment_id[]"]:checked').length;
            $('#total_selected').html('( <b>'+totalCnt+'</b> selected )');

            if(totalCnt > 0){
                $('#resubmit_shipment').removeAttr('disabled');
            }

        });*/

        $(document).on('click','#refresh_list',function () {
            loadDatatables();
        });

        $('#filter-form').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            loadDatatables();
        });

        $(document).on('change','#listing_store',function () {
            $('#store_id').val($(this).val());
            loadDatatables();
        });

        loadDatatables();

        $(document).on('click','#resubmit_shipment',function () {

            let shipment_ids = [];
            $('input[name="shipment_id[]"]:checked').each(function () {
                if($(this).data('status') == 3){
                    shipment_ids.push($(this).val());
                }

            });

            if(shipment_ids.length === 0){
                alert('Please select label printed status shipment.');
                return false;
            }

            $.ajax({
                url: "{{ route('shipment.resubmitshipment') }}",
                type: 'POST',
                data: {
                    shipment_ids: shipment_ids,
                    "_token": '{{ csrf_token() }}'
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                success: function (response) {
                    // If success, remove the user row from the table
                    if (response.success) {
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




        $(document).keydown(function(e) {
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault(); // prevent the default browser "Select All"
                $('#shipment-list tr').addClass('bg-blue');
                $('#shipment-list tr').find('.pro-id').prop('checked', true);

                let totalCnt = $('input[name="shipment_id[]"]:checked').length;
                $('#total_selected').html('( <b>'+totalCnt+'</b> selected )');
            }
        });



    });

    function loadDatatables() {

        $("#shipmentTable").dataTable().fnDestroy();

        var table = $('#shipmentTable').DataTable({
            "iDisplayLength": 20,
            "info": true,
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "searching": false,
            "ordering": false,
            "aLengthMenu": [20, 25, 50, 100],
            'ajax': {
                url: "{{ route('shipment.allfilter') }}",
                type: "POST",
                //dataType:'json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                data: function (d) {
                    // Create a FormData object
                    var formData = $('#filter-form').serializeArray(); // Serialize form inputs

                    // Add form data to the DataTable's request payload
                    $.each(formData, function (index, field) {
                        d[field.name] = field.value; // Append form data to the DataTables request
                    });

                    d['store_id'] = $('#listing_store').val();

                    var columnValues = [];
                    if (table) {
                        table.rows({search: 'applied'}).every(function (rowIdx) {
                            var rowData = this.data();
                            columnValues.push(rowData.id);
                        });
                    }

                    // Add to the request payload
                    d.columnValues = columnValues;

                },
            },
            //"order": [[7, "desc"]],
            'columns': [
                {data: 'image'},
                {data: 'submit_count'},
                {data: 'status'},
                {data: 'carrier_package'},
                {data: 'carrier_service'},
                {data: 'store'},
                {data: 'purchase_order_id'},
                {data: 'order_date'},
                {data: 'product_code'},
                {data: 'weight'},
                {data: 'order_id'},
                {data: 'expected_shipment_date'},
                {data: 'first_name'},
                {data: 'last_name'},
                {data: 'state'}
            ]
        });

        $('#productsTable_wrapper').removeClass('form-inline');

        $.fn.dataTable.ext.errMode = 'throw';
    }

    function loadDatatablesold(){

        //$("#shipmentTable").dataTable().fnDestroy();
        $("#ajax-preloader").show();
        if ($.fn.DataTable.isDataTable('#shipmentTable')) {
            $('#shipmentTable').DataTable().clear().destroy();
        }

        const form = document.querySelector("#filter-form");
        let formData = new FormData(form);
        formData.append('store_id',$('#listing_store').val());

        $.ajax({
            url: "{{ route('shipment.allfilter') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.html) {
                    $('#shipment-list').html(response.html);

                    if(response.totalRecords > 0) {
                        var proTable = $('#shipmentTable').DataTable({
                            "iDisplayLength": 10,
                            "lengthChange": true,
                            "autoWidth": false,
                            "processing": false,
                            "serverSide": false,
                            "searching": false,
                            "paging": true,
                            "info": true,
                            "order": [[7, "desc"]],
                            columnDefs: [
                                {
                                    targets: [0],
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
                    }else{
                        $('#totalRecords').html('Total : <b>0</b>');

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

    }
</script>
@stop
