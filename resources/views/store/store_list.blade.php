@if(count($stores ?? []) > 0)
    @foreach($stores as $store)
        @php
            switch($store->upc_usage_type){
                case '0':
                    $upc_type = 'None';
                    break;
                case '1':
                    $upc_type = 'Storeownupc';
                    break;
                case '2':
                    $upc_type = 'Upcpool';
                    break;
                case '3':
                    $upc_type = 'Product';
                    break;
                default:
                    $upc_type = 'None';
                    break;
            }
        @endphp
        <tr id="store-{{ $store->id }}">

            {{--<td>{{ $store->id }}</td>--}}
            <td>{{ $store->name }}</td>
            <td>{{ $store->marketplace_name }}</td>
            <td>{{ ($store->storeType()->count() > 0) ? $store->storeType->name : '' }}</td>
            <td>{{ $store->listing_type }}</td>
            <td>{{ ($store->store_status == 1) ? 'Active' : ''}}</td>
            <td align="center"><input type="checkbox" class="authorize" data-id="{{ $store->id }}" {{ ($store->authorized == 1) ? 'checked' : '' }}> </td>
            <td>{{ $store->short_code }}</td>
            <td>{{ $upc_type }}</td>
            <td align="center"><input type="checkbox" class="ad_account" data-id="{{ $store->id }}" {{ ($store->ad_account == 1) ? 'checked' : '' }}> </td>
            <td class="d-flex" style="gap:10px;">
                <a href="{{ route('store.edit', $store->id) }}" class="btn btn-primary" title="Edit">
                    <i class="fas fa-fw fa-pencil-alt"></i>
                </a>
                <button type="button" class="btn btn-danger delete-btn" data-module="stores" data-id="{{ $store->id }}" title="Remove"> <i class="fas fa-fw fa-trash"></i></button>
                <a href="{{ route('store.authorize', $store->id) }}" class="btn btn-secondary" title="Authorize">Authorize</a>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="10" align="center"> No stores found </td>
    </tr>
@endif
