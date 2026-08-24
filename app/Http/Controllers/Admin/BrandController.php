<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('admin.brands.index', [
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.brands.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $brand = Brand::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
        ]);

        if ($request->hasFile('logo')) {
            $brand->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        return redirect()->route('admin.brands.index')->with('success', 'Brand created.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.edit', [
            'brand' => $brand,
        ]);
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($data['name'] !== $brand->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $brand);
        }

        $brand->update($data);

        if ($request->hasFile('logo')) {
            $brand->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        return redirect()->route('admin.brands.index')->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ]);

        unset($data['logo']);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function uniqueSlug(string $name, ?Brand $ignoring = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Brand::where('slug', $slug)->when($ignoring, fn ($q) => $q->whereKeyNot($ignoring->id))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
