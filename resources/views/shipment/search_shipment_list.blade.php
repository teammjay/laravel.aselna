@if(count($shipments ?? []) > 0)
    @foreach($shipments as $shipment)
        <tr>

            <td>
                <button type="button" class="btn btn-warning show-detail" data-id="{{ $shipment['oid'] }}">Show Detail</button><input type="checkbox" class="pro-id" name="shipment_id[]" value="{{ $shipment['oid'] }}" style="visibility:hidden;">
            </td>
            <td>
                <div class="d-flex" style="gap:5px">
                @if(!empty($shipment['image']))
                    @foreach($shipment['image'] as $key=>$img)
                        @if($img != '')
                            <div class="main-image">
                                <img src="{{ $img }}" alt="{{ $shipment['imagename'][$key] }}" class="mainImage" width="70" height="70" data-product-id="{{ $shipment['product_id'][$key] }}">
                            </div>
                        @endif
                    @endforeach
                @endif
                </div>
            </td>
            <td>{{ $shipment['status'] }}</td>
            <td>{{ $shipment['carrier_package'] }}</td>
            <td>{{ $shipment['carrier_service'] }}</td>
            <td>{{ $shipment['store'] }}</td>
            <td>{{ $shipment['order_id'] }}</td>
            <td>{{ $shipment['order_date'] }}</td>
            <td>{{ $shipment['expected_shipment_date'] }}</td>
            <td>{{ $shipment['product_code'] }}</td>
            <td>{{ $shipment['weight'] }}</td>
            <td>{{ $shipment['first_name'] }}</td>
            <td>{{ $shipment['last_name'] }}</td>
            <td>{{ $shipment['city'] }}</td>


        </tr>
    @endforeach
@else
    <tr class="text-center">
        <td colspan="12">No shipment found</td>
    </tr>
@endif
