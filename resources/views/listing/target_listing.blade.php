@if(count($productsArray ?? []) > 0)
    @foreach($productsArray as $product)
        @if(count($product ?? []) > 0)
            @foreach($product as $key=>$pro)
                @if(isset($pro['product_id']))
                    <tr class="listingRow listingproduct-{{ $pro['product_id'] }}" data-proid="{{ $pro['product_id'] }}" data-key="{{ $key }}">
                        <td>
                            @if(isset($pro['pimage']) && $pro['pimage'] !='')
                                <img src="{{ $pro['pimage'] }}" alt="{{ $pro['pimage'] }}" width="70" height="70">
                            @endif
                        </td>
                        <td>
                            {{--<input type="hidden" name="generate_type[]" value="{{ $pro['generate_type'] }}">--}}
                            <input type="hidden" name="final_store_product_id[]" value="{{ $pro['store_product_id'] }}">
                            <input type="hidden" name="final_product_id[]" value="{{ $pro['product_id'] }}">
                            <input type="hidden" name="final_brand_id[]" value="{{ $pro['brand_id'] }}">
                            <input type="hidden" name="final_device_id[]" value="{{ $pro['device_id'] }}">
                            <input type="hidden" name="final_product_code[]" value="{{ $pro['product_code'] }}">
                            <input type="hidden" name="final_product_price[]" value="{{ $pro['price'] }}">
                            <input type="hidden" name="final_product_price_overwrite[]" value="{{ $pro['price_overwrite'] }}">
                            {{--<input type="hidden" name="final_store_type_category_id[]" value="{{ $pro['store_type_category_id'] }}">--}}
                            {{ $pro['product_code'] }}</td>
                        <td></td>
                        <td>
                            <div class="d-flex align-items-end gap-5">
                                <textarea class="form-control pro-list-title" rows="5" name="final_product_title[]">{{ $pro['title'] ?? '' }}</textarea>
                                @if(strlen($pro['title']) > $max_title_length)
                                    <span class="titleLength error" data-length="{{ strlen($pro['title'] ?? '') }}" data-maxlength="{{ $max_title_length }}"><i class="fa fa-ban"></i> {{ strlen($pro['title'] ?? '') }} / {{ $max_title_length }}</span>
                                @else
                                    <span class="titleLength success" data-length="{{ strlen($pro['title'] ?? '') }}" data-maxlength="{{ $max_title_length }}"><i class="fa fa-check-circle"></i> {{ strlen($pro['title'] ?? '') }} / {{ $max_title_length }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endif
            @endforeach
        @endif
    @endforeach
@endif
