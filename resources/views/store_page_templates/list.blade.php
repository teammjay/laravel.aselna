@extends('adminlte::page')

@section('title', 'Store Page Templates')

@section('content_header')
    <h1>Store Page Templates</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('store_page.create') }}" class="btn btn-primary">
                Add Store Page Template
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="storePageTemplatesTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Page Type</th>
                    <th>Store</th>
                    <th>Product Type</th>
                    <th>Overwrite</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($store_page_templates as $sPage)
                    <tr>
                        <td>{{ $sPage->name }}</td>
                        <td>{{ \App\Constants\PageTypes::getPageTypeName($sPage->page_type) }}</td>
                        <td>{{ $sPage->store->name }} {{ (!empty((array)$sPage->store->storeType)) ? ' - '.$sPage->store->storeType->name : '' }}</td>
                        <td></td>
                        <td><input type="checkbox" name="overwrite[]" @if($sPage->overwrite) checked @endif></td>
                        <td>
                            <a href="{{ route('store_page.edit', $sPage->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('store_page.delete', $sPage->id) }}" class="btn btn-danger" title="Remove">
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
            $('#storePageTemplatesTable').DataTable();
        });
    </script>
@stop