@extends('adminlte::page')

@section('title', 'Add/Edit Customer')

@section('content_header')
    <h1>{{ isset($device) ? 'Edit Customer' : 'Add Customer' }}</h1>
@stop

@section('content')
    <form method="POST"
          action="{{ isset($customer) ? route('customers.update', ['id' => $customer->id]) : route('customers.store') }}">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name"
                                   name="first_name"
                                   placeholder="Enter first name" value="{{ old('first_name', $customer['first_name'] ?? '') }}">
                            @if ($errors->has('first_name'))
                                <span class="text-danger">{{ $errors->first('first_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name"
                                   name="last_name"
                                   placeholder="Enter last name" value="{{ old('last_name', $customer['last_name'] ?? '') }}">
                            @if ($errors->has('last_name'))
                                <span class="text-danger">{{ $errors->first('last_name') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-7">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" class="form-control @error('full_name') is-invalid @enderror" id="full_name"
                                   name="full_name"
                                   placeholder="Enter Full name" value="{{ old('full_name', $customer['full_name'] ?? '') }}">
                            @if ($errors->has('full_name'))
                                <span class="text-danger">{{ $errors->first('full_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="user_id">User ID</label>
                            <input type="text" class="form-control @error('user_id') is-invalid @enderror" id="user_id"
                                   name="user_id"
                                   placeholder="Enter User ID" value="{{ old('user_id', $customer['user_id'] ?? '') }}">
                            @if ($errors->has('user_id'))
                                <span class="text-danger">{{ $errors->first('user_id') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
{{--                <div class="row">--}}
{{--                    <div class="col-md-8">--}}
{{--                        <div class="form-group">--}}
{{--                            <label for="store_id">Store </label>--}}
{{--                            <select class="form-control @error('store_id') is-invalid @enderror" id="store_id"--}}
{{--                                    name="store_id">--}}
{{--                                <option value="">Select Store</option>--}}
{{--                                @foreach($stores as $store)--}}
{{--                                    <option value="{{ $store->id }}"--}}
{{--                                            {{ old('store_id', $customer['store_id'] ?? '') == $store->id ? 'selected' : '' }}>--}}
{{--                                        {{ $store->name }}--}}
{{--                                    </option>--}}
{{--                                @endforeach--}}
{{--                            </select>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
                {{--                Create a table in which the columns are email_address, primary checkbox --}}
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"> Emails </h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>
                                    <button type="button" class="btnp-0">
                                        <i class="fas fa-plus-circle text-success"></i>
                                    </button>
                                </th>
                                <th>
                                    Email
                                </th>
                                <th>
                                    Primary
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>1.</td>
                                <td>
                                    <input type="text" class="form-control" name="email[]">
                                </td>
                                <td>
                                    <input type="checkbox" class="">
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"> Phones </h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>
                                    <button type="button" class="btnp-0">
                                        <i class="fas fa-plus-circle text-success"></i>
                                    </button>
                                </th>
                                <th>
                                    Phone
                                </th>
                                <th>
                                    Primary
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>1.</td>
                                <td>
                                    <input type="text" class="form-control" name="email[]">
                                </td>
                                <td>
                                    <input type="checkbox" class="">
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="cart-title">Addresses</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>
                                    <button type="button" class="btnp-0">
                                        <i class="fas fa-plus-circle text-success"></i>
                                    </button>
                                </th>
                                <th>
                                    Address
                                </th>
                                <th>
                                    Address Type
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>1.</td>
                                <td>
                                    <input type="text" class="form-control" name="email[]">
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="email[]">
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 ">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Orders</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>
                                </th>
                                <th>
                                    Order ID
                                </th>
                                <th>
                                    Order Date
                                </th>
                                <th>
                                    Order Status
                                </th>
                                <th>
                                    Order Total
                                </th>

                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>1.</td>
                                <td>
                                </td>
                                <td>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-center">
            <button type="submit" class="btn btn-success">Submit</button>
            @if(!isset($customer))
                <button type="reset" class="btn btn-primary">Reset</button>
            @endif
        </div>
    </form>
@stop

@section('js')
    <script>
        $(document).ready(function () {

        });
    </script>
@stop
