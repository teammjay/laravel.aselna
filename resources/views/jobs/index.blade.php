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

    @include('jobs.run_mode_filter')

    <table id="stored-jobs" class="table table-bordered table-striped table-hover">
        <thead>
        <tr>
            <th>Job Name</th>
            <th>Description</th>
            <th>Last Run</th>
            <th>Run Mode</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($jobs as $job)
            <tr>
                <td>{{ $job->name }}</td>
                <td>{{ $job->description }}</td>
                <td>{{ $job->last_run }}</td>
                @include('jobs.run_mode_badge', ['runMode' => $runModes[$job->id] ?? []])
                <td class="d-flex">
                    <a href="{{ route('jobs.edit', $job) }}" class="btn btn-warning btn-sm mr-1">
                        Edit
                    </a>
                    @include('jobs.run_now_button', [
                        'runMode' => $runModes[$job->id] ?? [],
                        'runUrl' => route('jobs.run', $job),
                        'storesUrl' => route('jobs.stores', $job),
                        'jobName' => $job->name,
                        'buttonClass' => 'btn-sm',
                    ])
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @include('jobs.run_stores_modal')
@endsection

@section('adminlte_js')
    @include('jobs.run_stores_js')
    @include('jobs.run_mode_js')
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
            initRunModeFilter('#stored-jobs', 3);
        });
    </script>
@stop
