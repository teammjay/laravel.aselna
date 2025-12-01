@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($store_pages) ? 'Edit Store Page' : 'Add Store Page' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($store_pages) ? route('store_page.update',['id' => $store_pages['id']]) : route('store_page.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                                   name="title"
                                   placeholder="Enter Code" value="{{ old('title', $store_pages['title'] ?? '') }}" required>
                            @if ($errors->has('title'))
                                <span class="text-danger">{{ $errors->first('title') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="store_id">Store</label>
                            <select class="form-control @error('store_id') is-invalid @enderror" name="store_id" id="store_id" required>
                                <option value="">- All Store -</option>
                                @if(count($stores ?? []) > 0)
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" @if(isset($store_pages) && $store_pages['store_id'] == $store->id) selected @endif>{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('store_id'))
                                <span class="text-danger">{{ $errors->first('store_id') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="page_type">Page Type</label>
                            <select class="form-control" name="page_type" id="page_type" required>
                                <option value="">--</option>
                                <option value="2" @if(isset($store_pages) && $store_pages['page_type'] == 2) selected @endif>Shipping</option>
                                <option value="3" @if(isset($store_pages) && $store_pages['page_type'] == 3) selected @endif>Returns</option>
                                <option value="4" @if(isset($store_pages) && $store_pages['page_type'] == 4) selected @endif>Payments</option>
                                <option value="8" @if(isset($store_pages) && $store_pages['page_type'] == 8) selected @endif>Feedback</option>
                                <option value="10" @if(isset($store_pages) && $store_pages['page_type'] == 10) selected @endif>Return Policy</option>
                            </select>
                            @if ($errors->has('page_type'))
                                <span class="text-danger">{{ $errors->first('page_type') }}</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="text" class="col-form-label">Text:</label>
                            <textarea class="form-control" name="text" id="text" rows="8" required>{{ old('code', $store_pages['text'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>


            </div>
            <div class="card-footer text-center">
                <button page="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('store_page.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($store_pages))
                    <button page="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
