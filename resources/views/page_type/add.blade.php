@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($page_type) ? 'Edit Page Type' : 'Add Page Type' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($page_type) ? route('page_type.update',['id' => $page_type['id']]) : route('page_type.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type">Type</label>
                            <input type="text" class="form-control @error('type') is-invalid @enderror" id="type"
                                   name="type"
                                   placeholder="Enter Page Type" value="{{ old('type', $page_type['type'] ?? '') }}">
                            @if ($errors->has('type'))
                                <span class="text-danger">{{ $errors->first('type') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($page_type))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
