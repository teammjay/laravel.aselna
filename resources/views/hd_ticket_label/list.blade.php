@extends('adminlte::page')

@section('title', 'HD Ticket Label')

@section('content_header')
    <h1>HD Ticket Label</h1>
@stop

@section('adminlte_css')
    <style>
        .acitve-text{
            color:green;
        }
        .inacitve-text{
            color:red;
        }

    </style>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('hd_ticket_label.create') }}" class="btn btn-primary">
                Add HD Ticket Label
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="hdMessageTemplateTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Parent</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($hd_ticket_labels as $ltype)
                    <tr>
                        <td>{{ $ltype->name }}</td>
                        <td>{{ (isset($ltype->parent)) ? $ltype->parent->name : '' }}</td>
                        <td>{{ $ltype->created_by  }}</td>
                        <td>{{ $ltype->created_at  }}</td>
                        <td>
                            <a href="{{ route('hd_ticket_label.edit', $ltype->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('hd_ticket_label.delete', $ltype->id) }}" class="btn btn-danger" title="Remove">
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
            $('#hdMessageTemplateTable').DataTable();
        });
    </script>
@stop