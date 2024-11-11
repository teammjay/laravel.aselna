@extends('adminlte::page')

@section('title', 'Add Store')

@section('content_header')
    <h1>Add Store</h1>
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
                        <a class="nav-link" id="message-tab" data-toggle="pill" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                            Message
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="address-tab" data-toggle="pill" href="#tab3" role="tab" aria-controls="tab3" aria-selected="false">
                            Address
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="payment-tab" data-toggle="pill" href="#tab4" role="tab" aria-controls="tab4" aria-selected="false">
                            Payment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="shipping-tab" data-toggle="pill" href="#tab5" role="tab" aria-controls="tab5" aria-selected="false">
                            Shipping
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="credential-tab" data-toggle="pill" href="#tab6" role="tab" aria-controls="tab6" aria-selected="false">
                            Credential
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="media-tab" data-toggle="pill" href="#tab7" role="tab" aria-controls="tab7" aria-selected="false">
                            Media
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pages-tab" data-toggle="pill" href="#tab8" role="tab" aria-controls="tab8" aria-selected="false">
                            Pages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="listingbar-tab" data-toggle="pill" href="#tab9" role="tab" aria-controls="tab9" aria-selected="false">
                            Listing
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="categories-tab" data-toggle="pill" href="#tab10" role="tab" aria-controls="tab10" aria-selected="false">
                            Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="logistics-tab" data-toggle="pill" href="#tab11" role="tab" aria-controls="tab11" aria-selected="false">
                            Logistics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="devices-tab" data-toggle="pill" href="#tab11" role="tab" aria-controls="tab11" aria-selected="false">
                            Devices
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="custom-tabs-one-tabContent">
                    <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="general-tab">
                        <form method="POST" id="frmtab1">

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(1)">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>

                    </div>
                    <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="inventory-tab">
                        <form method="POST" id="frmtab2">

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(2)">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab3" role="tabpanel" aria-labelledby="tier-pricing-tab">
                        <form method="POST" id="frmtab3">

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(3)">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab4" role="tabpanel" aria-labelledby="compatibility-tab">
                        <form method="POST" id="frmtab4">


                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(4)">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab5" role="tabpanel" aria-labelledby="features-tab">
                        <form method="POST" id="frmtab5">

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(5)">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab6" role="tabpanel" aria-labelledby="search-terms-tab">
                        <form method="POST" id="frmtab6">

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(6)">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab7" role="tabpanel" aria-labelledby="images-tab">
                    </div>
                    <div class="tab-pane fade" id="tab8" role="tabpanel" aria-labelledby="tags-tab">
                        <form method="POST" id="frmtab8">

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(8)">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab9" role="tabpanel" aria-labelledby="stores-tab">
                        <form method="POST" id="frmtab9">

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(9)">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab10" role="tabpanel" aria-labelledby="channel-tab">
                        <form method="POST" id="frmtab10">

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(10)">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab11" role="tabpanel" aria-labelledby="spitex-tab">
                        <form method="POST" id="frmtab11">

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(11)">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab12" role="tabpanel" aria-labelledby="spitex-tab">
                        <form method="POST" id="frmtab12">

                            <div class="card-footer text-center mt-3">
                                <button type="button" class="btn btn-success" onclick="saveTabData(11)">Submit</button>
                                {{--<button type="reset" class="btn btn-primary">Reset</button>--}}
                                <a href="{{ route('catalog.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
@stop

@section('adminlte_js')
<script>

    var product_id = "{{ $product_id ?? '' }}"; // Passed from controller

    $(document).ready(function() {

        function saveTabData(tabId) {
            let formData = new FormData(document.getElementById('frmtab' + tabId));
            formData.append('product_id', product_id);
            formData.append('tab', tabId);

            fetch('{{ route('catalog.save') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Switch to the next tab
                       /* let nextTab = new bootstrap.Tab(document.querySelector(`#${tabId}-tab`).nextElementSibling);
                        nextTab.show();*/
                       location.reload();
                    } else {
                        alert('Error saving data for ' + tabId);
                    }
                });
        }

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

    });
</script>
@stop
