@if(count($devices ?? []) > 0)
    @foreach($devices as $device)
        <tr class="targetRow product-{{ $device->product_id }} {{ (isset($selected_device[$device->product_id]) && is_array($selected_device[$device->product_id]) && in_array($device->id,$selected_device[$device->product_id])) ? 'bg-blue' : '' }}" >
            <td>
                @if($is_selected)
                    <input type="checkbox" class="sel-device" name="listing_product_id[]" style="visibility: hidden;" data-proid="{{ $device->product_id }}" value="{{ $device->id }}" {{ (isset($selected_device[$device->product_id]) && is_array($selected_device[$device->product_id]) && in_array($device->id,$selected_device[$device->product_id])) ? 'checked' : '' }}>
                @else
                    <input type="checkbox" class="sel-device" name="listing_product_id[]" style="visibility: hidden;" data-proid="{{ $device->product_id }}" value="{{ $device->id }}" checked>
                @endif
            </td>
            <td>{{$device->carrier_name}}</td>
            <td>{{ $device->product_sku }}</td>
            <td>{{ (isset($device->brand->name)) ? $device->brand->name : '' }}</td>
            <td>{{ $device->deviceType->name }}</td>
            <td>{{ $device->name }}</td>
            <td>0</td>
            <td>0</td>
            <td>{{ (isset($device->rating)) ? $device->rating->value : '' }}</td>
            <td></td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="9"> No products found </td>
    </tr>
@endif
