@if(count($products ?? []) > 0)
    @foreach($products as $product)
        <tr class="selectproRow" id="product-{{ $product->product_id }}">

            <td>
                <div class="d-flex">
                @if(isset($product->image))
                    <img src="{{ asset('storage/' .$product->image->getFileUrl()) }}" alt="{{ asset('storage/' .$product->image->getFileUrl())}}" loading="lazy" width="50" height="50" class="mainImage" data-product-id="{{ $product->product_id }}">
                @endif
                    <input type="checkbox" class="proCheck" name="products[]" value="{{ $product->product_id }}" checked>
                </div>
            </td>
            <td>{{ (isset($product->product_sku)) ? $product->product_sku : '' }}</td>
            <td>{{ $product->productType->name ?? '' }}</td>
            <td>{{ $product->product_title  }}</td>
            <td>{{ $product->product_price }}</td>
            <td>{{ $product->product_qty }}</td>
            <td>{{ (isset($product->pinterface)) ? $product->pinterface->interfaces->name : '' }}</td>
            <td>{{ $product->created_at }}</td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="7"> No products found </td>
    </tr>
@endif
