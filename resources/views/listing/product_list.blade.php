@if(!empty($products))
    @foreach($products as $product)
        <tr>
            <td>{{ $product->sku }}</td>
            <td></td>
            <td></td>
            <td>{{ $product->store_id }}</td>
            <td>{{ $product->product->product_sku }}</td>
            <td></td>
            <td>{{ $product->product_name }}</td>
            <td>{{ ($product->status == 1) ? 'Active' : 'Inactive' }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td>{{ $product->price }}</td>
            <td></td>
            <td>
                <a href="{{ route('catalog.edit', $product->id) }}" class="btn btn-primary">
                    <i class="fas fa-fw fa-pencil-alt"></i>
                </a>
                <button class="btn btn-danger delete-btn" data-id="{{ $product->id }}"> <i class="fas fa-fw fa-trash"></i></button>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="7"> No products found </td>
    </tr>
@endif