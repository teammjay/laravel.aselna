<div class="row pt-2">
    <div class="col-md-12">
        <table id="listingTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Image</th>
                <th>Code</th>
                <th>SKU</th>
                <th>Title</th>
                <th>Model Name</th>
            </tr>
            </thead>
            <tbody id="listing-summary">
                @if(count($summary_products ?? []) > 0)
                    @foreach($summary_products as $product)
                        <tr>
                            <td></td>
                            <td>{{ $product['product_code'] }}</td>
                            <td>{{ $product['sku'] }}</td>
                            <td style="white-space: break-spaces;">{{ $product['title'] }}</td>
                            <td style="white-space: break-spaces;">{{ $product['models'] }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
