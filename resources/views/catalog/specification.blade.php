<button type="button" class="btn btn-secondary" id="add_specification"><i class="fa fa-plus-square"></i> Add</button>
<button type="button" class="btn btn-secondary" id="add_required_specification"><i class="fa fa-plus-square"></i> Add
    Required
</button>
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
        @include('catalog.specification_options', ['specificationArr' => $specificationArr, 'specificationInfo' => $specificationInfo])
        </tbody>
    </table>
</div>
