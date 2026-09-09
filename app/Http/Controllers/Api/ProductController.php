<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['vendor', 'category.parent', 'brand'])
            ->where('is_active', true);

        if ($request->filled('ids')) {
            $ids = collect(explode(',', (string) $request->query('ids')))
                ->map(fn ($id) => (int) trim($id))
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->take(24)
                ->values()
                ->all();
            if ($ids !== []) {
                $query->whereIn('id', $ids);
            }
        }

        if ($request->filled('category_id')) {
            $categoryIds = [(int) $request->query('category_id')];

            // Include descendant categories (one level of grandchildren is enough for this catalog's depth).
            $childIds = \App\Models\Category::where('parent_id', $request->query('category_id'))->pluck('id');
            $categoryIds = array_merge($categoryIds, $childIds->all());
            $grandchildIds = \App\Models\Category::whereIn('parent_id', $childIds)->pluck('id');
            $categoryIds = array_merge($categoryIds, $grandchildIds->all());

            $query->whereIn('category_id', $categoryIds);
        }

        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where('name', 'like', "%{$q}%");
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->query('brand_id'));
        }

        switch ($request->query('sort')) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'top_rated':
                $query->orderByDesc('rating')->orderByDesc('total_reviews')->orderByDesc('created_at');
                break;
            case 'trending':
                $query->orderByDesc('is_featured')->orderByDesc('created_at');
                break;
            default:
                if ($request->filled('ids')) {
                    // keep DB order; frontend can re-sort
                    $query->orderByDesc('created_at');
                } else {
                    $query->orderByDesc('created_at');
                }
        }

        $products = $query->paginate(16)->withQueryString();

        return response()->json($products);
    }

    public function show(Product $product)
    {
        $product->load(['vendor', 'category.parent', 'brand', 'variants', 'images']);
        $product->brand?->loadCount('products');

        return response()->json($product);
    }
}
