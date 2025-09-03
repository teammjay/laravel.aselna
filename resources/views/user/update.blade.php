@extends('adminlte::page')

@section('title', 'Edit User')

@section('content_header')
    <h1>Edit User</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <style>
        .nav-link {
            padding: 0.5rem 0.9rem !important;
        }
        ul.manufacturer-list{list-style: none;}
        ul.manufacturer-list li{display: inline-block; width:12%; margin-bottom: 10px;}
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
        .select2-container--default .select2-selection--multiple .select2-selection__choice__display{color:#333;}
    </style>
@stop


@section('content')

    @csrf
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="general-tab" data-toggle="pill" href="#tab1" role="tab" aria-controls="tab1" aria-selected="true">
                        General
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="message-tab" data-toggle="pill" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                        SMTP Account
                    </a>
                </li>

            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="custom-tabs-one-tabContent">
                <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="general-tab">
                    <form method="POST" id="frmtab1">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="user_id">ID</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="user_id" name="user_id" value="{{ old('id',$user->id) }}" placeholder="Enter product title" readonly="">
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="name">Name</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name',$user->name) }}" placeholder="Enter user name">
                                        <span class="error-message text-danger" id="name-error"></span>
                                    </div>
                                </div>
                                
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="email">Email</label>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control" id="email" name="email" value="{{ old('email',$user->email) }}">
                                        <span class="error-message text-danger" id="email-error"></span>
                                    </div>
                                </div>
                                <div class="row mt-3 g-3 align-items-center">
                                    <div class="col-md-5">
                                        <label for="email">CC Email</label>
                                    </div>
                                    <div class="col-md-7">
                                        <textarea class="form-control" id="cc_email" name="cc_email">{{ old('cc_email',$user->cc_email) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-center mt-3">
                            <button type="button" class="btn btn-success" onclick="saveUserTabData(1)">Save</button>
                            <a href="{{ route('user.list') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>

                </div>
                <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="message-tab">
                    <button type="button" class="btn btn-secondary" id="add_message" data-toggle="modal" data-target="#addMessageModal" ><i class="fa fa-plus-square"></i> Create</button>
                    <table class="table table-bordered mt-2">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Outgoing Account</th>
                            <th>Active</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                            @if(count($userAccount ?? []) > 0)
                                @foreach($userAccount as $uaccount)
                                    <tr class="field-group">
                                        <td>{{ $uaccount->name }}</td>
                                        <td>{{ $uaccount->email }}</td>
                                        <td>
                                            {{ (isset($uaccount->outgoingAccount)) ? 'Email: '.$uaccount->outgoingAccount->email : '' }}<br>
                                            {{ (isset($uaccount->outgoingAccount)) ? 'User Name: '.$uaccount->outgoingAccount->username : '' }}
                                        </td>
                                        <td>{{ $uaccount->active == 1 ? 'Yes' : 'No' }}</td>
                                        <td>

                                            <button type="button" class="btn btn-primary edit-message-account" data-id="{{ $uaccount->id }}" title="Edit"> <i class="fas fa-fw fa-pencil-alt"></i></button>
                                            <button type="button" class="btn btn-danger remove-message-account" data-id="{{ $uaccount->id }}"> <i class="fas fa-fw fa-trash"></i></button></td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('user.modal')
@stop

@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Lightbox2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script>
        var user_id = "{{ $user->id ?? '' }}"; // Passed from controller

        function saveUserTabData(tabId) {
            const form = document.querySelector("#frmtab"+ tabId);
            let formData = new FormData(form);
            formData.append('user_id', user_id);
            formData.append('tab', tabId);

            $.ajax({
                type: 'POST',
                url: "{{ route('user.save') }}", // Adjust this URL to your endpoint
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                dataType:'json',
                success: function(response) {
                    // Display a success message
                    alert(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Validation error
                        var errors = xhr.responseJSON.errors;

                        // Clear previous errors
                        $('.error-message').text('');

                        // Loop through and display errors
                        $.each(errors, function(field, messages) {
                            $('#' + field + '-error').text(messages[0]); // Display the first error message
                        });
                    }
                }
            });

        }


        function saveUserMessageAccount() {
            const form = document.querySelector("#frmUserAccount");
            let formData = new FormData(form);
            formData.append('user_id', user_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('user.notificationaccount') }}", // Adjust this URL to your endpoint
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                dataType:'json',
                success: function(response) {
                    // Display a success message
                    alert(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Validation error
                        var errors = xhr.responseJSON.errors;

                        // Clear previous errors
                        $('.error-message').text('');

                        // Loop through and display errors
                        $.each(errors, function(field, messages) {
                            $("#frmuserMessageAccount").find('#acc_' + field + '-error').text(messages[0]); // Display the first error message
                            $("#frmuserMessageAccount").find('#' + field + '-error').text(messages[0]); // Display the first error message

                        });
                    }
                }
            });

        }

        $(document).ready(function() {

            $(document).on('click', '.edit-message-account', function() {
                var accountId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion

                $.ajax({
                    type: 'POST',
                    url: "{{ route('user.getusernotificationAccount') }}",
                    data: {
                        "account_id": accountId
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    dataType:'json',
                    success: function(response) {
                        // Display a success message
                        if(response.data) {
                            $('#user_account_id').val(response.data.id);
                            $('#acc_name').val(response.data.name);
                            $('#acc_email').val(response.data.email);
                            if(response.data.api_account == 1){
                                $('#api_account').prop('checked',true);
                            }
                            if(response.data.active == 1){
                                $('#message_active').prop('checked',true);
                            }

                            if(response.outgoingAccount){
                                $('#acc_out_id').val(response.outgoingAccount.id);
                                $('#acc_out_protocol').val(response.outgoingAccount.protocol);
                                $('#acc_out_email').val(response.outgoingAccount.email);
                                $('#acc_out_user_id').val(response.outgoingAccount.username);
                                $('#acc_out_password').val(response.outgoingAccount.password);
                                $('#acc_out_server').val(response.outgoingAccount.server);
                                $('#acc_out_port').val(response.outgoingAccount.port);
                                $('#acc_out_ssl_port').val(response.outgoingAccount.ssl_port);
                                $('#acc_out_tls_port').val(response.outgoingAccount.tls_port);

                                if(response.outgoingAccount.requires_ssl == 1){
                                    $('#acc_out_requires_ssl').prop('checked',true);
                                }
                                if(response.outgoingAccount.requires_tls == 1){
                                    $('#acc_out_requires_tls').prop('checked',true);
                                }
                                if(response.outgoingAccount.requires_authentication == 1){
                                    $('#acc_out_requires_authentication').prop('checked',true);
                                }
                            }

                            $('#addMessageModal').modal('show');
                        }
                    },
                    error: function(xhr) {
                        alert(response.message);
                    }
                });
            });


            $(document).on('click', '.remove-message-account', function() {
                var accId = $(this).data('id');
                var ele = $(this);
                // Confirm the deletion
                if (confirm("Are you sure you want to delete this message accounts?")) {

                    $.ajax({
                        type: 'POST',
                        url: "{{ route('user.removenotificationaccount') }}", // Adjust this URL to your endpoint
                        data: {
                            "accId": accId
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        dataType:'json',
                        success: function(response) {
                            // Display a success message
                            alert(response.message);
                            ele.parents('tr.field-group').remove();
                        },
                        error: function(xhr) {
                            alert(response.message);
                        }
                    });
                }
            });
        });
    </script>
@stop
