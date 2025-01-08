@php use App\Constants\FeedStatus;use App\Constants\FeedTypes; @endphp
@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-dropdown .select2-search__field:focus, .select2-container--default .select2-search--inline .select2-search__field:focus {
            outline: 0;
            border: none;
        }
        .select2-container{width:100% !important;}
        .select2-container .select2-selection--single{
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

        table th, table td, .column-no-whitespace{
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
                            data-store-name="{{ $store->name }} - {{ $store->storeType->name }}">{{ $store->name }}
                        - {{ $store->storeType->name }}</option>
                @endforeach
            </select>
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
            table = $('#listingTable').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: "{{ route('listing-data') }}",
                    data: function (d) {
                        d.store_id = $('#listing_store').val();
                    }
                },
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'listing_id', name: 'listing_id'},
                    {
                        data: 'feed_type', name: 'feed_type', render: function (data) {
                            const feedJson = '<?= json_encode(FeedTypes::names) ?>';
                            const feedTypes = JSON.parse(feedJson);
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
                                '<button class="btn btn-primary generate-feed-file" data-listing="' + data.listing_id + '">Download Feed File</button>' +
                                '</div>';
                        }
                    }
                ]
            });
        });

        // change listing store
        $('#listing_store').change(function () {
            table.ajax.reload();
        });

        $(document).on('click', '.generate-feed-file', function () {
            var listingId = $(this).data('listing');
            $.ajax({
                url: "<?= route('generate-feed-file') ?>",
                type: 'POST',
                data: {
                    _token: "<?= csrf_token() ?>",
                    listing_id: listingId
                },
                success: function (response) {
                    //download the file url php code is  return response()->download($filetPath);
                    if (response.generating) return;
                    var link = document.createElement('a');
                    link.href = response.file_path;
                    link.download = response.file_path.split('/').pop();
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            });
        });

        $(document).on('click','.generate-result-feed-file',function () {
            var listingId = $(this).data('listing');
            $.ajax({
                url: "<?= route('generate-result-feed-file') ?>",
                type: 'POST',
                data: {
                    _token: "<?= csrf_token() ?>",
                    listing_id: listingId
                },
                success: function (response) {
                    //download the file url php code is  return response()->download($filetPath);
                    // var link = document.createElement('a');
                    // link.href = response.file_path;
                    // link.download = response.file_path.split('/').pop();
                    // document.body.appendChild(link);
                    // link.click();
                    // document.body.removeChild(link);
                }
            });
        })
    </script>
@stop
