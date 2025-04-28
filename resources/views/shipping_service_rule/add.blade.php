@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($shipping_service_rule) ? 'Edit Shipping Service Rule' : 'Add Shipping Service Rule' }}</h1>
@stop

@section('adminlte_css')
    <!-- Pickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css"/>
@stop

@section('content')
    <form
        action="{{ isset($shipping_service_rule) ? route('shipping_service_rule.update',['id' => $shipping_service_rule['id']]) : route('shipping_service_rule.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <input type="text" class="form-control @error('priority') is-invalid @enderror" id="priority"
                                   name="priority"
                                   placeholder="priority" value="{{ old('name', $shipping_service_rule['priority'] ?? '') }}">
                            @if ($errors->has('priority'))
                                <span class="text-danger">{{ $errors->first('priority') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="weight_operator">Weight Filter</label>
                            <select class="form-control @error('weight_operator') is-invalid @enderror" id="weight_operator" name="weight_operator">
                                <option value="0">None</option>
                                <option value="1"> < </option>
                                <option value="2"> <= </option>
                                <option value="3"> > </option>
                                <option value="4"> >= </option>
                                <option value="5"> Between </option>
                                <option value="6"> = </option>
                                <option value="7"> <> </option>
                            </select>
                            @if ($errors->has('weight_operator'))
                                <span class="text-danger">{{ $errors->first('weight_operator') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex align-items-center" style="gap:10px;">
                                <input type="text" class="form-control" id="min_weight_value" name="min_weight_value" placeholder="Weigth" value="{{ old('min_weight_value', $shipping_service_rule['min_weight_value'] ?? '') }}">
                                <select class="form-control" name="min_weight_weight_unit" id="min_weight_weight_unit">
                                    <option value="0" @if(isset($shipping_service_rule['min_weight_weight_unit']) &&  $shipping_service_rule['min_weight_weight_unit'] == 0) selected @endif>Oz</option>
                                    <option value="1" @if(isset($shipping_service_rule['min_weight_weight_unit']) &&  $shipping_service_rule['min_weight_weight_unit'] == 1) selected @endif>Lb</option>
                                    <option value="2" @if(isset($shipping_service_rule['min_weight_weight_unit']) &&  $shipping_service_rule['min_weight_weight_unit'] == 2) selected @endif>G</option>
                                    <option value="3" @if(isset($shipping_service_rule['min_weight_weight_unit']) &&  $shipping_service_rule['min_weight_weight_unit'] == 3) selected @endif>Kg</option>
                                    <option value="4" @if(isset($shipping_service_rule['min_weight_weight_unit']) &&  $shipping_service_rule['min_weight_weight_unit'] == 4) selected @endif>Mg</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="price_operator">Price Filter</label>
                            <select class="form-control @error('price_operator') is-invalid @enderror" id="price_operator" name="price_operator">
                                <option value="0">None</option>
                                <option value="1"> < </option>
                                <option value="2"> <= </option>
                                <option value="3"> > </option>
                                <option value="4"> >= </option>
                                <option value="5"> Between </option>
                                <option value="6"> = </option>
                                <option value="7"> <> </option>
                            </select>
                            @if ($errors->has('price_operator'))
                                <span class="text-danger">{{ $errors->first('price_operator') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="quantity_operator">Quantity Operator</label>
                            <select class="form-control @error('quantity_operator') is-invalid @enderror" id="quantity_operator" name="quantity_operator">
                                <option value="0">None</option>
                                <option value="1"> < </option>
                                <option value="2"> <= </option>
                                <option value="3"> > </option>
                                <option value="4"> >= </option>
                                <option value="5"> Between </option>
                                <option value="6"> = </option>
                                <option value="7"> <> </option>
                            </select>
                            @if ($errors->has('quantity_operator'))
                                <span class="text-danger">{{ $errors->first('quantity_operator') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="package_type_filter_id">Package Type Filter</label>
                            <select class="form-control" id="package_type_filter_id" name="package_type_filter_id">
                                <option value=""></option>
                                @if(count($ShippingPackages ?? []) > 0)
                                    @foreach($ShippingPackages as $sPackage)
                                        <option value="{{ $sPackage->id }}" @if((isset($shipping_service_rule) && $shipping_service_rule['package_type_filter_id'] === $sPackage->id)) selected @endif >{{ $sPackage->name }}</option>
                                    @endforeach
                                @endif


                            </select>

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="shipping_carrier_service_id">Carrier Service</label>
                            <select class="form-control" id="shipping_carrier_service_id" name="shipping_carrier_service_id">
                                <option value="0">None</option>
                                @if(count($shippingCarrierServices ?? []) > 0)
                                    @foreach($shippingCarrierServices as $sCarrierService)
                                        <option value="{{ $sCarrierService->id }}" @if((isset($shipping_service_rule) && $shipping_service_rule['shipping_carrier_service_id'] === $sCarrierService->id)) selected @endif >{{ $sCarrierService->name }}</option>
                                    @endforeach
                                @endif
                            </select>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="signature_confirmation">Signature Confirmation</label>
                            <select class="form-control" id="signature_confirmation" name="signature_confirmation">
                                <option value="0" @if((isset($shipping_service_rule) && $shipping_service_rule['signature_confirmation'] === 0)) selected @endif>None</option>
                                <option value="1" @if((isset($shipping_service_rule) && $shipping_service_rule['signature_confirmation'] === 1)) selected @endif>Direct Signature</option>
                                <option value="2" @if((isset($shipping_service_rule) && $shipping_service_rule['signature_confirmation'] === 2)) selected @endif>Adult Signature</option>

                            </select>

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="round_weight_value">Round Weigth</label>
                            <div class="d-flex" style="gap:10px;">
                                <input type="text" class="form-control" id="round_weight_value" name="round_weight_value" placeholder="Weigth" value="{{ old('round_weight_value', $shipping_service_rule['round_weight_value'] ?? '') }}">
                                <select class="form-control" name="round_weight_weight_unit" id="round_weight_weight_unit">
                                    <option value="0" @if(isset($shipping_service_rule['round_weight_weight_unit']) &&  $shipping_service_rule['round_weight_weight_unit'] == 0) selected @endif>Oz</option>
                                    <option value="1" @if(isset($shipping_service_rule['round_weight_weight_unit']) &&  $shipping_service_rule['round_weight_weight_unit'] == 1) selected @endif>Lb</option>
                                    <option value="2" @if(isset($shipping_service_rule['round_weight_weight_unit']) &&  $shipping_service_rule['round_weight_weight_unit'] == 2) selected @endif>G</option>
                                    <option value="3" @if(isset($shipping_service_rule['round_weight_weight_unit']) &&  $shipping_service_rule['round_weight_weight_unit'] == 3) selected @endif>Kg</option>
                                    <option value="4" @if(isset($shipping_service_rule['round_weight_weight_unit']) &&  $shipping_service_rule['round_weight_weight_unit'] == 4) selected @endif>Mg</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="color">Color</label>
                            <div id="color-picker"></div>
                            <input type="hidden" name="color" id="picked-color" value="{{ old('color', $shipping_service_rule['color'] ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($shipping_service_rule))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop

@section('js')
    <!-- Pickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr"></script>
    <script>
        const pickr = Pickr.create({
            el: '#color-picker',
            theme: 'classic',
            default: "<?php if(isset($shipping_service_rule['color']) &&  $shipping_service_rule['color'] != ''){ echo $shipping_service_rule['color']; }else{ echo '#000000';} ?>",
            components: {
                preview: true,
                opacity: true,
                hue: true,
                interaction: {
                    hex: true,
                    rgba: true,
                    input: true,
                    save: true
                }
            }
        });

        pickr.on('save', (color) => {
            const hex = color.toHEXA().toString();
            document.getElementById('picked-color').value = hex;
            pickr.hide();
        });
    </script>
@stop
