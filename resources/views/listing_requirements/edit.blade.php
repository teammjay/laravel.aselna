@extends('adminlte::page')

@section('content_header')
    <h1>Edit Listing Requirement</h1>
@stop

@section('content')
    <div class="container mt-4">
        <h2></h2>

        <form method="POST" action="{{ route('listing-requirements.update', $listingRequirement) }}">
            @csrf
            @method('PUT')

            @include('listing_requirements.partials.form', ['button' => 'Update'])

        </form>
    </div>
@endsection
