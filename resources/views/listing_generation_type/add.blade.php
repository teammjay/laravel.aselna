@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($listing_generation_type) ? 'Edit Listing Generation Type' : 'Add Listing Generation Type' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($listing_generation_type) ? route('listing_generation_type.update',['id' => $listing_generation_type['id']]) : route('listing_generation_type.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code"
                                   name="code"
                                   placeholder="Enter Listing Generation Type Code" value="{{ old('code', $listing_generation_type['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Listing Generation Type Name" value="{{ old('name', $listing_generation_type['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="singleton">Singleton</label>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" name="singleton" id="singleton" @if((isset($listing_generation_type) && $listing_generation_type['singleton'] === 1)) checked @endif >
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="allow_duplicates">Allow Duplicated</label>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" name="allow_duplicates" id="allow_duplicates" @if((isset($listing_generation_type) && $listing_generation_type['allow_duplicates'] === 1)) checked @endif >
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('listing_generation_type.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($listing_generation_type))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
