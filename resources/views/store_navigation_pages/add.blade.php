@extends('adminlte::page')

@section('content_header')
    <h1>{{ isset($navigation_page) ? 'Edit Navigation Page' : 'Add Navigation Page' }}</h1>
@stop

@section('content')
    <form action="{{ isset($navigation_page) ? route('store_navigation_page.update', ['id' => $navigation_page->id]) : route('store_navigation_page.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                                   name="title" placeholder="Enter Title"
                                   value="{{ old('title', $navigation_page->title ?? '') }}" required>
                            @if ($errors->has('title'))
                                <span class="text-danger">{{ $errors->first('title') }}</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="store_id">Store</label>
                            <select class="form-control @error('store_id') is-invalid @enderror" name="store_id" id="store_id" required>
                                <option value="">--</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}"
                                        @if(isset($navigation_page) && $navigation_page->store_id == $store->id) selected @endif>
                                        {{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($errors->has('store_id'))
                                <span class="text-danger">{{ $errors->first('store_id') }}</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="navigation_type">Navigation Type</label>
                            <select class="form-control @error('navigation_type') is-invalid @enderror"
                                    name="navigation_type" id="navigation_type" required>
                                <option value="">--</option>
                                <option value="header" @if(isset($navigation_page) && $navigation_page->navigation_type == 'header') selected @endif>Header</option>
                                <option value="footer" @if(isset($navigation_page) && $navigation_page->navigation_type == 'footer') selected @endif>Footer</option>
                                <option value="sidebar" @if(isset($navigation_page) && $navigation_page->navigation_type == 'sidebar') selected @endif>Sidebar</option>
                            </select>
                            @if ($errors->has('navigation_type'))
                                <span class="text-danger">{{ $errors->first('navigation_type') }}</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea class="form-control @error('content') is-invalid @enderror"
                                      name="content" id="content" rows="8" required>{{ old('content', $navigation_page->content ?? '') }}</textarea>
                            @if ($errors->has('content'))
                                <span class="text-danger">{{ $errors->first('content') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('store_navigation_page.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($navigation_page))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
