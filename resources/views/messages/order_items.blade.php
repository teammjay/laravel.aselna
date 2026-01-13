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
        <tr>
            <td>
                <div class="btn-group float-right" role="group">
                    <button id="btnGroupDrop2" type="button" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-info-circle"></i></button>
                    <div class="dropdown-menu" aria-labelledby="btnGroupDrop2">
                        <a class="dropdown-item show_attributes" href="javascript:void(0)" data-id="{{ $oitem->id }}">Show Attributes</a>
                        <a class="dropdown-item clickable-row" data-href="{{ $url }}" href="javascript:void(0)">Open Store Page</a>
                    </div>
                </div>
            </td>
            <td>
                @if(isset($oitem->product) && $oitem->product->image != '')
                    <div class="main-image">
                        <img src="{{ asset('storage/' .$oitem->product->image->getFileUrl()) }}"
                                loading="lazy" width="50" height="50" class="mainImage"
                                data-product-id="{{ $oitem->product->product_id }}" style="width: revert-layer;">
                    </div>
                @endif
            </td>
            <td>{{ $oitem->sku }}</td>
            <td>{{ $oitem->quantity }}</td>
            <td style="white-space: break-spaces;">{{ $oitem->product->product_title ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
@endif
