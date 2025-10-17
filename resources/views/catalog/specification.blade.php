<button type="button" class="btn btn-secondary" id="add_specification"> <i class="fa fa-plus-square"></i> Add</button>
<div class="card card-body border mt-2">
    <table class="table table-bordered" id="specification_table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Value</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @if(count($specificationArr ?? []) > 0)
            @foreach($specificationArr as $specification)
                <tr class="field-group">
                    <td>
                        <select class="form-control select2 specification" name="specification[]">
                            <option value=""></option>
                            @if(count($specificationInfo ?? []) > 0)
                                @foreach($specificationInfo as $spinfo)
                                    <option value="{{ $spinfo->field_local_name }}" {{ (isset($specification->name) && $specification->name == $spinfo->field_local_name) ? 'selected' : '' }}>{{ $spinfo->field_local_name }}</option>
                                @endforeach
                            @endif

                        </select>
                    </td>
                    <td class="field_option">
                        @if($specification->field == 'select')

                            <select class="form-control specification-select" name="specification_value[]">
                                <option value=""></option>
                                @if(count($specification->valoption ?? []) > 0)
                                    @foreach($specification->valoption as $vopt)
                                        <option value="{{ trim($vopt->id) }}" {{ (isset($specification->value) && $specification->value == trim($vopt->id)) ? 'selected' : '' }}>{{ trim($vopt->field_value) }}</option>
                                    @endforeach
                                @endif
                            </select>
                        @else
                            <textarea class="form-control" name="specification_value[]">{{ $specification->value }}</textarea>
                        @endif
                    </td>
                    <td><button type="button" class="btn btn-danger remove-specification"> <i class="fas fa-fw fa-trash"></i></button></td>
                </tr>
            @endforeach
        @else
            <tr>
                <td>
                    <select class="form-control specification" name="specification[]">
                        <option value=""></option>
                        @if(count($specificationInfo ?? []) > 0)
                            @foreach($specificationInfo as $spinfo)
                                <option value="{{ $spinfo->field_local_name }}">{{ $spinfo->field_local_name }}</option>
                            @endforeach
                        @endif

                    </select>
                </td>
                <td class="field_option"><textarea class="form-control" name="specification_value[]"></textarea></td>
                <td></td>
            </tr>
        @endif
        </tbody>
    </table>
</div>
