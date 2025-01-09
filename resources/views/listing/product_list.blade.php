@if(count($products ?? []) > 0)
    @foreach($products as $product)
        <tr>
            <td style="white-space: nowrap;">{{ $product->sku }}</td>
            <td>{{ ($product->sale_count) ? $product->sale_count : 0 }}</td>
            <td></td>
            <td style="white-space: nowrap;">{{ $product->store_sku }}</td>
            <td>{{ $product->product_sku }}</td>
            <td style="white-space: nowrap;">{{ $product->store_name.' - '.$product->storeTypeName }}</td>
            <td  style="white-space: nowrap;">{{ strlen($product->title) > 50 ? substr($product->title, 0, 50) . "..." : $product->title }}</td>
            <td>{{ ($product->status == 1) ? 'Active' : 'Inactive' }}</td>
            <td></td>
            <td>{{ $product->product_color }}</td>
            <td></td>
            <td style="white-space: nowrap;">{{ $product->generation_type_name }}</td>
            <td>{{ $product->price }}</td>
            <td>{{ $product->quantity }}</td>
            <td>
                <a href="{{ route('listing.edit', $product->id) }}" class="btn btn-primary">
                    <i class="fas fa-fw fa-pencil-alt"></i>
                </a>
                {{--<button class="btn btn-danger delete-btn" data-id="{{ $product->id }}"> <i class="fas fa-fw fa-trash"></i></button>--}}
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="14" align="center"> No products found </td>
    </tr>
@endif