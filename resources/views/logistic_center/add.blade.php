@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($logistic_center) ? 'Edit Logistic Center' : 'Add Logistic Center' }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="general-tab" data-toggle="pill" href="#tab1" role="tab"
                       aria-controls="tab1" aria-selected="true">
                        General
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="from-address-tab" data-toggle="pill" href="#tab2" role="tab"
                       aria-controls="tab2" aria-selected="false">
                        From Address
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="to-address-tab" data-toggle="pill" href="#tab3" role="tab"
                       aria-controls="tab3" aria-selected="false">
                        To Address
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="custom-tabs-one-tabContent">
                <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="general-tab">
                    <form method="POST" id="frmtab1">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="size">Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                           name="name"
                                           placeholder="Enter Shipping Carrier Name" value="{{ old('name', $logistic_center['name'] ?? '') }}">
                                    @if ($errors->has('name'))
                                        <span class="text-danger">{{ $errors->first('name') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
{{--                        <div class="row">--}}
{{--                            <div class="col-md-6">--}}
{{--                                <div class="form-group">--}}
{{--                                    <label for="size">Client</label>--}}
{{--                                    <select class="form-control" name="client_id" id="client_id">--}}
{{--                                        <option value=""></option>--}}
{{--                                        <option value="">Aselna</option>--}}
{{--                                    </select>--}}
{{--                                    @if ($errors->has('name'))--}}
{{--                                        <span class="text-danger">{{ $errors->first('code') }}</span>--}}
{{--                                    @endif--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="row">--}}
{{--                            <div class="col-md-6">--}}
{{--                                <div class="form-group">--}}
{{--                                    <label for="size">Center Type</label>--}}
{{--                                    <select class="form-control" name="center_type" id="center_type">--}}
{{--                                        <option value=""></option>--}}
{{--                                        <option value="1">Aselna</option>--}}
{{--                                    </select>--}}
{{--                                    @if ($errors->has('name'))--}}
{{--                                        <span class="text-danger">{{ $errors->first('code') }}</span>--}}
{{--                                    @endif--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(1)">Save</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="from-address-tab">
                    <form method="POST" id="frmtab2">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <input type="text" class="form-control" name="first_name" id="first_name"
                                           value="{{ old('first_name', $fromAddress->first_name ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" id="last_name"
                                           value="{{ old('last_name', $returnAddress->last_name ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company">Company</label>
                                    <input type="text" class="form-control" name="company" id="company"
                                           value="{{ old('company', $fromAddress->company ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" name="email" id="email"
                                           value="{{ old('email', $fromAddress->email ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address_line_1">Address Line 1</label>
                                    <input type="text" class="form-control" name="address_line_1"
                                           id="address_line_1"
                                           value="{{ old('address_line_1', $fromAddress->address_line_1 ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address_line_2">Address Line 2</label>
                                    <input type="text" class="form-control" name="address_line_2"
                                           id="address_line_2"
                                           value="{{ old('address_line_2', $fromAddress->address_line_2 ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address_line_3">Address Line 3</label>
                                    <input type="text" class="form-control" name="address_line_3"
                                           id="address_line_3"
                                           value="{{ old('address_line_3', $fromAddress->address_line_3 ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" class="form-control" name="city" id="city"
                                           value="{{ old('city', $fromAddress->city ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="state_or_region">State/Region</label>
                                    <input type="text" class="form-control" name="state_or_region"
                                           id="state_or_region"
                                           value="{{ old('state_or_region', $fromAddress->state_or_region ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="postal_code">Postal Code</label>
                                    <input type="text" class="form-control" name="postal_code"
                                           id="postal_code"
                                           value="{{ old('postal_code', $fromAddress->postal_code ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <input type="text" class="form-control" name="country" id="country"
                                           value="{{ old('country', $fromAddress->country ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="country_code">Country Code</label>
                                    <input type="text" class="form-control" name="country_code"
                                           id="country_code"
                                           value="{{ old('country_code', $fromAddress->country_code ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="district">District</label>
                                    <input type="text" class="form-control" name="district" id="district"
                                           value="{{ old('district', $fromAddress->district ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(2)">Save</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="to-address-tab">
                    <form method="POST" id="frmtab3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <input type="text" class="form-control" name="first_name" id="first_name"
                                           value="{{ old('first_name', $returnAddress->first_name ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" id="last_name"
                                           value="{{ old('last_name', $returnAddress->last_name ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company">Company</label>
                                    <input type="text" class="form-control" name="company" id="company"
                                           value="{{ old('company', $returnAddress->company ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="text" class="form-control" name="phone" id="phone"
                                           value="{{ old('phone', $returnAddress->phone ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" name="email" id="email"
                                           value="{{ old('email', $returnAddress->email ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address_line_1">Address Line 1</label>
                                    <input type="text" class="form-control" name="address_line_1"
                                           id="address_line_1"
                                           value="{{ old('address_line_1', $returnAddress->address_line_1 ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address_line_2">Address Line 2</label>
                                    <input type="text" class="form-control" name="address_line_2"
                                           id="address_line_2"
                                           value="{{ old('address_line_2', $returnAddress->address_line_2 ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address_line_3">Address Line 3</label>
                                    <input type="text" class="form-control" name="address_line_3"
                                           id="address_line_3"
                                           value="{{ old('address_line_3', $returnAddress->address_line_3 ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" class="form-control" name="city" id="city"
                                           value="{{ old('city', $returnAddress->city ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="state_or_region">State/Region</label>
                                    <input type="text" class="form-control" name="state_or_region"
                                           id="state_or_region"
                                           value="{{ old('state_or_region', $returnAddress->state_or_region ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="postal_code">Postal Code</label>
                                    <input type="text" class="form-control" name="postal_code" id="postal_code"
                                           value="{{ old('postal_code', $returnAddress->postal_code ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="country">Country</label>
                                    <input type="text" class="form-control" name="country" id="country"
                                           value="{{ old('country', $returnAddress->country ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="country_code">Country Code</label>
                                    <input type="text" class="form-control" name="country_code" id="country_code"
                                           value="{{ old('country_code', $returnAddress->country_code ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="district">District</label>
                                    <input type="text" class="form-control" name="district" id="district"
                                           value="{{ old('district', $returnAddress->district ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client">Client</label>
                                    <input type="text" class="form-control" name="client" id="client"
                                           value="{{ old('client', $returnAddress->client ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(3)">Save</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Lightbox2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script>
        var store_id = "{{ $store->id ?? '' }}"; // Passed from controller

        function saveStoreTabData(tabId) {
            const form = document.querySelector("#frmtab" + tabId);
            let formData = new FormData(form);
            formData.append('store_id', store_id);
            formData.append('tab', tabId);

            $.ajax({
                type: 'POST',
                url: "{{ route('logistic_center.update',['id' => $logistic_center['id']]) }}", // Adjust this URL to your endpoint
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                dataType:'json',
                success: function(response) {
                    // Display a success message
                    alert(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Validation error
                        var errors = xhr.responseJSON.errors;

                        // Clear previous errors
                        $('.error-message').text('');

                        // Loop through and display errors
                        $.each(errors, function(field, messages) {
                            $('#' + field + '-error').text(messages[0]); // Display the first error message
                        });
                    }
                }
            });

        }
    </script>
@stop
