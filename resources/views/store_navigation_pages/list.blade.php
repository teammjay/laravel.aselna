@extends('adminlte::page')

@section('title', 'Store Navigation Pages')

@section('content_header')
    <h1>Store Navigation Pages</h1>
@stop

@section('content')
    <div class="card card-secondary">
        <div class="card-header bg-white">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form">
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Store
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="store_id" name="store_id">
                                    <option value="">Select store</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" id="{{ $store->id }}"
                                                data-store-name="{{ $store->name }} - {{ $store->storeType ? $store->storeType->name : '' }}">{{ $store->name }}
                                            - {{ $store->storeType ? $store->storeType->name : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
{{--                    <div class="col-md-4">--}}
{{--                        <div class="row">--}}
{{--                            <div class="col-3 text-right ">--}}

{{--                            </div>--}}
{{--                            <div class="col-3">--}}
{{--                                <select class="custom-select" id="" name="prefix_operator">--}}
{{--                                    <option value="{{FilterCodes::EQUAL}}">=</option>--}}
{{--                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>--}}
{{--                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>--}}
{{--                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>--}}
{{--                                    <option value="{{FilterCodes::IN}}">in</option>--}}
{{--                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>--}}
{{--                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                            <div class="col-6">--}}
{{--                                <input type="text" name="prefix" id="prefix" class="form-control">--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" id="apply_filter" class="form-control btn btn-primary">Filter</button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="reset" class="form-control btn  btn-reset btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="row my-3 gap-2">
        <a href="{{ route('store_navigation_page.create') }}" class="btn btn-primary">
            Add Navigation Page
        </a>
        <button class="btn btn-primary" id="generate_brands_page" >
            Generate Brands Page
        </button>
    </div>
    <table id="navigationPageTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Store</th>
            <th>Navigation Type</th>
            <th>Action</th>
        </tr>
        </thead>
    </table>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function () {
            let navigationTable = $('#navigationPageTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('store_navigation_page.filter') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    data: function (d) {
                        var formData = $('#filter-form').serializeArray(); // Serialize form inputs

                        console.log(formData)

                        // Add form data to the DataTable's request payload
                        $.each(formData, function (index, field) {
                            d[field.name] = field.value; // Append form data to the DataTables request
                        });
                    }
                },
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'title', name: 'title'},
                    {data: 'store_name', name: 'store_name'},
                    {data: 'navigation_type', name: 'navigation_type'},
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
            });

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

            $('#apply_filter').on('click', function (event) {
                event.preventDefault();
                navigationTable.ajax.reload();
            });

            $('#generate_brands_page').on('click', function (e) {
                e.preventDefault();
                const storeId = $('#store_id').val();
                if (!storeId) {
                    alert('Please select a store.');
                    return;
                }

                $.ajax({
                    url: '{{ route('store_navigation_page.generate_brands_page') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        store_id: storeId
                    },
                    success: function (response) {
                        if (response.success) {
                            alert('Brands page generated successfully.');
                            window.location.href = response.redirect_url;
                        } else {
                            alert('Failed to generate brands page. Please try again.');
                        }
                    },
                    error: function () {
                        alert('An error occurred while generating the brands page.');
                    }
                });
            });
        });
    </script>
@stop
