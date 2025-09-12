@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Listing')

@section('content_header')
    <h1>Listing</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-dropdown .select2-search__field:focus, .select2-container--default .select2-search--inline .select2-search__field:focus {
            outline: 0;
            border: none;
        }
        .select2-container{width:100% !important;}
        .select2-container .select2-selection--single{
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

        table th, table td, .column-no-whitespace{
            white-space: nowrap;
        }

        button#goDownBtn {
            height: 48px;
            width: 48px;
            float: right;
            position: fixed;
            margin-bottom: 12px;
            border-radius: 50px;
            box-shadow: 0 2px 9px 0 rgba(0, 0, 0, .16);
            right: 5px;
            text-align: center;
            cursor: pointer;
            opacity: .84;
            bottom: 0;
        }
    </style>
@stop

@section('content')

    <div class="card card-secondary">
        <div class="card-header bg-white">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a> </h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form">
                <input type="hidden" name="store_id" id="store_id">
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
                                <select class="form-control" id="main_product_type" name="main_product_type">
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
                                    <option value="">--</option>
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
                                <input type="textbox" class="form-control" name="store_sku" id="store_sku" value="">
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
                                <input type="text" name="sync_message" id="sync_message">
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
                                <input type="text" name="product_code" id="product_code" class="form-control" value="{{ $code }}">
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
                                    <option value="">--</option>
                                    @if(count($devices ?? []) > 0)
                                        @foreach($devices as $dvc)
                                            <option value="{{ $dvc->id }}">{{ $dvc->name }}</option>
                                        @endforeach
                                    @endif
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
                                    <option value=""></option>
                                    @if(count($brands ?? []) > 0)
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                        @endforeach
                                    @endif
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
                            <div class="col-6">
                                <select class="form-control" id="product_type" name="product_type">
                                    <option value="">--</option>
                                    @php
                                        renderCategories($product_types);
                                    @endphp
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
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="reset" class="form-control btn  btn-reset btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
    <div class="row my-3">
        {{--<div class="col-md-1">
            <a href="{{ route('listing.create') }}" class="btn btn-secondary">
                Create
            </a>
        </div>--}}
        <div class="col-md-2">
            <select class="form-control" id="listing_store" name="listing_store_id">
                <option value="">- All Store -</option>
                @if(count($stores ?? []) > 0)
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}{{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                    @endforeach
                @endif
            </select>

        </div>
        <div class="col-md-9 d-flex">
            <button type="button" class="btn btn-default" id="edit_list" disabled="">
                <i class="fas fa-fw fa-pencil-alt"></i> Edit
            </button>
            <button type="button" class="btn btn-default ml-2" id="end_listing" disabled>
                End Listing
            </button>
            <button type="button" class="btn btn-default ml-2" id="re_list" disabled="">
                Relist
            </button>
            <button type="button" class="btn btn-default ml-2" id="open_store_page">
                Open Store Page
            </button>
            <button type="button" class="btn btn-default ml-2" id="sync_inventory">
                Sync Inventory
            </button>
            <button type="button" class="btn btn-default ml-2" id="sync_images">
                Sync Images
            </button>
            <button type="button" class="btn btn-default ml-2" id="sync_price">
                Sync Price
            </button>
            <button type="button" class="btn btn-default ml-2" id="select_all">
                Select All
            </button>
            <button type="button" class="btn btn-default ml-2" id="select_none">
                Select None
            </button>
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
                <th>Upc</th>
                <th>Manufacturer</th>
                <th>Created Date</th>
                <th>Sync Count</th>
            </tr>
            </thead>
            <tbody id="product-list">
            </tbody>
        </table>
    </div>

    <!-- Add sync price modal -->
    <div class="modal fade" id="syncPriceModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="syncPriceModalLabel">Price Update Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="frmproductPrice">
                        <input type="hidden" name="id" id="product_channel_id">
                        <p>Are you sure you want to update inventory for the selected listing?</p>
                        <div class="mb-3">
                            <label for="sync_price_value" class="col-form-label">Price:</label>
                            <input type="text" class="form-control" id="sync_price_value" placeholder="Price">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                    <button type="button" class="btn btn-primary" id="syncListingPrice">OK <i class="fa fa-check"></i> </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add sync quantity modal -->
    <div class="modal fade" id="syncInventoryModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="syncInventoryModalLabel">Inventory Update Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="frmproductInventory">
                        <input type="hidden" name="id" id="product_channel_id">
                        <p>Are you sure you want to update inventory for the selected listing?</p>
                        <div class="mb-3">
                            <label for="sync_inventory" class="col-form-label">Quantity:</label>
                            <input type="text" class="form-control" id="sync_listing_inventory" placeholder="Quantity">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                    <button type="button" class="btn btn-primary" id="syncListingInventory">OK <i class="fa fa-check"></i> </button>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-secondary" id="goDownBtn"><i class="fa fa-arrow-circle-down"></i> </button>
@stop

@section('adminlte_js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $(document).ajaxStart(function () {
            $("#ajax-preloader").show(); // Show preloader when AJAX starts
        }).ajaxStop(function () {
            $("#ajax-preloader").hide(); // Hide preloader when AJAX stops
        });
        $('#device_brand, #device, #product_type, #listing_store').select2();
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

        document.getElementById("goDownBtn").addEventListener("click", function() {
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        });

        $('.btn-reset').on('click', function () {
            $('#device_brand').val(null).trigger('change');
            $('#device').val(null).trigger('change');
            $('#product_type').val(null).trigger('change');
        });

        $('#open_store_page').on('click',function () {
            let selected_list = [];
            $('input[name="listing_item[]"]:checked').each(function () {
                selected_list.push($(this).val())
            });

            if (selected_list.length === 0) {
                alert('Please select listing first.');
            } else if(selected_list.length > 1) {
                alert('Please select only one listing to open store page.');
            } else {
                let storeId = "tr[id=\"" + selected_list[0] + "\"] a.store_url";
                console.log(storeId)
                let href = $(storeId);
                console.log(href);
                if (!href) {
                    alert('Store URL not found for this listing.');
                    return;
                }
                window.open(href.attr('href'), '_blank');
            }
        });

        $('#select_all').on('click',function () {
            $('input[name="listing_item[]"]').each(function () {
                $(this).prop('checked',true);
                $(this).parents('tr').addClass('bg-blue');
            });
        });

        $('#select_none').on('click',function () {
            $('input[name="listing_item[]"]').each(function () {
                $(this).prop('checked',false);
                $(this).parents('tr').removeClass('bg-blue');
            });
        });
        let lastChecked = null;
        $(document).on('click','#product-list tr',function (e) {
            let ele = $(this);

            if($(this).find('.proList').is(':checked')){
                ele.removeClass('bg-blue');
                $(this).find('.proList').prop('checked',false);
            }else{
                ele.addClass('bg-blue');
                $(this).find('.proList').prop('checked',true);
            }
            $('#edit_list').removeAttr('disabled');
            $('#end_listing').removeAttr('disabled');
            $('#re_list').removeAttr('disabled');

            if (lastChecked && e.shiftKey) {
                e.preventDefault();
                let checkboxes = $(".proList");
                let start = checkboxes.index(ele.find('.proList'));
                let end = checkboxes.index(lastChecked);

                checkboxes.slice(Math.min(start, end), Math.max(start, end) + 1).prop("checked", lastChecked.checked).parents('tr').addClass('bg-blue');
            }
            lastChecked = ele.find('.proList');

           /*$('#product-list tr').removeClass('bg-blue');
           $('input[name="listing_item[]"]').prop('checked',false);
           ele.addClass('bg-blue');
           ele.find('input[name="listing_item[]"]').prop('checked',true);
           $('#edit_list').removeAttr('disabled');*/
        });

        $(document).on('mousedown','#product-list tr',function (e) {
            if (e.shiftKey) {
                // Prevent text selection
                e.preventDefault();
            }
        });

        $('#edit_list').on('click',function () {
            let flag = true;
            $('input[name="listing_item[]"]').each(function () {
                if($(this).is(':checked')){
                    flag = false;
                    let selectedId = $(this).val();
                    location.href = "{{ url('listing/edit') }}/" + selectedId;
                }
            });

        });

        $('#listing_store').on('change',function () {

            var perPage =  $('#per_page').val();
            var page =  1;
            $('#store_id').val($(this).val());
            loadDatatables();
            /*$.ajax({
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
            });*/

        });

        $(document).on('change', '#per_page', function(event) {
            $('#filter-form').submit();
        });

        $('#filter-form').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            var perPage =  $('#per_page').val();
            var page =  1;

            loadDatatables();
            /*$.ajax({
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
            });*/
        });

        /*// Handle pagination links click
        $(document).on('click', '.pagination a', function(event) {
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            loadProductsWithPage(page);
        });

        function loadProductsWithPage(page) {
            var perPage = $('#per_page').val();
            $.ajax({
                url: "{{ route('listing.filter') }}?page=" + page + "&perPage=" + perPage,
                data: $('#filter-form').serialize(),
                success: function(response) {
                    $('#product-list').html(response.html);
                    $('#pagination').html(response.pagination);
                }
            });
        }*/

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

        loadDatatables();

        $(document).on('click', '#sync_price', function() {
            let selected_list = [];
            let selected_product = [];
            var is_same_product = false;
            $('input[name="listing_item[]"]:checked').each(function () {
                var prod_code = $(this).data('code');
                if(selected_product.length !== 0 && $.inArray(prod_code, selected_product) == -1){
                    is_same_product = true;
                }
                selected_product.push(prod_code);
                selected_list.push($(this).val());
            });
            if (selected_list.length === 0) {
                alert('Please select listing first.');
            }else if (is_same_product) {
                alert('Please select same product for update price.');
            }else{
                $('#syncPriceModal').modal('show');
            }
        });

        $(document).on('click', '#syncListingPrice', function() {
            let selected_list = [];
            $('input[name="listing_item[]"]:checked').each(function () {
                selected_list.push($(this).val())
            });

            $.ajax({
                url: "{{ route('listing.synclistingprice') }}",
                type: 'POST',
                data: {
                    selected_list:selected_list,
                    price:$('#sync_price_value').val(),
                    "_token": '{{ csrf_token() }}'
                },
                success: function (response) {
                    // If success, remove the user row from the table
                    if (response.success) {
                        alert(response.message);
                        $('#syncPriceModal').modal('hide');
                        location.reload();
                    }
                },
                error: function (xhr) {
                    // Handle error response
                    alert('Error: ' + xhr.responseJSON.error);
                }
            });
        });

        $(document).on('click', '#sync_inventory', function() {
            let selected_list = [];
            let selected_product = [];
            var is_same_product = false;
            $('input[name="listing_item[]"]:checked').each(function () {
                var prod_code = $(this).data('code');
                if(selected_product.length !== 0 && $.inArray(prod_code, selected_product) == -1){
                    is_same_product = true;
                }
                selected_product.push(prod_code);
                selected_list.push($(this).val());
            });
            if (selected_list.length === 0) {
                alert('Please select listing first.');
            }else{
                $('#syncInventoryModal').modal('show');
            }
        });

        $(document).on('click', '#syncListingInventory', function() {
            let selected_list = [];
            $('input[name="listing_item[]"]:checked').each(function () {
                selected_list.push($(this).val())
            });

            $.ajax({
                url: "{{ route('listing.synclistinginventory') }}",
                type: 'POST',
                data: {
                    selected_list:selected_list,
                    inventory:$('#sync_listing_inventory').val(),
                    "_token": '{{ csrf_token() }}'
                },
                success: function (response) {
                    // If success, remove the user row from the table
                    if (response.success) {
                        alert(response.message);
                        $('#syncInventoryModal').modal('hide');
                        location.reload();
                    }
                },
                error: function (xhr) {
                    // Handle error response
                    alert('Error: ' + xhr.responseJSON.error);
                }
            });
        });

        $(document).on('click', '#end_listing', function() {
            let selected_list = [];
            let selected_sku = [];
            $('input[name="listing_item[]"]:checked').each(function () {
                selected_list.push($(this).val())
                selected_sku.push($(this).data('sku'));
            });

            if (confirm("Are you sure you want to end the listing?")) {
                $.ajax({
                    url: "{{ url('listing/endlist') }}",
                    type: 'POST',
                    data: {
                        selected_list:selected_list,
                        selected_sku:selected_sku,
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            alert('End listing successfully');
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



        $(document).on('click', '#re_list', function() {
            let selected_list = [];
            let selected_sku = [];
            $('input[name="listing_item[]"]:checked').each(function () {
                selected_list.push($(this).val());
                selected_sku.push($(this).data('sku'));
            });

            $.ajax({
                url: "{{ url('listing/relist') }}",
                type: 'POST',
                data: {
                    selected_list:selected_list,
                    selected_sku:selected_sku,
                    "_token": "{{ csrf_token() }}"
                },
                success: function (response) {
                    // If success, remove the user row from the table
                    if (response.success) {
                        alert('Re-list successfully');
                        location.reload();
                    }
                },
                error: function (xhr) {
                    // Handle error response
                    alert('Error: ' + xhr.responseJSON.error);
                }
            });
        });


    });

    function loadDatatables(){

        $("#productsTable").dataTable().fnDestroy();

        var table = $('#productsTable').DataTable({
            "iDisplayLength": 10,
            "info": true,
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "searching": false,
            "aLengthMenu": [10, 20, 25, 50, 100],
            'ajax': {
                url: "{{ route('listing.filter') }}",
                type: "POST",
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

                    d['listing_status'] = 1;

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
            "order": [[ 16, "desc" ]],
            'columns': [
                { data: 'sku'},
                { data: 'sales_count' },
                { data: 'last_sale_date' },
                { data: 'store_sku' },
                { data: 'product_sku' },
                { data: 'store_name' },
                { data: 'title', render: function(data, type, row) {
                    if (row.title_url) {
                        return '<a href="' + row.title_url + '" class="store_url" target="_blank">' + data + '</a>';
                    } else {
                        return data;
                    }
                }},
                { data: 'status' },
                { data: 'product_size' },
                { data: 'product_color' },
                { data: 'sync_status' },
                { data: 'generation_type_name' },
                { data: 'price' },
                { data: 'qty' },
                { data: 'upc' },
                { data: 'manufacturer' },
                { data: 'created_at' },
                { data: 'sync_count' }
            ],
            columnDefs: [
                {
                    targets: 6, // The column index
                    className: 'column-no-whitespace' // The class to add
                }
            ],
            "createdRow": function(row, data, dataIndex) {
                $(row).attr('id', data.listing_id);
                $(row).addClass('listing-item');
            },
        });

        $( '#productsTable_wrapper').removeClass( 'form-inline' );

        $.fn.dataTable.ext.errMode = 'throw';
    }
</script>
@stop


