@if(count($products ?? []) > 0)
    @foreach($products as $product)
        <tr class="sprolistRow product-list-{{ $product->product_id }}">
            <td>
                <input type="hidden" name="product_title_template[]" >
                @if(isset($product->Product) && isset($product->Product->image))
                    <img src="{{ asset('storage/' . $product->Product->image->getFileUrl()) }}" alt="{{ asset('storage/' . $product->Product->image->getFileUrl()) }}" width="70" height="70">
                @endif
                <input type="checkbox" class="pro-id" style="visibility: hidden;" name="selected_list_product[]" value="{{ $product->product_id }}">
            </td>
            <td>{{ (isset($product->Product->product_sku)) ? $product->Product->product_sku : '' }}</td>
            <td>
                {{ (isset($productDeviceCounts[$product->product_id])) ? $productDeviceCounts[$product->product_id] : 0 }}
            </td>
            <td>0</td>
            <td></td>
            <td>{{ isset($product->total_alt_images) ? $product->total_alt_images : 0 }}</td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="6"> No products found </td>
    </tr>
@endif
