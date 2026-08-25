@csrf

<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $asset->name ?? '') }}">
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="file" class="form-label">File</label>
    <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept="image/*" {{ isset($asset) ? '' : 'required' }}>
    @error('file')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @if (isset($asset) && $asset->getFirstMedia('file'))
        <img src="{{ $asset->getFirstMediaUrl('file', 'thumb') }}" alt="{{ $asset->name }}" class="img-thumbnail mt-2" style="max-width: 150px;">
    @endif
</div>

<div class="mb-3">
    <label class="form-label d-block">Usable for</label>
    @php $selectedPurposes = old('usable_for', $asset->usable_for ?? $defaultPurposes ?? ['product']); @endphp
    @foreach (\App\Models\MediaAsset::PURPOSES as $purpose)
        <div class="form-check form-check-inline">
            <input type="checkbox" name="usable_for[]" id="purpose-{{ $purpose }}" value="{{ $purpose }}" class="form-check-input"
                   @checked(in_array($purpose, $selectedPurposes))>
            <label for="purpose-{{ $purpose }}" class="form-check-label text-capitalize">{{ $purpose }}</label>
        </div>
    @endforeach
    @error('usable_for')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
    <div class="form-text">Defaults to "product" if nothing is selected.</div>
</div>

<button type="submit" class="btn btn-primary">Save</button>
<a href="{{ route('admin.media.index') }}" class="btn btn-outline-secondary">Cancel</a>
