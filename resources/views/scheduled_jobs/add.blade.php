@extends('adminlte::page')

@section('content_header')
    <h1>{{ isset($device) ? 'Edit Cron Job' : 'Add Cron Job' }}</h1>
@stop

@section('adminlte_css')
<style>
    .storetypelist li {
        display: inline-block;
        width: 48%;
        margin-bottom: 5px;
    }
</style>
@stop
@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
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

                        <div class="form-group">
                            <label for="parameters" class="col-form-label">Parameters  (JSON):</label>
                            <textarea name="parameters" class="form-control">{{ $scheduled_job->parameters }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="parameters" class="col-form-label">Store Type:</label>
                            <ul class="storetypelist">
                                @if($storeTypes)
                                    @foreach($storeTypes as $stype)
                                        @php
                                            if(isset($scheduled_job->store_type) && $stype->code != $scheduled_job->store_type) {
                                                continue;
                                            }

                                            if(isset($storeTypeIds) && in_array($stype->id,$storeTypeIds)){
                                                $checked = 'checked';
                                            }else{
                                                $checked = '';
                                            }

                                        @endphp
                                        <li><input type="checkbox" name="store_type_id[]" value="{{ $stype->id }}" {{ $checked }}/> {{  $stype->name }}</li>
                                    @endforeach
                                @endif
                            </ul>
                            {{--<select name="store_type_id" id="store_type_id" class="form-control">
                                <option value="">- Store Type -</option>
                                @if($storeTypes)
                                    @foreach($storeTypes as $stype)
                                        <option value="{{ $stype->id }}">{{ $stype->name }}</option>
                                    @endforeach
                                @endif
                            </select>--}}
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
                    <div class="col-md-6">
                        <div style="max-height: 100vh; overflow-y: scroll">
                            <table class="table table-bordered" id="store_list">
                                <thead>
                                <th>Store</th>
                                <th>Active</th>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
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

@section('adminlte_js')
    <script>
        var job_id = '{{ $scheduled_job->id }}';
        $(document).ready(function () {

            var store_type_id = [];
            $('input[name="store_type_id[]"]').each(function () {
                if($(this).is(':checked')){
                    store_type_id.push($(this).val());
                }
            });
            if(store_type_id.length) {
                loadStores(store_type_id, job_id);
            }

            $(document).on('click','input[name="store_type_id[]"]',function () {

                var store_type_id = [];
                $('input[name="store_type_id[]"]').each(function () {
                    if($(this).is(':checked')){
                        store_type_id.push($(this).val());
                    }
                });

                loadStores(store_type_id, job_id);

            });

            function loadStores(store_type_id, job_id){

                $.ajax({
                    url: "{{ route('scheduled_jobs.getstore') }}",   // Laravel route for deletion
                    type: 'POST',
                    data: {
                        store_type_id: store_type_id,
                        job_id: job_id,
                        "_token": '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            if(response.options){
                                $('#store_list tbody').html(response.options);
                            }
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });
            }

        });
    </script>
@stop