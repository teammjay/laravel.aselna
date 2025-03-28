@extends('adminlte::page')

@section('content_header')
    <h1>Scheduled Jobs</h1>
@stop



@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <table id="stored-jobs" class="table table-bordered table-striped table-hover">
        <thead>
        <tr>
            <th>Job Name</th>
            <th>Description</th>
            <th>Last Run</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($jobs as $job)
            <tr>
                <td>{{ $job->name }}</td>
                <td>{{ $job->description }}</td>
                <td>{{ $job->last_run }}</td>
                <td>
                    <form method="POST" action="{{ route('jobs.run', $job) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            Run Now
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

@section('adminlte_js')
    <script>
        $(document).ready(function () {
            $('#stored-jobs').DataTable();
        });
    </script>
@stop
