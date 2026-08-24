<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::defaultOrder()->get()->toTree(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'parentOptions' => $this->parentOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $category = Category::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
        ]);

        if ($request->hasFile('image')) {
            $category->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parentOptions' => $this->parentOptions($category),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validateData($request, $category);

        if ($data['name'] !== $category->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category);
        }

        $category->update($data);

        if ($request->hasFile('image')) {
            $category->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    private function validateData(Request $request, ?Category $category = null): array
    {
        $parentIdRules = ['nullable', 'integer', 'exists:categories,id'];

        if ($category) {
            $parentIdRules[] = function ($attribute, $value, $fail) use ($category) {
                if ($value == $category->id) {
                    $fail('A category cannot be its own parent.');
                }
            };
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'parent_id' => $parentIdRules,
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        unset($data['image']);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function parentOptions(?Category $excluding = null): array
    {
        $tree = Category::defaultOrder()->get()->toTree();

        $options = [];

        $flatten = function ($nodes, $depth = 0) use (&$flatten, &$options, $excluding) {
            foreach ($nodes as $node) {
                if ($excluding && ($node->id === $excluding->id || $node->isDescendantOf($excluding))) {
                    continue;
                }

                $options[$node->id] = str_repeat('— ', $depth).$node->name;
                $flatten($node->children, $depth + 1);
            }
        };

        $flatten($tree);

        return $options;
    }

    private function uniqueSlug(string $name, ?Category $ignoring = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Category::where('slug', $slug)->when($ignoring, fn ($q) => $q->whereKeyNot($ignoring->id))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
