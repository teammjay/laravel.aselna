@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>UPC Generate</h1>
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
    <div class="row my-3">
        <div class="col-md-3">
            <div class="btn btn-primary" id="createUpcModalButton" data-toggle="modal" data-target="#createUpcModal">
                create UPC
            </div>
        </div>
    </div>
    <div class="card card-secondary">
        <div class="card-header bg-white">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form">
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-3 text-right">
                                Store
                            </div>
                            <div class="col-9">
                                <select class="form-control" id="store_id" name="store_id">
                                    <option value="">Select store</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->name }}
                                            - {{ $store->storeType ? $store->storeType->name : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-3 text-right">
                                Company Prefix
                            </div>
                            <div class="col-3">
                                <select class="custom-select" name="prefix_operator">
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
                                <input type="text" name="prefix" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-3 text-right">
                                Start Product Number
                            </div>
                            <div class="col-3">
                                <select class="custom-select" name="product_number_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::GREATER_THAN}}">></option>
                                    <option value="{{FilterCodes::LESS_THAN}}"><</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="number" name="product_number" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-3 text-right">
                                Number of Codes
                            </div>
                            <div class="col-3">
                                <select class="custom-select" name="number_of_codes_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::GREATER_THAN}}">></option>
                                    <option value="{{FilterCodes::LESS_THAN}}"><</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="number" name="number_of_codes" class="form-control">
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
                            <button type="reset" id="resetForm" class="form-control btn btn-reset btn-secondary">Reset</button>
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
            <th>Start Product Number</th>
            <th>Number of Codes</th>
            <th>Stores</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    <div>
        <div class="modal fade" id="createUpcModal" tabindex="-1" role="dialog" aria-labelledby="createUpcModalLabel"
             aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="{{ route('upc_codes.generate') }}" id="generateUpcCode" method="post">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="createUpcModalLabel">Create UPC</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="p-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="store">Store</label>
                                        <select class="form-control @error('store') is-invalid @enderror"
                                                id="store"
                                                name="store" value="{{ old('store') }}">
                                            <option value="">Select Store</option>
                                            @foreach($stores as $store)
                                                <option value="{{ $store->id }}">{{ $store->name }} - {{ $store->storeType ? $store->storeType->name : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="quantity">Company Prefix</label>
                                        <input type="text"
                                               class="form-control @error('company_prefix') is-invalid @enderror"
                                               id="company_prefix"
                                               name="company_prefix"
                                               placeholder="Enter Company Prefix" value="{{ old('company_prefix') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="quantity">Start product number</label>
                                        <input type="text"
                                               class="form-control @error('product_number') is-invalid @enderror"
                                               id="product_number"
                                               name="product_number"
                                               placeholder="Enter product number" value="{{ old('product_number') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="quantity">Number of codes</label>
                                        <input type="text"
                                               class="form-control @error('number_of_codes') is-invalid @enderror"
                                               id="number_of_codes"
                                               name="number_of_codes"
                                               placeholder="Enter number of codes" value="{{ old('number_of_codes') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#store_id').select2({placeholder: 'Select store', allowClear: true});
            $('#store').select2({placeholder: 'Select Store', dropdownParent: $('#createUpcModal')});

            // put the last search back before the table makes its first request
            let savedFilters = JSON.parse(sessionStorage.getItem("upc_generate_filters") || 'null');
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

            var table = $('#upcTable').DataTable({
                dom: '<"datatable-top"lip>rt<"datatable-bottom"ip>',
                processing: true,
                serverSide: true,
                searching: false,
                stateSave: true,
                lengthMenu: [10, 20, 25, 50, 100],
                order: [[0, 'asc']],
                ajax: {
                    url: "{{ route('upc_codes.generate_data') }}",
                    data: function (d) {
                        var formData = $('#filter-form').serializeArray();
                        $.each(formData, function (index, field) {
                            d[field.name] = field.value;
                        });
                        sessionStorage.setItem("upc_generate_filters", JSON.stringify(formData));
                    }
                },
                columns: [
                    {data: 'company_prefix', name: 'company_prefix'},
                    {data: 'product_number', name: 'product_number'},
                    {data: 'number_of_codes', name: 'number_of_codes'},
                    {
                        data: 'store', name: 'store',
                        render: function (data, type, row) {
                            if (!row.store_data) {
                                return '';
                            }
                            return $('<div>').text(row.store_data.name + ' - ' + (row.store_data.store_type ? row.store_data.store_type.name : '')).html();
                        }
                    }
                ]
            });

            // Filter button and Enter in any field: search from the first page
            $('#filter-form').on('submit', function (e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // clear the fields and show everything again
            $('#resetForm').on('click', function (e) {
                e.preventDefault();
                $('#filter-form')[0].reset();
                $('#store_id').trigger('change.select2');
                sessionStorage.removeItem("upc_generate_filters");
                table.ajax.reload();
            });

            $('#generateUpcCode').on('submit', function (e) {
                e.preventDefault();
                var form = $(this);
                var data = form.serialize();
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: data,
                    success: function (response) {
                        console.log(response)
                        alert(response.message);
                        $('#createUpcModal').modal('hide');
                        location.reload();
                    },
                    error: function (xhr, status, error) {
                        console.error('Error generating UPC code:', error);
                        var errors = xhr.responseJSON && xhr.responseJSON.errors;
                        alert(errors ? Object.values(errors).flat().join("\n") : 'Error generating UPC code: ' + error);
                    }
                });
            });
        });
    </script>
@stop
