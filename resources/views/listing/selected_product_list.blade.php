<table id="productsTable2" class="table table-bordered table-striped">
    <thead>
    <tr>
        <th>Image</th>
        <th>Product Code</th>
        <th>Title</th>
        <th>Short Tail Keyword</th>
        <th>Long Tail Keyword</th>
        <th>Price <span class="float-right"><input type="checkbox" id="select_all_pricebox"> Select All</span></th>
        <th>Active</th>
        <th>Channel Category</th>
        <th>Errors</th>
    </tr>
    </thead>
    <tbody id="selected_product_list">
    @if(count($products ?? []) > 0)
        @foreach($products as $product)
            <tr class="sproRow product-{{ $product->product_id }}">
                <td>
                    <input type="hidden" name="selected_store_product[]" value="{{ $product->id }}">
                    <input type="hidden" class="pid-{{ $product->id }}" name="selected_product[]"
                           value="{{ $product->product_id }}">
                    @if(isset($product->Product->image))
                        <img src="{{ asset('storage/' .$product->Product->image->getFileUrl()) }}"
                             alt="{{ asset('storage/' .$product->Product->image->getFileUrl()) }}" width="70"
                             height="70">
                    @endif
                </td>
                <td>{{ (isset($product->Product->product_sku)) ? $product->Product->product_sku : '' }}</td>
                <td>
                    <div class="d-flex align-items-end flex-column gap-1">
                        <textarea class="form-control pro-title title-{{ $product->product_id }}"
                                  data-id="{{ $product->product_id }}" name="product_title[{{ $product->product_id }}]"
                                  style="min-width:200px;">{{ ($product->title != '') ? $product->title : $product->Product->product_title }}</textarea>
                        <span
                            class="tLength-{{ $product->product_id }}">{{ ($product->title != '') ? strlen($product->title) : strlen($product->Product->product_title) }}</span>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-end flex-column gap-1">
                        <textarea class="form-control pro-shortkey shortKey-{{ $product->product_id }}"
                                  data-id="{{ $product->product_id }}"
                                  name="prodcut_short_keyword[{{ $product->product_id }}]"
                                  style="min-width:250px;"></textarea>
                        <span class="sLength-{{ $product->product_id }}"></span>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-end flex-column gap-1">
                        <textarea class="form-control pro-longkey longKey-{{ $product->product_id }}"
                                  data-id="{{ $product->product_id }}"
                                  name="prodcut_long_keyword[{{ $product->product_id }}]"
                                  style="min-width:250px;"></textarea>
                        <span class="lLength-{{ $product->product_id }}"></span>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-5">
                        <input type="checkbox" class="price-overwrite po-{{ $product->product_id }}"
                               name="price_overwrite[{{ $product->product_id }}]">
                        <input class="form-control pro-price price-{{ $product->product_id }}" type="text"
                               name="prodcut_price[{{ $product->product_id }}]"
                               value="{{ ($product->price != '' && $product->overwrite_price == 1) ? $product->price : $product->Product->product_marketplace_price }}"
                               readonly="" style="width:150px;">
                    </div>
                </td>
                <td><input type="checkbox" name="product_status[{{ $product->product_id }}]" checked></td>
                <td>
                    <select class="form-control store-type-category stc-{{ $product->product_id }}"
                            data-spid="{{ $product->id }}" data-pid="{{ $product->product_id }}" name="store_type_category_id[{{ $product->product_id }}]"
                            style="width: 200px;" data-store-type-name="{{ $storeTypeName->storeTypeName }}">
                        <option value=""></option>
                        @if(count($storeCategories ?? []) > 0)
                            @foreach($storeCategories as $key2=>$sCat)
                                @if($storeTypeName->storeTypeName === 'Magento')
                                    <option
                                        value="{{ $sCat->id }}" {{ ($sCat->id == $product->store_category_id) ? 'selected' : '' }}>{{ $sCat->name }}</option>
                                @else
                                    <option
                                        value="{{ $sCat->id }}" {{ ($sCat->id == $product->store_type_category_id) ? 'selected' : '' }}>{{ $sCat->name }}</option>
                                @endif
                            @endforeach
                        @endif
                    </select>
                </td>
                <td>
                    <div class="d-flex align-items-center flex-column gap-2">
                        <button type="button"
                                id="errorButton-{{$product->product_id}}"
                                class="btn btn-sm no_of_errors {{count($issues[$product->product_id] ?? []) > 0 ? 'btn-danger' : 'btn-success' }}"
                                data-bs-toggle="modal"
                                data-error="{{ count($issues[$product->product_id] ?? []) }}"
                                data-bs-target="#errorModal-{{ $product->product_id }}">
                            {{ count($issues[$product->product_id] ?? []) }} Errors
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary refreshErrorsBtn"
                                id="refreshErrorsBtn-{{ $product->product_id }}"
                                style="{{ count($issues[$product->product_id] ?? []) > 0 ? '' : 'display:none;' }}"
                                data-pid="{{ $product->product_id }}" title="Refresh Errors">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </td>

            </tr>
        @endforeach
    @else
        <tr align="center">
            <td colspan="7"> No products found</td>
        </tr>
    @endif
    </tbody>
</table>

@foreach($products as $product)
    <!-- Modal for displaying errors -->
    <div class="modal fade errorModal" id="errorModal-{{ $product->product_id }}" tabindex="-1"
         aria-labelledby="errorModalLabel-{{ $product->product_id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel-{{ $product->product_id }}">Errors for Product
                        : {{ $product->product_title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if(!empty($issues[$product->product_id]))
                        <!-- Call the error_tables.blade.php file to display the errors -->
                        @include('listing.error_tables', ['issues' => $issues[$product->product_id], 'product' => $product])
                    @else
                        <p>No errors found.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
