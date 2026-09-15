<?php

namespace App\Services\Express;

use App\Models\ExpressCategory;
use App\Models\ExpressMenuItem;
use App\Models\ExpressStore;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ExpressBrowseService
{
    public function browse(Request $request): array
    {
        $runner = function () use ($request) {
            $stores = $this->filteredStores($request);
            $items = $this->filteredItems($request, $stores);

            return [
                'categories' => $this->categoriesPayload(),
                'stores' => $stores->map(fn (ExpressStore $s) => $this->serializeStore($s))->values()->all(),
                'items' => $items->map(fn (ExpressMenuItem $i) => $this->serializeItem($i))->values()->all(),
                'meta' => [
                    'sort' => $request->query('sort', 'recommended'),
                    'stores_count' => $stores->count(),
                    'items_count' => $items->count(),
                ],
            ];
        };

        return ExpressCache::remember('browse', $this->cacheParams($request), ExpressCache::TTL_BROWSE, $runner);
    }

    public function facets(Request $request): array
    {
        $runner = function () {
            $cuisines = ExpressStore::query()
                ->active()
                ->whereNotNull('cuisine')
                ->where('cuisine', '!=', '')
                ->distinct()
                ->orderBy('cuisine')
                ->pluck('cuisine')
                ->values()
                ->all();

            return [
                'categories' => $this->categoriesPayload(),
                'cuisines' => $cuisines,
                'eta' => [
                    ['key' => 'all', 'label' => 'الكل', 'max_min' => null],
                    ['key' => 'fast', 'label' => 'خلال 20 دقيقة', 'max_min' => 20],
                    ['key' => 'medium', 'label' => 'خلال 35 دقيقة', 'max_min' => 35],
                    ['key' => 'any', 'label' => 'أي وقت', 'max_min' => null],
                ],
                'ratings' => [
                    ['key' => 'all', 'label' => 'الكل', 'min' => 0],
                    ['key' => '4.5', 'label' => '4.5 فأكثر', 'min' => 4.5],
                    ['key' => '4', 'label' => '4 فأكثر', 'min' => 4],
                    ['key' => '3.5', 'label' => '3.5 فأكثر', 'min' => 3.5],
                ],
                'sort' => [
                    ['key' => 'recommended', 'label' => 'المقترح'],
                    ['key' => 'fastest', 'label' => 'الأسرع توصيلاً'],
                    ['key' => 'price_asc', 'label' => 'السعر من الأقل'],
                    ['key' => 'price_desc', 'label' => 'السعر من الأعلى'],
                    ['key' => 'rating', 'label' => 'الأعلى تقييماً'],
                ],
            ];
        };

        return ExpressCache::remember('facets', ['v' => 1], ExpressCache::TTL_FACETS, $runner);
    }

    public function item(int $id): ?array
    {
        return ExpressCache::remember('item', ['id' => $id, 'opts' => 2], ExpressCache::TTL_BROWSE, function () use ($id) {
            $item = ExpressMenuItem::query()
                ->with([
                    'store.category',
                    'category',
                    'variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                    'extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                ])
                ->active()
                ->whereHas('store', fn ($q) => $q->active())
                ->find($id);

            if (! $item) {
                return null;
            }

            $variants = $item->variants->map(fn ($v) => [
                'id' => $v->id,
                'label' => $v->label,
                'unit_label' => $v->unit_label,
                'price' => number_format($v->effective_price).' ل.س',
                'priceNum' => $v->effective_price,
                'oldPrice' => ($v->sale_price_syp && $v->sale_price_syp < $v->price_syp)
                    ? number_format((int) $v->price_syp).' ل.س'
                    : null,
                'is_default' => (bool) $v->is_default,
            ])->values()->all();

            $extraGroups = $item->extras
                ->groupBy('group_name')
                ->map(fn ($rows, $group) => [
                    'name' => $group,
                    'choices' => $rows->map(fn ($e) => [
                        'id' => $e->id,
                        'label' => $e->label,
                        'price_delta' => (int) $e->price_delta_syp,
                        'price_delta_label' => $e->price_delta_syp > 0
                            ? '+'.number_format((int) $e->price_delta_syp).' ل.س'
                            : ($e->price_delta_syp < 0 ? number_format((int) $e->price_delta_syp).' ل.س' : null),
                        'is_default' => (bool) $e->is_default,
                    ])->values()->all(),
                ])
                ->values()
                ->all();

            return [
                'item' => array_merge($this->serializeItem($item), [
                    'description' => $item->description,
                    'unit_label' => $item->unit_label,
                    'serving_note' => $item->serving_note,
                    'ingredients' => $item->ingredients,
                    'store_slug' => $item->store?->slug,
                    'delivery_fee_syp' => (int) ($item->store?->delivery_fee_syp ?? 0),
                    'store_eta' => $item->store?->eta_label,
                    'total_reviews' => (int) $item->total_reviews,
                    'variants' => $variants,
                    'extra_groups' => $extraGroups,
                ]),
                'store' => $item->store ? $this->serializeStore($item->store) : null,
            ];
        });
    }

    public function similar(int $id): array
    {
        return ExpressCache::remember('similar', ['id' => $id], ExpressCache::TTL_BROWSE, function () use ($id) {
            $item = ExpressMenuItem::query()->with(['store', 'category'])->active()->find($id);
            if (! $item) {
                return ['items' => []];
            }

            $sameStore = ExpressMenuItem::query()
                ->with(['store.category', 'category'])
                ->active()
                ->where('express_store_id', $item->express_store_id)
                ->where('id', '!=', $item->id)
                ->orderByDesc('is_featured')
                ->orderByDesc('rating')
                ->limit(4)
                ->get();

            $sameCategory = ExpressMenuItem::query()
                ->with(['store.category', 'category'])
                ->active()
                ->where('id', '!=', $item->id)
                ->whereNotIn('id', $sameStore->pluck('id'))
                ->when($item->express_category_id, fn ($q) => $q->where('express_category_id', $item->express_category_id))
                ->orderByDesc('rating')
                ->limit(8)
                ->get();

            $merged = $sameStore->concat($sameCategory)->unique('id')->take(8);

            return [
                'items' => $merged->map(fn (ExpressMenuItem $i) => $this->serializeItem($i))->values()->all(),
            ];
        });
    }

    public function store(int $id, ?string $tab = null): ?array
    {
        return ExpressCache::remember('store', ['id' => $id, 'tab' => $tab ?: 'all'], ExpressCache::TTL_BROWSE, function () use ($id, $tab) {
            $store = ExpressStore::query()
                ->with('category')
                ->active()
                ->find($id);

            if (! $store) {
                return null;
            }

            $query = ExpressMenuItem::query()
                ->with(['store.category', 'category'])
                ->active()
                ->where('express_store_id', $store->id);

            if ($tab === 'offers') {
                $query->where(function ($q) {
                    $q->where('is_offer', true)
                        ->orWhereColumn('sale_price_syp', '<', 'price_syp');
                });
            } elseif ($tab === 'new') {
                $query->where('created_at', '>=', now()->subDays(14));
            }

            $items = $query->orderByDesc('is_featured')->orderBy('sort_order')->get();

            return [
                'store' => array_merge($this->serializeStore($store), [
                    'description' => $store->description,
                    'area' => $store->area,
                    'total_reviews' => (int) $store->total_reviews,
                ]),
                'items' => $items->map(fn (ExpressMenuItem $i) => $this->serializeItem($i))->values()->all(),
                'meta' => ['tab' => $tab ?: 'all'],
            ];
        });
    }

    /**
     * @return Collection<int, ExpressStore>
     */
    private function filteredStores(Request $request): Collection
    {
        $query = ExpressStore::query()
            ->with('category')
            ->active()
            ->orderByDesc('is_featured')
            ->orderByDesc('rating');

        if ($request->filled('category') && $request->query('category') !== 'all') {
            $slug = (string) $request->query('category');
            $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
        }

        if ($request->filled('cuisine')) {
            $query->where('cuisine', $request->query('cuisine'));
        }

        $etaMax = $this->etaMax($request);
        if ($etaMax !== null) {
            $query->where('eta_max_minutes', '<=', $etaMax);
        }

        if (filter_var($request->query('fast_only'), FILTER_VALIDATE_BOOLEAN)) {
            $query->where('eta_max_minutes', '<=', 25);
        }

        $ratingMin = $this->ratingMin($request);
        if ($ratingMin > 0) {
            $query->where('rating', '>=', $ratingMin);
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, ExpressStore>  $stores
     * @return Collection<int, ExpressMenuItem>
     */
    private function filteredItems(Request $request, Collection $stores): Collection
    {
        $storeIds = $stores->pluck('id');
        if ($storeIds->isEmpty()) {
            return collect();
        }

        $query = ExpressMenuItem::query()
            ->with(['store.category', 'category'])
            ->active()
            ->whereIn('express_store_id', $storeIds);

        if ($request->filled('category') && $request->query('category') !== 'all') {
            $slug = (string) $request->query('category');
            $query->where(function ($q) use ($slug) {
                $q->whereHas('category', fn ($c) => $c->where('slug', $slug))
                    ->orWhereHas('store.category', fn ($c) => $c->where('slug', $slug));
            });
        }

        if ($request->filled('store_id')) {
            $query->where('express_store_id', (int) $request->query('store_id'));
        }

        if ($request->filled('cuisine')) {
            $query->whereHas('store', fn ($q) => $q->where('cuisine', $request->query('cuisine')));
        }

        $etaMax = $this->etaMax($request);
        if ($etaMax !== null) {
            $query->where(function ($q) use ($etaMax) {
                $q->where('eta_min_minutes', '<=', $etaMax)
                    ->orWhere(function ($q2) use ($etaMax) {
                        $q2->whereNull('eta_min_minutes')
                            ->whereHas('store', fn ($s) => $s->where('eta_max_minutes', '<=', $etaMax));
                    });
            });
        }

        if (filter_var($request->query('fast_only'), FILTER_VALIDATE_BOOLEAN)) {
            $query->where(function ($q) {
                $q->where('eta_min_minutes', '<=', 25)
                    ->orWhere(function ($q2) {
                        $q2->whereNull('eta_min_minutes')
                            ->whereHas('store', fn ($s) => $s->where('eta_max_minutes', '<=', 25));
                    });
            });
        }

        $ratingMin = $this->ratingMin($request);
        if ($ratingMin > 0) {
            $query->where('rating', '>=', $ratingMin);
        }

        if (filter_var($request->query('offers_only'), FILTER_VALIDATE_BOOLEAN)) {
            $query->where('is_offer', true);
        }

        if ($request->filled('min_price')) {
            $min = (int) $request->query('min_price');
            $query->whereRaw('COALESCE(NULLIF(sale_price_syp, 0), price_syp) >= ?', [$min]);
        }

        if ($request->filled('max_price')) {
            $max = (int) $request->query('max_price');
            $query->whereRaw('COALESCE(NULLIF(sale_price_syp, 0), price_syp) <= ?', [$max]);
        }

        $sort = (string) $request->query('sort', 'recommended');
        match ($sort) {
            'fastest' => $query->orderByRaw('COALESCE(eta_min_minutes, 999) asc'),
            'price_asc' => $query->orderByRaw('COALESCE(NULLIF(sale_price_syp, 0), price_syp) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(NULLIF(sale_price_syp, 0), price_syp) desc'),
            'rating' => $query->orderByDesc('rating'),
            default => $query->orderByDesc('is_featured')->orderByDesc('is_offer')->orderBy('sort_order'),
        };

        return $query->get();
    }

    private function categoriesPayload(): array
    {
        $cats = ExpressCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'icon', 'image']);

        return array_merge(
            [['key' => 'all', 'label' => 'الكل', 'id' => null]],
            $cats->map(fn ($c) => [
                'key' => $c->slug,
                'label' => $c->name,
                'id' => $c->id,
                'icon' => $c->icon,
                'image' => $c->image ? url($c->image) : null,
            ])->all()
        );
    }

    private function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $relative = ltrim($path, '/');
        $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $relative);
        if ($webp && $webp !== $relative && is_file(public_path($webp))) {
            return '/'.$webp;
        }

        return '/'.$relative;
    }

    private function serializeStore(ExpressStore $s): array
    {
        $image = $s->banner ?: $s->logo;

        return [
            'id' => $s->id,
            'name' => $s->store_name,
            'slug' => $s->slug,
            'cuisine' => $s->cuisine,
            'eta' => $s->eta_label,
            'etaMin' => (int) $s->eta_max_minutes,
            'rating' => (float) $s->rating,
            'category' => $s->category?->slug ?? 'all',
            'image' => $this->mediaUrl($image),
            'delivery_fee_syp' => (int) $s->delivery_fee_syp,
            'is_featured' => (bool) $s->is_featured,
        ];
    }

    private function serializeItem(ExpressMenuItem $i): array
    {
        $price = $i->effective_price;
        $hasSale = $i->sale_price_syp && $i->sale_price_syp > 0 && $i->sale_price_syp < $i->price_syp;
        $etaMin = $i->eta_min_minutes ?? $i->store?->eta_max_minutes ?? 30;

        return [
            'id' => $i->id,
            'name' => $i->name,
            'store' => $i->store?->store_name,
            'storeId' => $i->express_store_id,
            'category' => $i->category?->slug ?? $i->store?->category?->slug ?? 'all',
            'cuisine' => $i->store?->cuisine,
            'price' => number_format($price).' ل.س',
            'priceNum' => $price,
            'oldPrice' => $hasSale ? number_format((int) $i->price_syp).' ل.س' : null,
            'eta' => $etaMin.' د',
            'etaMin' => (int) $etaMin,
            'rating' => (float) $i->rating,
            'offer' => (bool) $i->is_offer || $hasSale,
            'image' => $this->mediaUrl($i->image),
            'unit_label' => $i->unit_label,
            'has_options' => $i->relationLoaded('variants')
                ? $i->variants->isNotEmpty()
                : null,
        ];
    }

    private function etaMax(Request $request): ?int
    {
        return match ((string) $request->query('eta', 'all')) {
            'fast' => 20,
            'medium' => 35,
            default => null,
        };
    }

    private function ratingMin(Request $request): float
    {
        $key = (string) $request->query('rating', 'all');
        if ($key === 'all' || $key === '') {
            return 0.0;
        }

        return (float) $key;
    }

    /**
     * @return array<string, mixed>
     */
    private function cacheParams(Request $request): array
    {
        $params = $request->query();
        ksort($params);

        return $params;
    }
}
