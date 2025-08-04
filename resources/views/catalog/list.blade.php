@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Catalog')

@section('content_header')
    <h1>Catalog</h1>
@stop

@section('adminlte_css')
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <link href="{{ asset('css/jquery.multiselect.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

    <style>
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

        .select2-container .select2-selection--single {
            height: auto !important;
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
            <a href="{{ route('catalog.create') }}" class="btn btn-secondary">
                Add Catalog
            </a>
        </div>
        <div class="col-md-10 d-flex align-items-center" style="gap:10px;">
            <button type="button" class="btn btn-default hide" id="show_listing">
                Show Listing
            </button>
            <input type="checkbox" id="show_simple_products"> <label for="show_simple_products" class="mb-0">Show simple
                products</label>

            <button type="button" class="btn btn-default" id="edit_product" disabled="">
                <i class="fas fa-fw fa-pencil-alt"></i> Bulk Edit
            </button>
            <button type="button" class="btn btn-default" id="update_product" disabled="">
                Update Product
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

                <div class="row">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Name
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="name_operator">
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="name" id="name" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Code
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="product_sku_operator">
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="product_sku" id="product_sku" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Product Type
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="product_type_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                </select>
                            </div>
                            <div class="col-6">
                                @php
                                    function renderCategories($productTypes, $prefix = '') {
                                        foreach ($productTypes as $ptype) {
                                            echo '<option value="' . $ptype->id . '">' . $prefix . $ptype->name . '</option>';

                                            if ($ptype->children) {
                                                renderCategories($ptype->children, $prefix . '-- ');
                                            }
                                        }
                                    }
                                @endphp
                                <select class="form-control select2" id="product_type" name="product_type">
                                    <option value=""> --</option>
                                    @php
                                        renderCategories($productTypes);
                                    @endphp
                                    {{--@if(count($productTypes ?? []) > 0)
                                        @foreach($productTypes as $ptype)
                                            <option value="{{ $ptype->id }}">{{ $ptype->name }}</option>
                                        @endforeach
                                    @endif--}}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row pt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Manufacturer
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="manufacturer_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    {{-- <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                     <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                     <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                     <option value="{{FilterCodes::IN}}">in</option>
                                     <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                     <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                     <option value="{{FilterCodes::IS_SET}}">is set</option>--}}
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-control select2" name="manufacturer_id" id="manufacturer_id">
                                    <option value=""> --</option>
                                    @if(count($manufacturers ?? []) > 0)
                                        @foreach($manufacturers as $mf)
                                            <option value="{{ $mf->id }}">{{ $mf->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Min Price
                            </div>
                            <div class="col-6">
                                <input type="number" name="price_min" id="price_min" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Max Price
                            </div>
                            <div class="col-6">
                                <input type="number" name="price_max" id="price_max" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row pt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Interface
                            </div>
                            <div class="col-3">
                                <select class="custom-select select-filter" id="interface_operator"
                                        name="interface_operator" data-for="interface">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    {{--                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>--}}
                                    {{--                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>--}}
                                    {{--                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>--}}
                                    {{--                                    <option value="{{FilterCodes::IN}}">in</option>--}}
                                    {{--                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>--}}
                                    {{--                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>--}}
                                    {{--                                    <option value="{{FilterCodes::IS_SET}}">is set</option>--}}
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-control select2" name="interface" id="interface">
                                    <option value=""> --</option>
                                    @if(count($interfaces ?? []) > 0)
                                        @foreach($interfaces as $interface)
                                            <option value="{{ $interface->id }}">{{ $interface->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Device
                            </div>
                            <div class="col-3">
                                <select class="custom-select select-filter" id="device_operator"
                                        name="device_operator" data-for="device">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    {{--                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>--}}
                                    {{--                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>--}}
                                    {{--                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>--}}
                                    {{--                                    <option value="{{FilterCodes::IN}}">in</option>--}}
                                    {{--                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>--}}
                                    {{--                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>--}}
                                    {{--                                    <option value="{{FilterCodes::IS_SET}}">is set</option>--}}
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-control select2" name="device" id="device">
                                    <option value=""> --</option>
                                    @if(count($devices ?? []) > 0)
                                        @foreach($devices as $device)
                                            <option value="{{ $device->id }}">{{ $device->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row pt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Status
                            </div>
                            <div class="col-6">
                                <select class="custom-select" name="status" id="status">
                                    <option value=""> --</option>
                                    <option value="active"> Active</option>
                                    <option value="discontinued"> Discontinued</option>
                                    <option value="out_of_stock"> Out of stock</option>
                                    <option value="draft"> Draft</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Product Condition
                            </div>
                            <div class="col-6">
                                <select class="custom-select" name="product_condition" id="product_condition">
                                    <option value=""> --</option>
                                    @if(count($productConditions ?? []) > 0)
                                        @foreach($productConditions as $pc)
                                            <option value="{{ $pc->id }}">{{ $pc->name }}</option>
                                        @endforeach
                                    @endif

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Weight
                            </div>
                            <div class="col-6">
                                <input type="text" name="product_weight" id="product_weight" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row pt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Target Audience
                            </div>
                            <div class="col-6">
                                <select class="custom-select" name="target_aud" id="target_aud">
                                    <option value=""> --</option>
                                    @if(count($targetAudience ?? []) > 0)
                                        @foreach($targetAudience as $ta)
                                            <option value="{{ $ta->name }}">{{ $ta->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Color
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="product_color_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    {{--<option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>--}}
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-control" name="product_color" id="product_color">
                                    <option value=""> --</option>
                                    @if(count($colors ?? []) > 0)
                                        @foreach($colors as $color)
                                            <option value="{{ $color->id }}">{{ $color->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Package Weight
                            </div>
                            <div class="col-6">
                                <input type="text" name="package_weight" id="package_weight" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row pt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Created Date
                            </div>
                            <div class="col-6">
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
                            <button type="reset" id="resetBtn" class="form-control btn btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>


            </form>
        </div>
    </div>
    <form action="{{ route('catalog.bulk-update') }}" id="frmbulkupdate" method="POST">
        @csrf
        <div class="table-responsive">
            <table id="productsTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Image</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Marketplace Price</th>
                    <th>Price</th>
                    <th>Interface</th>
                    <th>Manufacturer</th>
                    <th>Color</th>
                    <th>Created Date</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="product-list">

                {{--@include('catalog.product_list', ['products' => $products])--}}

                </tbody>
            </table>
        </div>
    </form>
    <div id="ajax-gallery" style="display: none;"></div>

    <div id="pagination">
        {{--@include('catalog.pagination', ['products' => $products])--}}
    </div>
@stop

@section('adminlte_js')
    <!-- Lightbox2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <script>
        $(document).ready(function () {
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
            $('#created_at').datepicker({
                dateFormat: 'yy-mm-dd',

            });

            $('.select2').select2({
                width: '100%',
            });

            $('#resetBtn').on('click', function () {
                $('.select2').val(null).trigger('change');
            });

            $('.select2').on('select2:open', function () {
                // Wait for dropdown to render before focusing the search field
                setTimeout(function () {
                    document.querySelector('.select2-container--open .select2-search__field').focus();
                }, 0);
            });

            $(document).on('click', '#product-list tr', function (e) {
                let ele = $(this);
                console.log($(e.target));
                if ($(e.target).is('input, textarea, select, checkbox, a, i, button, img')) {
                    return;
                }
                //$('#product-list tr').removeClass('bg-blue');
                //$('.pro-id').prop('checked',false);
                if (ele.find('.pro-id').is(':checked')) {
                    ele.removeClass('bg-blue');
                    ele.find('.pro-id').prop('checked', false);
                    ele.find('.bulk-edit').attr('disabled', true).hide();
                    ele.find('.prVal').show();
                } else {
                    ele.addClass('bg-blue');
                    ele.find('.pro-id').prop('checked', true);
                    $('#edit_product').removeAttr('disabled');
                    $('#update_product').removeAttr('disabled');
                    $('#show_listing').removeAttr('disabled');
                }

                if (!$('.pro-id:checked').length) {
                    $('#edit_product').attr('disabled', true);
                    $('#update_product').attr('disabled', true);
                    $('#show_listing').attr('disabled', true);
                }
            });

            $(document).on('click', '#edit_product', function () {
                $('#product-list tr').each(function () {
                    var $ele = $(this);
                    if ($ele.find('.pro-id').is(':checked')) {
                        $ele.find('.bulk-edit').removeAttr('disabled').show();
                        $ele.find('.prVal').hide();
                    }
                });

            });



            $(document).on('click', '#show_listing', function () {
                var ele = $('.pro-id:checked');
                var pCode = ele.parents('tr').find('.prVal').html();

                if(pCode != ''){
                    window.location.href = "{{ url('listing') }}?code="+pCode;
                }
            });

            $(document).on('click', '#update_product', function () {
                $('#frmbulkupdate').submit();
            });

            $(document).on('click', '.configurable', function () {
                let ele = $(this);
                let product_id = $(this).data('id');
                let is_configurable = 0;
                if ($(this).is(':checked')) {
                    is_configurable = 1
                }

                $.ajax({
                    url: "{{ route('catalog.update') }}",   // Laravel route for deletion
                    type: 'POST',
                    data: {
                        product_id: product_id,
                        configurable: is_configurable,
                        "_token": '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            if (is_configurable) {
                                ele.prop('checked', true);
                            } else {
                                ele.prop('checked', false);
                            }
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });
            });

            $('#filter-form').on('submit', function (e) {
                e.preventDefault(); // Prevent default form submission

                loadDatatables();
                /*var perPage =  '{{ $perPage }}';
            var page =  1;
            $.ajax({
                url: "{{ route('catalog.filter') }}?page=" + page + "&perPage=" + perPage,
                method: 'GET',
                data: $(this).serialize(),
                success: function(response) {
                    // Update the product list and pagination
                    $('#product-list').html(response.html);
                    $('#pagination').html(response.pagination);
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                }
            });*/
            });

            // Handle pagination links click
            /*$(document).on('click', '.pagination a', function(event) {
                event.preventDefault();
                var page = $(this).attr('href').split('page=')[1];
                loadProductsWithPage(page);
            });

            function loadProductsWithPage(page) {
                var perPage = '{{ $perPage }}';
            $.ajax({
                url: "{{ route('catalog.filter') }}?page=" + page + "&perPage=" + perPage,
                data: $('#filter-form').serialize(),
                success: function(response) {
                    $('#product-list').html(response.html);
                    $('#pagination').html(response.pagination);
                }
            });
        }*/

            $(document).on('click', '.delete-btn', function () {
                var productId = $(this).data('id');
                var token = '{{ csrf_token() }}'; // CSRF token for security

                // Confirm the deletion
                if (confirm("Are you sure you want to delete this product?")) {
                    $.ajax({
                        url: "{{ url('catalog/destroy') }}/" + productId,   // Laravel route for deletion
                        type: 'GET',
                        data: {
                            "_token": token
                        },
                        success: function (response) {
                            // If success, remove the user row from the table
                            if (response.success) {
                                location.reload();
                            }
                        },
                        error: function (xhr) {
                            // Handle error response
                            alert('Error: ' + xhr.responseJSON.error);
                        }
                    });
                }
            });

            $(document).on('click', '#show_simple_products', function () {
                $('#configurable').remove();
                if ($(this).is(':checked')) {
                    $('#filter-form').prepend('<input type="hidden" name="configurable" id="configurable" value="1">');
                } else {
                    $('#filter-form').prepend('<input type="hidden" name="configurable" id="configurable" value="0">');
                }

                loadDatatables();
            });

            loadDatatables();

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
        });

        function loadDatatables() {

            $("#productsTable").dataTable().fnDestroy();

            var table = $('#productsTable').DataTable({
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
                    url: "{{ route('catalog.filter') }}",
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

                        // Append DataTables parameters
                        $.each(d, function (key, value) {
                            //formData.append(key, value);
                            //d.key = value;
                        });


                        //return formData;
                    },
                    //processData: false,
                    //contentType: false
                },
                "order": [[10, "desc"]],
                'columns': [
                    {data: 'product_id'},
                    {data: 'product_image_url'},
                    {data: 'product_sku'},
                    {data: 'product_type'},
                    {data: 'product_title'},
                    {data: 'product_status'},
                    {data: 'marketplace_price'},
                    {data: 'product_price'},
                    {data: 'interface'},
                    {data: 'manufacture_name'},
                    {data: 'product_color'},
                    {data: 'created_at'},
                    {data: 'action'}
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

            $('#productsTable_wrapper').removeClass('form-inline');

            $.fn.dataTable.ext.errMode = 'throw';
        }
    </script>
@stop


