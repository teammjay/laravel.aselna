@extends('adminlte::page')

@section('title', 'Store Pages')

@section('content_header')
    <h1>Store Pages</h1>
@stop

@section('content')
    @csrf
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}</div>
    @endif
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('store_page.create') }}" class="btn btn-primary">
                Add Store Page
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="storePageTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Store</th>
                    <th>Page Type</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($store_pages as $sPage)
                    <tr>
                        <td>{{ $sPage->id }}</td>
                        <td>{{ $sPage->title }}</td>
                        <td>{{ $sPage->store->name }} {{ (!empty((array)$sPage->store->storeType)) ? ' - '.$sPage->store->storeType->name : '' }}</td>
                        <td>{{ \App\Constants\PageTypes::getPageTypeName($sPage->page_type) }}</td>
                        <td>
                            <a href="{{ route('store_page.edit', $sPage->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <button type="button" class="btn btn-danger delete-btn" data-module="store_page" data-id="{{ $sPage->id }}" title="Remove"> <i class="fas fa-fw fa-trash"></i></button>

                            {{--<a href="{{ route('store_page.delete', $sPage->id) }}" class="btn btn-danger" title="Remove">
                                <i class="fas fa-fw fa-trash"></i>
                            </a>--}}
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
            $('#storePageTable').DataTable({
                "dom": '<"datatable-top"lfip>rt<"datatable-bottom"ip>',
                stateSave: true,
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
                },
            });
        });
    </script>
@stop