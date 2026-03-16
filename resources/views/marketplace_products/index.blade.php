@php use App\Constants\FilterCodes; @endphp

@extends('adminlte::page')

@section('title', 'Marketplace Products')

@section('content_header')
    <h1>Marketplace Products</h1>
@stop

@section('adminlte_css')
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
    </style>
@endsection

@section('content')
    <div class="card card-secondary">
        <div class="card-header bg-white">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
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
    <div class="row my-3">
        <div class="col-md-2">
            <select class="form-control" id="listing_store" name="listing_store_id">
                <option value="">- All Store -</option>
                @if(count($stores ?? []) > 0)
                    @foreach($stores as $store)
                        <option
                            value="{{ $store->id }}">{{ $store->name }}{{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-md-9 d-flex">
        </div>
    </div>
    <div class="table-responsive">
        <table id="productsTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Sku</th>
                <th>Code</th>
                <th>Upload Status</th>
                <th>Store</th>
                <th>Created At</th>
            </tr>
            </thead>
            <tbody id="product-list">
            </tbody>
        </table>
    </div>
@endsection


@section('adminlte_js')
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

            $(' #listing_store').select2();

            var productsTable = $('#productsTable').DataTable({
                "dom": '<"datatable-top"lfip>rt<"datatable-bottom"ip>',
                processing: true,
                serverSide: true,
                stateSave:true,
                stateSaveCallback: function(settings, data) {
                    sessionStorage.setItem(
                        'DataTables_' + settings.sInstance,
                        JSON.stringify(data)
                    );
                },

                stateLoadCallback: function(settings) {
                    return JSON.parse(
                        sessionStorage.getItem('DataTables_' + settings.sInstance)
                    );
                },
                ajax: {
                    url: '{{ route('marketplace_product.filter') }}',
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    data: function (d) {
                        d.store_id = $('#listing_store').val();

                        var formData = $('#filter-form').serializeArray();
                        $.each(formData, function (i, field) {
                            d[field.name] = field.value;
                        });

                        sessionStorage.setItem("maketplace_product_filters", JSON.stringify(formData));
                    }
                },
                columns: [
                    {data: 'sku', name: 'sku'},
                    {data: 'code', name: 'code', render: function (data, type, row) {
                        return `<a href="catalog/edit/${row.product_id}" target="_blank">${data}</a>`;
                    }},
                    {data: 'upload_status', name: 'upload_status'},
                    {data: 'store_name', name: 'store_name', orderable: false},
                    {data: 'created_at', name: 'created_at'},
                ]
            });


            $('#filter-form').on('submit', function (e) {
                e.preventDefault();
                productsTable.ajax.reload();
            });

            $('#listing_store').on('change', function () {
                productsTable.ajax.reload();
            });

            document.getElementById("resetForm").addEventListener("click", function () {
                sessionStorage.removeItem("maketplace_product_filters");
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            let savedFilters = JSON.parse(sessionStorage.getItem("maketplace_product_filters"));
            if (savedFilters) {
                Object.keys(savedFilters).forEach(function (key) {

                    //console.log(savedFilters[key]);
                    let input = $('#filter-form [name="'+savedFilters[key].name+'"]');
                    if (input) {
                        input.val(savedFilters[key].value)
                    }
                });
            }
        });

    </script>
@endsection
