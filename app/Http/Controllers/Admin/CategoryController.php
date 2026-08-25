<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use LogicException;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::defaultOrder()->get()->toTree(),
        ]);
    }

    public function create(Request $request): View
    {
        $parentOptions = $this->parentOptions();
        $selectedParentId = $request->integer('parent_id');

        return view('admin.categories.create', [
            'parentOptions' => $parentOptions,
            'selectedParentId' => array_key_exists($selectedParentId, $parentOptions) ? $selectedParentId : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $parent = $data['parent_id'] ? Category::find($data['parent_id']) : null;
        $category = Category::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name'], parent: $parent),
        ]);

        $this->resortAmongSiblings($category);

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

        if ($data['name'] !== $category->name || $data['parent_id'] != $category->parent_id) {
            $parent = $data['parent_id'] ? Category::find($data['parent_id']) : null;

            $data['slug'] = $this->uniqueSlug($data['name'], $category, $parent);
        }

        $category->update($data);

        $this->resortAmongSiblings($category);

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

    public function move(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'target_id' => [
                'required', 'integer', 'exists:categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    if ($value == $category->id) {
                        $fail('A category cannot be moved relative to itself.');
                    }
                },
            ],
            'position' => ['required', Rule::in(['before', 'after', 'into'])],
        ]);

        $target = Category::findOrFail($data['target_id']);

        try {
            match ($data['position']) {
                'before' => $category->insertBeforeNode($target),
                'after' => $category->insertAfterNode($target),
                'into' => $category->appendToNode($target)->save(),
            };
        } catch (LogicException $e) {
            abort(422, 'A category cannot be moved into its own descendant.');
        }

        $category->refresh();

        Category::where('parent_id', $category->parent_id)->update(['is_manually_ordered' => true]);

        return response()->json(['success' => true]);
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
        $data['parent_id'] = $data['parent_id'] ?? null;

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

    private function resortAmongSiblings(Category $category): void
    {
        $siblingsManuallyOrdered = Category::where('parent_id', $category->parent_id)
            ->whereKeyNot($category->id)
            ->where('is_manually_ordered', true)
            ->exists();

        if ($siblingsManuallyOrdered) {
            return;
        }

        $nextSibling = Category::query()
            ->where('parent_id', $category->parent_id)
            ->whereKeyNot($category->id)
            ->where('name', '>', $category->name)
            ->orderBy('name')
            ->first();

        if ($nextSibling) {
            $category->refresh()->insertBeforeNode($nextSibling);

            return;
        }

        $previousSibling = Category::query()
            ->where('parent_id', $category->parent_id)
            ->whereKeyNot($category->id)
            ->where('name', '<=', $category->name)
            ->orderByDesc('name')
            ->first();

        if ($previousSibling) {
            $category->refresh()->insertAfterNode($previousSibling);
        }
    }

    private function uniqueSlug(string $name, ?Category $ignoring = null, ?Category $parent = null): string
    {
        $base = $parent ? "{$parent->slug}-".Str::slug($name) : Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Category::where('slug', $slug)->when($ignoring, fn ($q) => $q->whereKeyNot($ignoring->id))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
