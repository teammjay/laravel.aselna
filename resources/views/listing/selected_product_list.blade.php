@if(count($products ?? []) > 0)
    @foreach($products as $product)
        <tr class="sproRow product-{{ $product->product_id }}">
            <td>
                <input type="hidden" name="selected_product[]" value="{{ $product->product_id }}">
                @if(isset($product->Product->product_image_url) && $product->Product->product_image_url != '')
                    <img src="{{ url('images/' . $product->Product->product_image_url) }}" alt="{{ $product->Product->product_image_url }}" width="70" height="70">
                @endif
            </td>
            <td>{{ (isset($product->Product->product_sku)) ? $product->Product->product_sku : '' }}</td>
            <td>
                <div class="d-flex align-items-end gap-5">
                    <textarea class="form-control title-{{ $product->product_id }}" name="prodcut_title[{{ $product->product_id }}]" style="width:200px;">{{ $product->title }}</textarea>
                    <span class="tLength-{{ $product->product_id }}">{{ strlen($product->title) }}</span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-end gap-5">
                    <textarea class="form-control shortKey-{{ $product->product_id }}" name="prodcut_short_keyword[{{ $product->product_id }}]" style="width:300px;"></textarea>
                    <span class="sLength-{{ $product->product_id }}"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-end gap-5">
                    <textarea class="form-control longKey-{{ $product->product_id }}" name="prodcut_long_keyword[{{ $product->product_id }}]" style="width:300px;"></textarea>
                    <span class="lLength-{{ $product->product_id }}"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center gap-5">
                    <input type="checkbox" class="price-overwrite" name="price_overwrite[{{ $product->product_id }}]">
                    <input class="form-control" type="text" name="prodcut_price[{{ $product->product_id }}]" value="{{ $product->price }}" readonly="" style="width:150px;">
                </div>
            </td>
            <td><input type="checkbox" name="product_status[{{ $product->product_id }}]" checked ></td>
            <td></td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="7"> No products found </td>
    </tr>
@endif