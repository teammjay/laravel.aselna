<form id="frmEditAddress">
    <div class="row">
        <div class="col-md-12">
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="first_name">Fullname</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="full_name" id="full_name" value="{{ isset($addressDetail->full_name) ? $addressDetail->full_name : '' }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="first_name">First Name</label>
                </div>
                <div class="col-md-8">
                    <input type="hidden" name="id" value="{{ $addressDetail->id }}">
                    <input type="text" class="form-control" name="first_name" id="first_name" value="{{ isset($addressDetail->first_name) ? $addressDetail->first_name : $addressDetail->customer->first_name }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="last_name">Last Name</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="last_name" id="last_name" value="{{ isset($addressDetail->last_name) ? $addressDetail->last_name : $addressDetail->customer->last_name }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="address_line_1">Address Line 1</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="address_line_1" id="address_line_1" value="{{ $addressDetail->address_line_1 }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="address_line_2">Address Line 2</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="address_line_2" id="address_line_2" value="{{ $addressDetail->address_line_2 }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="city">City</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="city" id="city" value="{{ $addressDetail->city }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="state">State</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="state" id="state" value="{{ $addressDetail->state }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="zip">Zip</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="zip" id="zip" value="{{ $addressDetail->zip }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="country_code">Country Code</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="country_code" id="country_code" value="{{ $addressDetail->country_code }}">
                </div>
            </div>
            {{--<div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="phone">Phone</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="phone" id="phone" value="{{ $addressDetail->customerPhone->phone_no }}">
                </div>
            </div>--}}
        </div>

    </div>
</form>