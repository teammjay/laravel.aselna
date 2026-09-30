{{-- Update Tracking Number popup, shared by the order list and order detail screens. Needs $shippingCarrier.
     Open it with openTrackingNumberModal(orderId). --}}
<div class="modal fade" id="updateTrackingNumberModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Tracking Number <small class="text-muted" id="tn_order_label"></small></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tn_order_id">
                <div class="alert alert-danger d-none" id="trackingNumberError"></div>
                <div class="form-group">
                    <label for="tn_carrier_name">Carrier Name</label>
                    <select class="form-control" id="tn_carrier_name">
                        <option value="">Select carrier</option>
                        @foreach($shippingCarrier ?? [] as $sCarrier)
                            <option value="{{ $sCarrier->id }}">{{ $sCarrier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="tn_carrier_service">Shipping Carrier Service</label>
                    <select class="form-control" id="tn_carrier_service"></select>
                </div>
                <div class="form-group">
                    <label for="tn_tracking_number">Tracking Number</label>
                    <input type="text" class="form-control" id="tn_tracking_number" maxlength="100">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveTrackingNumber"><i class="fa fa-check"></i> Update &amp; Mark Shipped</button>
            </div>
        </div>
    </div>
</div>

<script>
    function loadTrackingCarrierServices(selectedService) {
        var carrierId = $('#tn_carrier_name').val();
        $('#tn_carrier_service').html('');
        if (!carrierId) {
            return;
        }
        $.ajax({
            url: "{{ route('shipment.getshippingcarrier') }}",
            type: 'POST',
            data: {
                carrier_id: carrierId,
                selected_service: selectedService || '',
                "_token": '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.success) {
                    $('#tn_carrier_service').html(response.option);
                }
            }
        });
    }

    // Looks up the order's open shipment first: the popup only opens when there is one to ship.
    function openTrackingNumberModal(orderId) {
        $.ajax({
            url: "{{ route('order.pendingshipment') }}",
            type: 'POST',
            data: {
                order_id: orderId,
                "_token": '{{ csrf_token() }}'
            },
            success: function (response) {
                if (!response.success) {
                    alert(response.message || 'No open shipment found for this order.');
                    return;
                }
                $('#tn_order_id').val(orderId);
                $('#tn_order_label').text(response.order_number ? '(' + response.order_number + ')' : '');
                $('#trackingNumberError').addClass('d-none').text('');
                $('#tn_tracking_number').val('');
                $('#tn_carrier_name').val(response.carrier_id || '');
                loadTrackingCarrierServices(response.carrier_service_id);
                $('#updateTrackingNumberModal').modal('show');
            },
            error: function () {
                alert('Something went wrong. Please try again.');
            }
        });
    }

    $(document).on('change', '#tn_carrier_name', function () {
        loadTrackingCarrierServices('');
    });

    $(document).on('click', '#saveTrackingNumber', function () {
        var $btn = $(this);
        var $error = $('#trackingNumberError');
        var data = {
            order_id: $('#tn_order_id').val(),
            carrier_name: $('#tn_carrier_name').val(),
            shipping_carrier_service: $('#tn_carrier_service').val(),
            tracking_number: $.trim($('#tn_tracking_number').val()),
            "_token": '{{ csrf_token() }}'
        };

        if (!data.carrier_name || !data.shipping_carrier_service || !data.tracking_number) {
            $error.removeClass('d-none').text('Carrier, carrier service and tracking number are required.');
            return;
        }

        $error.addClass('d-none').text('');
        $btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('order.updatetrackingnumber') }}",
            type: 'POST',
            data: data,
            success: function (response) {
                if (response.success) {
                    $('#updateTrackingNumberModal').modal('hide');
                    alert(response.message);
                    location.reload();
                } else {
                    $error.removeClass('d-none').text(response.message || 'Something went wrong. Please try again.');
                }
            },
            error: function (xhr) {
                var message = 'Something went wrong. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).map(function (e) { return e[0]; }).join(' ');
                }
                $error.removeClass('d-none').text(message);
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
</script>
