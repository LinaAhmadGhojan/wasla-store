<?php

namespace App\Services\ExternalShopping;

use App\Contracts\ExternalCatalogBrowserInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SearchAPI.io adapter (paid).
 * Falls back to MockExternalCatalogBrowser when the API key is missing or the call fails,
 * so Wasla browse UX never fully breaks.
 */
class SearchApiExternalCatalogBrowser implements ExternalCatalogBrowserInterface
{
    public function __construct(private MockExternalCatalogBrowser $fallback)
    {
    }

    public function categories(string $platformSlug): array
    {
        // SearchAPI is search-oriented; category trees stay mock until a feed is configured.
        return $this->fallback->categories($platformSlug);
    }

    public function products(string $platformSlug, ?string $categoryId = null, ?string $query = null, int $page = 1): array
    {
        $apiKey = config('external_shopping.searchapi.api_key');

        if (! $apiKey || $platformSlug !== 'shein') {
            $result = $this->fallback->products($platformSlug, $categoryId, $query, $page);
            $result['meta']['driver'] = $apiKey ? 'mock_fallback_unsupported_platform' : 'mock_no_api_key';

            return $result;
        }

        try {
            $response = Http::timeout(12)
                ->get(config('external_shopping.searchapi.base_url'), [
                    'engine' => 'shein_search',
                    'q' => $query ?: ($categoryId ?: 'dress'),
                    'api_key' => $apiKey,
                    'shein_domain' => 'ae.shein.com',
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('SearchAPI HTTP ' . $response->status());
            }

            $json = $response->json();
            $organic = $json['organic_results'] ?? $json['products'] ?? [];
            $mapped = [];

            foreach ($organic as $index => $item) {
                $mapped[] = [
                    'id' => (string) ($item['product_id'] ?? $item['sku'] ?? ('searchapi-' . $index)),
                    'platform' => 'shein',
                    'name' => $item['title'] ?? 'SHEIN Product',
                    'name_ar' => $item['title'] ?? 'منتج SHEIN',
                    'image' => $item['thumbnail'] ?? ($item['image'] ?? null),
                    'price' => $this->extractPrice($item),
                    'currency' => $item['currency'] ?? 'AED',
                    'original_price' => null,
                    'rating' => $item['rating'] ?? null,
                    'category_id' => $categoryId,
                    'external_url' => $item['link'] ?? null,
                    'is_demo' => false,
                ];
            }

            return [
                'data' => $mapped,
                'meta' => [
                    'page' => $page,
                    'per_page' => count($mapped) ?: 12,
                    'total' => count($mapped),
                    'last_page' => 1,
                    'driver' => 'searchapi',
                ],
            ];
        } catch (\Throwable $e) {
            Log::warning('SearchAPI browse failed, using mock fallback', ['error' => $e->getMessage()]);
            $result = $this->fallback->products($platformSlug, $categoryId, $query, $page);
            $result['meta']['driver'] = 'mock_fallback_error';

            return $result;
        }
    }

    public function product(string $platformSlug, string $externalProductId): ?array
    {
        // TODO: SearchAPI has no stable per-product detail endpoint for SHEIN yet, and result
        // ids from products() aren't stored anywhere for a later lookup. Until that's wired,
        // the reliable path for a *specific* real product is "Buy From Anywhere" (paste its URL).
        return $this->fallback->product($platformSlug, $externalProductId);
    }

    private function extractPrice(array $item): float
    {
        foreach (['extracted_price', 'price', 'extracted_price_usd'] as $key) {
            if (isset($item[$key]) && is_numeric($item[$key])) {
                return (float) $item[$key];
            }
        }

        if (isset($item['price']) && is_string($item['price'])) {
            $n = preg_replace('/[^0-9.]/', '', $item['price']);

            return $n !== '' ? (float) $n : 0.0;
        }

        return 0.0;
    }
}
