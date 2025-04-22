@extends('adminlte::page')

@section('content_header')
    <h1>{{ isset($device) ? 'Edit Job' : 'Add Job' }}</h1>
@stop
@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
@endsection

@section('content')
    <form action="{{ isset($job) ? route('jobs.update',['job' => $job['id']]) : route('jobs.add') }}"
          method="POST">
        @csrf
        <div class="card">
            <div class="card-header">
                {{ isset($job) ? 'Added Required job for '. $job->name : '' }}
            </div>
            <div class="card-body">
                <select class="form-control pro-jobs" name="parents[]" multiple="multiple">
                    <option value="">Select Job</option>
                    @foreach($allJobs as $job)
                        <option
                            value="{{ $job->id }}" {{ isset($device) && $device->id == $job->id ? 'selected' : '' }}>
                            {{ $job->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('devices.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($device))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@endsection


@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.pro-jobs').select2({
                placeholder: 'Select Parent Jobs',
                allowClear: true
            });
        });
    </script>
@endsection
