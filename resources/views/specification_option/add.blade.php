@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($specification_option) ? 'Edit Specification Option' : 'Add Specification Option' }}</h1>
@stop

@section('content')
    <form
        action="{{ isset($specification_option) ? route('specification_option.update',['id' => $specification_option['id']]) : route('specification_option.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="field_local_name">Field Local Name</label>
                            <select class="form-control" name="field_local_name" id="field_local_name">
                                <option value="">-- Specification Field --</option>
                                @if(count($specification_info ?? []) > 0)
                                    @foreach($specification_info as $sInfo)
                                        <option value="{{ $sInfo->field_local_name }}" {{ (isset($specification_option['field_local_name']) && $sInfo->field_local_name == $specification_option['field_local_name']) ? 'selected' : '' }}>{{ $sInfo->field_local_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('field_local_name'))
                                <span class="text-danger">{{ $errors->first('field_local_name') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="field_option">Field Value</label>
                            <textarea class="form-control" name="field_option" id="field_option" rows="3">{{ old('field_option', $specification_option['field_option'] ?? '') }}</textarea>
                            @if ($errors->has('field_option'))
                                <span class="text-danger">{{ $errors->first('field_option') }}</span>
                            @endif
                        </div>

                    </div>

                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('specification_option.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($specification_option))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
