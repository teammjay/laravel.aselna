@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])

@section('auth_header', __('adminlte::adminlte.password_reset_message'))

@section('auth_body')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <style>
        .login-logo img{
            margin: 0 auto;
        }
    </style>

    <form action="{{ route('password.email') }}" method="POST">
        @csrf
        {{-- Email field --}}
        <div class="input-group mb-3">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="{{ __('adminlte::adminlte.email') }}" autofocus>

            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-envelope {{ config('adminlte.classes_auth_icon', '') }}"></span>
                </div>
            </div>

            @error('email')
            <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block"><span class="fas fa-share-square"></span> {{ __('adminlte::adminlte.send_password_reset_link') }}</button>
    </form>
@endsection

