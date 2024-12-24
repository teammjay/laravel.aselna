@php use App\Constants\FeedTypes; @endphp
@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <select class="form-control" id="listing_store">
                <option value="-">Select Store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" id="{{ $store->id }}" data-store="{{ $store->name }}" data-store-type="{{ $store->storeType->name }}">{{ $store->name }} - {{ $store->storeType->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
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
                @foreach($listings as $listing)
                    <tr>
                        <td>{{$listing->id}}</td>
                        <td>{{$listing->listing_id}}</td>
                        <td>{{FeedTypes::getFeedName($listing->feed_type)}}</td>
                        <td>{{$listing->store->name}} - {{$listing->store->storeType->name}}</td>
                        <td>{{$listing->status}}</td>
                        <td>{{$listing->created_at}}</td>
                        <td>{{$listing->submit_ts}}</td>
                        <td>{{$listing->number_of_items}}</td>
                        <td>{{$listing->processing_count}}</td>
                        <td>{{$listing->success_count}}</td>
                        <td>{{$listing->fail_count}}</td>
                        <td>{{$listing->warning_count}}</td>
                        <td>{{$listing->number_of_attempts}}</td>
                        <td>{{$listing->feed_id}}</td>
                        <td>
                            <button class="btn btn-primary generate-feed-file" data-listing="{{$listing->id}}"
                                    data-toggle="tooltip" title="Generate Feed File">Download Feed File
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script>
        var table;
        $(document).ready(function () {
            table = $('#listingTable').DataTable();
        });

        // change listing store
        $('#listing_store').change(function () {
            //get selected label
            var storeId = $(this).val();
            var storeName = $(this).find(':selected').data('store');
            var storeType = $(this).find(':selected').data('store-type');

            table.columns(3).search(storeName + " - " + storeType).draw();
        });

        //CSRF token mismatch.
        $('.generate-feed-file').click(function () {
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
                    var link = document.createElement('a');
                    link.href = response.file_path;
                    link.download = response.file_path.split('/').pop();
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            });
        });
    </script>
@stop
