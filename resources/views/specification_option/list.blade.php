@extends('adminlte::page')

@section('title', 'Specification  Option')

@section('content_header')
    <h1>Specification Option</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('specification_option.create') }}" class="btn btn-primary">
                Add Specification Option
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="specificationOptionTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Field Name</th>
                    <th>Field Local Name</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($specification_options as $spec_info)
                    <tr>
                        <td>{{ $spec_info->field_local_name }}</td>
                        <td>{{ $spec_info->field_option }}</td>
                        <td>
                            <a href="{{ route('specification_option.edit', $spec_info->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('specification_option.delete', $spec_info->id) }}" class="btn btn-danger" title="Remove">
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
            $('#specificationOptionTable').DataTable();
        });
    </script>
@stop