@extends('adminlte::page')

@section('plugins.Select2', true)

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
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('scheduled_jobs.create') }}" class="btn btn-primary">
                Add Cron Job
            </a>
        </div>
    </div>

    @include('jobs.run_mode_filter')

    <table id="scheduled-jobs" class="table table-bordered table-striped table-hover">
        <thead>
        <tr>
            <th>Job Name</th>
            <th>Class Name</th>
            <th>cron_expression</th>
            <th>Store Type</th>
            <th>Is active?</th>
            <th>Run Mode</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($scheduled_jobs as $job)
            <tr>
                <td>{{ $job->name }}</td>
                <td>{{ $job->class_name }}</td>
                <td>{{ $job->cron_expression }}</td>
                <td>{{ $job->store_type }}</td>
                <td>{{ ($job->is_active == 1) ? 'Yes' : 'No' }}</td>
                @include('jobs.run_mode_badge', ['runMode' => $runModes[$job->id] ?? []])
                <td class="d-flex">
                    <a href="{{ route('scheduled_jobs.edit', $job) }}" class="btn btn-warning btn-sm">
                        Edit
                    </a>
                    <a href="{{ route('scheduled_jobs.delete', $job->id) }}" class="btn btn-danger" title="Remove">
                        <i class="fas fa-fw fa-trash"></i>
                    </a>
                    @include('jobs.run_now_button', [
                        'runMode' => $runModes[$job->id] ?? [],
                        'runUrl' => route('scheduled_jobs.run', $job->id),
                        'storesUrl' => route('scheduled_jobs.stores', $job->id),
                        'jobName' => $job->name,
                        'buttonClass' => 'px-3 ml-1',
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
            $('#scheduled-jobs').DataTable({
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
            initRunModeFilter('#scheduled-jobs', 5);
        });
    </script>
@stop
