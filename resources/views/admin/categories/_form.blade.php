@csrf

<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $category->name ?? '') }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="parent_id" class="form-label">Parent category</label>
    <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
        <option value="">— None (top level) —</option>
        @foreach ($parentOptions as $id => $label)
            <option value="{{ $id }}" @selected(old('parent_id', $category->parent_id ?? $selectedParentId ?? '') == $id)>{{ $label }}</option>
        @endforeach
    </select>
    @error('parent_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $category->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="image" class="form-label">Image</label>
    <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
    @error('image')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @if (isset($category) && $category->getFirstMedia('image'))
        <img src="{{ $category->getFirstMediaUrl('image', 'thumb') }}" alt="{{ $category->name }}" class="img-thumbnail mt-2" style="max-width: 150px;">
    @endif

    <x-media-picker context="category" file-input-id="image" modal-id="categoryMediaPicker" />
</div>

<div class="form-check mb-3">
    <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input"
           @checked(old('is_active', $category->is_active ?? true))>
    <label for="is_active" class="form-check-label">Active</label>
</div>

<button type="submit" class="btn btn-primary">Save</button>
<a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
