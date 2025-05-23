@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($shipping_account) ? 'Edit Shipping Account' : 'Add Shipping Account' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($shipping_account) ? route('shipping_account.update',['id' => $shipping_account['id']]) : route('shipping_account.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"  name="name"
                                   placeholder="Enter Shipping Carrier Package Name" value="{{ old('name', $shipping_account['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="api_key">API Key</label>
                            <input type="text" class="form-control" id="api_key"  name="api_key"
                                   placeholder="Enter API Key" value="{{ old('api_key', $shipping_account['api_key'] ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="user_id">User Id</label>
                            <input type="text" class="form-control" id="user_id"  name="user_id"
                                   placeholder="Enter User ID" value="{{ old('user_id', $shipping_account['user_id'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="text" class="form-control" id="password"  name="password"
                                   placeholder="Enter Password" value="{{ old('password', $shipping_account['password'] ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="shipping_solution_id">Shipping Solution</label>
                            <select class="form-control" name="shipping_solution_id" id="shipping_solution_id">
                                <option value="">--</option>
                                @if(count($shipping_solutions ?? []) > 0)
                                    @foreach($shipping_solutions as $ss)
                                        <option value="{{ $ss->id }}" @if(isset($shipping_account['shipping_solution_id']) && $shipping_account['shipping_solution_id'] == $ss->id) selected @endif >{{ $ss->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="account_id">Account ID</label>
                            <input type="text" class="form-control" id="account_id"  name="account_id"
                                   placeholder="Enter Account ID" value="{{ old('account_id', $shipping_account['account_id'] ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pass_phrase">Pass Phrase</label>
                            <input type="text" class="form-control" id="pass_phrase"  name="pass_phrase"
                                   placeholder="Enter Pass Phrase" value="{{ old('pass_phrase', $shipping_account['pass_phrase'] ?? '') }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="requester_id">Requester ID</label>
                            <input type="text" class="form-control" id="requester_id"  name="requester_id"
                                   placeholder="Enter Requester ID" value="{{ old('requester_id', $shipping_account['requester_id'] ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="maximum_allowable_balance">Maximum Allowable Balance</label>
                            <input type="text" class="form-control" id="maximum_allowable_balance"  name="maximum_allowable_balance"
                                   placeholder="Enter Maximum Allowable Balance" value="{{ old('maximum_allowable_balance', $shipping_account['maximum_allowable_balance'] ?? '') }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="auto_recharge_threshold">Auto Recharge Threshold</label>
                            <input type="text" class="form-control" id="auto_recharge_threshold"  name="auto_recharge_threshold"
                                   placeholder="Enter Auto Recharge Threshold" value="{{ old('auto_recharge_threshold', $shipping_account['auto_recharge_threshold'] ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sandbox_account">Sandbox Account</label>
                            <input type="checkbox" class="custom-control" name="sandbox_account" id="sandbox_account" @if((isset($shipping_account) && $shipping_account['sandbox_account'] === 1)) checked @endif >
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="auto_recharge">Auto Recharge</label>
                            <input type="checkbox" class="custom-control" name="auto_recharge" id="auto_recharge" @if((isset($shipping_account) && $shipping_account['auto_recharge'] === 1)) checked @endif >
                        </div>
                    </div>
                </div>


            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($shipping_account))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop