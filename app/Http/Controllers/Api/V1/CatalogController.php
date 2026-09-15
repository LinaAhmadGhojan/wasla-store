<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Product;
use App\Services\Catalog\CatalogBrowseService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(private CatalogBrowseService $catalog)
    {
    }

    public function index(Request $request)
    {
        $result = $this->catalog->browse($request);
        $paginator = $result['products'];

        return $this->cachedJson([
            'data' => $paginator->items(),
            'meta' => array_merge($result['meta'], [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'links' => $paginator->linkCollection(),
            ]),
        ]);
    }

    public function facets(Request $request)
    {
        return $this->cachedJson($this->catalog->facets($request));
    }

    public function suggestions(Request $request)
    {
        $q = (string) $request->query('q', '');

        return $this->cachedJson($this->catalog->suggestions($q, (int) $request->query('limit', 10)));
    }

    public function trendingSearches(Request $request)
    {
        return $this->cachedJson([
            'data' => $this->catalog->trendingSearches((int) $request->query('limit', 12)),
        ]);
    }

    public function similar(Product $product)
    {
        return $this->cachedJson([
            'data' => $this->catalog->similar($product, (int) request('limit', 8)),
        ]);
    }

    public function compare(Request $request)
    {
        $ids = $request->input('ids', $request->query('ids'));
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        if (! is_array($ids)) {
            $ids = [];
        }

        return response()->json($this->catalog->compare($ids));
    }

    public function recordView(Product $product)
    {
        $this->catalog->recordView($product);

        return response()->json(['ok' => true, 'view_count' => $product->fresh()->view_count]);
    }

    public function collections()
    {
        return response()->json(
            Collection::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
        );
    }

    public function collectionShow(Collection $collection)
    {
        $collection->load(['products' => fn ($q) => $q->where('is_active', true)->with(['brand', 'vendor', 'category'])]);

        return response()->json($collection);
    }

    private function cachedJson(mixed $payload)
    {
        $response = response()->json($payload);

        if (! config('catalog_cache.enabled', true)) {
            return $response;
        }

        $maxAge = max(0, (int) config('catalog_cache.http_max_age', 30));
        $swr = max(0, (int) config('catalog_cache.http_stale_while_revalidate', 90));

        return $response->header(
            'Cache-Control',
            "public, max-age={$maxAge}, stale-while-revalidate={$swr}"
        );
    }
}
