@extends('adminlte::page')

@section('content_header')
    <h1>{{ isset($device) ? 'Edit Cron Job' : 'Add Cron Job' }}</h1>
@stop

@section('content')
    <form
            action="{{ isset($scheduled_job) ? route('scheduled_jobs.update',['id' => $scheduled_job->id]) : route('scheduled_jobs.store') }}"
            method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Job Name" value="{{ old('name', $scheduled_job->name ?? '') }}" required>
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="name">Class Name</label>
                            <input type="text" class="form-control @error('class_name') is-invalid @enderror" id="class_name"
                                   name="class_name"
                                   placeholder="Enter Job Class Name" value="{{ old('class_name', $scheduled_job->class_name ?? '') }}" required>
                            @if ($errors->has('class_name'))
                                <span class="text-danger">{{ $errors->first('class_name') }}</span>
                            @endif
                        </div>


                        <div class="form-group">
                            <label for="text" class="col-form-label">Cron Expression:</label>
                            <input type="text" class="form-control @error('class_name') is-invalid @enderror" name="cron_expression" id="cron_expression"  required value="{{ old('cron_expression', $scheduled_job->cron_expression ?? '') }}">
                            @if ($errors->has('cron_expression'))
                                <span class="text-danger">{{ $errors->first('cron_expression') }}</span>
                            @endif
                            <br>
                            <div class="text-gray">
                                <p>Example:</p>
                                <p>* * * * * → every minute</p>
                                <p>0 * * * * → hourly</p>
                                <p>0 12 * * * → every day at noon</p>
                                <p>45 * * * * → at 45th minute every hour</p>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active">Is active?</label>
                                    <input type="checkbox" data-toggle="toggle" class="@error('is_active') is-invalid @enderror" id="is_active"
                                           name="is_active" {{ (isset($scheduled_job->is_active) && $scheduled_job->is_active == 1 ) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>


            </div>
            <div class="card-footer text-center">
                <button page="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('scheduled_jobs.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($scheduled_job))
                    <button page="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@endsection