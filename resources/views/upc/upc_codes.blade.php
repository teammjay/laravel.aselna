@php use App\Constants\FeedStatus;use App\Constants\FeedTypes; use App\Constants\FilterCodes @endphp
@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>UPC codes</h1>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.25rem);
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: auto;
            position: absolute;
            top: 50%;
            right: 1px;
            width: 20px;
            transform: translateY(-50%);
        }
    </style>
@stop

@section('content')
<div class="card card-secondary">
        <div class="card-header bg-white">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form">
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Store
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="store_id" name="store_id">
                                    <option value="">Select store</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" id="{{ $store->id }}"
                                                data-store-name="{{ $store->name }} - {{ $store->storeType ? $store->storeType->name : '' }}">{{ $store->name }}
                                            - {{ $store->storeType ? $store->storeType->name : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right ">
                                Prefix
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="prefix_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="prefix" id="prefix" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Used Status
                            </div>
                            <div class="col-9">
                                <select class="custom-select" name="is_used">
                                    <option value="">All</option>
                                    <option value="1">Used</option>
                                    <option value="0">Not Used</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Product Number
                            </div>
                            <div class="col-3">
                                <select class="custom-select" name="product_number_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="product_number" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                UPC Code
                            </div>
                            <div class="col-3">
                                <select class="custom-select" name="upc_code_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="upc_code" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Sequence ID
                            </div>
                            <div class="col-3">
                                <select class="custom-select" name="sequence_id_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::GREATER_THAN}}">></option>
                                    <option value="{{FilterCodes::LESS_THAN}}"><</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="sequence_id" class="form-control">
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
                            <button type="reset" id="resetForm" class="form-control btn  btn-reset btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <table id="upcTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Company prefix</th>
            <th>Product number</th>
            <th>UPC</th>
            <th>used</th>
            <th>Sequence ID</th>
            <th>Store</th>
        </tr>
        </thead>
        <tbody>
{{--        @foreach($upcCodes as $upcCode)--}}
{{--            <tr>--}}
{{--                <td>{{$upcCode->company_prefix}}</td>--}}
{{--                <td>{{$upcCode->product_number}}</td>--}}
{{--                <td>{{$upcCode->upc_code}}</td>--}}
{{--                <td>{{$upcCode->is_used ? 'Used' : 'Not Used'}}</td>--}}
{{--                <td>{{$upcCode->sequence_id}}</td>--}}
{{--                <td>{{$upcCode->storeData->name}}</td>--}}
{{--            </tr>--}}
{{--        @endforeach--}}
        </tbody>
    </table>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        var table;
        $(document).ready(function() {
            $('#store_id').select2({placeholder: 'Select store', allowClear: true});

            // put the last search back before the table makes its first request
            let savedFilters = JSON.parse(sessionStorage.getItem("upc_code_filters") || 'null');
            if (savedFilters) {
                savedFilters.forEach(function (field) {
                    $('#filter-form [name="' + field.name + '"]').val(field.value);
                });
                $('#store_id').trigger('change.select2');
            }

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

            table = $('#upcTable').DataTable(
                {
                    dom: '<"datatable-top"lip>rt<"datatable-bottom"ip>',
                    processing: true,
                    serverSide: true,
                    searching: false,
                    stateSave: true,
                    lengthMenu: [10, 20, 25, 50, 100],
                    order: [[0, 'asc']],
                    ajax: {
                        url: "{{ route('upc-code-data') }}",
                        data: function (d) {
                            var formData = $('#filter-form').serializeArray(); // Serialize form inputs

                            // Add form data to the DataTable's request payload
                            $.each(formData, function (index, field) {
                                d[field.name] = field.value; // Append form data to the DataTables request
                            });

                            sessionStorage.setItem("upc_code_filters", JSON.stringify(formData));
                        }
                    },
                    columns: [
                        {
                            data: 'company_prefix',
                            name: 'company_prefix'
                        },
                        {
                            data: 'product_number',
                            name: 'product_number'
                        },
                        {
                            data: 'upc_code',
                            name: 'upc_code'
                        },
                        {
                            data: 'is_used',
                            name: 'is_used'
                        },
                        {
                            data: 'sequence_id',
                            name: 'sequence_id'
                        },
                        {
                            // not sortable: ordering ~700k codes by store name sorts the whole table, use the Store filter
                            data: null, name: 'store', orderable: false,
                            render: function (data, type, row) {
                                if (!row.store_data) {
                                    return '';
                                }
                                return $('<div>').text(row.store_data.name + " " + (row.store_data.store_type ? row.store_data.store_type.name : '')).html();
                            }
                        }
                    ]
                }
            );

            // Filter button and Enter in any field: search from the first page
            $('#filter-form').on('submit', function (event) {
                event.preventDefault();
                table.ajax.reload();
            });

            // clear the fields and show everything again
            $('#resetForm').on('click', function (event) {
                event.preventDefault();
                $('#filter-form')[0].reset();
                $('#store_id').trigger('change.select2');
                sessionStorage.removeItem("upc_code_filters");
                table.ajax.reload();
            });

        } );

    </script>
@stop
