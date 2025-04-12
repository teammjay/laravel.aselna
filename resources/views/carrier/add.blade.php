@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($carrier) ? 'Edit Carrier' : 'Add Carrier' }}</h1>
@stop

@section('content')
    <form action="{{ isset($carrier) ? route('carrier.update',['id' => $carrier['id']]) : route('carrier.store') }}"
          method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                {{--<div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input @error('status') is-invalid @enderror"
                                   @if((isset($carrier) && $carrier->status === 'active') || old('status') == 'on') checked
                                   @endif name="status"
                                   id="status">
                            <label class="form-check-label" for="status">Status</label>
                            @error('status')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>--}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Carrier Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Carrier Name" value="{{ old('name', $carrier['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Code</label>
                            <input type="text" class="form-control @error('ccode') is-invalid @enderror" id="code"
                                   name="code"
                                   placeholder="Enter Code" value="{{ old('code', $carrier['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sort_order">Order</label>
                            <input type="text" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order"
                                   name="sort_order"
                                   placeholder="Enter Order" value="{{ old('sort_order', $carrier['sort_order'] ?? '') }}">
                            @if ($errors->has('sort_order'))
                                <span class="text-danger">{{ $errors->first('sort_order') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="image">Image</label>
                            <input type="file" class="form-control @error('image') is-invalid @enderror"
                                   id="image_reference" name="image_reference"
                                   onchange="uploadImage(event,'image','image-preview')">
                            <input type="hidden" id="image" name="image" value="{{ $carrier['image'] ?? '' }}">
                            <img id="image-preview" src="{{isset($carrier) && $carrier->imageUrl}}" alt="Image Preview"
                                 style="@if(!(isset($carrier) && $carrier->imageUrl)) display:none; @endif max-width: 100%; margin-top: 10px;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="image">Image Thumbnail</label>
                            <input type="file" class="form-control @error('thumbnailImageUrl') is-invalid @enderror"
                                   id="thumbnail_image_reference" name="thumbnail_image_reference"
                                   onchange="uploadImage(event,'thumbnail_image','thumbnail-image-preview')">
                            <input type="hidden" id="thumbnail_image" name="thumbnail_image"
                                   value="{{ $carrier['thumbnail_image'] ?? '' }}">
                            <img id="thumbnail-image-preview" src="{{isset($carrier) && $carrier->thumbnailImageUrl}}"
                                 alt="Image Preview"
                                 style="@if(!(isset($carrier) && $carrier->thumbnailImageUrl)) display:none; @endif max-width: 100%; margin-top: 10px;">
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($carrier))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop

