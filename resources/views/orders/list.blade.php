@php use App\Constants\FilterCodes;use App\Constants\OrderStatus; @endphp
@extends('adminlte::page')

@section('title', 'Orders')

@section('content_header')
    <h1>Orders</h1>
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

        .bulk-edit {
            display: none;
            width: 150px;
        }

        table th, .column-no-whitespace {
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
        <div class="col-md-2">
            <select class="form-control mt-3 mb-3" name="store_id" id="listing_store">
                <option value="">All Stores</option>
                @if(count($stores ?? []) > 0)
                    @foreach($stores as $store)
                        <option
                            value="{{ $store->id }}">{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-md-10 d-flex align-items-center" style="gap:10px;">
            <button type="button" class="btn btn-default" id="edit_order" disabled="">
                <i class="fas fa-fw fa-pencil-alt"></i> Edit
            </button>
            <button type="button" class="btn btn-default" id="create_message" disabled="">
                <i class="fas fa-fw fa-envelope"></i> Create Message
            </button>
        </div>
    </div>
    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form">
                <input type="hidden" name="store_id" id="store_id">
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
                                <input type="text" name="order_date" id="order_date" class="form-control">
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
                            <button type="reset" class="form-control btn btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>


            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table id="ordersTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Order ID</th>
                <th>Store</th>
                <th>Order Date</th>
                <th>Order Image</th>
                <th>Order SKU</th>
                <th>Order Status</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Phone</th>
                <th>Order Total</th>
                <th>Buyer Last Name</th>
                <th>State or Origin</th>
                <th>Purchase Order Id</th>
                <th>Customer</th>
            </tr>
            </thead>
            <tbody id="order-list">

            {{--@include('Orders.product_list', ['products' => $products])--}}

            </tbody>
        </table>
    </div>
    <div id="ajax-gallery" style="display: none;"></div>

@stop

@section('adminlte_js')
    <!-- Lightbox2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#listing_store').select2();
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
            $('#created_at, #order_date').datepicker({
                dateFormat: 'yy-mm-dd',
            });

            $(document).on('click', '#order-list tr', function (e) {
                let ele = $(this);

                if ($(e.target).is('input, textarea, select, checkbox, a, i, button, img')) {
                    return;
                }
                $('#order-list tr').removeClass('bg-blue');
                $('.pro-id').prop('checked', false);
                if (ele.find('.pro-id').is(':checked')) {
                    ele.removeClass('bg-blue');
                    ele.find('.pro-id').prop('checked', false);
                } else {
                    ele.addClass('bg-blue');
                    ele.find('.pro-id').prop('checked', true);
                    $('#edit_order').removeAttr('disabled');
                    $('#create_message').removeAttr('disabled');
                }

                if (!$('.pro-id:checked').length) {
                    $('#edit_product').attr('disabled', true);
                    $('#create_message').attr('disabled', true);
                }
            });

            $('#edit_order').on('click', function () {
                let flag = true;
                $('input[name="order_id[]"]').each(function () {
                    if ($(this).is(':checked')) {
                        flag = false;
                        let selectedId = $(this).val();
                        location.href = "{{ url('orders/edit') }}/" + selectedId;
                    }
                });

            });

            $('#create_message').on('click', function () {
                let flag = true;
                $('input[name="order_id[]"]').each(function () {
                    if ($(this).is(':checked')) {
                        flag = false;
                        let selectedId = $(this).val();
                        location.href = "{{ url('orders/create_message') }}/" + selectedId;
                    }
                });

            });


            $('#filter-form').on('submit', function (e) {
                e.preventDefault(); // Prevent default form submission
                loadDatatables();
            });

            $(document).on('change', '#listing_store', function () {
                $('#store_id').val($(this).val());
                loadDatatables();
            });

            //loadDatatables();

            $(document).on('click', '.mainImage', function () {
                const productId = $(this).data('product-id'); // Get the product ID
                const galleryDiv = $('#ajax-gallery'); // Reference to the hidden gallery div

                // Fetch images via AJAX
                $.ajax({
                    url: '{{ route('catalog.getimages') }}',
                    method: 'POST',
                    data: {
                        productId: productId,
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

            loadDatatables();

        });

        function loadDatatables() {

            $("#ordersTable").dataTable().fnDestroy();

            var table = $('#ordersTable').DataTable({
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
                    data: function (d) {
                        // Create a FormData object
                        var formData = $('#filter-form').serializeArray(); // Serialize form inputs

                        // Add form data to the DataTable's request payload
                        $.each(formData, function (index, field) {
                            d[field.name] = field.value; // Append form data to the DataTables request
                        });

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
                    //processData: false,
                    //contentType: false
                },
                "order": [[2, "desc"]],
                'columns': [
                    {data: 'order_id'},
                    {data: 'store'},
                    {data: 'order_date'},
                    {data: 'order_image'},
                    {data: 'order_sku'},
                    {
                        data: 'order_status', render: function (data,) {
                            const statusJson = '<?= json_encode(OrderStatus::names) ?>';
                            const statusTypes = JSON.parse(statusJson);
                            if (statusTypes[data] === undefined) {
                                return data;
                            }
                            return statusTypes[data];
                        }
                    },
                    {data: 'first_name'},
                    {data: 'last_name'},
                    {data: 'phone'},
                    {data: 'order_total'},
                    {data: 'buyer_last_name'},
                    {data: 'state'},
                    {data: 'purchase_order_id'},
                    {data: 'customer'}
                ],
                columnDefs: [
                    {
                        targets: 6, // The column index
                        className: 'column-no-whitespace' // The class to add
                    },
                    {
                        targets: [1, 6, 8], // Index of the column you want to disable sorting for
                        orderable: false // Disable sorting
                    }
                ]
            });

            $('#ordersTable_wrapper').removeClass('form-inline');

            $.fn.dataTable.ext.errMode = 'throw';
        }
    </script>
@stop


