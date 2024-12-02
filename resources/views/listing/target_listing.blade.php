@if(count($devices ?? []) > 0)
    @foreach($devices as $device)
        <tr class="sproRow product-{{ $device->product_id }}">
            <td></td>
            <td></td>
            <td>{{ (isset($device->brand->name)) ? $device->brand->name : '' }}</td>
            <td>{{ $device->deviceType->name }}</td>
            <td>{{ $device->name }}</td>
            <td>0</td>
            <td>0</td>
            <td>{{ $device->rating_id }}</td>
            <td></td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="9"> No products found </td>
    </tr>
@endif