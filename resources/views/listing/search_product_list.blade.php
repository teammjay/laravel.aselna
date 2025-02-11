@if(count($products ?? []) > 0)
    @foreach($products as $product)

        <tr class="selectproRow" id="product-{{ $product->product_id }}">

            <td>
                <div class="d-flex">
                @if(isset($product->Product->image))
                    <img src="{{ asset('storage/' .$product->Product->image->getFileUrl()) }}" alt="{{ asset('storage/' .$product->Product->image->getFileUrl())}}" loading="lazy" width="50" height="50" class="mainImage" data-product-id="{{ $product->Product->product_id }}">
                @endif
                    <input type="checkbox" class="proCheck" name="products[]" value="{{ $product->id }}" checked>
                </div>
            </td>
            <td>{{ (isset($product->Product->product_sku)) ? $product->Product->product_sku : '' }}</td>
            <td>{{ $product->ProductType }}</td>
            <td>{{ ($product->title != '') ? $product->title : $product->product->product_title }}</td>
            <td>{{ ($product->price != '') ? $product->price : $product->product->product_price }}</td>
            <td>{{ $product->Product->product_qty }}</td>
            <td>{{ $product->created_at }}</td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="7"> No products found </td>
    </tr>
@endif
