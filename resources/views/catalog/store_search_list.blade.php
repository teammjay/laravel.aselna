@if(count($store_details ?? []) > 0)
    @foreach($store_details as $key=>$store)
        <tr class="store_row" id="store_{{ $store['store_detail']->id }}" data-id="{{ $store['store_detail']->id }}">
            <td>{{ $store['store_detail']->name }} {{ (!empty($store['store_detail']->storeType)) ? ' - '.$store['store_detail']->storeType->name : ''}}</td>
            <td align="center">
                <input type="checkbox" name="active" {{ ((isset($store['store_product']) && $store['store_product']->active == 1)) ? 'checked' : '' }} />
                <input type="hidden" name="store_id" value="{{ $store['store_detail']->id }}">
            </td>
            <td>
                <select class="form-control" name="store_type_category_id">
                    <option value=""></option>
                    @if(count($store['store_category'] ?? []) > 0)
                        @foreach($store['store_category'] as $key2=>$sCat)
                            <option value="{{ $sCat->id }}" {{ (isset($productStore[$sCat->id]) && $productStore[$sCat->id] == $store['store_detail']->id ) ? 'selected' : '' }}>{{ $sCat->name }}</option>
                        @endforeach
                    @endif
                </select>
            </td>
        </tr>
    @endforeach
@endif