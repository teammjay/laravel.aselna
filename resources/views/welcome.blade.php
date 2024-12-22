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
                    <option value="{{ $store->id }}">{{ $store->name }} - {{ $store->storeType->name }}</option>
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
{{--                    <th>Action</th>--}}
                </tr>
                </thead>
                <tbody>
                @foreach($listings as $listing)
                    <tr>
                        <td>{{$listing->id}}</td>
                        <td>{{$listing->listing_id}}</td>
                        <td>{{$listing->store_id}}</td>
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
                        <td></td>
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
        $(document).ready(function () {
            $('#listingTable').DataTable();
        });
    </script>
@stop
