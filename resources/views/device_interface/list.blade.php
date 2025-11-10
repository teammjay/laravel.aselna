@extends('adminlte::page')

@section('title', 'Interface')

@section('content_header')
    <h1>Interface Devices</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('device_interface.create') }}" class="btn btn-primary">
                Add Interface Device
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-header bg-white">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <form id="filter-form">
                        <div class="row">
                            <!-- Device Name -->
                            <div class="col-4 mb-3">
                                <div class="row align-items-center">
                                    <div class="col-4">
                                        Device Name
                                    </div>
                                    <div class="col-3">
                                        <select class="custom-select" name="device_name_operator">
                                            <option value="=">=</option>
                                            <option value="contains">contains</option>
                                            <option value="starts_with">starts with</option>
                                            <option value="ends_with">ends with</option>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <input type="text" name="device_name" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Brand Name -->
                            <div class="col-4 mb-3">
                                <div class="row align-items-center">
                                    <div class="col-4">
                                        Interface Name
                                    </div>
                                    <div class="col-3">
                                        <select class="custom-select" name="interface_name_operator">
                                            <option value="=">=</option>
                                            <option value="contains">contains</option>
                                            <option value="starts_with">starts with</option>
                                            <option value="ends_with">ends with</option>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <input type="text" name="interface_name" class="form-control">
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
        </div>
    </div>
    <table id="deviceInterfaceTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Device Name</th>
            <th>Interface Name</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
@Stop

@section('js')
    <script>
        var deviceInterfaceTable = null;
        $(document).ready(function () {
            deviceInterfaceTable = $('#deviceInterfaceTable').DataTable(
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
                        url: '{{ route('device-interface-data') }}',
                        data: function (d) {
                            var formData = $('#filter-form').serializeArray();
                            $.each(formData, function (index, field) {
                                d[field.name] = field.value;
                            });
                        }
                    },
                    columns: [
                        {
                            data: null, name: 'device_name',
                            render: function (data, type, row) {
                                return row.device.name;
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
                                return '<a href="{{ route('device_interface.edit', ':id') }}" class="btn btn-primary">Edit</a>'.replace(':id', row.id) +
                                    '<a href="{{ route('device_interface.delete', ':id') }}" class="btn btn-danger">Delete</a>'.replace(':id', row.id);
                            }
                        },
                    ]
                }
            );

            $('#apply_filter').on('click', function (event) {
                event.preventDefault();
                deviceInterfaceTable.ajax.reload();
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
    </script>
@Stop
