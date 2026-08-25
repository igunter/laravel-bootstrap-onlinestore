@csrf

<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $brand->name ?? '') }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $brand->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="logo" class="form-label">Logo</label>
    <input type="file" name="logo" id="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
    @error('logo')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @if (isset($brand) && $brand->getFirstMedia('logo'))
        <img src="{{ $brand->getFirstMediaUrl('logo', 'thumb') }}" alt="{{ $brand->name }}" class="img-thumbnail mt-2" style="max-width: 150px;">
    @endif

    <x-media-picker context="brand" file-input-id="logo" modal-id="brandMediaPicker" />
</div>

<div class="form-check mb-3">
    <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input"
           @checked(old('is_active', $brand->is_active ?? true))>
    <label for="is_active" class="form-check-label">Active</label>
</div>

<button type="submit" class="btn btn-primary">Save</button>
<a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
