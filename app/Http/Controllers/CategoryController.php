<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        // /page/{page} (see routes/shop.php) is read manually here rather than as a typed
        // parameter, for consistency with ProductController::index.
        $page = $request->route('page') ? (int) $request->route('page') : null;

        $categoryIds = $category->descendants()->pluck('id')->push($category->id);

        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('category_id', $categoryIds)
            ->with(['category', 'brand'])
            ->orderBy('name')
            ->paginate(12, ['*'], 'page', $page);

        return view('shop.categories.show', [
            'category' => $category,
            'products' => $products,
        ]);
    }
}
