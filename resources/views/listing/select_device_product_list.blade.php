@if(count($products ?? []) > 0)
    @foreach($products as $product)
        <tr class="sprolistRow product-list-{{ $product->product_id }}">
            <td>
                <input type="hidden" class="pro-id" name="selected_list_product[]" value="{{ $product->product_id }}">
                @if(isset($product->Product->product_image_url) && $product->Product->product_image_url != '')
                    <img src="{{ url('images/' . $product->Product->product_image_url) }}" alt="{{ $product->Product->product_image_url }}" width="70" height="70">
                @endif
            </td>
            <td>{{ (isset($product->Product->product_sku)) ? $product->Product->product_sku : '' }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="6"> No products found </td>
    </tr>
@endif