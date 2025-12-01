@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($store_page_templates) ? 'Edit Store Page Template' : 'Add Store Page Template' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($store_page_templates) ? route('store_page_templates.update',['id' => $store_page_templates->id]) : route('store_page_templates.store') }}"
        method="POST">
        @csrf
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="general-tab" data-toggle="pill" href="#tab1" role="tab" aria-controls="tab1" aria-selected="true">
                            General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="seo-tab" data-toggle="pill" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                            SEO Fields
                        </a>
                    </li>
                </ul>
                <div class="tab-content p-2" id="custom-tabs-one-tabContent">
                    <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="general-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title">Name</label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="name"
                                           name="name"
                                           placeholder="Enter Name" value="{{ old('name', $store_page_templates->name ?? '') }}" required>
                                    @if ($errors->has('name'))
                                        <span class="text-danger">{{ $errors->first('name') }}</span>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="store_id">Store</label>
                                    <select class="form-control @error('store_id') is-invalid @enderror" name="store_id" id="store_id">
                                        <option value="">- All Store -</option>
                                        @if(count($stores ?? []) > 0)
                                            @foreach($stores as $store)
                                                <option value="{{ $store->id }}" @if(isset($store_page_templates) && $store_page_templates->store_id == $store->id) selected @endif>{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @if ($errors->has('store_id'))
                                        <span class="text-danger">{{ $errors->first('store_id') }}</span>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="page_type">Page Type</label>
                                    <select class="form-control" name="page_type" id="page_type">
                                        <option value="">--</option>

                                    </select>
                                    @if ($errors->has('page_type'))
                                        <span class="text-danger">{{ $errors->first('page_type') }}</span>
                                    @endif
                                </div>



                                <div class="form-group">
                                    <label for="template_code" class="col-form-label">Template Code:</label>
                                    <textarea class="form-control" name="template_code" id="template_code" rows="20" required>{{ old('template_code', $store_page_templates->template_code ?? '&nbsp;') }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label for="store_id">Product Type</label>
                                    <select class="form-control @error('product_type_id') is-invalid @enderror" name="product_type_id" id="product_type_id">
                                        <option value="">--</option>
                                        @if(count($product_types ?? []) > 0)
                                            @foreach($product_types as $pType)
                                                <option value="{{ $pType->id }}" @if(isset($store_page_templates) && $store_page_templates->product_type_id == $pType->id) selected @endif>{{ $pType->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @if ($errors->has('product_type_id'))
                                        <span class="text-danger">{{ $errors->first('product_type_id') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="seo-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="meta_title_template" class="col-form-label">Meta Title Template:</label>
                                    <textarea class="form-control" name="meta_title_template" id="meta_title_template" rows="8">{{ old('meta_title_template', $store_page_templates->meta_title_template ?? '') }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label for="meta_description_template" class="col-form-label">Meta Description Template:</label>
                                    <textarea class="form-control" name="meta_description_template" id="meta_description_template" rows="8">{{ old('meta_description_template', $store_page_templates->meta_description_template ?? '') }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label for="meta_keywords_template" class="col-form-label">Meta Keywords Template:</label>
                                    <textarea class="form-control" name="meta_keywords_template" id="meta_keywords_template" rows="8">{{ old('meta_keywords_template', $store_page_templates->meta_keywords_template ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button page="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('store_page_templates.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($store_page_templates))
                    <button page="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
