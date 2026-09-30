@extends('adminlte::page')

@section('plugins.Select2', true)

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
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
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
                <td class="d-flex">
                    <a href="{{ route('jobs.edit', $job) }}" class="btn btn-warning btn-sm">
                        Edit
                    </a>
                    @if(in_array($job->id, $storeScopedJobIds))
                        <button type="button" class="btn btn-primary btn-sm js-run-stores"
                                data-run-url="{{ route('jobs.run', $job) }}"
                                data-stores-url="{{ route('jobs.stores', $job) }}"
                                data-job-name="{{ $job->name }}">
                            Run Now...
                        </button>
                    @else
                        <form method="POST" action="{{ route('jobs.run', $job) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                Run Now
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @include('jobs.run_stores_modal')
@endsection

@section('adminlte_js')
    @include('jobs.run_stores_js')
    <script>
        $(document).ready(function () {
            $('#stored-jobs').DataTable({
                "dom": '<"datatable-top"lfip>rt<"datatable-bottom"ip>',
                stateSave:true,
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
