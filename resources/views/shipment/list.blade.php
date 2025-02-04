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
                    <div class="form-group">
                        <input type="radio" name="ship_from" value="fullfillment_center"> <span>Fullfillment Center</span>
                    </div>
                    <div class="form-group">
                        <input type="radio" name="ship_from" value="uzid_fullfillment_center"> <span>UZID Fullfillment Center</span>
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
                    <div class="form-group">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="m-0">Carrier</label>
                            <select class="form-control w-75">

                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="m-0">Service</label>
                            <select class="form-control w-75">

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
                    <button class="btn btn-default w-100">Update Selected</button>
                    <button class="btn btn-default mt-2 w-100">Update All</button>
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
                <i class="fas fa-fw fa-pencil-alt"></i> Refresh
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="shipmentTable" class="table table-bordered table-striped">
            <thead>
            <tr>
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
                    <form id="frmShippingDetail">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="carrier_name">Carrier Name</label>
                                    </div>
                                    <div class="col-md-8">
                                        <select class="form-control" id="carrier_name" name="carrier_name">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="shipment_carrier">Shipping Carrier</label>
                                    </div>
                                    <div class="col-md-8">
                                        <select class="form-control" id="shipment_carrier" name="shipment_carrier">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="ship_date">Ship Date</label>
                                    </div>
                                    <div class="col-md-8 d-flex" style="gap:10px;">
                                        <input type="text" class="form-control" id="ship_date" name="ship_date" value="">
                                        <input type="text" class="form-control" id="ship_time" name="ship_time" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="insurance_value">Insurance Value</label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" id="insurance_value" name="insurance_value" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="weight">Weight</label>
                                    </div>
                                    <div class="col-md-8 d-flex" style="gap:10px;">
                                        <input type="text" class="form-control" id="weight" name="weight" value="">
                                        <select class="form-control" id="weight_type" name="weight_type">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="carrier_package">Carrier Package</label>
                                    </div>
                                    <div class="col-md-8">
                                        <select class="form-control" id="carrier_package" name="carrier_package">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="cost">Cost</label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" id="cost" name="cost" value="" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="shipment_from">Shipment from</label>
                                    </div>
                                    <div class="col-md-8">
                                        <select class="form-control" id="shipment_from" name="shipment_from">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="shipping_address">Shipping Address</label>
                                    </div>
                                    <div class="col-md-8">
                                        <select class="form-control" id="shipping_address" name="shipping_address">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="signature">Signature</label>
                                    </div>
                                    <div class="col-md-8">
                                        <div>
                                            <input type="radio" name="signature" value="signature"> None
                                        </div>
                                        <div>
                                            <input type="radio" name="signature" value="directsignature"> Direct Signature
                                        </div>
                                        <div>
                                            <input type="radio" name="signature" value="adultsignature"> Adult Signature
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label for="store">Store</label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" id="store" name="store" value="" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 mt-5">
                                <div class="card">
                                    <div class="card-header">
                                        Shipment Items
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="shipmentItemTable" class="table table-bordered table-striped">
                                                <thead>
                                                <tr>
                                                    <th>Image</th>
                                                    <th>Sku</th>
                                                    <th>Quantity</th>
                                                </tr>
                                                </thead>
                                                <tbody id="shipment-item-list">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                    <button type="button" class="btn btn-primary" id="shipThis"><i class="fa fa-save"></i> Ship This </button>
                </div>
            </div>
        </div>
    </div>

@stop

@section('adminlte_js')
    <!-- Lightbox2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#listing_store').select2();
        $('#shippingDetailModal').modal('show');
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
            console.log($(e.target));
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
                $('#edit_product').removeAttr('disabled');
                $('#update_product').removeAttr('disabled');
            }

            if(!$('.pro-id:checked').length){
                $('#edit_product').attr('disabled',true);
                $('#update_product').attr('disabled',true);
            }
        });

        $(document).on('click','#edit_product',function () {
            $('#product-list tr').each(function () {
                var $ele = $(this);
                if($ele.find('.pro-id').is(':checked')){
                    $ele.find('.bulk-edit').removeAttr('disabled').show();
                    $ele.find('.prVal').hide();
                }
            });

        });


        $('#filter-form').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            //loadDatatables();
        });

        $(document).on('change','#listing_store',function () {
            $('#store_id').val($(this).val());
            //loadDatatables();
        });

        //loadDatatables();

        $(document).on('click','.mainImage',function () {
            const productId = $(this).data('product-id'); // Get the product ID
            const galleryDiv = $('#ajax-gallery'); // Reference to the hidden gallery div

            // Fetch images via AJAX
            $.ajax({
                url: '{{ route('order.getimages') }}',
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
    });

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
            "searching": false,
            "aLengthMenu": [20, 25, 50, 100],
            'ajax': {
                url: "{{ route('order.filter') }}",
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
            "order": [[ 10, "desc" ]],
            'columns': [
                { data: 'order_id'},
                { data: 'store' },
                { data: 'order_date' },
                { data: 'order_image' },
                { data: 'order_sku' },
                { data: 'order_status' },
                { data: 'first_name' },
                { data: 'last_name' },
                { data: 'phone' },
                { data: 'order_total' },
                { data: 'buyer_last_name' },
                { data: 'state' },
                { data: 'purchase_order_id' },
                { data: 'customer' }
            ],
            columnDefs: [
                {
                    targets: 6, // The column index
                    className: 'column-no-whitespace' // The class to add
                },
                {
                    targets: [1,6,8], // Index of the column you want to disable sorting for
                    orderable: false // Disable sorting
                }
            ]
        });

        $( '#shipmentTable_wrapper').removeClass( 'form-inline' );

        $.fn.dataTable.ext.errMode = 'throw';
    }
</script>
@stop


