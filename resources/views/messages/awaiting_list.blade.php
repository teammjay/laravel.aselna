@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Messages')

@section('content_header')
    <h1>Awaiting Messages</h1>
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
        tr.bg-blue {
            background-color: #007bff!important;
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
            @include('messages.filter_form', ['ticketTypes' => $ticketTypes])
        </div>
    </div>
    <div class="row my-3">

        <div class="col-md-2">
            <select class="form-control" id="messages_store" name="messages_store">
                <option value="">- All Store -</option>
                @if(count($stores ?? []) > 0)
                    @foreach($stores as $store)
                        @if(!empty($store->messageAccount) && $store->messageAccount->active == 1)
                            <option value="{{ $store->id }}">{{ $store->name }}{{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                        @endif
                    @endforeach
                @endif
            </select>

        </div>
        <div class="col-md-9 d-flex">
            <button type="button" class="btn btn-default mr-2" id="open_message" disabled="">
                <i class="fas fa-fw fa-pencil-alt"></i> Open
            </button>

            <button type="button" class="btn btn-default mr-2" id="mark_as_open" disabled="">
                <i class="fas fa-fw fa-envelope"></i> Mark As Open
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
            //$('#message-list tr').removeClass('bg-blue');
            //$('input[name="message_id[]"]').prop('checked',false);
            ele.addClass('bg-blue');
            ele.find('input[name="message_id[]"]').prop('checked',true);
            $('#open_message').removeAttr('disabled');
            $('#mark_as_open').removeAttr('disabled');
            //var ticket_id = ele.find('input[name="message_id[]"]').val();
            //location.href = "{{ url('messages/detail') }}/" + ticket_id;
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

        $(document).on('click','#mark_as_open',function () {
            var selected_ticket = [];

            $('input[name="message_id[]"]:checked').each(function () {
                selected_ticket.push($(this).val())
            });

            var data = {
                ticket_id: selected_ticket,
                status: 0,
                message_read: 0
            };

            if(selected_ticket.length) {
                updateTicket(data);
            }
        });

    });

    function updateTicket(data) {
        let ticket_id = $('input[name="message_id[]"]:checked').val();

        if(ticket_id != '') {
            $.ajax({
                type: "POST",
                url: "{{ route('message.updateticket') }}",
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },

                success: function (data) {
                    if (data.success) {
                        location.reload();
                    }
                },
                error: function (xhr) {
                },
                complete: function () {
                    $("#ajax-preloader").hide();
                }
            });
        }
    }

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
            "stateSave": true,
            "stateDuration": -1,
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

                    d['status'] = 1;

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


