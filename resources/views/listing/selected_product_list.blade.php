@if(count($products ?? []) > 0)
    @foreach($products as $product)
        <tr class="sproRow" id="product-{{ $product->product_id }}">
            <td>
                @if(isset($product->Product->product_image_url) && $product->Product->product_image_url != '')
                    <img src="{{ url('images/' . $product->Product->product_image_url) }}" alt="{{ $product->Product->product_image_url }}" width="70" height="70">
                @endif
            </td>
            <td>{{ (isset($product->Product->product_sku)) ? $product->Product->product_sku : '' }}</td>
            <td><textarea class="form-control" name="prodcut_title[{{ $product->product_id }}]">{{ $product->title }}</textarea><span>{{ strlen($product->title) }}</span></td>
            <td><textarea class="form-control" name="prodcut_short_keyword[{{ $product->product_id }}]"></textarea></td>
            <td><textarea class="form-control" name="prodcut_long_keyword[{{ $product->product_id }}]"></textarea></td>
            <td><input class="form-control" type="text" name="prodcut_price[{{ $product->product_id }}]" value="{{ $product->price }}" readonly=""></td>
            <td><input type="checkbox" name="product_status[{{ $product->product_id }}]" checked ></td>
            <td></td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="7"> No products found </td>
    </tr>
@endif