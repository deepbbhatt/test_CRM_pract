<div class="mb-3">
    <label class="form-label">Field Name</label>
    <input type="text" name="name" value="{{ old('name', $field->name ?? '') }}" 
           class="form-control" placeholder="Ex: Birthday">

    @error('name')<small class="text-danger">{{ $message }}</small>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Slug</label>
    <input type="text" name="slug" 
           value="{{ old('slug', $field->slug ?? '') }}" 
           class="form-control" placeholder="Ex: birthday">

    @error('slug')<small class="text-danger">{{ $message }}</small>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Field Type</label>
    <select name="type" id="fieldType" class="form-control">
        <option value="text" {{ old('type', $field->type ?? '') == 'text' ? 'selected' : '' }}>Text</option>
        <option value="number" {{ old('type', $field->type ?? '') == 'number' ? 'selected' : '' }}>Number</option>
        <option value="date" {{ old('type', $field->type ?? '') == 'date' ? 'selected' : '' }}>Date</option>
        <option value="select" {{ old('type', $field->type ?? '') == 'select' ? 'selected' : '' }}>Select</option>
        <option value="radio" {{ old('type', $field->type ?? '') == 'radio' ? 'selected' : '' }}>Radio</option>
        <option value="checkbox" {{ old('type', $field->type ?? '') == 'checkbox' ? 'selected' : '' }}>Checkbox</option>
    </select>

    @error('type')<small class="text-danger">{{ $message }}</small>@enderror
</div>

{{-- Dynamic Options --}}
<div class="mb-3" id="optionsBox" style="display:none;">
    <label class="form-label">Options (comma separated)</label>

    <input type="text" name="options" 
           value="{{ old('options', isset($field->options) ? implode(',', $field->options) : '') }}"
           class="form-control" placeholder="Example: Red, Blue, Green">

    <small class="text-muted">Required for select, radio, checkbox.</small>
    @error('options')<small class="text-danger">{{ $message }}</small>@enderror
</div>

{{-- Validation Rule --}}
<div class="mb-3">
    <label class="form-label">Validation Rule (Optional)</label>
    <input type="text" name="validation" 
           value="{{ old('validation', $field->validation ?? '') }}"
           class="form-control" placeholder="e.g., required|string|max:255">

    @error('validation')<small class="text-danger">{{ $message }}</small>@enderror
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let typeSelect = document.getElementById("fieldType");
    let optionsBox = document.getElementById("optionsBox");

    function toggleOptions() {
        if (['select', 'radio', 'checkbox'].includes(typeSelect.value)) {
            optionsBox.style.display = 'block';
        } else {
            optionsBox.style.display = 'none';
        }
    }

    toggleOptions();
    typeSelect.addEventListener('change', toggleOptions);
});
</script>
