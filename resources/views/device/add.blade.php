@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($device) ? 'Edit Device' : 'Add Device' }}</h1>
@stop

@section('content')
    <form action="{{ isset($device) ? route('devices.update',['id' => $device['id']]) : route('devices.store') }}"
          method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input @error('status') is-invalid @enderror"
                                   @if((isset($device) && $device->status === 'active') || old('status') == 'on') checked
                                   @endif name="status"
                                   id="status">
                            <label class="form-check-label" for="status">Status</label>
                            @error('status')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Device Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Device Name" value="{{ old('name', $device['name'] ?? '') }}">
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
                                   placeholder="Enter Code" value="{{ old('code', $device['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Order</label>
                            <input type="text" class="form-control @error('order') is-invalid @enderror" id="order"
                                   name="order"
                                   placeholder="Enter Order" value="{{ old('order', $device['order'] ?? '') }}">
                            @if ($errors->has('order'))
                                <span class="text-danger">{{ $errors->first('order') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="release_date">Release Date</label>
                            <div class="input-group date">
                                <input type="text"
                                       class="form-control datetimepicker-input @error('release_date') is-invalid @enderror"
                                       id="release_date" name="release_date"
                                       value="{{ old('release_date', $device['release_date'] ?? '') }}">
                            </div>
                            @if ($errors->has('release_date'))
                                <span class="text-danger">{{ $errors->first('release_date') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dimensions_width">Dimension width</label>
                            <input type="text" class="form-control @error('dimensions_width') is-invalid @enderror"
                                   id="dimensions_width"
                                   name="dimensions_width"
                                   placeholder="Enter Dimension width"
                                   value="{{ old('dimensions_width', $device['dimensions_width'] ?? '') }}">
                            @if ($errors->has('dimensions_width'))
                                <span class="text-danger">{{ $errors->first('dimensions_width') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dimensions_height">Dimension Height</label>
                            <input type="text" class="form-control @error('dimensions_height') is-invalid @enderror"
                                   id="dimensions_height"
                                   name="dimensions_height"
                                   placeholder="Enter Dimension Height"
                                   value="{{ old('dimensions_height', $device['dimensions_height'] ?? '') }}">
                            @if ($errors->has('dimensions_height'))
                                <span class="text-danger">{{ $errors->first('dimensions_height') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dimensions_length">Device Length</label>
                            <input type="text" class="form-control @error('dimensions_length') is-invalid @enderror"
                                   id="dimensions_length"
                                   name="dimensions_length"
                                   placeholder="Enter Dimension Length"
                                   value="{{ old('dimensions_length', $device['dimensions_length'] ?? '') }}">
                            @if ($errors->has('dimensions_length'))
                                <span class="text-danger">{{ $errors->first('dimensions_length') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dimensions_length_unit">Device Length Unit</label>
                            <input type="text"
                                   class="form-control @error('dimensions_length_unit') is-invalid @enderror"
                                   id="dimensions_length_unit"
                                   name="dimensions_length_unit"
                                   placeholder="Enter Dimension Length Unit"
                                   value="{{ old('dimensions_length_unit',  $device['dimensions_length_unit'] ?? '') }}">
                            @if ($errors->has('dimensions_length_unit'))
                                <span class="text-danger">{{ $errors->first('dimensions_length_unit') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="device_type_id">Device Type</label>
                            <select class="form-control @error('device_type_id') is-invalid @enderror"
                                    id="device_type_id"
                                    name="device_type_id">
                                <option value="">Select Device Type</option>
                                @foreach($deviceTypes as $deviceType)
                                    <option value="{{ $deviceType->id }}"
                                            @if(isset($device) && $device['device_type_id'] == $deviceType->id) selected @endif>{{ $deviceType->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('device_type_id'))
                                <span class="text-danger">{{ $errors->first('device_type_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="device_sub_type_id">Device Sub Type</label>
                            <select class="form-control @error('device_sub_type_id') is-invalid @enderror"
                                    id="device_sub_type_id"
                                    name="device_sub_type_id">
                                <option value="">Select Device Sub Type</option>
                                @foreach($deviceSubTypes as $deviceSubType)
                                    <option value="{{ $deviceSubType->id }}"
                                            @if(isset($device) && $device['device_sub_type_id'] == $deviceSubType->id) selected @endif>{{ $deviceSubType->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('device_sub_type_id'))
                                <span class="text-danger">{{ $errors->first('device_sub_type_id') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="device_series_id">Device Series</label>
                            <select class="form-control @error('device_series_id') is-invalid @enderror"
                                    id="device_series_id"
                                    name="device_series_id">
                                <option value="">Select Device Series</option>
                                @foreach($deviceSeries as $deviceSerie)
                                    <option value="{{ $deviceSerie->id }}"
                                            @if(isset($device) && $device['device_series_id'] == $deviceSerie->id) selected @endif>{{ $deviceSerie->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('device_series_id'))
                                <span class="text-danger">{{ $errors->first('device_series_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="rating_id">Device Rating</label>
                            <select class="form-control @error('rating_id') is-invalid @enderror" id="rating_id"
                                    name="rating_id">
                                <option value="">Select Device Series</option>
                                @foreach($deviceRatings as $deviceRating)
                                    <option value="{{ $deviceRating->id }}"
                                            @if(isset($device) && $device['rating_id'] == $deviceRating->id) selected @endif>{{ $deviceRating->value }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('rating_id'))
                                <span class="text-danger">{{ $errors->first('rating_id') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="screen_id">Device Screen</label>
                            <select class="form-control @error('screen_id') is-invalid @enderror" id="screen_id"
                                    name="screen_id">
                                <option value="">Select Device Screen</option>
                                @foreach($deviceScreens as $deviceScreen)
                                    <option value="{{ $deviceScreen->id }}"
                                            @if(isset($device) && $device['screen_id'] == $deviceScreen->id) selected @endif>{{ $deviceScreen->size }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('screen_id'))
                                <span class="text-danger">{{ $errors->first('screen_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="brand_id">Brand</label>
                            <select class="form-control @error('brand_id') is-invalid @enderror" id="brand_id"
                                    name="brand_id">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}"
                                            @if(isset($device) && $device['brand_id'] == $brand->id) selected @endif>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('brand_id'))
                                <span class="text-danger">{{ $errors->first('brand_id') }}</span>
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
                            <input type="hidden" id="image" name="image" value="{{ $device['image'] ?? '' }}">
                            <img id="image-preview" src="{{isset($device) && $device->imageUrl}}" alt="Image Preview"
                                 style="@if(!(isset($device) && $device->imageUrl)) display:none; @endif max-width: 100%; margin-top: 10px;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="image">Image Thumbnail</label>
                            <input type="file" class="form-control @error('thumbnailImageUrl') is-invalid @enderror"
                                   id="thumbnail_image_reference" name="thumbnail_image_reference"
                                   onchange="uploadImage(event,'thumbnail_image','thumbnail-image-preview')">
                            <input type="hidden" id="thumbnail_image" name="thumbnail_image"
                                   value="{{ $device['thumbnail_image'] ?? '' }}">
                            <img id="thumbnail-image-preview" src="{{isset($device) && $device->thumbnailImageUrl}}"
                                 alt="Image Preview"
                                 style="@if(!(isset($device) && $device->thumbnailImageUrl)) display:none; @endif max-width: 100%; margin-top: 10px;">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes"
                                      placeholder="Enter Notes">{{ old('notes',$device['notes'] ?? '') }}</textarea>
                            @if ($errors->has('notes'))
                                <span class="text-danger">{{ $errors->first('notes') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($device))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $('#release_date').datepicker();
        });
    </script>
@stop
