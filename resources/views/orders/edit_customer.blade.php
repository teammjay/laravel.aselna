<form id="frmEditCustomer">
    <div class="row">
        <div class="col-md-12">
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="first_name">First Name</label>
                </div>
                <div class="col-md-8">
                    <input type="hidden" name="id" value="{{ $customerDetail->id }}">
                    <input type="text" class="form-control" name="first_name" id="first_name" value="{{ $customerDetail->first_name }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="last_name">Last Name</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="last_name" id="last_name" value="{{ $customerDetail->last_name }}">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="email">Email</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="email" id="email" value="{{ $customerDetail->email }}" readonly="">
                </div>
            </div>
            <div class="row mt-3 g-3 align-items-center">
                <div class="col-md-4">
                    <label for="phone">Phone</label>
                </div>
                <div class="col-md-8">
                    @if(isset($customerDetail->phones) && count($customerDetail->phones ?? []) > 0)
                        @foreach($customerDetail->phones as $phone)
                            <input type="text" class="form-control" name="phone[{{ $phone->id }}]" value="{{ $phone->phone_no }}">
                        @endforeach
                    @endif

                </div>
            </div>

        </div>

    </div>
</form>