@if(count($orderDetail->orderItem ?? []) > 0)
    <tbody>
    @foreach($orderDetail->orderItem as $oitem)
        <tr>
            <td>
                @if($oitem->product->image != '')
                    <div class="main-image">
                        <img
                                src="{{ asset('storage/' .$oitem->product->image->getFileUrl()) }}"
                                {{--                                                        alt="{{ asset('storage/' .$product->image->getFileUrl())}}"--}}
                                loading="lazy" width="50" height="50" class="mainImage"
                                data-product-id="{{ $oitem->product->product_id }}">
                    </div>
                @endif
            </td>
            <td>{{ $oitem->sku }}</td>
            <td>{{ $oitem->quantity }}</td>
            <td>{{ $oitem->product->product_title }}</td>
        </tr>
    @endforeach
    </tbody>
@endif