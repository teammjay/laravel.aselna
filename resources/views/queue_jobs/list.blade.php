@extends('adminlte::page')

@section('title', 'Queue Jobs')

@section('content_header')
    <h1>Queue Jobs</h1>
@stop

@section('adminlte_css')
    <style>
        .status-pending { color: orange; font-weight: 600; }
        .status-processing { color: blue; font-weight: 600; }
        .status-completed { color: green; font-weight: 600; }
        .status-failed { color: red; font-weight: 600; }
    </style>
@stop

@section('content')
    <div class="card card-primary">
        <div class="card-body">
            <table id="queueJobsTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    {{--<th>Chain ID</th>--}}
                    <th>Job ID</th>
                    <th>Name</th>
                    <th>Job Type</th>
                    <th>Status</th>
                    <th>Queued</th>
                    <th>Job Started</th>
                    <th>Job Finished</th>
                    <th>Error</th>
                    <th>Created At</th>
                </tr>
                </thead>
                <tbody>
                @foreach($jobs as $job)
                    @php

                        $jobType = explode('/',str_replace("\\","/",$job->type));
                        $name = end($jobType);
                    @endphp
                    <tr>
                        {{--<td>{{ $job->chain_id }}</td>--}}
                        <td>{{ $job->job_id }}</td>
                        <td>{{ $name }}</td>
                        <td>{{ $job->type }}</td>
                        <td><span class="status-{{ $job->status }}">{{ ucfirst($job->status) }}</span></td>
                        <td>{{ $job->queue }}</td>
                        <td>@if($job->started_at) {{ $job->started_at->diffForHumans() }} @endif</td>
                        <td>@if($job->finished_at) {{ $job->finished_at->diffForHumans() }} @endif</td>
                        <td>@if($job->output) <i class="fa fa-exclamation-triangle" style="font-size:24px" title="{{ $job->output }}"></i> @endif</td>
                        <td>{{ $job->created_at}}</td>
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
            $('#queueJobsTable').DataTable({
                "order": [[ 8, "desc" ]],
            });
        });
    </script>
@stop