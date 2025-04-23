@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($shipping_package) ? 'Edit Shipping Package' : 'Add Shipping Package' }}</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
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
        .ui-sortable tr:hover{
            cursor:move;
        }

        .ui-sortable-placeholder{
            background:#ccc !important;
            visibility: initial !important;
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
    <form
        action="{{ isset($shipping_package) ? route('shipping_package.update',['id' => $shipping_package['id']]) : route('shipping_package.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="size">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"  name="name"
                                   placeholder="Enter Shipping Carrier Package Name" value="{{ old('name', $shipping_package['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dimensions_length">Dimensions Length</label>
                            <div class="d-flex" style="gap:10px;">
                                <input type="text" class="form-control" id="dimensions_width" name="dimensions_width" placeholder="Dimention Width" value="{{ old('dimensions_width', $shipping_package['dimensions_width'] ?? '') }}">
                                <input type="text" class="form-control" id="dimensions_length" name="dimensions_length" placeholder="Dimention Length" value="{{ old('dimensions_length', $shipping_package['dimensions_length'] ?? '') }}">
                                <input type="text" class="form-control" id="dimensions_height" name="dimensions_height" placeholder="Dimention Height" value="{{ old('dimensions_height', $shipping_package['dimensions_height'] ?? '') }}">
                                <select class="form-control" name="dimensions_length_unit" id="dimensions_length_unit">
                                    <option value="1" @if(isset($shipping_package['dimensions_length_unit']) &&  $shipping_package['dimensions_length_unit'] == 1) selected @endif>Inch</option>
                                    <option value="2" @if(isset($shipping_package['dimensions_length_unit']) &&  $shipping_package['dimensions_length_unit'] == 2) selected @endif>Millimeters</option>
                                    <option value="3" @if(isset($shipping_package['dimensions_length_unit']) &&  $shipping_package['dimensions_length_unit'] == 3) selected @endif>Centimeters</option>
                                    <option value="4" @if(isset($shipping_package['dimensions_length_unit']) &&  $shipping_package['dimensions_length_unit'] == 4) selected @endif>Meters</option>
                                    <option value="5" @if(isset($shipping_package['dimensions_length_unit']) &&  $shipping_package['dimensions_length_unit'] == 5) selected @endif>Feet</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="weight_value">Weight</label>
                            <div class="d-flex" style="gap:10px;">
                                <input type="text" class="form-control" id="weight_value" name="weight_value" placeholder="Weigth" value="{{ old('weight_value', $shipping_package['weight_value'] ?? '') }}">
                                <select class="form-control" name="weight_weigth_unit" id="weight_weigth_unit">
                                    <option value="0" @if(isset($shipping_package['weight_weigth_unit']) &&  $shipping_package['weight_weigth_unit'] == 0) selected @endif>Oz</option>
                                    <option value="1" @if(isset($shipping_package['weight_weigth_unit']) &&  $shipping_package['weight_weigth_unit'] == 1) selected @endif>Lb</option>
                                    <option value="2" @if(isset($shipping_package['weight_weigth_unit']) &&  $shipping_package['weight_weigth_unit'] == 2) selected @endif>G</option>
                                    <option value="3" @if(isset($shipping_package['weight_weigth_unit']) &&  $shipping_package['weight_weigth_unit'] == 3) selected @endif>Kg</option>
                                    <option value="4" @if(isset($shipping_package['weight_weigth_unit']) &&  $shipping_package['weight_weigth_unit'] == 4) selected @endif>Mg</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                Compatible carrier packages
                            </div>
                            <div class="card-body">
                                <select class="form-control carrier-package">
                                    @if(count($shippingCarrierPackages ?? []) > 0)
                                        <option value=""></option>
                                        @foreach($shippingCarrierPackages as $spackage)
                                            <option value="{{ $spackage->id }}" data-carrier-id="{{ (isset($spackage->shippingCarrier)) ? $spackage->shippingCarrier->id : '' }}" data-carrier="{{ (isset($spackage->shippingCarrier)) ? $spackage->shippingCarrier->name : '' }}">{{ $spackage->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <table class="table table-bordered table-hover mt-5" id="table_interface">
                                    <thead>
                                    <tr>
                                        <th>Carrier</th>
                                        <th>Name</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody id="package-carrier-list">
                                    @if(count($carrierPackages ?? []) > 0)
                                        @foreach($carrierPackages as $scp)
                                                <tr class="field-group">
                                                    <td>{{ (isset($scp->ShippingCarrierPackage->shippingCarrier)) ? $scp->ShippingCarrierPackage->shippingCarrier->name : '' }}<input type="hidden" name="carrier_package[]" value="{{ $scp->shipping_package_id }}"></td>
                                                    <td>{{ (isset($scp->ShippingCarrierPackage)) ? $scp->ShippingCarrierPackage->name : '' }}</td>
                                                    <td><button type="button" class="btn btn-danger remove-carrier-package p-1"> <i class="fas fa-fw fa-trash"></i></button></td>
                                                </tr>
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
                @if(!isset($shipping_package))
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
            $('.carrier-package').select2({
                placeholder: "Select carrier package",
                initSelection: function(element, callback) {
                }
            });
            $('.carrier-package').on('select2:select', function (e) {
                var data = e.params.data;
                /* check selected store upc exist or not*/
                var ele = data.element;
                var carrier = $(ele).data('carrier');
                //console.log(data);
                //console.log($(ele).data('producttype')); return false;
                var carrier_package_id = data.id;
                var carrier_package = data.text;
                if(carrier_package_id != '' && $('input[name="carrier_package[]"][value="'+carrier_package_id+'"]').length == 0) {
                $('#package-carrier-list').prepend('<tr class="field-group"><td>' + carrier + '<input type="hidden" name="carrier_package[]" value="' + carrier_package_id + '"></td><td>'+carrier_package+'</td><td><button type="button" class="btn btn-danger remove-carrier-package p-1"> <i class="fas fa-fw fa-trash"></i></button></td></tr>');

                }
                setTimeout(function () {
                    $('.carrier-package').val("").trigger('change')
                },500);
            });

            $(document).on('click', '.remove-carrier-package', function() {
                $(this).parents('tr.field-group').remove();
            });
        });
    </script>
@stop