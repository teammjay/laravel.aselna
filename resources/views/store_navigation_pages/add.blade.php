@php use App\Constants\WebStorePageType; @endphp
@extends('adminlte::page')

@section('content_header')
    <h1>{{ isset($navigation_page) ? 'Edit Navigation Page' : 'Add Navigation Page' }}</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
        }
    </style>
@endsection

@section('content')
    <form
        action="{{ isset($navigation_page) ? route('store_navigation_page.update', ['id' => $navigation_page->id]) : route('store_navigation_page.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">

                    <div class="form-group col-md-7">
                        <label for="store_id">Store</label>
                        <select class="form-control @error('store_id') is-invalid @enderror" name="store_id"
                                id="store_id" required>
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

                    <div class="form-group col-md-7">
                        <label for="page_type">Page Type</label>
                        <select class="form-control @error('page_type') is-invalid @enderror"
                                name="page_type" id="page_type" required>
                            <option value="">--</option>
                            @foreach(WebStorePageType::getPageTypes() as $pageType)
                                <option value="{{ $pageType }}"
                                        @if(isset($navigation_page) && $navigation_page->page_type == $pageType) selected @endif>
                                    {{ WebStorePageType::getPageTypeName($pageType) }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('page_type'))
                            <span class="text-danger">{{ $errors->first('page_type') }}</span>
                        @endif
                    </div>
                    <div class="form-group col-md-7">
                        <label for="template_id">Page Template</label>
                        <select class="form-control @error('template_id') is-invalid @enderror"
                                name="template_id" id="template_id" required>
                            <option value="">--</option>
                            @foreach($pageTemplates as $pageTemplate)
                                <option value="{{ $pageTemplate->id }}"
                                        @if(isset($navigation_page) && $navigation_page->template_id == $pageTemplate->id) selected @endif>
                                    {{ $pageTemplate->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('template_id'))
                            <span class="text-danger">{{ $errors->first('template_id') }}</span>
                        @endif
                    </div>

                    <div class="form-group col-md-7">
                        <label for="title">Title</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                               name="title" placeholder="Enter Title"
                               value="{{ old('title', $navigation_page->title ?? '') }}" required>
                        @if ($errors->has('title'))
                            <span class="text-danger">{{ $errors->first('title') }}</span>
                        @endif
                    </div>

                    <div class="form-group col-md-7" id="device_brand_id_wrapper">
                        <label for="device_brand_id">Brand</label>
                        <select class="form-control @error('device_brand_id') is-invalid @enderror"
                                name="device_brand_id" id="device_brand_id" required>
                            <option value="">--</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}"
                                        @if(isset($navigation_page) && $navigation_page->device_brand_id == $brand->id) selected @endif>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('device_brand_id'))
                            <span class="text-danger">{{ $errors->first('device_brand_id') }}</span>
                        @endif
                    </div>

                    <div class="form-group col-md-10">
                        <label for="description">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  name="description" id="description" rows="3"
                                  required>
                            {{ old('description', $navigation_page->description ?? '') }}
                        </textarea>
                    </div>

                    <div class="form-group col-md-10">
                        <label for="content">Content</label>
                        <textarea class="form-control @error('content') is-invalid @enderror"
                                  name="content" id="content" rows="8"
                                  required>{{ old('content', $navigation_page->content ?? '') }}</textarea>
                        @if ($errors->has('content'))
                            <span class="text-danger">{{ $errors->first('content') }}</span>
                        @endif
                    </div>
                    <div class="form-group col-md-2 mt-4">
                        <button class="generate btn btn-secondary" id="generate_content"> Generate</button>
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

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#store_id').select2({
                placeholder: 'Select Store',
                allowClear: true
            });

            $('#generate_content').on('click', function (e) {
                e.preventDefault();
                const storeId = $('#store_id').val();
                const pageType = $('#page_type').val();
                const templateId = $('#template_id').val();
                const description = $('#description').val();
                const title = $('#title').val();
                if (!storeId || !pageType) {
                    alert('Please select a store and page type.');
                    return;
                }

                $.ajax({
                    url: '{{ route('store_navigation_page.generate_content') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        store_id: storeId,
                        page_type: pageType,
                        template_id: templateId,
                        description: description,
                        title: title
                    },
                    success: function (response) {
                        $('#content').val(response.content);
                    },
                    error: function () {
                        alert('Failed to generate content. Please try again.');
                    }
                });
            });

            $('#page_type').on('change', function () {
                const pageType = $(this).val();
                switch (pageType) {
                    case '<?= WebStorePageType::ALL_BRANDS ?>':
                        $('#title').val('Shop By Brand');
                        $('#device_brand_id_wrapper').hide();
                        break;
                    case '<?= WebStorePageType::BRAND ?>':
                        $('#device_brand_id_wrapper').show();
                        break;
                }
            });
        });
    </script>
@endsection
