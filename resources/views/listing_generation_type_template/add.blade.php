@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($listing_generation_type_template) ? 'Edit Listing Generation Type' : 'Add Listing Generation Type' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($listing_generation_type_template) ? route('listing_generation_type_template.update',['id' => $listing_generation_type_template['id']]) : route('listing_generation_type_template.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="template">Template</label>
                            <input type="text" class="form-control @error('template') is-invalid @enderror" id="template"
                                   name="template"
                                   placeholder="Enter Listing Generation Type Code" value="{{ old('template', $listing_generation_type_template['template'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('template') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="name">Generate Type ID</label>
                            <select class="form-control" name="generation_type_id" id="generation_type_id">
                                @if(count($listing_generation_types ?? []) > 0)
                                    @foreach($listing_generation_types as $gtype)
                                        <option value="{{ $gtype->id }}">{{ $gtype->name }}</option>
                                    @endforeach
                                @endif

                            </select>
                            @if ($errors->has('generation_type_id'))
                                <span class="text-danger">{{ $errors->first('generation_type_id') }}</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="default_template">Default Template</label>
                                </div>
                                <div class="col-md-3">
                                    <input type="checkbox" name="default_template" id="default_template" @if((isset($listing_generation_type_template) && $listing_generation_type_template['default_template'] === 1)) checked @endif >
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('listing_generation_type_template.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($listing_generation_type_template))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
