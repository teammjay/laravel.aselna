@extends('adminlte::page')

@section('title', 'Device')

@section('content_header')
    <h1>Device </h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('devices.create') }}" class="btn btn-primary">
                Add Device
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
                                        <select class="custom-select" name="name_operator">
                                            <option value="=">=</option>
                                            <option value="contains">contains</option>
                                            <option value="starts_with">starts with</option>
                                            <option value="ends_with">ends with</option>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <input type="text" name="name" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Brand Name -->
                            <div class="col-4 mb-3">
                                <div class="row align-items-center">
                                    <div class="col-4">
                                        Brand Name
                                    </div>
                                    <div class="col-3">
                                        <select class="custom-select" name="brand_name_operator">
                                            <option value="=">=</option>
                                            <option value="contains">contains</option>
                                            <option value="starts_with">starts with</option>
                                            <option value="ends_with">ends with</option>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <input type="text" name="brand_name" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Device Type -->
                            <div class="col-4 mb-3">
                                <div class="row align-items-center">
                                    <div class="col-4">
                                        Device Type
                                    </div>
                                    <div class="col-3">
                                        <select class="custom-select" name="device_type_operator">
                                            <option value="=">=</option>
                                            <option value="contains">contains</option>
                                            <option value="starts_with">starts with</option>
                                            <option value="ends_with">ends with</option>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <input type="text" name="device_type" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-4 mb-3">
                                <div class="row align-items-center">
                                    <div class="col-4">
                                        Status
                                    </div>
                                    <div class="col-3">
                                        <select class="custom-select" name="status_operator">
                                            <option value="=">=</option>
                                            <option value="contains">contains</option>
                                            <option value="starts_with">starts with</option>
                                            <option value="ends_with">ends with</option>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <input type="text" name="status" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Created At -->
                            <div class="col-4 mb-3">
                                <div class="row align-items-center">
                                    <div class="col-4">
                                        Created At
                                    </div>
                                    <div class="col-3">
                                        <select class="custom-select" name="created_at_operator">
                                            <option value="=">=</option>
                                            <option value="gt">&gt;</option>
                                            <option value="lt">&lt;</option>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <input type="date" name="created_at" class="form-control">
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
    <table id="deviceTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Image</th>
            <th>Device Name</th>
            <th>Brand Name</th>
            <th>Device Type</th>
            <th>Status</th>
            <th>Last modified by</th>
            <th>Last modified at</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        {{--        @foreach($devices as $device)--}}
        {{--            <tr>--}}
        {{--                <td>--}}
        {{--                    <img src="{{ asset('storage/device_images/'.$device->image) }}" alt="{{ $device->name }}"--}}
        {{--                         width="50">--}}
        {{--                </td>--}}
        {{--                <td>{{$device->name}}</td>--}}
        {{--                <td>{{ isset($device->brand) ? $device->brand->name : '' }}</td>--}}
        {{--                <td>{{ isset($device->deviceType) ? $device->deviceType->name : '' }}</td>--}}
        {{--                <td>{{ ucfirst($device->status) }}</td>--}}
        {{--                <td>{{ $device->updated_by }}</td>--}}
        {{--                <td>{{ $device->updated_at->format('d M Y g:i A') }}</td>--}}
        {{--                <td>--}}
        {{--                    <a href="{{ route('devices.edit', ['id' => $device->id]) }}" class="btn btn-primary">Edit</a>--}}
        {{--                    <a href="{{ route('devices.delete', ['id' => $device->id]) }}" class="btn btn-danger">Delete</a>--}}
        {{--                </td>--}}
        {{--            </tr>--}}
        {{--        @endforeach--}}
        </tbody>
    </table>

    <!-- Device Details Modal -->
    <div class="modal fade" id="deviceDetailsModal" tabindex="-1" role="dialog" aria-labelledby="deviceDetailsModalLabel">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h4 class="modal-title text-white" id="deviceDetailsModalLabel">Device Details</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="deviceDetailsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">General</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="carrier-tab" data-toggle="tab" href="#carrier" role="tab">Carrier</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="interfaces-tab" data-toggle="tab" href="#interfaces" role="tab">Interfaces</a>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="deviceDetailsTabsContent">
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="row" id="general-content">
                                <!-- General details will be loaded here -->
                            </div>
                        </div>
                        <div class="tab-pane fade" id="carrier" role="tabpanel">
                            <table class="table table-bordered" id="carrier-table">
                                <thead>
                                    <tr>
                                        <th>Carrier Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Carrier data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="interfaces" role="tabpanel">
                            <table class="table table-bordered" id="interfaces-table">
                                <thead>
                                    <tr>
                                        <th>Interface Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Interfaces data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        var deviceTable = null;
        $(document).ready(function () {
            deviceTable = $('#deviceTable').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: '{{ route('device-data') }}',
                    data: function (d) {
                        var formData = $('#filter-form').serializeArray();
                        $.each(formData, function (index, field) {
                            d[field.name] = field.value;
                        });
                    }
                },
                "order": [[ 6, "desc" ]],
                columns: [
                    {
                        data: '', name: 'image', render: function (data, type, row) {
                            return '<img src="{{ asset('storage/device_images/') }}/' + row.image + '" alt="' + row.name + '" width="50">';
                        }
                    },
                    {data: 'name', name: 'name'},
                    {data: 'brand.name', name: 'brand.name'},
                    {data: 'device_type.name', name: 'device_type.name'},
                    {data: 'status', name: 'status'},
                    {data: 'updated_by', name: 'updated_by'},
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data) {
                            return moment(data).format('MM/DD/YYYY HH:mm:ss');
                        }
                    },
                    {
                        data: 'action', name: 'action', orderable: false, searchable: false,
                        render: function (data, type, row) {
                            return '<a href="{{ route('devices.edit', ['id' => ':id']) }}'.replace(':id','') + row.id + '" class="btn btn-primary">Edit</a>' +
                                '<a href="{{ route('devices.delete', ['id' => ':id']) }}'.replace(':id','') + row.id + '" class="btn btn-danger">Delete</a>';
                        }
                    }
                ],
                createdRow: function(row, data, dataIndex) {
                    $(row).data('device-id', data.id);
                }
            });

            // Device Details Modal Handler
        $('body').on('click', '#deviceTable tbody tr', function(e) {

            if ($(e.target).is('a, img')) {
                return;
            }
            const deviceId = $(this).data('device-id');
            $('#deviceDetailsModal').modal('show');

            // Load general details immediately
            $.get('devices/'+deviceId+'/details', function(data) {
                $('#general-content').html(`
                    <div class="col-md-4 mb-3">
                        <label>Device Type:</label>
                        <p>${data.device_type?.name || ''}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Sub Type:</label>
                        <p>${data.device_sub_type?.name || ''}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Brand:</label>
                        <p>${data.brand?.name || ''}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Device Name:</label>
                        <p>${data.name || ''}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Status:</label>
                        <p>${data.status}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Rating:</label>
                        <p>${data.rating?.name || ''}</p>
                    </div>
                `);
            });

            // Handle tab changes
            $('#deviceDetailsTabs a').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('href');

                if(target === '#carrier' && !$('#carrier-table').data('loaded')) {
                    $.get('devices/'+deviceId+'/carriers', function(data) {
                        $('#carrier-table tbody').html(
                            data.map(carr => `
                                <tr>
                                    <td>${carr.carrier.name}</td>
                                </tr>
                            `).join('')
                        );
                        $('#carrier-table').data('loaded', true);
                    });
                }

                if(target === '#interfaces') {
                    $.get('devices/'+deviceId+'/interfaces', function(data) {

                        $('#interfaces-table tbody').html(
                            data.map(intf => console.log(intf)+ `
                                <tr>
                                    <td>${intf.interfaces?.name}</td>
                                    <td>${intf.interfaces.product_type?.name}</td>
                                </tr>
                            `).join('')
                        );
                        $('#interfaces-table').data('loaded', true);
                    });
                }
            });
        });

            $('#apply_filter').on('click', function (event) {
                event.preventDefault();
                deviceTable.ajax.reload();
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
@stop
