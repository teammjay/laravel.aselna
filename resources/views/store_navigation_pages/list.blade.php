@extends('adminlte::page')

@section('title', 'Store Navigation Pages')

@section('content_header')
    <h1>Store Navigation Pages</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('store_navigation_page.create') }}" class="btn btn-primary">
                Add Navigation Page
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
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
                <tbody>
                @foreach($navigation_pages as $page)
                    <tr>
                        <td>{{ $page->id }}</td>
                        <td>{{ $page->title }}</td>
                        <td>{{ $page->store->name }} {{ (!empty((array)$page->store->storeType)) ? ' - '.$page->store->storeType->name : '' }}</td>
                        <td>{{ $page->navigation_type }}</td>
                        <td>
                            <a href="{{ route('store_navigation_page.edit', $page->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('store_navigation_page.delete', $page->id) }}" class="btn btn-danger" title="Remove">
                                <i class="fas fa-fw fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script>
        $(document).ready(function () {
            $('#navigationPageTable').DataTable();
        });
    </script>
@stop
