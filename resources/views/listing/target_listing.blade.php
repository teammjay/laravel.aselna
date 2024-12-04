@if(count($productsArray ?? []) > 0)
    @foreach($productsArray as $product)
        @if(count($product ?? []) > 0)
            @foreach($product as $pro)
                <tr class="listingRow listingproduct-{{ $pro['product_id'] }}">
                    <td></td>
                    <td>{{ $pro['product_code'] }}</td>
                    <td></td>
                    <td>
                        <div class="d-flex align-items-end gap-5">
                            <textarea class="form-control pro-list-title" rows="5" name="listing_product_title[{{ $pro['product_id'] }}]">{{ $pro['title'] }}</textarea>
                            <span class="titleLength">{{ strlen($pro['title']) }} / {{ $max_title_length }}</span>
                        </div>
                    </td>
                </tr>
            @endforeach
        @endif
    @endforeach
@endif