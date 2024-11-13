@if(!empty($stores))
    @foreach($stores as $store)
        <tr id="store-{{ $store->id }}">

            <td>{{ $store->id }}</td>
            <td>{{ $store->marketplace_name }}</td>
            <td>{{ $store->name }}</td>
            <td>{{ $store->store_type_id }}</td>
            <td>{{ $store->listing_type }}</td>
            <td></td>
            <td>{{ $store->short_code }}</td>
            <td>{{ $store->upc_usage_type }}</td>
            <td>
                <a href="{{ route('store.edit', $store->id) }}" class="btn btn-primary" title="Edit">
                    <i class="fas fa-fw fa-pencil-alt"></i>
                </a>
                <button class="btn btn-danger delete-btn" data-id="{{ $store->id }}" title="Remove"> <i class="fas fa-fw fa-trash"></i></button>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="7"> No stores found </td>
    </tr>
@endif