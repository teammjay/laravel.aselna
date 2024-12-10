@if(count($productsArray ?? []) > 0)
    @foreach($productsArray as $product)
        @if(count($product ?? []) > 0)
            @foreach($product as $key=>$pro)
                <tr class="listingRow listingproduct-{{ $pro['product_id'] }}" data-key="{{ $key }}">
                    <td></td>
                    <td>
                        <input type="hidden" name="final_product_id[{{ $key }}]" value="{{ $pro['product_id'] }}" style="visibility: hidden;">
                        <input type="hidden" name="final_product_code[{{ $key }}]" value="{{ $pro['product_code'] }}">
                        <input type="hidden" name="final_product_price[{{ $key }}]" value="{{ $pro['price'] }}">
                        {{ $pro['product_code'] }}</td>
                    <td></td>
                    <td>
                        <div class="d-flex align-items-end gap-5">
                            <textarea class="form-control pro-list-title" rows="5" name="final_product_title[{{ $key }}]">{{ $pro['title'] }}</textarea>
                            <span class="titleLength">{{ strlen($pro['title']) }} / {{ $max_title_length }}</span>
                        </div>
                    </td>
                </tr>
            @endforeach
        @endif
    @endforeach
@endif