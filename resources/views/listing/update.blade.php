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
                                        <input type="text" class="form-control" id="sku" name="sku" value="{{ old('sku',$productDetail->sku) }}" placeholder="SKU" readonly="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="product_code">Product Code</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="product_code" name="product_code" value="{{ old('product_sku',$productDetail->product_sku) }}" readonly>
                                        <span class="error-message text-danger" id="product_code-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="title">Title</label>
                                    </div>
                                    <div class="col-md-7">
                                        <textarea class="form-control" id="title" name="title" rows="8">{{ old('product_name',$productDetail->title) }}</textarea>
                                        <span class="error-message text-danger" id="title-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="description">Description</label>
                                    </div>
                                    <div class="col-md-7">
                                        <textarea class="form-control" id="description" name="description" rows="8">{{ old('description',$productDetail->description) }}</textarea>
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
                                                    <option value="{{ $mf->id }}" {{ ($mf->id === $productDetail->manufacturer_id) ? 'selected' :'' }}>{{ $mf->name }}</option>
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
                                        <input type="text" class="form-control" id="quantity" name="quantity" value="{{ old('price',$productDetail->product_qty) }}">
                                        <span class="error-message text-danger" id="quantity-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="price">Price</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="price" name="price" value="{{ old('price',$productDetail->price) }}">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="processing_days">Processing Days</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="processing_days" name="processing_days" value="{{ old('processing_days',$productDetail->processing_days) }}">
                                        <span class="error-message text-danger" id="processing_days-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="store_url">Store Url</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="store_url" name="store_url" value="{{ old('store_url',$productDetail->store_url) }}">
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
                                        <input type="checkbox" id="overwrite_price" name="overwrite_price" {{ ($productDetail->overwrite_price == 1) ? 'checked' :'' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="overwrite_msrp">Overwrite msrp</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="overwrite_msrp" name="overwrite_msrp" {{ ($productDetail->overwrite_msrp == 1) ? 'checked' :'' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="overwrite_compare_at_price">Overwrite Compare at price</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="overwrite_compare_at_price" name="overwrite_compare_at_price" {{ ($productDetail->overwrite_compare_at_price == 1) ? 'checked' :'' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="overwrite_title">Overwrite title</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="overwrite_title" name="overwrite_title" {{ ($productDetail->overwrite_title == 1) ? 'checked' :'' }}>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="overwrite_description">Overwrite description</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="checkbox" id="overwrite_description" name="overwrite_description" {{ ($productDetail->overwrite_description == 1) ? 'checked' :'' }}>
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-6">

                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="status">Status</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="status" name="status" value="{{ ($productDetail->status == '') ? 'Active' : 'Inactive' }}" readonly>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="generation_type">Generation Type</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="generation_type" name="generation_type" value="{{ $productDetail->generation_name }}" readonly>
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
                                        <input type="text" class="form-control" id="upc" name="upc" value="{{ old('upc',$productDetail->upc) }}">
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
                                        <input type="text" class="form-control" id="store_sku" name="store_sku" value="{{ old('store_sku',$productDetail->store_sku) }}" readonly>
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
                            <a href="{{ route('listing.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>

                </div>
                <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="channel-tab">
                    <form id="frmtab2">
                        <div class="card">
                            <div class="card-header">
                                Channel Listing
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="product_category">Product Category</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="product_category" name="product_category" value="{{ old('product_category',$storeTypeDetail->product_category) }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="publish_status">Publish status</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="publish_status" name="publish_status" value="{{ old('publish_status',$storeTypeDetail->publish_status) }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="status_change_reason">Status change reason</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="status_change_reason" name="status_change_reason" value="{{ old('status_change_reason',$storeTypeDetail->status_change_reason) }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="inventory_count">Inventory Count</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="inventory_count" name="inventory_count" value="{{ old('inventory_count',$storeTypeDetail->inventory_count) }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="wp_id">Wpid</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="wp_id" name="wp_id" value="" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="shelf_name">Shelf Name</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="shelf_name" name="shelf_name" value="{{ old('shelf_name',$storeTypeDetail->shelf_name) }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="primary_catalog_path">Primary catalog path</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="primary_catalog_path" name="primary_catalog_path" value="" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="review_count">Review Count</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="review_count" name="review_count" value="{{ old('review_count',$storeTypeDetail->review_count) }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="average_rating">Average Rating</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="average_rating" name="average_rating" value="{{ old('average_rating',$storeTypeDetail->average_rating) }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="channel_status">Status</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="channel_status" name="channel_status" value="" readonly>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-6">
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="buy_box_price">Buy box price</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="buy_box_price" name="buy_box_price" value="{{ old('buy_box_price',$storeTypeDetail->buy_box_price) }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="buy_box_shipping_price">Buy box shipping price</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="buy_box_shipping_price" name="buy_box_shipping_price" value="{{ old('buy_box_shipping_price',$storeTypeDetail->buy_box_shipping_price) }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="seller_buy_box_winner">Seller buy box winner</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="checkbox" id="seller_buy_box_winner" name="seller_buy_box_winner" {{ ($storeTypeDetail->seller_buy_box_winner == 1) ? 'checked' :'' }} readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="buy_box_winner">Buy box winner</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="buy_box_winner" name="buy_box_winner" value="" readonly>
                                            </div>
                                        </div>
                                        <div class="row mt-3 g-3 align-items-center">
                                            <div class="col-md-5">
                                                <label for="quality_explanation">Quality Explanation</label>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" id="quality_explanation" name="quality_explanation" value="{{ old('quality_explanation',$storeTypeDetail->quality_explanation) }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                Offers
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered mt-2">
                                    <thead>
                                    <tr>
                                        <th>Seller</th>
                                        <th>Price</th>
                                        <th>Shipping Price</th>
                                        <th>First found date</th>
                                        <th>Last check date</th>
                                        <th>Our price</th>
                                        <th>Our shipping price</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveStoreTabData(2)">Save</button>
                            <button type="button" class="btn btn-primary" onclick="finalizeForm()">Submit</button>
                            {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                            <a href="{{ route('listing.list') }}" class="btn btn-secondary">Cancel</a>
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
                        <tbody>
                            @if(count($productVariants ?? []) > 0)
                                @foreach($productVariants as $var)
                                    <tr>
                                        <td>{{ $var->brand_name }}</td>
                                        <td>{{ $var->device_name }}</td>
                                        <td></td>
                                        <td>{{ $var->sku }}</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
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


    </script>
@stop
