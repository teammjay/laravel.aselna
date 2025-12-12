@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($device_series) ? 'Edit Device Series' : 'Add Device Series' }}</h1>
@stop

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <form action="{{ isset($device_series) ? route('device_series.update',['id' => $device_series['id']]) : route('device_series.store') }}" method="POST">
    @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input @error('code') is-invalid @enderror"
                                   @if((isset($device_series) && $device_series->status === 'active') || old('status') == 'on' || !isset($device_series)) checked @endif name="status" id="status">
                            <label class="form-check-label" for="status">Status</label>
                            @error('status')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Device Series Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Device Series Name" value="{{ old('name' ,$device_series['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    {{--<div class="col-md-6">
                        <div class="form-group">
                            <label for="slug">Device Series slug</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug"
                                   name="slug"
                                   placeholder="Enter Device series slug" value="{{ old('slug',$device_series['slug'] ?? '') }}">
                            @if ($errors->has('slug'))
                                <span class="text-danger">{{ $errors->first('slug') }}</span>
                            @endif
                        </div>
                    </div>--}}
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                Devices
                            </div>
                            <div class="card-body">
                                <select class="form-control pro-devices">
                                    @if(count($devices ?? []) > 0)
                                        <option value=""></option>
                                        @foreach($devices as $device)
                                            <option value="{{ $device->id }}"
                                                    data-brand="{{ $device->brand->name ?? '' }}"
                                                    data-type="{{ $device->deviceType->name ?? '' }}">
                                                {{ $device->name }}{{ $device->brand ? ' (' . $device->brand->name . ')' : '' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <table class="table table-bordered table-hover mt-5" id="table_device">
                                    <thead>
                                    <tr>
                                        <th>Device</th>
                                        <th>Brand</th>
                                        <th>Device Type</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody id="device-list">
                                    @if(isset($deviceLinks) && count($devices ?? []) > 0)
                                        @foreach($devices as $device)
                                            @if(in_array($device->id, $deviceLinks))
                                                <tr class="field-group">
                                                    <td>{{ $device->name }}<input type="hidden" name="devices[]"
                                                                                  value="{{ $device->id }}"></td>
                                                    <td>{{ $device->brand->name ?? '' }}</td>
                                                    <td>{{ $device->deviceType->name ?? '' }}</td>
                                                    <td>
                                                        <button type="button"
                                                                class="btn btn-danger remove-device p-1"><i
                                                                class="fas fa-fw fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($device_series))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.pro-devices').select2({
            placeholder: "Select Devices",
            initSelection: function(element, callback) {
            }
        });

        // Add device to the list when selected from dropdown
        $('.pro-devices').on('change', function() {
            const deviceId = $(this).val();
            if (!deviceId) return;

            const deviceName = $(this).find('option:selected').text();
            const deviceBrand = $(this).find('option:selected').data('brand');
            const deviceType = $(this).find('option:selected').data('type');

            // Check if device already exists in the table
            const exists = $('#device-list').find('input[value="' + deviceId + '"]').length > 0;
            if (exists) {
                alert('This device is already added to the series.');
                $(this).val('');
                return;
            }

            // Add device to the table
            $('#device-list').append(`
                <tr class="field-group">
                    <td>${deviceName}<input type="hidden" name="devices[]" value="${deviceId}"></td>
                    <td>${deviceBrand}</td>
                    <td>${deviceType}</td>
                    <td>
                        <button type="button" class="btn btn-danger remove-device p-1">
                            <i class="fas fa-fw fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);

            // Clear dropdown selection
            $(this).val('');
        });

        // Remove device from the list
        $(document).on('click', '.remove-device', function() {
            $(this).closest('tr').remove();
        });
    });
</script>
@stop
