@if(!empty($products))
    @foreach($products as $product)
        <tr id="product-{{ $product->product_id }}">
            <td>{{ $product->product_id }}</td>
            <td>
                @if($product->product_image_url != '')
                    <img src="{{ asset('storage/product_images/'. $product->product_image_url)  }}" alt="{{ $product->product_image_url }}" width="70" height="70">
                @endif
            </td>
            <td>{{ $product->product_sku }}</td>
            <td>{{ $product->product_type }}</td>
            <td>{{ $product->product_title }}</td>
            <td>{{ $product->product_status }}</td>
            <td align="center"><input type="checkbox" class="configurable" data-id="{{ $product->product_id }}" {{ ($product->configurable == 1) ? 'checked' : '' }}></td>
            <td>{{ $product->product_price }}</td>
            <td>{{ isset($product->Manufacturer->name) ? $product->Manufacturer->name : '' }}</td>
            <td>{{ $product->product_color }}</td>
            <td>{{ $product->created_at }}</td>
            <td>
                <a href="{{ route('catalog.edit', $product->product_id) }}" class="btn btn-primary">
                    <i class="fas fa-fw fa-pencil-alt"></i>
                </a>
                <button class="btn btn-danger delete-btn" data-id="{{ $product->product_id }}"> <i class="fas fa-fw fa-trash"></i></button>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="12"> No products found </td>
    </tr>
@endif
