@extends('adminlte::page')

@section('title', 'Specification  Info')

@section('content_header')
    <h1>Specification Info</h1>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('specification_info.create') }}" class="btn btn-primary">
                Add Specification Info
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="specificationInfoTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Field Name</th>
                    <th>Field Local Name</th>
                    <th>Store Type</th>
                    <th>Required</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($specification_infos as $spec_info)
                    <tr>
                        <td>{{ $spec_info->field_name }}</td>
                        <td>{{ $spec_info->field_local_name }}</td>
                        <td>{{ (isset($spec_info->storeType)) ? $spec_info->storeType->name : '' }}</td>
                        <td>{{ ($spec_info->required == 1) ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('specification_info.edit', $spec_info->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('specification_info.delete', $spec_info->id) }}" class="btn btn-danger" title="Remove">
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
            $('#specificationInfoTable').DataTable();
        });
    </script>
@stop