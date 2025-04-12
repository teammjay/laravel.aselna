@php use App\Constants\FeedStatus;use App\Constants\FeedTypes; use App\Constants\FilterCodes @endphp
@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <style>
        .select2-container--default .select2-dropdown .select2-search__field:focus, .select2-container--default .select2-search--inline .select2-search__field:focus {
            outline: 0;
            border: none;
        }

        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px);
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: auto;
            position: absolute;
            top: 50%;
            right: 1px;
            width: 20px;
            transform: translateY(-50%);
        }

        table th, table td, .column-no-whitespace {
            white-space: nowrap;
        }
    </style>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <select class="form-control" id="listing_store">
                <option value="">Select Store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" id="{{ $store->id }}"
                            data-store-name="{{ $store->name }} - {{ $store->storeType ? $store->storeType->name : '' }}">{{ $store->name }}
                        - {{ $store->storeType ? $store->storeType->name : '' }}</option>
                @endforeach
            </select>
        </div>
    </div>
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
                                Feed Type
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="feed_type" name="feed_type">
                                    <option value="">Select Feed Type</option>
                                    @foreach(FeedTypes::names as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right ">
                                Feed Id
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="feed_id_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="feed_id" id="feed_id" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right ">
                                Listing Id
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="listing_id_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="listing_id" id="listing_id" class="form-control">
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
                            <button type="reset" class="form-control btn  btn-reset btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="listingTable">
                    <thead>
                    <tr>
                        <th>Id</th>
                        <th>Listing Id</th>
                        <th>Feed type</th>
                        <th>Store</th>
                        <th>Status</th>
                        <th>created At</th>
                        <th> Submit At</th>
                        <th>Number Of Items</th>
                        <th>Processing Count</th>
                        <th>Success</th>
                        <th>Fail Count</th>
                        <th>Warnings</th>
                        <th>Number Of Attempts</th>
                        <th> Feed Id</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        var table;
        $(document).ready(function () {
            $('#listing_store').select2();
            // refresh after every 5 seconds
            table = $('#listingTable').DataTable({
                processing: true,
                order: [[5, 'desc']],
                serverSide: true,
                searching: false,
                ajax: {
                    url: "{{ route('listing-data') }}",
                    data: function (d) {
                        d.store_id = $('#listing_store').val();
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
                    {data: 'listing_id', name: 'listing_id'},
                    {
                        data: 'feed_type', name: 'feed_type', render: function (data) {
                            const feedJson = '<?= json_encode(FeedTypes::names) ?>';
                            const feedTypes = JSON.parse(feedJson);
                            if (feedTypes[data] === undefined) {
                                return data;
                            }
                            return feedTypes[data];
                        }
                    },
                    {
                        data: 'null', name: 'store', render: function (data, type, row) {
                            return row.store.name + ' - ' + row.store.store_type.name;
                        }
                    },
                    {
                        data: 'status', name: 'status', render: function (data) {
                            const statusJson = '<?= json_encode(FeedStatus::names) ?>';
                            const status = JSON.parse(statusJson);
                            return status[data];
                        }
                    },
                    {
                        data: 'created_at', name: 'created_at',
                        render: function (data) {
                            // change that to 09/29/2022 12:00:00
                            return moment(data).format('MM/DD/YYYY HH:mm:ss');
                        }
                    },
                    {
                        data: 'submit_ts', name: 'submit_ts',
                        render: function (data) {
                            if (!data) return '';
                            return moment(data).format('MM/DD/YYYY HH:mm:ss');
                        }
                    },
                    {data: 'number_of_items', name: 'number_of_items'},
                    {data: 'processing_count', name: 'processing_count'},
                    {data: 'success_count', name: 'success_count'},
                    {data: 'fail_count', name: 'fail_count'},
                    {data: 'warning_count', name: 'warning_count'},
                    {data: 'number_of_attempts', name: 'number_of_attempts'},
                    {data: 'feed_id', name: 'feed_id', maxWidth: '100px'},
                    {
                        data: null, name: 'action', orderable: false, searchable: false, render: function (data) {
                            return '<div>' +
                                '<button class="btn btn-primary generate-feed-file" data-listing="' + data.listing_id + '" data-feed="' + data.id + '">Download Feed File</button>' +
                                '<button class="btn btn-primary generate-result-feed-file" data-listing="' + data.listing_id + '" data-feed="' + data.id + '">Download Result Feed File</button>' +
                                '<button class="btn btn-primary submit-feed-file" data-listing="' + data.listing_id + '" data-feed="' + data.id + '">Submit Feed</button>' +
                                '</div>';
                        }
                    }
                ]
            });

            // setInterval(function () {
                // if ($('#listing_store').val()) {
                    // table.ajax.reload();
                // }
            // }, 20000);
        });

        $(document).ajaxStart(function () {
            $("#ajax-preloader").show(); // Show preloader when AJAX starts
        }).ajaxStop(function () {
            $("#ajax-preloader").hide(); // Hide preloader when AJAX stops
        });

        // change listing store
        $('#listing_store').change(function () {
            table.ajax.reload();
        });

        $('#apply_filter').on('click', function (event) {
            event.preventDefault();
            table.ajax.reload();
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

        $(document).on('click', '.generate-feed-file', function () {
            var listingId = $(this).data('listing');
            var feedId = $(this).data('feed');
            $.ajax({
                url: "<?= route('generate-feed-file') ?>",
                type: 'POST',
                data: {
                    _token: "<?= csrf_token() ?>",
                    listing_id: listingId,
                    feed_id: feedId
                },
                success: function (response) {
                    //download the file url php code is  return response()->download($filetPath);
                    if (<?php echo env('APP_ENV') != 'local' ? 'true' : 'false' ?>) {
                        if (response.generating) return;
                        var link = document.createElement('a');
                        link.href = response.file_path;
                        link.download = response.file_path.split('/').pop();
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                }
            });
        });

        $(document).on('click', '.generate-result-feed-file', function () {
            var listingId = $(this).data('listing');
            var feedId = $(this).data('feed');
            $.ajax({
                url: "<?= route('generate-result-feed-file') ?>",
                type: 'POST',
                data: {
                    _token: "<?= csrf_token() ?>",
                    listing_id: listingId,
                    feed_id: feedId
                },
                success: function (response) {
                    if (!response.success) {
                        alert("An Error Occurred which submitting the feed");
                        return;
                    }
                    if (<?php echo env('APP_ENV') != 'local' ? 'true' : 'false' ?>) {
                        // download the file url php code is  return response()->download($filetPath);
                        var link = document.createElement('a');
                        link.href = response.file_path;
                        link.download = response.file_path.split('/').pop();
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                }
            });
        });

        $(document).on('click', '.submit-feed-file', function () {
            console.log("Submit Clicked");
            var listingId = $(this).data('listing');
            var feedId = $(this).data('feed');
            $.ajax({
                url: "<?= route('submit-feed') ?>",
                type: 'POST',
                data: {
                    _token: "<?= csrf_token() ?>",
                    listing_id: listingId,
                    feed_id: feedId
                },
                success: function (response) {
                    // download the file url php code is  return response()->download($filetPath);

                    if (response.success) {
                        alert("Feed Submitted Successfully");
                        console.log(response);
                        table.ajax.reload();
                    } else {
                        alert("An Error Occurred which submitting the feed");
                    }
                }
            });
        });
    </script>
@stop
