@extends('adminlte::page')

@section('content_header')
    <h1>Cron Jobs</h1>
@stop


@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('scheduled_jobs.create') }}" class="btn btn-primary">
                Add Cron Job
            </a>
        </div>
    </div>

    <table id="scheduled-jobs" class="table table-bordered table-striped table-hover">
        <thead>
        <tr>
            <th>Job Name</th>
            <th>Class Name</th>
            <th>cron_expression</th>
            <th>Is active?</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($scheduled_jobs as $job)
            <tr>
                <td>{{ $job->name }}</td>
                <td>{{ $job->class_name }}</td>
                <td>{{ $job->cron_expression }}</td>
                <td>{{ ($job->is_active == 1) ? 'Yes' : 'No' }}</td>
                <td class="d-flex">
                    <a href="{{ route('scheduled_jobs.edit', $job) }}" class="btn btn-warning btn-sm">
                        Edit
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

@section('adminlte_js')
    <script>
        $(document).ready(function () {
            $('#scheduled-jobs').DataTable();
        });
    </script>
@stop
