@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($specification_info) ? 'Edit Specification Info' : 'Add Specification Info' }}</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Lightbox2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    <style>
        .select2-container--default .select2-dropdown .select2-search__field:focus, .select2-container--default .select2-search--inline .select2-search__field:focus {
            outline: 0;
            border: none;
        }
        .select2-container{width:100% !important;}
        .select2-container .select2-selection--single{
            height: calc(2.25rem + 2px);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: auto;
            position: absolute;
            top: 50%;
            right: 1px;
            width: 20px;
            transform: translateY(-50%);
        }
        .ui-sortable tr:hover{
            cursor:move;
        }

        .ui-sortable-placeholder{
            background:#ccc !important;
            visibility: initial !important;
        }

    </style>
@stop

@section('content')
    <form
        action="{{ isset($specification_info) ? route('specification_info.update',['id' => $specification_info['id']]) : route('specification_info.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="field_name">Field Name</label>
                            <input type="text" class="form-control @error('field_name') is-invalid @enderror" id="field_name"
                                   name="field_name"
                                   placeholder="Field Name" value="{{ old('field_name', $specification_info['field_name'] ?? '') }}">
                            @if ($errors->has('field_name'))
                                <span class="text-danger">{{ $errors->first('field_name') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="field_local_name">Field Local Name</label>
                            <input type="text" class="form-control @error('field_local_name') is-invalid @enderror" id="field_local_name"
                                   name="field_local_name"
                                   placeholder="Field Local Name" value="{{ old('field_local_name', $specification_info['field_local_name'] ?? '') }}">
                            @if ($errors->has('field_local_name'))
                                <span class="text-danger">{{ $errors->first('field_local_name') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="store_type_id">Store Type</label>
                            <select class="form-control" name="store_type_id" id="store_type_id">
                                <option value="">-- Store Type --</option>
                                @if(count($store_types ?? []) > 0)
                                    @foreach($store_types as $stype)
                                        <option value="{{ $stype->id }}" {{ (isset($specification_info['store_type_id']) && $stype->id == $specification_info['store_type_id']) ? 'selected' : '' }}>{{ $stype->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('store_type_id'))
                                <span class="text-danger">{{ $errors->first('store_type_id') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="product_type_id">Product Type</label>
                            <select class="form-control" name="product_type_id[]" id="product_type_id" multiple>
                                <option>-- Product Type --</option>
                                @if(count($product_types ?? []) > 0)
                                    @foreach($product_types as $ptype)
                                        <option value="{{ $ptype->id }}" @if(is_array($info_category)){{ in_array($ptype->id,($info_category ?? [])) ? 'selected' : '' }} @endif>{{ $ptype->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('product_type_id'))
                                <span class="text-danger">{{ $errors->first('product_type_id') }}</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="required">Required</label><br>
                            <input type="checkbox" name="required" @if(isset($specification_info['required']) && $specification_info['required'] == 1) checked @endif>
                        </div>

                    </div>

                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('specification_info.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($specification_info))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop

@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Lightbox2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script>
        $(document).ready(function() {

            $('#product_type_id').select2();

        });
    </script>
@stop