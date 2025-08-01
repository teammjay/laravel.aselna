@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Messages')

@section('content_header')
    <h1>Spam Messages</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-dropdown .select2-search__field:focus, .select2-container--default .select2-search--inline .select2-search__field:focus {
            outline: 0;
            border: none;
        }
        .select2-container{width:100% !important;}
        .select2-container .select2-selection--single{
            height: calc(2.25rem + 2px);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: auto;
            position: absolute;
            top: 50%;
            right: 1px;
            width: 20px;
            transform: translateY(-50%);
        }

        table th, table td, .column-no-whitespace{
            white-space: nowrap;
        }
        table tbody tr{
            /*background-color: rgba(0,0,0,0.051) !important;*/
        }
        .unread {
            background-color: #fff !important;
            font-weight: 600;
        }
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
                            <div class="col-3 text-right">
                                First Name
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="first_name_operator">
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
                                <input type="text" name="first_name" id="first_name" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Last Name
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="last_name_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="last_name" id="last_name" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Subject
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="subject_operator">
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
                                <input type="text" name="subject" id="subject" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Order Id
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="order_id_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="order_id" id="order_id" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Ticket Code
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="ticket_code_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="ticket_code" id="ticket_code" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Body
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="body_operator">
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="body" id="body" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                Product Code
                            </div>
                            <div class="col-6">
                                <input type="text" name="product_code" id="product_code" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Ticket Type
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="ticket_type_operator">
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
                                <select class="form-control" name="ticket_type">
                                    <option value=""></option>
                                    @if(count($ticketTypes ?? []) > 0)
                                        @foreach($ticketTypes as $tt)
                                            <option value="{{ $tt->id }}">{{ $tt->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-6 text-right">
                                RMA
                            </div>
                            <div class="col-6">
                                <input type="text" name="rma" id="rma" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Sender Email
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="sender_email_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="text" name="sender_email" id="sender_email" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                Reference Id
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="reference_id_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-control" id="reference_id" name="reference_id">
                                    <option></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-3 text-right">
                                User Id
                            </div>
                            <div class="col-3">
                                <select class="custom-select" id="" name="user_id_operator">
                                    <option value="{{FilterCodes::EQUAL}}">=</option>
                                    <option value="{{FilterCodes::CONTAINS}}">contains</option>
                                    <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                                    <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                                    <option value="{{FilterCodes::IN}}">in</option>
                                    <option value="{{FilterCodes::NOT_IN}}">not in</option>
                                    <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                                    <option value="{{FilterCodes::IS_SET}}">is set</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input class="form-control" type="text" name="user_id" id="user_id">
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
    <div class="row my-3">

        <div class="col-md-2">
            <select class="form-control" id="messages_store" name="messages_store">
                <option value="">All stores</option>
                @if(count($stores ?? []) > 0)
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}{{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                    @endforeach
                @endif
            </select>

        </div>
        <div class="col-md-9 d-flex">
            <button type="button" class="btn btn-default" id="open_message" disabled="">
                <i class="fas fa-fw fa-pencil-alt"></i> Open
            </button>

        </div>
    </div>
    <div class="table-responsive">
        <table id="messageTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th></th>
                <th>Store</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Subject</th>
                <th>Received</th>
                <th>Ticket Type</th>
                <th>Status</th>
                <th>Order Id</th>
                <th>Ticket Code</th>
                <th>Created At</th>
            </tr>
            </thead>
            <tbody id="message-list">

               {{-- @include('messages.product_list', ['products' => $products])--}}

            </tbody>
        </table>
    </div>
    <div id="pagination" class="pt-3 pb-3">
        {{--@include('messages.pagination', ['products' => $products])--}}
    </div>
@stop

@section('adminlte_js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    var table;
    $(document).ready(function() {

        $(document).ajaxStart(function () {
            $("#ajax-preloader").show(); // Show preloader when AJAX starts
        }).ajaxStop(function () {
            $("#ajax-preloader").hide(); // Hide preloader when AJAX stops
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

        $(document).on('click','#message-list tr',function () {
            let ele = $(this);
            $('#message-list tr').removeClass('bg-blue');
            $('input[name="message_id[]"]').prop('checked',false);
            ele.addClass('bg-blue');
            ele.find('input[name="message_id[]"]').prop('checked',true);
            $('#open_message').removeAttr('disabled');
            var ticket_id = ele.find('input[name="message_id[]"]').val();
            location.href = "{{ url('messages/detail') }}/" + ticket_id;
        });

        $('#open_message').on('click',function () {
            let flag = true;
            $('input[name="message_id[]"]').each(function () {
                if($(this).is(':checked')){
                    flag = false;
                    let selectedId = $(this).val();
                    location.href = "{{ url('messages/detail') }}/" + selectedId;
                }
            });
        });

        $(document).on('dblclick','#message-list tr',function () {
            var ticket_id = $(this).find('input[name="message_id[]"]').val();
            location.href = "{{ url('messages/detail') }}/" + ticket_id;
        });

        /*table = $('#messageTable').DataTable({
            processing: true,
            serverSide: true,
            order: [[9, 'desc']],
            ajax: {
                url: '{{ route('message.getdata') }}',
                type: "POST",
                //dataType:'json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                data: function (d) {
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
                }
            },
            columns: [
                {data: 'store', name: 'store'},
                {data: 'first_name', name: 'first_name'},
                {data: 'last_name', name: 'last_name'},
                {data: 'subject', name: 'subject'},
                {data: 'received', name: 'received'},
                {data: 'ticket_type', name: 'ticket_type'},
                {data: 'status', name: 'status'},
                {data: 'order_id', name: 'order_id'},
                {data: 'ticket_code', name: 'ticket_code'},
                {data: 'created_at', name: 'created_at'}
            ]
        });*/

        loadDatatables();

        $('#messages_store').on('change',function () {
            $('#store_id').val($(this).val());
            loadDatatables();
        });

        $(document).on('change', '#per_page', function(event) {
            $('#filter-form').submit();
        });

        $('#filter-form').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            loadDatatables();
        });

    });

    function loadDatatables() {

        $("#messageTable").dataTable().fnDestroy();

        var table = $('#messageTable').DataTable({
            "iDisplayLength": 20,
            "info": true,
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "searching": false,
            "aLengthMenu": [20, 25, 50, 100],
            'ajax': {
                url: "{{ route('message.getdata') }}",
                type: "POST",
                //dataType:'json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                data: function (d) {
                    // Create a FormData object
                    var formData = $('#filter-form').serializeArray(); // Serialize form inputs

                    // Add form data to the DataTable's request payload
                    $.each(formData, function (index, field) {
                        d[field.name] = field.value; // Append form data to the DataTables request
                    });

                    d['status'] = 4;

                    var columnValues = [];
                    if (table) {
                        table.rows({search: 'applied'}).every(function (rowIdx) {
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
            "order": [[5, "desc"]],
            columns: [
                {data: 'message_id'},
                {data: 'store'},
                {data: 'first_name'},
                {data: 'last_name'},
                {data: 'subject'},
                {data: 'ticket_date'},
                {data: 'ticket_type'},
                {data: 'status'},
                {data: 'order_id'},
                {data: 'ticket_code'},
                {data: 'created_at'}
            ],
            columnDefs: [
                {
                    targets: [0], // Index of the column you want to disable sorting for
                    orderable: false // Disable sorting
                }
            ],
            createdRow: function(row, data, dataIndex) {
                if (!data.read) {
                    // Add class to the row if message is not read
                    $(row).addClass('unread');
                }
            }
        });

        $('#messageTable_wrapper').removeClass('form-inline');

        $.fn.dataTable.ext.errMode = 'throw';
    }

</script>
@stop


