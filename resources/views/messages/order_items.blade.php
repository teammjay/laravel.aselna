@if(count($orderDetail->orderItem ?? []) > 0)
    <tbody>
    @foreach($orderDetail->orderItem as $oitem)
        @php
            $url = '';
            if(isset($oitem->listingItem) && !empty($oitem->listingItem)){
                $store_sku = $oitem->listingItem->store_sku;
                $store_type = isset($orderDetail->store->storeType->name) ? $orderDetail->store->storeType->name : '';
                $site_url = isset($orderDetail->store->store_url) ? $orderDetail->store->store_url : '';
                $store_url = isset($oitem->listingItem->store_url) ? $oitem->listingItem->store_url : '';
                if($store_sku != ''){
                    $url = \App\Utils\ListingUtility::generateItemUrl($store_type, $store_sku, $site_url, $store_url);
                }
            }
        @endphp
        <tr class="clickable-row" data-href="{{ $url }}">
            <td>
                @if($oitem->product->image != '')
                    <div class="main-image">
                        <img src="{{ asset('storage/' .$oitem->product->image->getFileUrl()) }}"
                                loading="lazy" width="50" height="50" class="mainImage"
                                data-product-id="{{ $oitem->product->product_id }}" style="width: revert-layer;">
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