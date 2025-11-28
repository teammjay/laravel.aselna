@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Error Log Dashboard')

@section('content_header')
    <h1>Error Log Dashboard</h1>
@stop

@section('adminlte_css')
    <style>
        .status-pending { color: orange; font-weight: 600; }
        .status-processing { color: blue; font-weight: 600; }
        .status-completed { color: green; font-weight: 600; }
        .status-failed { color: red; font-weight: 600; }
        .show-error{cursor: pointer; color:#e71717;}
        .hide{display: none;}
    </style>
@stop

@section('content')

    <div class="card card-secondary">
        <div class="card-header bg-white">
            <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a> </h3>
            <i class="fa fa-angle-down float-right"></i>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form">
                <input type="hidden" name="store_id" id="store_id">
                <div class="row">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Store
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="store_type" name="store_id">
                                    <option value=""></option>
                                    @if(count($stores ?? []) > 0)
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}">{{ ucfirst($store->name) }} - {{ isset($store->storeType) ? ucfirst($store->storeType->code) : '' }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Operation
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="operation_operator">
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="operation" id="operation" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Created Date
                            </div>
                            <div class="col-6">
                                <input type="text" name="created_at" id="created_at" class="form-control" autocomplete="off">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row mt-3">

                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Stack Trace
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="status_operator">
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="stack_trace" id="stack_trace" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Updated Date
                            </div>
                            <div class="col-6">
                                <input type="text" name="updated_at" id="updated_at" class="form-control" autocomplete="off">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" id="apply_filter" class="form-control btn btn-primary">Filter</button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="reset" class="form-control btn  btn-reset btn-secondary">Reset</button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <div class="row mt-3 mb-3">
        <div class="col-md-10 d-flex align-items-center" style="gap:10px;">
            <button type="button" class="btn btn-default" id="purge_records">
                <i class="fa fa-fw fa-trash"></i> Purge Records
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="errorLogsTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Store</th>
                <th>Created Date</th>
                <th>Updated Date</th>
                <th>Error Count</th>
                <th>Error Level</th>
                <th>Operaton</th>
                <th>Status</th>
                <th>Stack Trace</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <!-- Mark as cancel shipment modal -->
    <div class="modal fade" id="erroInfoModal">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="erroInfoModalLabel">Error Info</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="word-wrap: break-word">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- purge option modal -->
    <div class="modal fade" id="purgeOptionModal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="erroInfoModalLabel">Purge Records</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="word-wrap: break-word">
                    <div class="d-flex align-items-center">
                        <input type="radio" class="mr-2" name="purge_opt" value="older_30_days" checked> <b>Older than 30 Days</b>
                        <input type="radio" class="mr-2 ml-2" name="purge_opt" value="older_7_days"> Older than 7 Days
                        <input type="radio" class="mr-2 ml-2" name="purge_opt" value="custom_period"> Between Dates
                    </div>

                    <div class="mt-5 hide" id="custom_period_sec">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="form-group">
                                <label>From Date:</label>
                                <input type="text" name="from_date" id="from_date" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>To Date:</label>
                                <input type="text" name="to_date" id="to_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="purgeRecords"><i class="fa fa-check"></i> Purge </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script>
        var table;
        $(document).ready(function () {
            /*$('#errorLogsTable').DataTable({
                sort:false,
                "order": [[ 8, "desc" ]],
            });*/

            $('#created_at,#updated_at').datepicker();
            $('#from_date,#to_date').datepicker({
                maxDate: '0'
            });

            $('.card-header').on('click',function () {
                if($(this).find('.fa').hasClass('fa-angle-down')){
                    $(this).find('.fa').removeClass('fa-angle-down');
                    $(this).find('.fa').addClass('fa-angle-up');
                }else{
                    $(this).find('.fa').removeClass('fa-angle-up');
                    $(this).find('.fa').addClass('fa-angle-down');
                }
                $('.card-body').toggle();
            });


            $(document).on('click', '.show-error', function(event) {
                var content = $(this).data('content');
                $('#erroInfoModal').find('.modal-body').html(content);
                $('#erroInfoModal').modal('show');
            });

            $(document).on('change', '#per_page', function(event) {
                $('#filter-form').submit();
            });

            $('#filter-form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                loadDatatables();

            });

            loadDatatables();

            $(document).on('click','input[name="purge_opt"]',function () {
                if($(this).val() == 'custom_period'){
                    $('#custom_period_sec').removeClass('hide');
                }else{
                    $('#custom_period_sec').addClass('hide');
                }
            });


            $(document).on('click','#purge_records',function () {
                const confirmation = confirm("Are you sure you want to purge the queue records?");
                if (confirmation) {
                    $('#purgeOptionModal').modal('show');
                }

            });

            $(document).on('click','#purgeRecords',function () {

                var purge_opt = $('input[name="purge_opt"]:checked').val();
                var from_date = $('#from_date').val();
                var to_date = $('#to_date').val();

                if(purge_opt == ''){
                    alert('Please select the purge option');
                    return false;
                }
                $.ajax({
                    url: "{{ route('error_logs.purge') }}",
                    type: 'POST',
                    data: {
                        purge_opt: purge_opt,
                        from_date:from_date,
                        to_date:to_date,
                        "_token": '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        // If success, remove the user row from the table
                        if (response.success) {
                            alert(response.message);
                            $('#purgeOptionModal').modal('hide');
                        }
                    },
                    error: function (xhr) {
                        // Handle error response
                        alert('Error: ' + xhr.responseJSON.error);
                    }
                });

            });


        });

        function loadDatatables(){

            $("#errorLogsTable").dataTable().fnDestroy();

            table = $('#errorLogsTable').DataTable({
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
                    url: "{{ route('error_logs.filter') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    data: function(d) {
                        // Create a FormData object
                        var formData = $('#filter-form').serializeArray(); // Serialize form inputs

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
                        d.columnValues = columnValues;
                    },
                    //processData: false,
                    //contentType: false
                },
                "order": [[ 2, "desc" ]],
                'columns': [
                    { data: 'store'},
                    { data: 'created_at' },
                    { data: 'updated_at' },
                    { data: 'error_count' },
                    { data: 'error_level' },
                    { data: 'operation' },
                    { data: 'status' },
                    { data: 'stack_trace' },
                ],
                columnDefs: [
                    {
                        targets: [0,4,6,7], // Index of the column you want to disable sorting for
                        orderable: false // Disable sorting
                    }
                ]
            });

            $( '#errorLogsTable_wrapper').removeClass( 'form-inline' );

            $.fn.dataTable.ext.errMode = 'throw';
        }
    </script>
@stop