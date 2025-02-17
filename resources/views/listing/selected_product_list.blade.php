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
                <div class="d-flex align-items-end gap-5">
                    <textarea class="form-control pro-title title-{{ $product->product_id }}" name="product_title[{{ $product->product_id }}]" style="width:200px;">{{ ($product->title != '') ? $product->title : $product->Product->product_title }}</textarea>
                    <span class="tLength-{{ $product->product_id }}">{{ ($product->title != '') ? strlen($product->title) : strlen($product->Product->product_title) }}</span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-end gap-5">
                    <textarea class="form-control pro-shortkey shortKey-{{ $product->product_id }}" name="prodcut_short_keyword[{{ $product->product_id }}]" style="width:250px;"></textarea>
                    <span class="sLength-{{ $product->product_id }}"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-end gap-5">
                    <textarea class="form-control pro-longkey longKey-{{ $product->product_id }}" name="prodcut_long_keyword[{{ $product->product_id }}]" style="width:250px;"></textarea>
                    <span class="lLength-{{ $product->product_id }}"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center gap-5">
                    <input type="checkbox" class="price-overwrite po-{{ $product->product_id }}" name="price_overwrite[{{ $product->product_id }}]">
                    <input class="form-control price-{{ $product->product_id }}" type="text" name="prodcut_price[{{ $product->product_id }}]" value="{{ ($product->price != '') ? $product->price : $product->Product->product_price }}" readonly="" style="width:150px;">
                </div>
            </td>
            <td><input type="checkbox" name="product_status[{{ $product->product_id }}]" checked ></td>
            <td>
                <select class="form-control store-type-category stc-{{ $product->product_id }}" data-spid="{{ $product->id }}" name="store_type_category_id[{{ $product->product_id }}]" style="width: 200px;">
                    <option value=""></option>
                    @if(count($storeCategories ?? []) > 0)
                        @foreach($storeCategories as $key2=>$sCat)
                            <option value="{{ $sCat->id }}" {{ ($sCat->id == $product->store_type_category_id) ? 'selected' : '' }}>{{ $sCat->name }}</option>
                        @endforeach
                    @endif
                </select>
            </td>

        </tr>
    @endforeach
@else
    <tr align="center">
        <td colspan="7"> No products found </td>
    </tr>
@endif
