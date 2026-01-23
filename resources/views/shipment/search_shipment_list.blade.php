@if(count($shipments ?? []) > 0)
    @foreach($shipments as $shipment)
        @php
            $moreQty = 0;
            if(isset($shipment['total_qty']) && is_array($shipment['total_qty'])){
                foreach($shipment['total_qty'] as $tqty){
                    if($tqty > 5){
                        $moreQty = 1;
                    }
                }
            }
        @endphp
        <tr data-id="{{ $shipment['oid'] }}" data-status="{{ $shipment['status'] }}" class="shipment-row {{ ($moreQty > 0 || $shipment['status'] == 1) ? 'bg-red' : '' }} {{ ($shipment['status'] == 2) ? 'bg-warning' : '' }} {{ ($shipment['op_status'] == 3) ? 'bg-gray' : '' }} {{  ($shipment['carrier_service_name'] == 'Priority' || $shipment['carrier_service_name'] == 'Priority Express') ? 'bg-purple' : '' }}">

            {{--<td>
                <button type="button" class="btn btn-warning show-detail" data-id="{{ $shipment['oid'] }}">Show Detail</button><input type="checkbox" class="pro-id" name="shipment_id[]" value="{{ $shipment['oid'] }}" style="visibility:hidden;">
            </td>--}}
            <td>
                <input type="checkbox" class="pro-id" name="shipment_id[]" data-status="{{ $shipment['status'] }}" value="{{ $shipment['oid'] }}" style="visibility:hidden;">
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
            @if(isset($shipment['operationstatus']))
                <td>{{ $shipment['operationstatus'] }}</td>
            @elseif($shipment['status'] == 1)
                <td>{!! ($shipment['status'] == 1) ? '<i class="fa fa-exclamation-triangle" style="font-size:24px" title="'.$shipment['status_message'].'"></i>' : '' !!}</td>
            @elseif($shipment['status'] == 2)
                <td>{!! ($shipment['status'] == 2) ? '<i class="fa fa-exclamation-triangle" style="font-size:24px" title="'.$shipment['status_message'].'"></i>' : '' !!}</td>
            @elseif($shipment['status'] == 3)
                <td>{!! ($shipment['status'] == 3) ? '<i class="fa fa-exclamation-triangle" style="font-size:24px" title="'.$shipment['status_message'].'"></i><button class="btn bg-white btn-circle" title="Resubmit Count">'.$shipment['operation_retry_count'].'</button>' : '' !!}</td>
            @else
                <td></td>
            @endif
            <td>{{ $shipment['carrier_package'] }}</td>
            <td>{{ $shipment['carrier_service'] }}</td>
            <td>{{ $shipment['store'] }}</td>
            <td>{!! $shipment['purchase_order_id'] !!}</td>
            <td>{{ $shipment['order_date'] }}</td>
            <td>{{ $shipment['product_code'] }}</td>
            <td>{{ $shipment['weight'] }}</td>
            <td>{!! $shipment['order_id'] !!}</td>
            <td>{{ $shipment['expected_shipment_date'] }}</td>
            <td>{{ $shipment['first_name'] }}</td>
            <td>{{ $shipment['last_name'] }}</td>
            <td>{{ $shipment['state'] }}</td>


        </tr>
    @endforeach
@else
    <tr class="text-center">
        <td colspan="14">No shipment found</td>
    </tr>
@endif
