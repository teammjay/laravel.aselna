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
            </table>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#navigationPageTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('store_navigation_page.filter') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    data: function (d) {

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
        });
    </script>
@stop
