@extends('adminlte::page')

@section('title', 'HD Message Template')

@section('content_header')
    <h1>HD Message Template</h1>
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
            <a href="{{ route('hd_message_template.create') }}" class="btn btn-primary">
                Add HD Message Template
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="hdMessageTemplateTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Store Type</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($hd_message_templates as $ltype)
                    <tr>
                        <td>{{ $ltype->name }}</td>
                        <td>{{ $ltype->template_code }}</td>
                        <td>{{ (isset($ltype->storeType)) ? $ltype->storeType->name : '' }}</td>
                        <td>{{ $ltype->created_by  }}</td>
                        <td>{{ $ltype->created_at  }}</td>
                        <td>
                            <a href="{{ route('hd_message_template.edit', $ltype->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('hd_message_template.delete', $ltype->id) }}" class="btn btn-danger" title="Remove">
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