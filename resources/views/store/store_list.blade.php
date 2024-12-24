@if(count($stores ?? []) > 0)
    @foreach($stores as $store)
        <tr id="store-{{ $store->id }}">

            {{--<td>{{ $store->id }}</td>--}}
            <td>{{ $store->marketplace_name }}</td>
            <td>{{ $store->name }}</td>
            <td>{{ ($store->storeType()->count() > 0) ? $store->storeType->name : '' }}</td>
            <td>{{ $store->listing_type }}</td>
            <td>{{ ($store->store_status == 1) ? 'Active' : ''}}</td>
            <td align="center"><input type="checkbox" class="authorize" data-id="{{ $store->id }}" {{ ($store->authorized == 1) ? 'checked' : '' }}> </td>
            <td>{{ $store->short_code }}</td>
            <td>{{ $store->upc_usage_type }}</td>
            <td align="center"><input type="checkbox" class="ad_account" data-id="{{ $store->id }}" {{ ($store->ad_account == 1) ? 'checked' : '' }}> </td>
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
        <td colspan="10" align="center"> No stores found </td>
    </tr>
@endif
