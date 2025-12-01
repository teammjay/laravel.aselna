@php use App\Constants\FilterCodes; @endphp
@extends('adminlte::page')

@section('title', 'Shipment')

@section('content_header')
    <h1>Account</h1>
@stop

@section('adminlte_css')
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link href="{{ asset('css/jquery.datetimepicker.css') }}" rel="stylesheet">

    <style>
        .select2-container--default .select2-dropdown .select2-search__field:focus, .select2-container--default .select2-search--inline .select2-search__field:focus {
            outline: 0;
            border: none;
        }

        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
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

        table th, table td, .column-no-whitespace {
            white-space: nowrap;
        }
        .bulk-edit{display: none; width: 150px;}
        table th, .column-no-whitespace{
            white-space: nowrap;
        }
        .main-image img {
            /*width: 300px;
            height: 300px;*/
            object-fit: cover;
            cursor: pointer;
        }

    </style>
@stop

@section('content')
    @csrf
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}</div>
    @endif
    <div class="row my-3">
        <div class="col-md-2">
            <select class="form-control mt-3 mb-3" name="store_id" id="listing_store">
                <option value="">- All Store -</option>
                @if(count($stores ?? []) > 0)
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-md-10 d-flex align-items-center" style="gap:10px;">
            <button type="button" class="btn btn-default" id="refresh_list">
                <i class="fa fa-fw fa-spinner"></i> Refresh
            </button>
            <button type="button" class="btn btn-default" id="ship_all">
                <i class="fa fa-fw fa-shipping-fast"></i> Ship All
            </button>
            <button type="button" class="btn btn-default" id="ship_selected">
                <i class="fa fa-fw fa-shipping-fast"></i> Ship Selected
            </button>
            <button type="button" class="btn btn-default" id="mark_as_shipped">
                Mark As Shipped
            </button>
            <button type="button" class="btn btn-default" id="cancel_selected">
                Cancel Selected
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="accountTable" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th></th>
                <th>Name</th>
                <th>Shipping Solution</th>
                <th>Sandbox Account</th>
            </tr>
            </thead>
            <tbody id="account-list">
            </tbody>
        </table>
    </div>
@stop

@section('adminlte_js')
    <script>
    $(document).ready(function() {
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

        $(document).on('click','#shipment-list tr',function (e) {
            let ele = $(this);

            if ($(e.target).is('input, textarea, select, checkbox, a, i, button, img')) {
                return;
            }
            //$('#product-list tr').removeClass('bg-blue');
            //$('.pro-id').prop('checked',false);
            if(ele.find('.pro-id').is(':checked')){
                ele.removeClass('bg-blue');
                ele.find('.pro-id').prop('checked', false);
                ele.find('.bulk-edit').attr('disabled',true).hide();
                ele.find('.prVal').show();
            }else {
                ele.addClass('bg-blue');
                ele.find('.pro-id').prop('checked', true);
            }
        });


        $('#filter-form').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            loadDatatables();
        });



        $(document).on('click','#update_selected',function () {
            updateRecord('selected');
        });

        $(document).on('click','#update_all',function () {
            updateRecord('all');
        });

        $(document).on('change','#carrier_id',function () {
            var ele = $(this);
            getShippingCarrierService(ele);
        });

        $("#accountTable").dataTable();

    });

    function loadDatatables(){

        $("#accountTable").dataTable().fnDestroy();

        var table = $('#accountTable').DataTable({
            "iDisplayLength": 20,
            "info": true,
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "searching": true,
            "aLengthMenu": [20, 25, 50, 100],
            'ajax': {
                url: "{{ route('shipment.filter') }}",
                type: "POST",
                //dataType:'json',
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

                    d['store_id'] = $('#listing_store').val();

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
            "order": [[ 6, "desc" ]],
            'columns': [
                { data: 'action'},
                { data: 'name'},
                { data: 'status' },
                { data: 'carrier_service' },
                { data: 'store' },
                { data: 'order_id' },
                { data: 'order_date' },
                { data: 'product_code' },
                { data: 'weight' },
                { data: 'first_name' },
                { data: 'last_name' },
                { data: 'city' }
            ],
            columnDefs: [
                {
                    targets: 3, // The column index
                    className: 'column-no-whitespace' // The class to add
                },
                {
                    targets: [0,1,3,7], // Index of the column you want to disable sorting for
                    orderable: false // Disable sorting
                }
            ]
        });

        $( '#shipmentTable_wrapper').removeClass( 'form-inline' );

        $.fn.dataTable.ext.errMode = 'throw';
    }
</script>
@stop


