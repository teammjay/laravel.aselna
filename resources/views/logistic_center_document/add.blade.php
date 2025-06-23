@extends('adminlte::page')

@section('title', 'Document Settings')

@section('content_header')
    <h1>{{ isset($document) ? 'Edit Document Setting' : 'Add Document Setting' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($document) ? route('logistic_center_document.update', ['id' => $document['id']]) : route('logistic_center_document.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="shipping_from_location_id">Logistic Center</label>
                            <select class="form-control @error('shipping_from_location_id') is-invalid @enderror" name="shipping_from_location_id" id="shipping_from_location_id">
                                <option value="">Select Logistic Center</option>
                                @foreach($logistic_centers as $center)
                                    <option value="{{ $center->id }}" {{ old('shipping_from_location_id', $document->shipping_from_location_id ?? '') == $center->id ? 'selected' : '' }}>
                                        {{ $center->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($errors->has('shipping_from_location_id'))
                                <span class="text-danger">{{ $errors->first('shipping_from_location_id') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="document_type">Document Type</label>
                            <select class="form-control @error('document_type') is-invalid @enderror" name="document_type" id="document_type">
                                <option value="">Select Document Type</option>
                                @foreach($document_types as $id => $name)
                                    <option value="{{ $id }}" {{ old('document_type', $document->document_type ?? '') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($errors->has('document_type'))
                                <span class="text-danger">{{ $errors->first('document_type') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="printer_id">Printer</label>
                            <select class="form-control @error('printer_id') is-invalid @enderror" name="printer_id" id="printer_id">
                                <option value="">Select Printer</option>
                                @foreach($printers as $printer)
                                    <option value="{{ $printer->id }}" {{ old('printer_id', $document->printer_id ?? '') == $printer->id ? 'selected' : '' }}>
                                        {{ $printer->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($errors->has('printer_id'))
                                <span class="text-danger">{{ $errors->first('printer_id') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="page_size_id">Page Size</label>
                            <select class="form-control @error('page_size_id') is-invalid @enderror" name="page_size_id" id="page_size_id">
                                <option value="">Select Page Size</option>
                                <option value="{{ \Illuminate\Support\Str::uuid() }}" {{ old('page_size_id', $document->page_size_id ?? '') ? 'selected' : '' }}>Default</option>
                            </select>
                            @if ($errors->has('page_size_id'))
                                <span class="text-danger">{{ $errors->first('page_size_id') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="rotate">Rotation</label>
                            <select class="form-control @error('rotate') is-invalid @enderror" name="rotate" id="rotate">
                                <option value="0" {{ old('rotate', $document->rotate ?? 0) == 0 ? 'selected' : '' }}>0 Degrees</option>
                                <option value="90" {{ old('rotate', $document->rotate ?? 0) == 90 ? 'selected' : '' }}>90 Degrees</option>
                                <option value="180" {{ old('rotate', $document->rotate ?? 0) == 180 ? 'selected' : '' }}>180 Degrees</option>
                                <option value="270" {{ old('rotate', $document->rotate ?? 0) == 270 ? 'selected' : '' }}>270 Degrees</option>
                            </select>
                            @if ($errors->has('rotate'))
                                <span class="text-danger">{{ $errors->first('rotate') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

{{--                <div class="row">--}}
{{--                    <div class="col-md-6">--}}
{{--                        <div class="form-group">--}}
{{--                            <label for="pdf_action">PDF Action</label>--}}
{{--                            <select class="form-control @error('pdf_action') is-invalid @enderror" name="pdf_action" id="pdf_action">--}}
{{--                                <option value="">None</option>--}}
{{--                                <option value="1" {{ old('pdf_action', $document->pdf_action ?? '') == 1 ? 'selected' : '' }}>Download</option>--}}
{{--                                <option value="2" {{ old('pdf_action', $document->pdf_action ?? '') == 2 ? 'selected' : '' }}>View</option>--}}
{{--                            </select>--}}
{{--                            @if ($errors->has('pdf_action'))--}}
{{--                                <span class="text-danger">{{ $errors->first('pdf_action') }}</span>--}}
{{--                            @endif--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="enabled">Status</label>
                            <select class="form-control @error('enabled') is-invalid @enderror" name="enabled" id="enabled">
                                <option value="1" {{ old('enabled', $document->enabled ?? 1) == 1 ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ old('enabled', $document->enabled ?? 1) == 0 ? 'selected' : '' }}>Disabled</option>
                            </select>
                            @if ($errors->has('enabled'))
                                <span class="text-danger">{{ $errors->first('enabled') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($document))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
