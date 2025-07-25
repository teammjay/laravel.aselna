@extends('adminlte::page')

@section('title', 'Marketplace Products')

@section('content_header')
    <h1>Marketplace Products</h1>
@stop

@section('content')
    <div class="card card-secondary">
        <div class="card-header bg-white">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
        </div>
    </div>
    <div class="row my-3">
        <div class="col-md-2">
            <select class="form-control" id="listing_store" name="listing_store_id">
                <option value="">- All Store -</option>
                @if(count($stores ?? []) > 0)
                    @foreach($stores as $store)
                        <option
                            value="{{ $store->id }}">{{ $store->name }}{{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-md-9 d-flex">
        </div>
    </div>
    <div class="table-responsive">
        <table id="productsTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Store</th>
                <th>Sku</th>
                <th>Upload Status</th>
                <th>Created At</th>
            </tr>
            </thead>
            <tbody id="product-list">
            </tbody>
        </table>
    </div>
@endsection


@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.card-header').on('click', function () {
                if ($(this).find('.fa').hasClass('fa-angle-down')) {
                    $(this).find('.fa').removeClass('fa-angle-down');
                    $(this).find('.fa').addClass('fa-angle-up');
                } else {
                    $(this).find('.fa').removeClass('fa-angle-up');
                    $(this).find('.fa').addClass('fa-angle-down');
                }
                $('.card-body').toggle();
            });

            var productsTable = $('#productsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('marketplace_product.filter') }}',
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    data: function (d) {
                        d.listing_store_id = $('#listing_store').val();
                    }
                },
                columns: [
                    {data: 'store_name', name: 'store_name'},
                    {data: 'sku', name: 'sku'},
                    {data: 'upload_status', name: 'upload_status'},
                    {data: 'created_at', name: 'created_at'},
                ]
            });
        });
    </script>
@endsection
