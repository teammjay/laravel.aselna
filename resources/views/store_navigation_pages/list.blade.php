@php use App\Constants\WebStorePageType; @endphp
@php use App\Constants\FilterCodes; @endphp

@extends('adminlte::page')

@section('title', 'Store Navigation Pages')

@section('content_header')
    <h1>Store Navigation Pages</h1>
@stop

@section('adminlte_css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
        }
    </style>
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
                                <select class="form-control" id="store_id" name="store_id" style="width: 100%;">
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
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right ">
                                Navigation Type
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="navigation_type_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select type="text" name="navigation_type" id="navigation_type" class="form-control">
                                    <option value="">Select Navigation Type</option>
                                    @foreach(WebStorePageType::getPageTypes() as $pageType)
                                        <option value="{{ $pageType }}"
                                                @if(isset($navigation_page) && $navigation_page->page_type == $pageType) selected @endif>
                                            {{ WebStorePageType::getPageTypeName($pageType) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
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
                            <button type="reset" id="resetForm" class="form-control btn  btn-reset btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="row my-3">
        <div class="col-md-10">
            <a href="{{ route('store_navigation_page.create') }}" class="btn btn-primary">
                Add Navigation Page
            </a>
            <button class="btn btn-primary" id="generate_bulk_pages" data-toggle="modal" data-target="#bulkPageModal">
                Generate Bulk Page
            </button>
            <button class="btn btn-primary" id="generate_bulk_all_pages" data-toggle="modal" data-target="#bulkAllPageModal">
                Generate Bulk Combine Page
            </button>
        </div>
        <div class="col-md-2">
            <span id="clear_session_filter" class="btn btn-secondary float-right mr-2">Clear Filter</span>
        </div>
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

    <!-- Bulk Page Generation Modal -->
    <div class="modal fade" id="bulkPageModal" tabindex="-1" role="dialog" aria-labelledby="bulkPageModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkPageModalLabel">Generate Bulk Navigation Pages</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="bulkPageForm">
                        @csrf
                        <div class="form-group">
                            <label for="bulk_store_id">Store</label>
                            <select class="form-control" name="store_id" id="bulk_store_id" style="width: 100%;">
                                <option value="">-- Select Store --</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">
                                        {{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bulk_page_type">Page Type</label>
                            <select class="form-control" name="page_type" id="bulk_page_type">
                                <option value="">-- Select Page Type --</option>
                                @foreach(WebStorePageType::getPageTypes() as $pageType)
                                    <option value="{{ $pageType }}"
                                            @if(isset($navigation_page) && $navigation_page->page_type == $pageType) selected @endif>
                                        {{ WebStorePageType::getPageTypeName($pageType) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bulk_template_id">Page Template</label>
                            <select class="form-control" name="template_id" id="bulk_template_id">
                                <option value="">-- Select Page Template --</option>
                                @foreach($pageTemplates as $pageTemplate)
                                    <option value="{{ $pageTemplate->id }}"
                                            @if(isset($navigation_page) && $navigation_page->template_id == $pageTemplate->id) selected @endif>
                                        {{ $pageTemplate->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitBulkGenerate">Generate Pages</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkAllPageModal" tabindex="-1" role="dialog" aria-labelledby="bulkAllPageModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkPageModalLabel">Generate Bulk Navigation Pages</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="bulkAllPageForm">
                        @csrf
                        <div class="form-group">
                            <label for="bulk_store_id">Store</label>
                            <select class="form-control" name="store_id" id="bulk_all_store_id" style="width: 100%;">
                                <option value="">-- Select Store --</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">
                                        {{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitAllBulkGenerate">Generate Pages</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        var navigationTable;
        $(document).ready(function () {
            $('#bulk_store_id').select2();
            $('#store_id').select2();

            navigationTable = $('#navigationPageTable').DataTable({
                processing: true,
                serverSide: true,
                stateSave: true,
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


                        sessionStorage.setItem("store_navigation_filters", JSON.stringify(formData));
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
                stateSaveCallback: function(settings, data) {
                    sessionStorage.setItem(
                        'DataTables_' + settings.sInstance,
                        JSON.stringify(data)
                    );
                },

                stateLoadCallback: function(settings) {
                    return JSON.parse(
                        sessionStorage.getItem('DataTables_' + settings.sInstance)
                    );
                }
            });

            document.getElementById("resetForm").addEventListener("click", function () {
                sessionStorage.removeItem("store_navigation_filters");
            });

            $(document).on('click','#clear_session_filter',function () {
                sessionStorage.removeItem("store_navigation_filters");
                $('#store_id').val('').trigger('change');
                $('#filter-form')[0].reset();
                navigationTable.ajax.reload();
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

            $('#submitBulkGenerate').on('click', function () {
                // This is the function call after the submit button click of the modal
                handleBulkPageGeneration();
            });

            function handleBulkPageGeneration() {
                const form = $('#bulkPageForm');
                const storeId = form.find('#bulk_store_id').val();
                const pageType = form.find('#bulk_page_type').val();
                const templateId = form.find('#bulk_template_id').val();

                if (!storeId || !pageType || !templateId) {
                    alert('Please select store, page type, and template.');
                    return;
                }

                // You can now perform an AJAX request to your bulk generation endpoint
                console.log({
                    _token: '{{ csrf_token() }}',
                    store_id: storeId,
                    page_type: pageType,
                    template_id: templateId
                });

                $.ajax({
                    url: '{{ route('store_navigation_page.generate_bulk') }}', // You need to create this route
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        store_id: storeId,
                        page_type: pageType,
                        template_id: templateId
                    },
                    success: function (response) {
                        if (response.success) {
                            alert('Bulk pages generated successfully.');
                            $('#bulkPageModal').modal('hide');
                            navigationTable.ajax.reload();
                        } else {
                            alert('Failed to generate bulk pages: ' + (response.message || 'Please try again.'));
                        }
                    },
                    error: function () {
                        alert('An error occurred while generating bulk pages.');
                    }
                });
            }

            $('#submitAllBulkGenerate').on('click', function () {
                // This is the function call after the submit button click of the modal
                handleAllBulkPageGeneration();
            });
            function handleAllBulkPageGeneration() {
                const form = $('#bulkAllPageForm');
                const storeId = form.find('#bulk_all_store_id').val();
                console.log(storeId);

                if (!storeId) {
                    alert('Please select store to move forward');
                    return;
                }

                $.ajax({
                    url: '{{ route('store_navigation_page.generate_all_bulk') }}', // You need to create this route
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        store_id: storeId,
                    },
                    success: function (response) {
                        if (response.success) {
                            alert('All Bulk pages started generating.');
                            $('#bulkPageModal').modal('hide');
                            navigationTable.ajax.reload();
                        } else {
                            alert('Failed to generate bulk pages: ' + (response.message || 'Please try again.'));
                        }
                    },
                    error: function () {
                        alert('An error occurred while generating bulk pages.');
                    }
                });
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
            let savedFilters = JSON.parse(sessionStorage.getItem("store_navigation_filters"));
            if (savedFilters) {
                console.log(savedFilters);
                Object.keys(savedFilters).forEach(function (key) {

                    //console.log(savedFilters[key]);
                    let input = $('#filter-form [name="'+savedFilters[key].name+'"]');
                    if (input) {
                        input.val(savedFilters[key].value)
                    }

                    if( savedFilters[key].name == 'store_id'){
                        $('#store_id').val(savedFilters[key].value);
                    }

                });

            }
        });
    </script>
@stop
