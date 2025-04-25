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
        @if(count($specifications ?? []) > 0)
            @foreach($specifications as $specification)
                <tr class="field-group">
                    <td>
                        <select class="form-control" name="specification[]">
                            <option value=""></option>
                            @if(count($specificationInfo ?? []) > 0)
                                @foreach($specificationInfo as $spinfo)
                                    <option value="{{ $spinfo->field_local_name }}" {{ (isset($specification->name) && $specification->name == $spinfo->field_local_name) ? 'selected' : '' }}>{{ $spinfo->field_local_name }}</option>
                                @endforeach
                            @endif

                        </select>
                    </td>
                    <td><textarea class="form-control" name="specification_value[]">{{ $specification->value }}</textarea></td>
                    <td><button type="button" class="btn btn-danger remove-specification"> <i class="fas fa-fw fa-trash"></i></button></td>
                </tr>
            @endforeach
        @else
            <tr>
                <td>
                    <select class="form-control" name="specification[]">
                        <option value=""></option>
                        @if(count($specificationInfo ?? []) > 0)
                            @foreach($specificationInfo as $spinfo)
                                <option value="{{ $spinfo->field_local_name }}">{{ $spinfo->field_local_name }}</option>
                            @endforeach
                        @endif

                    </select>
                </td>
                <td><textarea class="form-control" name="specification_value[]"></textarea></td>
                <td></td>
            </tr>
        @endif
        </tbody>
    </table>
</div>