@extends('adminlte::page')

@section('content_header')
    <h1>Add Listing Requirement</h1>
@stop

@section('content')
    <div class="mt-4">

        <form method="POST" action="{{ route('listing_requirements.store') }}">
            @csrf

            @include('listing_requirements.partials.form', ['button' => 'Create'])

        </form>
    </div>
@endsection
