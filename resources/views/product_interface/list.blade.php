@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Interface')

@section('content_header')
    <h1>Product Devices</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('product_interface.create') }}" class="btn btn-primary">
                Add Product Device
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Filters</h3>
        </div>
        {{--        Add search url to this form and pass as post data to search--}}
        <form id="filter-form">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="row align-items-center">
                            <div class="col-3">
                                Product Name
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="product_name_operator">
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
                                <input type="text" name="product_name" id="product_name" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="row align-items-center">
                            <div class="col-3">
                                Interface Name
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="interface_name_operator">
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
                                <input type="text" name="interface_name" id="interface_name" class="form-control">
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
            </div>
        </form>
    </div>
    <table id="productInterfaceTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Product Name</th>
            <th>Interface Name</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
{{--        @foreach($productInterfaces as $productInterface)--}}
{{--            <tr>--}}
{{--                <td>{{$productInterface->product->product_title}}</td>--}}
{{--                <td>{{$productInterface->interfaces->name}}</td>--}}
{{--                <td>--}}
{{--                    <a href="{{ route('product_interface.edit', $productInterface->id) }}" class="btn btn-primary">--}}
{{--                        Edit--}}
{{--                    </a>--}}
{{--                    <a href="{{ route('product_interface.delete', $productInterface->id) }}" class="btn btn-danger">--}}
{{--                        Delete--}}
{{--                    </a>--}}
{{--                </td>--}}
{{--            </tr>--}}
{{--        @endforeach--}}
        </tbody>
    </table>
@stop

@section('js')
    <script>
        var productInterfaceTable = null;
        $(document).ready(function () {

            document.getElementById("resetForm").addEventListener("click", function () {
                sessionStorage.removeItem("product_interface_filters");
            });

            productInterfaceTable = $('#productInterfaceTable').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
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
                    url: '{{ route('product-interface-data') }}',
                    data: function (d) {
                        var formData = $('#filter-form').serializeArray();
                        $.each(formData, function (index, field) {
                            d[field.name] = field.value;
                        });

                        sessionStorage.setItem("product_interface_filters", JSON.stringify(formData));
                    }
                },
                columns: [
                    {
                        data: null, name: 'product_name',
                        render: function (data, type, row) {
                            if (row.product && row.product.product_title) {
                                return row.product.product_title;
                            }
                            return '';
                        }
                    },
                    {
                        data: null, name: 'interface_name',
                        render: function (data, type, row) {
                            return row.interfaces.name;
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return '<a href="{{ route('product_interface.edit', ':id') }}" class="btn btn-primary">Edit</a>'.replace(':id', row.id) +
                                '<a href="{{ route('product_interface.delete', ':id') }}" class="btn btn-danger">Delete</a>'.replace(':id', row.id);
                        }
                    }
                ]
            });

            $('#apply_filter').on('click', function (event) {
                event.preventDefault();
                productInterfaceTable.ajax.reload();
            });

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
        });

        document.addEventListener("DOMContentLoaded", function () {
            let savedFilters = JSON.parse(sessionStorage.getItem("product_interface_filters"));
            if (savedFilters) {
                console.log(savedFilters);
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
@stop
