@if(isset($specification_values))
    @foreach($specification_values as $specification_value)
        <tr class="field-group">
            <td>
            <textarea class="form-control"
                      name="field_value[{{$specification_value->id}}]">{{$specification_value->field_value}}</textarea>
            </td>
            <td>
                @if(isset($specification_value->storeType->name))
                    {{ $specification_value->storeType->name }}
                @endif
            </td>
            <td>
                @if(isset($specification_value->storeTypeCategory->marketplace_code))
                    {{ $specification_value->storeTypeCategory->marketplace_code }}
                @endif
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
        <td></td>
        <td></td>
        <td>
            <button type="button" class="btn btn-danger remove-specification"><i
                    class="fas fa-fw fa-trash"></i></button>
        </td>
    </tr>
@endif