@if(!empty($products))
    @foreach($products as $product)
        <tr id="product-{{ $product->product_id }}">
            <td>
                @if(isset($product->Product->product_image_url) && $product->Product->product_image_url != '')
                    <img src="{{ url('images/' . $product->Product->product_image_url) }}" alt="{{ $product->Product->product_image_url }}" width="70" height="70">
                @endif
            </td>
            <td>{{ (isset($product->Product->product_sku)) ? $product->Product->product_sku : '' }}</td>
            <td>{{ $product->product_type }}</td>
            <td>{{ $product->title }}</td>
            <td>{{ $product->price }}</td>
            <td>{{ $product->product_color }}</td>
            <td>{{ $product->created_at }}</td>

        </tr>
    @endforeach
@else
    <tr>
        <td colspan="7"> No products found </td>
    </tr>
@endif