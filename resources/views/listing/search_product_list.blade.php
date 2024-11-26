@if(count($products ?? []) > 0)
    @foreach($products as $product)
        <tr class="sproRow" id="product-{{ $product->product_id }}">
            <td>
                <input type="checkbox" class="proCheck" name="products[]" value="{{ $product->id }}" checked>
            </td>
            <td>
                @if(isset($product->Product->product_image_url) && $product->Product->product_image_url != '')
                    <img src="{{ url('images/' . $product->Product->product_image_url) }}" alt="{{ $product->Product->product_image_url }}" width="70" height="70">
                @endif
            </td>
            <td>{{ (isset($product->Product->product_sku)) ? $product->Product->product_sku : '' }}</td>
            <td>{{ $product->ProductType }}</td>
            <td>{{ $product->title }}</td>
            <td>{{ $product->price }}</td>
            <td>{{ $product->Product->product_qty }}</td>
            <td>{{ $product->created_at }}</td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="7"> No products found </td>
    </tr>
@endif