@if(count($products ?? []) > 0)
    @foreach($products as $product)
        <tr class="sproRow product-{{ $product->product_id }}">
            <td>
                <input type="hidden" name="selected_store_product[]" value="{{ $product->id }}">
                <input type="hidden" class="pid-{{ $product->id }}" name="selected_product[]" value="{{ $product->product_id }}">
                @if(isset($product->Product->image))
                    <img src="{{ asset('storage/' .$product->Product->image->getFileUrl()) }}" alt="{{ asset('storage/' .$product->Product->image->getFileUrl()) }}" width="70" height="70">
                @endif
            </td>
            <td>{{ (isset($product->Product->product_sku)) ? $product->Product->product_sku : '' }}</td>
            <td>
                <div class="d-flex align-items-end flex-column gap-1">
                    <textarea class="form-control pro-title title-{{ $product->product_id }}" data-id="{{ $product->product_id }}" name="product_title[{{ $product->product_id }}]" style="min-width:200px;">{{ ($product->title != '') ? $product->title : $product->Product->product_title }}</textarea>
                    <span class="tLength-{{ $product->product_id }}">{{ ($product->title != '') ? strlen($product->title) : strlen($product->Product->product_title) }}</span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-end flex-column gap-1">
                    <textarea class="form-control pro-shortkey shortKey-{{ $product->product_id }}" data-id="{{ $product->product_id }}" name="prodcut_short_keyword[{{ $product->product_id }}]" style="min-width:250px;"></textarea>
                    <span class="sLength-{{ $product->product_id }}"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-end flex-column gap-1">
                    <textarea class="form-control pro-longkey longKey-{{ $product->product_id }}" data-id="{{ $product->product_id }}" name="prodcut_long_keyword[{{ $product->product_id }}]" style="min-width:250px;"></textarea>
                    <span class="lLength-{{ $product->product_id }}"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center gap-5">
                    <input type="checkbox" class="price-overwrite po-{{ $product->product_id }}" name="price_overwrite[{{ $product->product_id }}]">
                    <input class="form-control price-{{ $product->product_id }}" type="text" name="prodcut_price[{{ $product->product_id }}]" value="{{ ($product->price != '') ? $product->price : $product->Product->product_marketplace_price }}" readonly="" style="width:150px;">
                </div>
            </td>
            <td><input type="checkbox" name="product_status[{{ $product->product_id }}]" checked ></td>
            <td>
                <select class="form-control store-type-category stc-{{ $product->product_id }}" data-spid="{{ $product->id }}" name="store_type_category_id[{{ $product->product_id }}]" style="width: 200px;" data-store-type-name="{{ $storeTypeName->storeTypeName }}">
                    <option value=""></option>
                    @if(count($storeCategories ?? []) > 0)
                        @foreach($storeCategories as $key2=>$sCat)
                            @if($storeTypeName->storeTypeName === 'Magento')
                                <option value="{{ $sCat->id }}" {{ ($sCat->id == $product->store_category_id) ? 'selected' : '' }}>{{ $sCat->name }}</option>
                            @else
                                <option value="{{ $sCat->id }}" {{ ($sCat->id == $product->store_type_category_id) ? 'selected' : '' }}>{{ $sCat->name }}</option>
                            @endif
                        @endforeach
                    @endif
                </select>
            </td>
            <td>
                {{count($issues[$product->product_id] ?? [])}} Errors
            </td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="7"> No products found </td>
    </tr>
@endif
