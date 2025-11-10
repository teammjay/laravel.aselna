@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Interface')

@section('content_header')
    <h1>Interface</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('interface.create') }}" class="btn btn-primary">
                Add Interface
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
                        <div class="row align-items-center">
                            <div class="col-3">
                                Product Type
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="product_type_operator">
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
                                <input type="text" name="product_type" id="product_type" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="row align-items-center">
                            <div class="col-3 text-right">
                                Code
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="code_operator">
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
                                <input type="text" name="code" id="code" class="form-control">
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
    <table id="interfaceTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Product Type</th>
            <th>Last modified by</th>
            <th>Last modified at</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        {{--        @foreach($interfaces as $interface)--}}
        {{--            <tr>--}}
        {{--                <td>{{ $interface->name }}</td>--}}
        {{--                <td>{{ $interface->code }}</td>--}}
        {{--                <td>{{ $interface->ProductType->name }}</td>--}}
        {{--                <td>{{ $interface->updated_by }}</td>--}}
        {{--                <td>{{ $interface->updated_at->format('d M Y g:i A') }}</td>--}}
        {{--                <td>--}}
        {{--                    <a href="{{ route('interface.edit', $interface->id) }}" class="btn btn-primary">--}}
        {{--                        Edit--}}
        {{--                    </a>--}}
        {{--                    <a href="{{ route('interface.delete', $interface->id) }}" class="btn btn-danger">--}}
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
        var interfacetable = null;
        $(document).ready(function () {
            // add ajax url to this datatable
            // the last table is not a data it should be generated by the action buttons
            interfacetable = $('#interfaceTable').DataTable(
                {
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
                        url: '{{ route('interface-data') }}',
                        data: function (d) {
                            var formData = $('#filter-form').serializeArray();
                            $.each(formData, function (index, field) {
                                d[field.name] = field.value;
                            });

                            sessionStorage.setItem("interface_filters", JSON.stringify(formData));

                        }
                    },
                    "columns": [
                        {"data": "name"},
                        {"data": "code"},
                        {
                            "data": null,
                            name : 'product_type',
                            render: function (data, type, row) {
                                return row.product_type.name;
                            }
                        },
                        {"data": "updated_by"},
                        {
                            "data": "updated_at",
                            render: function (data) {
                                return moment(data).format('YYYY-MM-DD HH:mm:ss');
                            }
                        },
                        {
                            "data": "action",
                            "orderable": false,
                            "searchable": false,
                            "name": "action",
                            "title": "Action",
                            render: function (data,type,row) {
                                return '<a href="{{ route('interface.edit', '') }}/' + row.id + '" class="btn btn-primary">Edit</a> ' +
                                    '<a href="{{ route('interface.delete', '') }}/' + row.id + '" class="btn btn-danger">Delete</a>';
                            }
                        },
                    ],
                }
            );

            document.getElementById("resetForm").addEventListener("click", function () {
                sessionStorage.removeItem("interface_filters");
            });

            $('#apply_filter').on('click', function (event) {
                event.preventDefault();
                interfacetable.ajax.reload();
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
            let savedFilters = JSON.parse(sessionStorage.getItem("interface_filters"));
            if (savedFilters) {
                console.log(savedFilters);
                Object.keys(savedFilters).forEach(function (key) {

                    //console.log(savedFilters[key]);
                    let input = $('#filter-form [name="'+savedFilters[key].name+'"]');
                    if (input) {
                        input.val(savedFilters[key].value)
                    }


                });

                loadDatatables();
            }
        });
    </script>
@stop

