@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($listing_template) ? 'Edit Listing Template' : 'Add Listing Template' }}</h1>
@stop

@section('content')
    <form action="{{ isset($listing_template) ? route('listing_template.update',['id' => $listing_template['id']]) : route('listing_template.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Listing Template Name" value="{{ old('name', $listing_template['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="code">Code</label>
                            <textarea name="code" id="code" class="form-control @error('name') is-invalid @enderror" rows="10">{{ old('code', $listing_template['code'] ?? '') }}</textarea>
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                    </div>
                </div>
        </div>
        <div class="card-footer text-center">
            <button type="submit" class="btn btn-success">Submit</button>
            <a href="{{ route('listing_template.index') }}" class="btn btn-secondary">Cancel</a>
            @if(!isset($listing_template))
                <button type="reset" class="btn btn-primary">Reset</button>
            @endif
        </div>
    </form>
@stop
