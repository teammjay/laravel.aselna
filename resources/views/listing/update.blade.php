@extends('adminlte::page')

@section('title', 'Listing')

@section('content_header')
    <h1>Edit Listing</h1>
@stop

@section('content')

    @csrf
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="general-tab" data-toggle="pill" href="#tab1" role="tab" aria-controls="tab1" aria-selected="true">
                        General
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="channel-tab" data-toggle="pill" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                        Channel Details
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="variant-tab" data-toggle="pill" href="#tab3" role="tab" aria-controls="tab3" aria-selected="false">
                        Variants
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="history-tab" data-toggle="pill" href="#tab4" role="tab" aria-controls="tab4" aria-selected="false">
                        History
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
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="sku">SKU</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="sku" name="sku" value="" placeholder="SKU" readonly="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="product_code">Product Code</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="product_code" name="product_code" value="">
                                        <span class="error-message text-danger" id="product_code-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="title">Title</label>
                                    </div>
                                    <div class="col-md-7">
                                        <textarea class="form-control" id="title" name="title" rows="8"></textarea>
                                        <span class="error-message text-danger" id="title-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="description">Description</label>
                                    </div>
                                    <div class="col-md-7">
                                        <textarea class="form-control" id="description" name="description" rows="8"></textarea>
                                        <span class="error-message text-danger" id="description-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="manufacturer">Manufacturer</label>
                                    </div>
                                    <div class="col-md-7">
                                        <select class="form-control" name="manufacturer" id="manufacturer">
                                            @if(count($manufacturers ?? []) > 0)
                                                @foreach($manufacturers as $mf)
                                                    <option value="{{ $mf->id }}">{{ $mf->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="quantity">Quantity</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="quantity" name="quantity" value="">
                                        <span class="error-message text-danger" id="quantity-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="price">Price</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="price" name="price" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="processing_days">Processing Days</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="processing_days" name="processing_days" value="">
                                        <span class="error-message text-danger" id="processing_days-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="store_url">Store Url</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="store_url" name="store_url" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="linked_item">Linked Item</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="linked_item" name="linked_item" value="">
                                    </div>
                                </div>

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="overwrite_price">Overwrite Price</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="overwrite_price" name="overwrite_price">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="overwrite_msrp">Overwrite msrp</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="overwrite_msrp" name="overwrite_msrp">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="overwrite_compare_at_price">Overwrite Compare at price</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="overwrite_compare_at_price" name="overwrite_compare_at_price">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="overwrite_title">Overwrite title</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="overwrite_title" name="overwrite_title">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="overwrite_description">Overwrite description</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="overwrite_description" name="overwrite_description">
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-6">

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="status">Status</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="status" name="status" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="generation_type">Generation Type</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="generation_type" name="generation_type" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="brand">Brand</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="brand" name="brand" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="model_name">Model Name</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="model_name" name="model_name" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="carrier">Carrier</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="carrier" name="carrier" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="mpn">Mpn</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="mpn" name="mpn" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="upc">Upc</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="upc" name="upc" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="brand_combo">Brand Combo</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="brand_combo" name="brand_combo">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="carrier_combo">Carrier Combo</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="carrier_combo" name="carrier_combo">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="store_sku">Store SKU</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="store_sku" name="store_sku" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="sync_status">Sync Status</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="sync_status" name="sync_status" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="sync_message">Sync Message</label>
                                    </div>
                                    <div class="col-md-7">
                                        <textarea class="form-control" name="sync_status" id="sync_status" rows="8"></textarea>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="submit_count">Submit Count</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="submit_count" name="submit_count" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="sales_count">Sales Count</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="sales_count" name="sales_count" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="last_sale_date">Last Sale Date</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="last_sale_date" name="last_sale_date" value="">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(1)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>

                </div>
                <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="channel-tab">
                    <form id="frmtab2">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="product_category">Product Category</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="product_category" name="product_category" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="publish_status">Publish status</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="publish_status" name="publish_status" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="status_change_reason">Status change reason</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="status_change_reason" name="status_change_reason" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="inventory_count">Inventory Count</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="inventory_count" name="inventory_count" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="wp_id">Wpid</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="wp_id" name="wp_id" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="shelf_name">Shelf Name</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="shelf_name" name="shelf_name" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="primary_catalog_path">Primary catalog path</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="primary_catalog_path" name="primary_catalog_path" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="review_count">Review Count</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="review_count" name="review_count" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="average_rating">Average Rating</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="average_rating" name="average_rating" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="channel_status">Status</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="channel_status" name="channel_status" value="">
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-6">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="buy_box_price">Buy box price</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="buy_box_price" name="buy_box_price" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="buy_box_shipping_price">Buy box shipping price</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="buy_box_shipping_price" name="buy_box_shipping_price" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="seller_buy_box_winner">Seller buy box winner</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="seller_buy_box_winner" name="seller_buy_box_winner" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="buy_box_winner">Buy box winner</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="buy_box_winner" name="buy_box_winner" value="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="quality_explanation">Quality Explanation</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="quality_explanation" name="quality_explanation" value="">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <table class="table table-bordered mt-2">
                                <thead>
                                <tr>
                                    <th>Brand</th>
                                    <th>Item Device</th>
                                    <th>Carrier</th>
                                    <th>SKU</th>
                                    <th>Item Price</th>
                                    <th>Item Index</th>
                                </tr>

                                </thead>
                            </table>
                        </div>

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(2)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('store.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>

                </div>
                <div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="variant-tab">
                    <table class="table table-bordered mt-2">
                        <thead>
                            <tr>
                                <th>Brand</th>
                                <th>Item Device</th>
                                <th>Carrier</th>
                                <th>SKU</th>
                                <th>Item Price</th>
                                <th>Item Index</th>
                            </tr>

                        </thead>
                    </table>
                </div>
                <div class="tab-pane fade" id="tab4" role="tabpanel" aria-labelledby="history-tab">
                    <table class="table table-bordered mt-2">
                        <thead>
                            <tr>
                                <th>Feed Type</th>
                                <th>Feed Status</th>
                                <th>Feed Id</th>
                                <th>Submit Ts</th>
                                <th>Status</th>
                                <th>Not included in feed</th>
                                <th>Moved</th>
                                <th>Old Pr</th>
                            </tr>
                        </thead>
                    </table>

                </div>
            </div>
        </div>
    </div>

@stop

@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        var store_id = "{{ $store->id ?? '' }}"; // Passed from controller

        function saveStoreTabData(tabId) {
            const form = document.querySelector("#frmtab"+ tabId);
            let formData = new FormData(form);
            formData.append('store_id', store_id);
            formData.append('tab', tabId);

            $.ajax({
                type: 'POST',
                url: "{{ route('store.save') }}", // Adjust this URL to your endpoint
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

        function finalizeForm() {

            const form = document.querySelector("#frmtab1");
            let formData = new FormData(form);
            formData.append('store_id', store_id);
            formData.set('store_status', 'active');

            $.ajax({
                type: 'POST',
                url: "{{ route('store.final') }}", // Adjust this URL to your endpoint
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
                    window.location.href = "{{ route('store.list') }}";
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

        function saveStoreShippingService() {
            const form = document.querySelector("#frmStoreShippingService");
            let formData = new FormData(form);
            formData.append('store_id', store_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('store.shippingservice') }}", // Adjust this URL to your endpoint
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

        function saveStorePages() {
            const form = document.querySelector("#frmStorePages");
            let formData = new FormData(form);
            formData.append('store_id', store_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('store.pages') }}", // Adjust this URL to your endpoint
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
                            $("#frmStorePages").find('#' + field + '-error').text(messages[0]); // Display the first error message
                        });
                    }
                }
            });

        }

        function saveStoreCategory() {
            const form = document.querySelector("#frmStoreCategory");
            let formData = new FormData(form);
            formData.append('store_id', store_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('store.category') }}", // Adjust this URL to your endpoint
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
                            $("#frmStoreCategory").find('#' + field + '-error').text(messages[0]); // Display the first error message
                        });
                    }
                }
            });

        }

        function saveStoreMessageAccount() {
            const form = document.querySelector("#frmStoreMessageAccount");
            let formData = new FormData(form);
            formData.append('store_id', store_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('store.messageaccount') }}", // Adjust this URL to your endpoint
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
                            $("#frmStoreMessageAccount").find('#acc_' + field + '-error').text(messages[0]); // Display the first error message
                            $("#frmStoreMessageAccount").find('#' + field + '-error').text(messages[0]); // Display the first error message

                        });
                    }
                }
            });

        }

        function saveStoreLogisticCenter() {
            const form = document.querySelector("#frmStoreLogisticCenter");
            let formData = new FormData(form);
            formData.append('store_id', store_id);
            $.ajax({
                type: 'POST',
                url: "{{ route('store.logisticcenter') }}", // Adjust this URL to your endpoint
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
                            $("#frmStoreLogisticCenter").find('#logistic_' + field + '-error').text(messages[0]);
                        });
                    }
                }
            });
        }

        $(document).ready(function() {

            $('.pro-tags').select2();
            // Add new field group
            $('#add_feature').click(function() {
                $('#features_table tbody').append(`
                    <tr class="field-group">
                        <td><textarea class="form-control" name="features[]"></textarea></td>
                        <td><input type="text" class="form-control" name="feature_sort_order[]" value=""></td>
                        <td><button type="button" class="btn btn-danger remove-feature"> <i class="fas fa-fw fa-trash"></i></button></td>
                    </tr>
                `);

            });
            // Remove field group
            $(document).on('click', '.remove-feature', function() {
                $(this).parents('tr.field-group').remove();
            });
            // Add new retail price
            $('#add_retail').click(function() {
                $('#table_retail tbody').append(`
                    <tr class="field-group">
                        <td><input type="text" class="form-control" name="retail_qty[]"></td>
                        <td><input type="text" class="form-control" name="retail_price[]"></td>
                        <td><button type="button" class="btn btn-danger remove-retail"> <i class="fas fa-fw fa-trash"></i></button></td>
                    </tr>
                `);

            });
            // Remove retail price group
            $(document).on('click', '.remove-retail', function() {
                $(this).parents('tr.field-group').remove();
            });
            // Add new retail price
            $('#add_wholesale').click(function() {
                $('#table_wholesale tbody').append(`
                    <tr class="field-group">
                        <td><input type="text" class="form-control" name="wholesale_qty[]"></td>
                        <td><input type="text" class="form-control" name="wholesale_price[]"></td>
                        <td><button type="button" class="btn btn-danger remove-wholesale"> <i class="fas fa-fw fa-trash"></i></button></td>
                    </tr>
                `);

            });
            // Remove retail price group
            $(document).on('click', '.remove-wholesale', function() {
                $(this).parents('tr.field-group').remove();
            });
            // Add new device
            $('#add_device').click(function() {
                $('#table_incompatible_device tbody').append(`
                    <tr class="field-group">
                        <td><input type="text" class="form-control" name="model_name[]"></td>
                        <td><input type="text" class="form-control" name="device_name[]"></td>
                        <td><button type="button" class="btn btn-danger remove-device"> <i class="fas fa-fw fa-trash"></i></button></td>
                    </tr>
                `);

            });
            // Remove retail price group
            $(document).on('click', '.remove-device', function() {
                $(this).parents('tr.field-group').remove();
            });
            // Add new device
            $('#add_title_keyword').click(function() {
                $('#table_title_keywords tbody').append(`
                    <tr class="field-group">
                        <td width="70%"><input type="text" class="form-control" name="title_keyword[]" value=""></td>
                        <td width="20%"><input type="text" class="form-control" name="title_priority[]" value=""></td>
                        <td width="10%"><button type="button" class="btn btn-danger remove-keyword"> <i class="fas fa-fw fa-trash"></i></button></td>
                     </tr>
                `);

            });
            // Remove retail price group
            $(document).on('click', '.remove-keyword', function() {
                $(this).parents('tr.field-group').remove();
            });
            $(document).on('click', '.remove-shipping', function() {
                var shippingId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion
                if (confirm("Are you sure you want to delete this shipping servie?")) {
                    $.ajax({
                        type: 'POST',
                        url: "{{ route('store.removeshipping') }}", // Adjust this URL to your endpoint
                        data: {
                            "shippingId": shippingId
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        dataType:'json',
                        success: function(response) {
                            // Display a success message
                            alert(response.message);
                            ele.parents('tr.field-group').remove();
                        },
                        error: function(xhr) {
                            alert(response.message);
                        }
                    });
                }
            });
            $(document).on('click', '.remove-page', function() {
                var pageId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion
                if (confirm("Are you sure you want to delete this page?")) {

                    $.ajax({
                        type: 'POST',
                        url: "{{ route('store.removepage') }}", // Adjust this URL to your endpoint
                        data: {
                            "pageId": pageId
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        dataType:'json',
                        success: function(response) {
                            // Display a success message
                            alert(response.message);
                            ele.parents('tr.field-group').remove();
                        },
                        error: function(xhr) {
                            alert(response.message);
                        }
                    });
                }
            });
            $(document).on('click', '.remove-message-account', function() {
                var accId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion
                if (confirm("Are you sure you want to delete this message accounts?")) {

                    $.ajax({
                        type: 'POST',
                        url: "{{ route('store.removemessageaccount') }}", // Adjust this URL to your endpoint
                        data: {
                            "accId": accId
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        dataType:'json',
                        success: function(response) {
                            // Display a success message
                            alert(response.message);
                            ele.parents('tr.field-group').remove();
                        },
                        error: function(xhr) {
                            alert(response.message);
                        }
                    });
                }
            });
            $(document).on('click', '.remove-logistic-center', function() {
                var centerId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion
                if (confirm("Are you sure you want to delete this logistic center?")) {

                    $.ajax({
                        type: 'POST',
                        url: "{{ route('store.removelogisticcenter') }}", // Adjust this URL to your endpoint
                        data: {
                            "centerId": centerId
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        dataType:'json',
                        success: function(response) {
                            // Display a success message
                            alert(response.message);
                            ele.parents('tr.field-group').remove();
                        },
                        error: function(xhr) {
                            alert(response.message);
                        }
                    });
                }
            });
        });
    </script>
@stop
