@extends('adminlte::page')

@section('content_header')
    <h1>Listing Requirements</h1>
@stop

@section('content')
    <div class="pt-2">
        <a href="{{ route('listing_requirements.create') }}" class="btn btn-primary mb-3">Add New Requirement</a>
        <span id="clear_session_filter" class="btn btn-secondary float-right mr-2">Clear Filter</span>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
                <i class="fa fa-angle-down float-right"></i>
            </div>
            <div class="card-body" style="display: none;">
                <form id="filter-form">
                    <input type="hidden" name="store_id" id="store_id">
                    <div class="row pt-3">
                        <div class="col-md-4">
                            <div class="row">
                                <div class="col-4 text-right">
                                    Store Type
                                </div>
                                <div class="col-8">
                                    <select class="form-control" name="store_type_id">
                                        <option value="">--</option>
                                        @if(count($storeTypes ?? []) > 0)
                                            @foreach($storeTypes as $st)
                                                <option value="{{ $st->id }}">{{ $st->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="row">
                                <div class="col-4 text-right">
                                    Type
                                </div>
                                <div class="col-8">
                                    <select class="form-control" name="type">
                                        <option value="">--</option>
                                        <option value="column">Column</option>
                                        <option value="key_value">Key Value</option>

                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="row">
                                <div class="col-4 text-right">
                                    Category
                                </div>
                                <div class="col-8">
                                    <input type="text" name="category" id="category" class="form-control">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row pt-3">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="form-control btn btn-primary">Apply Filter</button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="reset" id="resetForm" class="form-control btn btn-secondary">Reset</button>
                            </div>
                        </div>
                    </div>


                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped no-footer" id="listingRequirementTable">
                <thead>
                <tr>
                    <th>Store Type</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Table</th>
                    <th>Column</th>
                    <th>Key Column</th>
                    <th>Key Value</th>
                    <th>Value Column</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                {{--@foreach ($requirements as $req)
                    <tr>
                        <td>{{ $req->storeType->name ?? '' }}</td>
                        <td>{{ $req->category }}</td>
                        <td>{{ $req->type }}</td>
                        <td>{{ $req->table }}</td>
                        <td>{{ $req->column }}</td>
                        <td>{{ $req->key_column }}</td>
                        <td>{{ $req->key_value }}</td>
                        <td>{{ $req->value_column }}</td>
                        <td>
                            <a href="{{ route('listing_requirements.edit', $req->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('listing_requirements.destroy', $req->id) }}" method="POST" style="display:inline-block;">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this requirement?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach--}}
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script>
        var accountTransactionTable;
        $(document).ready(function() {


            //$('#listingRequirementTable').DataTable();

            $('.card-header').on('click', function () {
                if ($(this).find('.fa').hasClass('fa-angle-down')) {
                    $(this).find('.fa').removeClass('fa-angle-down');
                    $(this).find('.fa').addClass('fa-angle-up');
                } else {
                    $(this).find('.fa').removeClass('fa-angle-up');
                    $(this).find('.fa').addClass('fa-angle-down');
                }
                $('.card-body').toggle();
            });
            $('#created_at, #transaction_date, #cancelled_date').datepicker({
                dateFormat: 'yy-mm-dd',
            });


            loadDatatables();


            $('#filter-form').on('submit', function (e) {
                e.preventDefault(); // Prevent default form submission
                accountTransactionTable.ajax.reload();
            });

            document.getElementById("resetForm").addEventListener("click", function () {
                sessionStorage.removeItem("all_listing_requirement_filters");
            });

            $(document).on('click','#clear_session_filter',function () {
                sessionStorage.removeItem("all_listing_requirement_filters");
                $('#filter-form')[0].reset();
                loadDatatables();
            });

        });

        document.addEventListener("DOMContentLoaded", function () {
            let savedFilters = JSON.parse(sessionStorage.getItem("all_listing_requirement_filters"));
            if (savedFilters) {
                console.log(savedFilters);
                Object.keys(savedFilters).forEach(function (key) {

                    //console.log(savedFilters[key]);
                    let input = $('#filter-form [name="'+savedFilters[key].name+'"]');
                    if (input) {
                        input.val(savedFilters[key].value)
                    }

                    if( savedFilters[key].name == 'store_id'){
                        $('#listing_store').val(savedFilters[key].value);
                    }

                });

                accountTransactionTable.ajax.reload();
            }
        });

        function loadDatatables(){

            $("#listingRequirementTable").dataTable().fnDestroy();

            accountTransactionTable = $("#listingRequirementTable").DataTable({
                "iDisplayLength": 10,
                "info": true,
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "processing": true,
                "serverSide": true,
                "searching": true,
                "stateSave": true,
                "aLengthMenu": [10, 20, 25, 50, 100],
                ajax: {
                    url: '{{ route('listing_requirements.filter') }}',
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

                        sessionStorage.setItem("all_listing_requirement_filters", JSON.stringify(formData));

                        var columnValues = [];
                        if (accountTransactionTable) {
                            accountTransactionTable.rows({ search: 'applied' }).every(function(rowIdx) {
                                var rowData = this.data();
                                columnValues.push(rowData.id);
                            });
                        }

                        // Add to the request payload
                        d.columnValues = columnValues;
                    },
                },
                "order": [[ 1, "asc" ]],
                columns: [
                    {data: 'store_type', name: 'store_type',orderable: false, searchable: false},
                    {data: 'category', name: 'category'},
                    {data: 'type', name: 'type'},
                    {data: 'table', name: 'table'},
                    {data: 'column', name: 'column'},
                    {data: 'key_column', name: 'key_column'},
                    {data: 'key_value', name: 'key_value'},
                    {data: 'value_column', name: 'value_column'},
                    {data: 'action', name: 'action'}
                ],
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
                }
            });


            $( '#listingRequirementTable_wrapper').removeClass( 'form-inline' );

            $.fn.dataTable.ext.errMode = 'throw';
        }

    </script>
@stop