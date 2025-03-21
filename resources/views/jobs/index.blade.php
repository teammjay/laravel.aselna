@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Scheduled Jobs</h1>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Job Name</th>
                    <th>Status</th>
                    <th>Last Run</th>
                    <th>Next Run</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($jobs as $job)
                <tr>
                    <td>{{ $job->name }}</td>
                    <td><span class="badge bg-{{ $job->status === 'queued' ? 'info' : 'success' }}">{{ $job->status }}</span></td>
                    <td>{{ $job->last_run ? $job->last_run->format('Y-m-d H:i:s') : 'Never' }}</td>
                    <td>{{ $job->next_run ? $job->next_run->format('Y-m-d H:i:s') : 'N/A' }}</td>
                    <td>
                        <form method="POST" action="{{ route('jobs.run', $job) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm" {{ $job->status === 'queued' ? 'disabled' : '' }}>
                                {{ $job->status === 'queued' ? 'Running...' : 'Run Now' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection