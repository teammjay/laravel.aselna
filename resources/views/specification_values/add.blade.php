@extends('adminlte::page')

@section('title', 'Specification Values')

@section('content_header')
    <h1>{{ isset($specification_info) ? 'Edit Specification Value' : 'Add Specification Value' }}</h1>
@stop

@section('adminlte_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <style>
        .select2-container .select2-selection--single {
            height: auto !important;
        }
    </style>
@endsection

@section('content')
    <form
        action="{{ isset($specification_info) ? route('specification_values.update',['id' => $specification_info['id']]) : route('specification_values.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        @if(isset($specification_info->id)) <input type="hidden" name="field_name" value="{{$specification_info->id}}"> @endif
                        <div class="form-group" >
                            <label for="field_name">Field Name</label>
                            <select class="form-control @error('field_name') is-invalid @enderror" id="field_name"
                                    name="field_name" @if(isset($specification_info->id)) disabled @endif>
                                <option value="">-- Select Field Name --</option>
                                @if(isset($specification_info->id))
                                    <option
                                        value="{{$specification_info->id}}"
                                        selected>{{ $specification_info->field_name }}</option>
                                @elseif(count($specification_infos ?? []) > 0 )
                                    @foreach($specification_infos as $spec_info)
                                        <option
                                            value="{{ $spec_info->field_name }}" {{ (isset($specification_info->field_name) && $spec_info->field_name == $specification_info->field_name) ? 'selected' : '' }}>{{ $spec_info->field_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if ($errors->has('field_name'))
                                <span class="text-danger">{{ $errors->first('field_name') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="field_value">Field Value</label>
                            <div id="specification_value_list">
                                <button type="button" class="btn btn-secondary" id="add_specification"><i
                                        class="fa fa-plus-square"></i> Add
                                </button>
                                <div class="card card-body border mt-2">
                                    <table class="table table-bordered" id="field_value">
                                        <thead>
                                        <tr>
                                            <th>Value</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @if(isset($specification_values))
                                            @foreach($specification_values as $specification_value)
                                                <tr class="field-group">
                                                    <td>
                                                    <textarea class="form-control"
                                                              name="field_value[{{$specification_value->id}}]">{{$specification_value->field_value}}</textarea>
                                                    </td>
                                                    <td>
                                                        <button type="button" data-id="{{$specification_value->id}}"
                                                                class="btn btn-danger remove-specification"><i
                                                                class="fas fa-fw fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                        <tr class="field-group">
                                            <td>
                                                <textarea class="form-control" name="field_value[]"></textarea>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger remove-specification"><i
                                                        class="fas fa-fw fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            {{--                            <input type="text" class="form-control @error('field_value') is-invalid @enderror" id="field_value"--}}
                            {{--                                   name="field_value"--}}
                            {{--                                   placeholder="Field Value" value="{{ old('field_value', $specification_value['field_value'] ?? '') }}">--}}
                            {{--                            @if ($errors->has('field_value'))--}}
                            {{--                                <span class="text-danger">{{ $errors->first('field_value') }}</span>--}}
                            {{--                            @endif--}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('specification_values.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
@stop


@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#field_name').select2();
            $(document).on('click', '#add_specification', function () {
                $('#specification_value_list tbody').append('<tr class="field-group"   ><td><textarea class="form-control" name="field_value[]"></textarea></td><td><button type="button" class="btn btn-danger remove-specification"> <i class="fas fa-fw fa-trash"></i></button></td></tr>')
            });

            $(document).on('click', '.remove-specification', function () {
                if($(this).data('id')) {
                    $.ajax({
                        type: 'POST',
                        url: "{{ route('specification_values.removevalue') }}",
                        data: {
                            id: $(this).data('id')
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        dataType: 'json',
                        success: function (response) {
                        },
                        error: function (xhr) {
                            if (xhr.status === 422) {
                                var errors = xhr.responseJSON.errors;
                                alert(errors[Object.keys(errors)[0]][0]);
                            } else {
                                alert('An error occurred while syncing categories');
                            }
                        }
                    });
                }
                $(this).parents('tr.field-group').remove();
            });

        });
    </script>
@endsection
