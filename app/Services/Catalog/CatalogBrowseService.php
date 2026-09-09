<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class CatalogBrowseService
{
    public function browse(Request $request): array
    {
        $query = $this->baseQuery();
        $this->applyBrowseScope($query, $request);
        $this->applySearch($query, $request);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        if ($request->filled('q')) {
            SearchQuery::record((string) $request->query('q'));
        }

        /** @var LengthAwarePaginator $page */
        $page = $query->paginate(min(48, max(8, (int) $request->query('per_page', 16))))->withQueryString();

        return [
            'products' => $page,
            'meta' => [
                'section' => $request->query('section'),
                'sort' => $request->query('sort', 'newest'),
                'q' => $request->query('q'),
                'active_filters' => $this->activeFilterSummary($request),
            ],
        ];
    }

    public function facets(Request $request): array
    {
        $category = null;
        if ($request->filled('category_id')) {
            $category = Category::query()->with('parent')->find((int) $request->query('category_id'));
        }

        $profileFilters = $this->filtersForCategory($category);
        $base = $this->baseQuery();
        $this->applyBrowseScope($base, $request);
        $this->applySearch($base, $request);
        // facets computed with other filters except the one being counted — simplified: apply all then extract distincts from matching set ids
        $this->applyFilters($base, $request, except: []);

        $productIds = (clone $base)->select('products.id')->limit(2000)->pluck('products.id');

        $colors = Product::query()
            ->whereIn('products.id', $productIds)
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->whereNotNull('product_variants.metadata')
            ->limit(500)
            ->get(['product_variants.metadata'])
            ->map(function ($row) {
                $m = is_array($row->metadata) ? $row->metadata : (json_decode((string) $row->metadata, true) ?: []);

                return $m['color_name'] ?? null;
            })
            ->filter()
            ->countBy()
            ->map(fn ($count, $name) => ['value' => $name, 'count' => $count])
            ->values()
            ->take(30)
            ->all();

        $sizes = Product::query()
            ->whereIn('products.id', $productIds)
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->limit(800)
            ->get(['product_variants.metadata', 'product_variants.option_value'])
            ->map(function ($row) {
                $m = is_array($row->metadata) ? $row->metadata : (json_decode((string) $row->metadata, true) ?: []);

                return $m['size'] ?? $m['size_key'] ?? $row->option_value;
            })
            ->filter(fn ($v) => $v !== null && $v !== '' && $v !== 'default')
            ->countBy()
            ->map(fn ($count, $name) => ['value' => (string) $name, 'count' => $count])
            ->values()
            ->take(40)
            ->all();

        $brands = Brand::query()
            ->whereHas('products', fn ($q) => $q->whereIn('products.id', $productIds))
            ->withCount(['products as matched_count' => fn ($q) => $q->whereIn('products.id', $productIds)])
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'count' => $b->matched_count])
            ->all();

        $stores = Vendor::query()
            ->whereHas('products', fn ($q) => $q->whereIn('products.id', $productIds))
            ->withCount(['products as matched_count' => fn ($q) => $q->whereIn('products.id', $productIds)])
            ->orderBy('store_name')
            ->get(['id', 'store_name'])
            ->map(fn ($v) => ['id' => $v->id, 'name' => $v->store_name, 'count' => $v->matched_count])
            ->all();

        $priceBounds = Product::query()
            ->whereIn('id', $productIds)
            ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
            ->first();

        $specKeys = ['material', 'type', 'occasion', 'age', 'fit', 'pattern', 'length', 'heel_height', 'style'];
        $specFacets = [];
        foreach ($specKeys as $key) {
            if (! in_array($key, $profileFilters, true) && ! in_array($key, config('catalog.common_filters'), true)) {
                continue;
            }
            $values = Product::query()
                ->whereIn('id', $productIds)
                ->whereNotNull("specs->{$key}")
                ->limit(500)
                ->pluck("specs->{$key}")
                ->filter()
                ->map(fn ($v) => is_array($v) ? implode(',', $v) : (string) $v)
                ->countBy()
                ->map(fn ($count, $name) => ['value' => $name, 'count' => $count])
                ->values()
                ->take(20)
                ->all();
            if ($values !== []) {
                $specFacets[$key] = $values;
            }
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon', 'parent_id', 'image']);

        $collections = Collection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'image']);

        $labels = config('catalog.filter_labels', []);

        return [
            'profile' => $this->profileKeyForCategory($category),
            'available_filters' => array_values(array_unique(array_merge(
                config('catalog.common_filters', []),
                $profileFilters
            ))),
            'labels' => $labels,
            'categories' => $categories,
            'collections' => $collections,
            'brands' => $brands,
            'stores' => $stores,
            'colors' => $colors,
            'sizes' => $sizes,
            'specs' => $specFacets,
            'price' => [
                'min' => (float) ($priceBounds->min_price ?? 0),
                'max' => (float) ($priceBounds->max_price ?? 0),
            ],
            'genders' => [
                ['value' => 'women', 'label' => 'نساء'],
                ['value' => 'men', 'label' => 'رجال'],
                ['value' => 'kids', 'label' => 'أطفال'],
                ['value' => 'unisex', 'label' => 'للجميع'],
            ],
            'sorts' => config('catalog.sorts', []),
            'sections' => [
                'all' => 'كل المنتجات',
                'new' => 'المنتجات الجديدة',
                'bestsellers' => 'الأكثر مبيعاً',
                'most_viewed' => 'الأكثر مشاهدة',
                'top_rated' => 'الأعلى تقييماً',
                'offers' => 'العروض',
                'flash' => 'Flash Sale',
                'recommended' => 'المقترحة',
            ],
        ];
    }

    public function suggestions(string $q, int $limit = 10): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 1) {
            return [
                'suggestions' => [],
                'products' => [],
                'brands' => [],
                'categories' => [],
                'stores' => [],
            ];
        }

        $lower = mb_strtolower($q);
        $phraseSuggestions = [];
        foreach (config('catalog.suggestion_prefixes', []) as $prefix => $list) {
            if (str_starts_with($lower, mb_strtolower($prefix)) || str_contains($lower, mb_strtolower($prefix))) {
                foreach ($list as $phrase) {
                    if (str_contains(mb_strtolower($phrase), $lower) || str_starts_with(mb_strtolower($phrase), $lower)) {
                        $phraseSuggestions[] = $phrase;
                    }
                }
            }
        }

        // Complete typed query with common fashion suffixes
        $suffixes = ['Dress', 'Maxi Dress', 'Evening Dress', 'Summer Dress', 'Shirt', 'Sneakers', 'Bag'];
        if (! preg_match('/\s/u', $q) && mb_strlen($q) >= 3) {
            foreach ($suffixes as $suf) {
                $phraseSuggestions[] = trim($q.' '.$suf);
            }
        }

        $nameHits = Product::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$q}%")
            ->orderByDesc('view_count')
            ->limit($limit)
            ->pluck('name')
            ->all();

        $phraseSuggestions = array_values(array_unique(array_merge($phraseSuggestions, $nameHits)));
        $phraseSuggestions = array_slice($phraseSuggestions, 0, $limit);

        $products = Product::with(['brand', 'vendor'])
            ->where('is_active', true)
            ->where(function (Builder $b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('keywords', 'like', "%{$q}%")
                    ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', "%{$q}%"))
                    ->orWhereHas('brand', fn ($br) => $br->where('name', 'like', "%{$q}%"));
            })
            ->orderByDesc('is_featured')
            ->limit(6)
            ->get(['id', 'name', 'image', 'price', 'sale_price', 'brand_id', 'vendor_id', 'pricing_type']);

        $brands = Brand::query()->where('name', 'like', "%{$q}%")->limit(5)->get(['id', 'name', 'slug']);
        $categories = Category::query()->where('is_active', true)->where('name', 'like', "%{$q}%")->limit(5)->get(['id', 'name', 'slug']);
        $stores = Vendor::query()->where('store_name', 'like', "%{$q}%")->limit(5)->get(['id', 'store_name']);

        return [
            'suggestions' => $phraseSuggestions,
            'products' => $products,
            'brands' => $brands,
            'categories' => $categories,
            'stores' => $stores,
        ];
    }

    public function trendingSearches(int $limit = 10): SupportCollection
    {
        return SearchQuery::query()
            ->orderByDesc('hits')
            ->orderByDesc('last_searched_at')
            ->limit($limit)
            ->get(['query', 'hits']);
    }

    public function similar(Product $product, int $limit = 8): SupportCollection
    {
        return Product::with(['vendor', 'category', 'brand'])
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where(function (Builder $q) use ($product) {
                $q->where('category_id', $product->category_id)
                    ->orWhere('brand_id', $product->brand_id);
            })
            ->orderByDesc('rating')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    public function compare(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $ids = array_slice($ids, 0, 4);
        $products = Product::with(['vendor', 'category.parent', 'brand', 'variants', 'images'])
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $ordered = [];
        foreach ($ids as $id) {
            if ($products->has($id)) {
                $ordered[] = $products->get($id);
            }
        }

        $rows = [
            ['key' => 'price', 'label' => 'السعر'],
            ['key' => 'sale_price', 'label' => 'سعر العرض'],
            ['key' => 'rating', 'label' => 'التقييم'],
            ['key' => 'reviews', 'label' => 'عدد التقييمات'],
            ['key' => 'brand', 'label' => 'البراند'],
            ['key' => 'store', 'label' => 'المتجر'],
            ['key' => 'category', 'label' => 'التصنيف'],
            ['key' => 'material', 'label' => 'المادة'],
            ['key' => 'colors', 'label' => 'الألوان'],
            ['key' => 'sizes', 'label' => 'المقاسات'],
            ['key' => 'gender', 'label' => 'الجنس'],
            ['key' => 'fast_delivery', 'label' => 'توصيل سريع'],
            ['key' => 'flash', 'label' => 'Flash Sale'],
        ];

        $matrix = [];
        foreach ($rows as $row) {
            $cells = [];
            foreach ($ordered as $p) {
                $cells[] = $this->compareCell($p, $row['key']);
            }
            $matrix[] = [
                'key' => $row['key'],
                'label' => $row['label'],
                'values' => $cells,
            ];
        }

        return [
            'products' => $ordered,
            'rows' => $matrix,
        ];
    }

    public function recordView(Product $product): void
    {
        $product->increment('view_count');
    }

    private function baseQuery(): Builder
    {
        return Product::query()
            ->with(['vendor', 'category.parent', 'brand'])
            ->where('products.is_active', true);
    }

    private function applyBrowseScope(Builder $query, Request $request): void
    {
        $section = $request->query('section');

        if ($request->filled('category_id')) {
            $ids = $this->categoryTreeIds((int) $request->query('category_id'));
            $query->whereIn('products.category_id', $ids);
        }

        if ($request->filled('subcategory_id')) {
            $ids = $this->categoryTreeIds((int) $request->query('subcategory_id'));
            $query->whereIn('products.category_id', $ids);
        }

        if ($request->filled('brand_id')) {
            $query->where('products.brand_id', (int) $request->query('brand_id'));
        }

        if ($request->filled('store_id') || $request->filled('vendor_id')) {
            $query->where('products.vendor_id', (int) ($request->query('store_id') ?: $request->query('vendor_id')));
        }

        if ($request->filled('collection_id')) {
            $collectionId = (int) $request->query('collection_id');
            $query->whereHas('collections', fn ($q) => $q->where('collections.id', $collectionId));
        }

        match ($section) {
            'new' => $query->orderByDesc('products.created_at'),
            'bestsellers' => null, // sort handles
            'most_viewed' => null,
            'top_rated' => null,
            'offers' => $query->whereNotNull('products.sale_price')->whereColumn('products.sale_price', '<', 'products.price'),
            'flash' => $query->where('products.is_flash_sale', true)
                ->where(function (Builder $q) {
                    $q->whereNull('products.flash_ends_at')->orWhere('products.flash_ends_at', '>', now());
                }),
            'recommended' => $query->where('products.is_featured', true),
            default => null,
        };
    }

    private function applySearch(Builder $query, Request $request): void
    {
        if (! $request->filled('q')) {
            return;
        }

        $q = trim((string) $request->query('q'));
        $query->where(function (Builder $b) use ($q) {
            $b->where('products.name', 'like', "%{$q}%")
                ->orWhere('products.description', 'like', "%{$q}%")
                ->orWhere('products.keywords', 'like', "%{$q}%")
                ->orWhereHas('brand', fn ($br) => $br->where('name', 'like', "%{$q}%"))
                ->orWhereHas('vendor', fn ($v) => $v->where('store_name', 'like', "%{$q}%"))
                ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                ->orWhereHas('variants', function (Builder $v) use ($q) {
                    $v->where('sku', 'like', "%{$q}%")
                        ->orWhere('option_value', 'like', "%{$q}%");
                });
        });
    }

    private function applyFilters(Builder $query, Request $request, array $except = []): void
    {
        if (! in_array('price', $except, true)) {
            if ($request->filled('min_price')) {
                $query->whereRaw('COALESCE(products.sale_price, products.price) >= ?', [(float) $request->query('min_price')]);
            }
            if ($request->filled('max_price')) {
                $query->whereRaw('COALESCE(products.sale_price, products.price) <= ?', [(float) $request->query('max_price')]);
            }
        }

        if ($request->filled('rating') && ! in_array('rating', $except, true)) {
            $query->where('products.rating', '>=', (float) $request->query('rating'));
        }

        if ($request->boolean('on_sale') || $request->boolean('discount')) {
            $query->whereNotNull('products.sale_price')->whereColumn('products.sale_price', '<', 'products.price');
        }

        if ($request->filled('in_stock') || $request->query('availability') === 'in_stock') {
            $query->whereHas('variants', fn ($v) => $v->where('stock_qty', '>', 0));
        }

        if ($request->filled('gender') && ! in_array('gender', $except, true)) {
            $query->where('products.gender', $request->query('gender'));
        }

        if ($request->boolean('fast_delivery')) {
            $query->where('products.fast_delivery', true);
        }

        if ($request->filled('color')) {
            $color = $request->query('color');
            $query->whereHas('variants', function (Builder $v) use ($color) {
                $v->where('metadata->color_name', $color)
                    ->orWhere('metadata->color_key', $color)
                    ->orWhere('option_value', 'like', "%{$color}%");
            });
        }

        if ($request->filled('size')) {
            $size = $request->query('size');
            $query->whereHas('variants', function (Builder $v) use ($size) {
                $v->where('metadata->size', $size)
                    ->orWhere('metadata->size_key', $size)
                    ->orWhere('option_value', $size);
            });
        }

        foreach (['material', 'type', 'occasion', 'age', 'fit', 'pattern', 'length', 'heel_height', 'style'] as $specKey) {
            if ($request->filled($specKey)) {
                $query->where("products.specs->{$specKey}", $request->query($specKey));
            }
        }

        if ($request->filled('shipping') && $request->query('shipping') === 'fast') {
            $query->where('products.fast_delivery', true);
        }
    }

    private function applySort(Builder $query, Request $request): void
    {
        $section = $request->query('section');
        $sort = $request->query('sort');

        if (! $sort) {
            $sort = match ($section) {
                'bestsellers' => 'bestsellers',
                'most_viewed' => 'most_viewed',
                'top_rated' => 'top_rated',
                'new' => 'newest',
                'flash', 'offers' => 'discount',
                'recommended' => 'trending',
                default => 'newest',
            };
        }

        match ($sort) {
            'price_asc' => $query->orderByRaw('COALESCE(products.sale_price, products.price) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(products.sale_price, products.price) desc'),
            'bestsellers' => $query->orderByDesc('products.sales_count')->orderByDesc('products.view_count'),
            'most_viewed' => $query->orderByDesc('products.view_count'),
            'top_rated' => $query->orderByDesc('products.rating')->orderByDesc('products.total_reviews'),
            'discount' => $query->orderByRaw('(products.price - COALESCE(products.sale_price, products.price)) desc'),
            'trending' => $query->orderByDesc('products.is_featured')->orderByDesc('products.view_count')->orderByDesc('products.created_at'),
            default => $query->orderByDesc('products.created_at'),
        };
    }

    private function categoryTreeIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $childIds = Category::where('parent_id', $categoryId)->pluck('id');
        $ids = array_merge($ids, $childIds->all());
        $grandchildIds = Category::whereIn('parent_id', $childIds)->pluck('id');

        return array_values(array_unique(array_merge($ids, $grandchildIds->all())));
    }

    private function filtersForCategory(?Category $category): array
    {
        $key = $this->profileKeyForCategory($category);
        $profiles = config('catalog.category_profiles', []);

        return $profiles[$key]['filters'] ?? ($profiles['default']['filters'] ?? []);
    }

    private function profileKeyForCategory(?Category $category): string
    {
        if (! $category) {
            return 'default';
        }
        $hay = mb_strtolower(($category->slug ?? '').' '.($category->name ?? '').' '.($category->parent?->slug ?? '').' '.($category->parent?->name ?? ''));
        foreach (config('catalog.category_profiles', []) as $key => $profile) {
            if ($key === 'default') {
                continue;
            }
            foreach ($profile['match'] ?? [] as $needle) {
                if ($needle !== '' && str_contains($hay, mb_strtolower($needle))) {
                    return $key;
                }
            }
        }

        return 'default';
    }

    private function activeFilterSummary(Request $request): array
    {
        $keys = [
            'category_id', 'subcategory_id', 'brand_id', 'store_id', 'vendor_id', 'collection_id',
            'min_price', 'max_price', 'color', 'size', 'rating', 'gender', 'material', 'type',
            'occasion', 'age', 'fit', 'pattern', 'length', 'heel_height', 'style', 'section', 'q', 'sort',
        ];
        $out = [];
        foreach ($keys as $k) {
            if ($request->filled($k)) {
                $out[$k] = $request->query($k);
            }
        }
        if ($request->boolean('on_sale') || $request->boolean('discount')) {
            $out['discount'] = true;
        }
        if ($request->boolean('fast_delivery')) {
            $out['fast_delivery'] = true;
        }

        return $out;
    }

    private function compareCell(Product $p, string $key): string
    {
        $specs = is_array($p->specs) ? $p->specs : [];

        return match ($key) {
            'price' => (string) $p->price,
            'sale_price' => $p->sale_price !== null ? (string) $p->sale_price : '—',
            'rating' => (string) ($p->rating ?? 0),
            'reviews' => (string) ($p->total_reviews ?? 0),
            'brand' => (string) ($p->brand?->name ?? '—'),
            'store' => (string) ($p->vendor?->store_name ?? '—'),
            'category' => (string) ($p->category?->name ?? '—'),
            'material' => (string) ($specs['material'] ?? '—'),
            'colors' => collect($p->colorCatalog())->pluck('name')->filter()->unique()->implode(', ') ?: '—',
            'sizes' => collect($p->variants)->map(function ($v) {
                $m = $v->metadata ?? [];

                return $m['size'] ?? $m['size_key'] ?? $v->option_value;
            })->filter()->unique()->implode(', ') ?: '—',
            'gender' => (string) ($p->gender ?? '—'),
            'fast_delivery' => $p->fast_delivery ? 'نعم' : 'لا',
            'flash' => $p->is_flash_sale ? 'نعم' : 'لا',
            default => '—',
        };
    }
}
