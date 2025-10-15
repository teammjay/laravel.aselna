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
                    <th>Job ID</th>
                    <th>Chain ID</th>
                    <th>Name</th>
                    <th>Job</th>
                    <th>Status</th>
                    <th>Queued</th>
                    <th>Job Started</th>
                    <th>Job Finished</th>
                    <th>Error</th>
                    <th>Created At</th>
                </tr>
                </thead>
                <tbody>
                {{--@foreach($jobs as $job)
                    @php

                        $jobType = explode('/',str_replace("\\","/",$job->type));
                        $name = end($jobType);
                    @endphp
                    <tr>
                        --}}{{--<td>{{ $job->chain_id }}</td>--}}{{--
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
                @endforeach--}}
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script>
        $(document).ready(function () {
            /*$('#queueJobsTable').DataTable({
                sort:false,
                "order": [[ 8, "desc" ]],
            });*/

            loadDatatables();
        });

        function loadDatatables(){

            $("#queueJobsTable").dataTable().fnDestroy();

            var table = $('#queueJobsTable').DataTable({
                "iDisplayLength": 10,
                "info": true,
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "processing": true,
                "serverSide": true,
                "searching": true,
                "aLengthMenu": [10, 20, 25, 50, 100],
                'ajax': {
                    url: "{{ route('queue.filter') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    data: function(d) {
                        // Create a FormData object
                        /*var formData = $('#filter-form').serializeArray(); // Serialize form inputs

                        // Add form data to the DataTable's request payload
                        $.each(formData, function(index, field) {
                            d[field.name] = field.value; // Append form data to the DataTables request
                        });

                        var columnValues = [];
                        if (table) {
                            table.rows({ search: 'applied' }).every(function(rowIdx) {
                                var rowData = this.data();
                                columnValues.push(rowData.id);
                            });
                        }

                        // Add to the request payload
                        d.columnValues = columnValues;*/
                    },
                    //processData: false,
                    //contentType: false
                },
                "order": [[ 8, "desc" ]],
                'columns': [
                    { data: 'job_id'},
                    { data: 'chain_id' },
                    { data: 'name' },
                    { data: 'type' },
                    { data: 'status' },
                    { data: 'queue' },
                    { data: 'started_at' },
                    { data: 'finished_at' },
                    { data: 'output' },
                    { data: 'created_at' },
                ],
                columnDefs: [
                    {
                        targets: [0,1,7], // Index of the column you want to disable sorting for
                        orderable: false // Disable sorting
                    }
                ]
            });

            $( '#queueJobsTable_wrapper').removeClass( 'form-inline' );

            $.fn.dataTable.ext.errMode = 'throw';
        }
    </script>
@stop
