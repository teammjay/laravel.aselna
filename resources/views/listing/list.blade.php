@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Listing')

@section('content_header')
    <h1>Listing</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-1">
            <a href="{{ route('listing.create') }}" class="btn btn-secondary">
                Create
            </a>
        </div>
        <div class="col-md-3">
            <select class="form-control" id="listing_store" name="store_id">
                <option value=""></option>
                @if(count($stores ?? []) > 0)
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}{{ ($store->marketplace_name) ? '-'.$store->marketplace_name : '' }}</option>
                    @endforeach
                @endif
            </select>

        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-secondary" id="open_store_page">
                Open Store Page
            </button>
        </div>
    </div>
    <div class="card card-secondary">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form">
                <div class="row">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                SKU
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="sku_operator">
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
                                <input type="text" name="sku" id="sku" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Parent or Child SKU
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="parent_sku_operator">
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
                                <input type="text" name="parent_sku" id="parent_sku" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Store
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="store_operator">
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
                                <input type="text" name="store" id="store" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Listing Status
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="listing_status_operator">
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
                                <input type="text" name="listing_status" id="listing_status" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Device Type
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="" name="device_type">
                                    <option value=""></option>
                                    @if(count($device_types ?? []) > 0)
                                        @foreach($device_types as $dtype)
                                            <option value="{{ $dtype->id }}">{{ $dtype->name }}</option>
                                        @endforeach
                                    @endif

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Main Product Type
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="" name="main_product_type">
                                    <option></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Manufacturer
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="manufacturer_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="manufacturer" name="manufacturer">
                                    <option></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Title
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="title_operator">
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
                                <input type="text" name="title" id="title" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Store SKU
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="store_sku_operator">
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="checkbox" name="store_sku" id="store_sku">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Quantity
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="quantity_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="quantity" id="quantity" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Product Status
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="product_status_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="product_status" name="product_status">
                                    <option></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Sync Message
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="sync_message_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="checkbox" name="sync_message" id="sync_message">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Product Code
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="product_code_operator">
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
                                <input type="text" name="product_code" id="product_code" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Device
                            </div>

                            <div class="col-6">
                                <select class="form-control" id="device" name="device">
                                    <option></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Device Brand
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="device_brand" name="device_brand">
                                    <option></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Sales Count
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="sales_count_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="sales_count" id="sales_count" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Last Sale Date
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="last_sale_date_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="last_sale_date" id="last_sale_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Price
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="price_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="price" id="price" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Product Type
                            </div>

                            <div class="col-6">
                                <select class="form-control" id="" name="product_type">
                                    <option value=""></option>
                                    @if(count($product_types ?? []) > 0)
                                        @foreach($product_types as $ptype)
                                            <option value="{{ $ptype->id }}">{{ $ptype->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Item Created Date
                            </div>
                            <div class="col-6">
                                <input type="text" name="item_created" id="item_created" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                ID
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="id_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="id" id="id" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Bundle
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="bundle_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="bundle" name="bundle">
                                    <option></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Sync Status
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="sync_status_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="sync_status" name="sync_status">
                                    <option></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                UPC
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="upc_operator">
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
                                <input type="text" name="upc" id="upc" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" id="apply_filter" class="form-control btn btn-primary">Filter</button>
                        </div>

                    </div>
                </div>

            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table id="productsTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>SKU</th>
                <th>Sales Count</th>
                <th>Last sale date</th>
                <th>Store SKU</th>
                <th>Product Code</th>
                <th>Store</th>
                <th>Title</th>
                <th>Status</th>
                <th>Size</th>
                <th>Color</th>
                <th>Sync Status</th>
                <th>Gen Type</th>
                <th>Price</th>
                <th>Quantity</th>
                <th></th>
            </tr>
            </thead>
            <tbody id="product-list">

                @include('listing.product_list', ['products' => $products])

            </tbody>
        </table>
    </div>
    <div id="pagination">
        @include('listing.pagination', ['products' => $products])
    </div>
@stop

@section('adminlte_js')

<script>
    $(document).ready(function() {
        $('#last_sale_date,#item_created').datepicker();
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

        $('#open_store_page').on('click',function () {
            var store_id = $('#listing_store').val();

            location.href = "{{ url('stores/edit') }}/" + store_id;
        });

        $('#listing_store').on('change',function () {

            var perPage =  '{{ $perPage }}';
            var page =  1;
            $.ajax({
                url: "{{ route('listing.filter') }}?page=" + page + "&perPage=" + perPage,
                method: 'GET',
                data: {
                    store_id:$(this).val()
                },
                success: function(response) {
                    // Update the product list and pagination
                    $('#product-list').html(response.html);
                    $('#pagination').html(response.pagination);
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                }
            });

        });



        $('#filter-form').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            var perPage =  '{{ $perPage }}';
            var page =  1;
            $.ajax({
                url: "{{ route('listing.filter') }}?page=" + page + "&perPage=" + perPage,
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
            });
        });

        // Handle pagination links click
        $(document).on('click', '.pagination a', function(event) {
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            loadProductsWithPage(page);
        });

        function loadProductsWithPage(page) {
            var perPage = '{{ $perPage }}';;
            $.ajax({
                url: "{{ route('listing.filter') }}?page=" + page + "&perPage=" + perPage,
                data: $('#filter-form').serialize(),
                success: function(response) {
                    $('#product-list').html(response.html);
                    $('#pagination').html(response.pagination);
                }
            });
        }

        $(document).on('click', '.delete-btn', function() {
            var productId = $(this).data('id');
            var token = '{{ csrf_token() }}'; // CSRF token for security

            // Confirm the deletion
            if (confirm("Are you sure you want to delete this product?")) {
                $.ajax({
                    url: "{{ url('listing/destroy') }}/" + productId,   // Laravel route for deletion
                    type: 'DELETE',
                    data: {
                        "_token": token
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            $('#product-' + productId).remove();
                            alert(response.success);
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
</script>
@stop


