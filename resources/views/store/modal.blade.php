<!-- Vertically centered modal -->
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
                        <label for="carrier-service" class="col-form-label">Carrier Service:</label>
                        <select class="form-control" name="carrier_service_id" id="carrier-service">
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