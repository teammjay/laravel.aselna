<!-- Add Shipping modal -->
<div class="modal fade" id="addShippingModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Store Shipping Service Editor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="frmStoreShippingService">
                    <div class="mb-3">
                        <label for="shipping-name" class="col-form-label">Name:</label>
                        <input type="text" name="name" class="form-control" id="shipping-name">
                    </div>
                    <div class="mb-3">
                        <label for="carrier_service_id" class="col-form-label">Carrier Service:</label>
                        <select class="form-control" name="carrier_service_id" id="carrier_service_id">
                            @if(count($shipping_services ?? []) > 0)
                                @foreach($shipping_services as $service)
                                    <option value="{{ $service->id }}">{{ $service->carrierName.' '.$service->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveStoreShippingService()" id="saveStoreShippingService">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Pages modal -->
<div class="modal fade" id="addPagesModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Store Pages Editor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="frmStorePages">
                    <div class="mb-3">
                        <label for="page_type_id" class="col-form-label">Page Type:</label>
                        <select class="form-control" name="page_type_id" id="page_type_id">
                            @if(count($pageTypes ?? []) > 0)
                                @foreach($pageTypes as $ptype)
                                    <option value="{{ $ptype->id }}">{{ $ptype->type }}</option>
                                @endforeach
                            @endif
                        </select>
                        <span class="error-message text-danger" id="page_type-error"></span>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="col-form-label">Title:</label>
                        <input type="text" name="title" class="form-control" id="title">
                        <span class="error-message text-danger" id="title-error"></span>
                    </div>
                    <div class="mb-3">
                        <label for="text" class="col-form-label">Text:</label>
                        <textarea class="form-control" name="text" id="text" rows="10"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveStorePages()" id="saveStorePages">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Pages modal -->
<div class="modal fade" id="addCategoryModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Store Cateory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="frmStoreCategory">
                    <div>
                        <label for="cat_code" class="col-form-label">Code:</label>
                        <input type="text" name="code" class="form-control" id="cat_code">
                    </div>
                    <div>
                        <label for="cat_name" class="col-form-label">Name:</label>
                        <input type="text" name="name" class="form-control" id="cat_name">
                        <span class="error-message text-danger" id="name-error"></span>
                    </div>
                    <div class="mb-3">
                        <label for="cat_url" class="col-form-label">Url:</label>
                        <input type="text" name="url" class="form-control" id="cat_url">
                    </div>
                    <div class="mb-3">
                        <div class="card">
                            <div class="card-header">Product Types</div>
                            <div class="card-body">
                                <label for="product_type_id" class="col-form-label">Code:</label>
                                <select class="form-control" name="product_type_id" id="product_type_id">

                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveStoreCategory()" id="saveStoreCategory">Save</button>
            </div>
        </div>
    </div>
</div>