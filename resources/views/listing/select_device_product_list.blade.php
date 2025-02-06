@if(count($products ?? []) > 0)
    @foreach($products as $product)
        <tr class="sprolistRow product-list-{{ $product->product_id }}">
            <td>
                <input type="hidden" name="product_title_template[]" >
                <input type="checkbox" class="pro-id" style="visibility: hidden;" name="selected_list_product[]" value="{{ $product->product_id }}">
                @if(isset($product->Product))
                    <img src="{{ asset('storage/' . $product->Product->image->getFileUrl()) }}" alt="{{ asset('storage/' . $product->Product->image->getFileUrl()) }}" width="70" height="70">
                @endif
            </td>
            <td>{{ (isset($product->Product->product_sku)) ? $product->Product->product_sku : '' }}</td>
            <td>0</td>
            <td>0</td>
            <td></td>
            <td>0</td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="6"> No products found </td>
    </tr>
@endif
