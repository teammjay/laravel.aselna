<div class="mb-3">
    <label class="form-label">Category</label>
    <input type="text" name="category" class="form-control" value="{{ old('category', $listingRequirement->category ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Type</label>
    <select name="type" class="form-select" required>
        <option value="column" {{ old('type', $listingRequirement->type ?? '') == 'column' ? 'selected' : '' }}>Column</option>
        <option value="key_value" {{ old('type', $listingRequirement->type ?? '') == 'key_value' ? 'selected' : '' }}>Key-Value</option>
    </select>
</div>

<div class="mb-3">
    <label class="form-label">Table</label>
    <input type="text" name="table" class="form-control" value="{{ old('table', $listingRequirement->table ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Column (for type 'column')</label>
    <input type="text" name="column" class="form-control" value="{{ old('column', $listingRequirement->column ?? '') }}">
</div>

<div class="mb-3">
    <label class="form-label">Key Column</label>
    <input type="text" name="key_column" class="form-control" value="{{ old('key_column', $listingRequirement->key_column ?? '') }}">
</div>

<div class="mb-3">
    <label class="form-label">Key Value</label>
    <input type="text" name="key_value" class="form-control" value="{{ old('key_value', $listingRequirement->key_value ?? '') }}">
</div>

<div class="mb-3">
    <label class="form-label">Value Column</label>
    <input type="text" name="value_column" class="form-control" value="{{ old('value_column', $listingRequirement->value_column ?? '') }}">
</div>

<button class="btn btn-success">{{ $button }}</button>
<a href="{{ route('listing_requirements.index') }}" class="btn btn-secondary">Cancel</a>
