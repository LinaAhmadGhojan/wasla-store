<?php

namespace App\Services\ExternalShopping;

use App\Models\ExternalPlatform;
use App\Models\ExternalProduct;
use App\Support\SheinCatalog;
use App\Support\SheinUrlParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Multi-level External Product Import Engine.
 *
 * Level 1 — Official API (not available for most platforms yet)
 * Level 2 — Affiliate / Product Feed (wire later with credentials)
 * Level 3 — URL / Open Graph parser (best-effort, often blocked)
 * Level 4 — Manual review fallback (always available)
 *
 * Never treats scraping as the sole business path.
 */
class ExternalProductImportEngine
{
    public function __construct(private PricingEngine $pricing)
    {
    }

    /**
     * @param array{
     *   name?: ?string,
     *   price?: mixed,
     *   currency?: ?string,
     *   image?: ?string,
     *   sku?: ?string,
     *   color?: ?string,
     *   size?: ?string
     * } $manual
     */
    public function preview(string $url, array $manual = []): array
    {
        $platform = $this->detectPlatform($url);

        $catalogPreview = $this->trySheinCatalog($url, $manual, $platform);
        if ($catalogPreview !== null) {
            return $catalogPreview;
        }

        $manualName = trim((string) ($manual['name'] ?? ''));
        $manualPrice = $manual['price'] ?? null;
        $hasManualOverride = $manualName !== '' || (is_numeric($manualPrice) && (float) $manualPrice > 0);

        // No point re-hitting a site that already told us it needs manual confirmation.
        $parsed = $hasManualOverride ? ['fetched' => false] : $this->tryUrlParser($url);
        $importLevel = $hasManualOverride ? 'manual_confirmed' : ($parsed['fetched'] ? 'url_parser' : 'manual');

        $name = $manualName
            ?: ($parsed['name'] ?? null)
            ?: $this->guessNameFromUrl($url)
            ?: 'External product';

        $originalCurrency = trim((string) ($manual['currency'] ?? '')) ?: ($parsed['currency'] ?? null) ?: ($platform?->currency ?: 'USD');
        $originalPrice = is_numeric($manualPrice) && (float) $manualPrice > 0
            ? (float) $manualPrice
            : ($parsed['price'] ?? $this->estimateBasePrice($platform));

        $image = trim((string) ($manual['image'] ?? '')) ?: ($parsed['image'] ?? null) ?: $this->placeholderImage($name);

        $quote = $this->pricing->quote(
            originalPrice: (float) $originalPrice,
            originalCurrency: $originalCurrency,
            markupRate: (float) ($platform?->markup_rate ?? 15),
            serviceFee: 10.0,
            shipping: 15.0
        );

        $status = $hasManualOverride ? 'manual_confirmed' : ($parsed['fetched'] ? 'preview_ready' : 'estimated_preview');

        $externalProduct = null;
        if ($platform) {
            $externalProduct = ExternalProduct::updateOrCreate(
                [
                    'platform_id' => $platform->id,
                    'external_url' => $url,
                ],
                [
                    'external_id' => $parsed['external_id'] ?? null,
                    'name' => $name,
                    'description' => $parsed['description'] ?? null,
                    'image' => $image,
                    'original_price' => $quote['original_price'],
                    'currency' => $quote['original_currency'],
                    'status' => $status === 'estimated_preview' ? 'pending' : 'fetched',
                    'last_synced_at' => $status === 'estimated_preview' ? null : now(),
                ]
            );
        }

        $messages = [
            'preview_ready' => 'Product details were partially imported. Prices below are converted to AED (UAE).',
            'manual_confirmed' => 'Using the name/price you confirmed from the store page. Price below converted to AED.',
            'estimated_preview' => 'Could not auto-fetch this product from the store (no public API, or the site blocked our request with a bot-check). Open the product page yourself and enter the real name/price below so we can give you an accurate quote — or add it as-is and our team will confirm the final price.',
        ];

        return [
            'status' => $status,
            'import_level' => $importLevel,
            'needs_manual_confirmation' => $status === 'estimated_preview',
            'message' => $messages[$status],
            'platform' => $platform ? [
                'id' => $platform->id,
                'name' => $platform->name,
                'slug' => $platform->slug,
                'logo' => $platform->logo ? url($platform->logo) : null,
                'website' => $platform->website,
                'currency' => $platform->currency,
                'country' => $platform->country,
            ] : null,
            'product' => [
                'external_product_id' => $externalProduct?->id,
                'name' => $name,
                'description' => $parsed['description'] ?? ("Product from " . ($platform?->name ?? 'external store')),
                'image' => $image,
                'external_url' => $url,
                'availability' => $parsed['availability'] ?? 'unknown',
                'variants' => $parsed['variants'] ?? [],
                'colors' => [],
                'sizes' => [],
                'gallery' => array_values(array_filter([$image])),
                'selected' => null,
            ],
            'quote' => $quote,
            'api_note' => 'SHEIN/Temu/Noon/Trendyol have no free public product API. Amazon has affiliate APIs with approval. Official/affiliate adapters can be plugged into this engine later without changing checkout.',
        ];
    }

    /**
     * Local JSON catalog: colors, sizes, SKUs, images — used when SHEIN scrape is blocked.
     */
    private function trySheinCatalog(string $url, array $manual, ?ExternalPlatform $platform): ?array
    {
        $parsedUrl = SheinUrlParser::parse($url);
        $skuHint = trim((string) ($manual['sku'] ?? '')) ?: $parsedUrl['sku'];
        $catalog = SheinCatalog::findByGoodsId($parsedUrl['goods_id'])
            ?: SheinCatalog::findBySku($skuHint);

        if (! $catalog) {
            return null;
        }

        $selectedRow = $this->resolveCatalogSku($catalog, $manual, $parsedUrl, $skuHint);
        $colorKey = $selectedRow['color'];
        $sizeKey = $selectedRow['size'];
        $color = SheinCatalog::colorByKey($catalog, $colorKey) ?? ($catalog['colors'][0] ?? null);
        $gallery = $color ? SheinCatalog::resolvedImages($catalog, $color) : [];
        $image = trim((string) ($manual['image'] ?? '')) ?: ($gallery[0] ?? null);

        $originalCurrency = trim((string) ($manual['currency'] ?? '')) ?: ($catalog['currency'] ?? 'AED');
        $originalPrice = is_numeric($manual['price'] ?? null) && (float) $manual['price'] > 0
            ? (float) $manual['price']
            : (float) $selectedRow['price'];

        $quote = $this->pricing->quote(
            originalPrice: $originalPrice,
            originalCurrency: $originalCurrency,
            markupRate: (float) ($platform?->markup_rate ?? 15),
            serviceFee: 10.0,
            shipping: 15.0
        );

        $sizeNames = collect($catalog['sizes'] ?? [])->keyBy('key');
        $colorsPayload = [];
        foreach ($catalog['colors'] ?? [] as $c) {
            $images = SheinCatalog::resolvedImages($catalog, $c);
            $colorsPayload[] = [
                'key' => $c['key'],
                'name' => $c['name'],
                'hex' => $c['hex'] ?? null,
                'attr_id' => $c['attr_id'] ?? null,
                'thumbnail' => $images[0] ?? null,
                'images' => $images,
            ];
        }

        $variantsPayload = [];
        foreach (SheinCatalog::expandSkus($catalog) as $row) {
            $rowColor = SheinCatalog::colorByKey($catalog, $row['color']);
            $rowImages = $rowColor ? SheinCatalog::resolvedImages($catalog, $rowColor) : [];
            $rowQuote = $this->pricing->quote(
                originalPrice: (float) $row['price'],
                originalCurrency: $originalCurrency,
                markupRate: (float) ($platform?->markup_rate ?? 15),
                serviceFee: 10.0,
                shipping: 15.0
            );
            $sizeName = data_get($sizeNames->get($row['size']), 'name', $row['size']);
            $variantsPayload[] = [
                'sku' => $row['sku'],
                'color' => $row['color'],
                'color_name' => $rowColor['name'] ?? $row['color'],
                'size' => $row['size'],
                'size_name' => $sizeName,
                'price' => (float) $row['price'],
                'image' => $rowImages[0] ?? null,
                'quote' => $rowQuote,
            ];
        }

        $externalProduct = null;
        if ($platform) {
            $externalProduct = ExternalProduct::updateOrCreate(
                [
                    'platform_id' => $platform->id,
                    'external_url' => $catalog['url'] ?? $url,
                ],
                [
                    'external_id' => (string) $catalog['goods_id'],
                    'name' => $catalog['name'],
                    'description' => $catalog['description'] ?? null,
                    'image' => $image,
                    'original_price' => $quote['original_price'],
                    'currency' => $quote['original_currency'],
                    'status' => 'fetched',
                    'last_synced_at' => now(),
                ]
            );
        }

        $sizeName = data_get($sizeNames->get($sizeKey), 'name', $sizeKey);

        return [
            'status' => 'catalog_ready',
            'import_level' => 'local_catalog',
            'needs_manual_confirmation' => false,
            'message' => 'تم تحميل المنتج من كتالوج وصلة: اختاري اللون والمقاس — كل لون له صوره وكل SKU له سعره.',
            'platform' => $platform ? [
                'id' => $platform->id,
                'name' => $platform->name,
                'slug' => $platform->slug,
                'logo' => $platform->logo ? url($platform->logo) : null,
                'website' => $platform->website,
                'currency' => $platform->currency,
                'country' => $platform->country,
            ] : null,
            'product' => [
                'external_product_id' => $externalProduct?->id,
                'name' => $catalog['name'],
                'description' => $catalog['description'] ?? $catalog['name_en'] ?? null,
                'image' => $image,
                'external_url' => $catalog['url'] ?? $url,
                'availability' => 'in_stock',
                'goods_id' => (string) $catalog['goods_id'],
                'variants' => $variantsPayload,
                'colors' => $colorsPayload,
                'sizes' => array_map(fn ($s) => [
                    'key' => $s['key'],
                    'name' => $s['name'],
                ], $catalog['sizes'] ?? []),
                'gallery' => $gallery,
                'selected' => [
                    'sku' => $selectedRow['sku'],
                    'color' => $colorKey,
                    'color_name' => $color['name'] ?? $colorKey,
                    'color_hex' => $color['hex'] ?? null,
                    'size' => $sizeKey,
                    'size_name' => $sizeName,
                    'attr_id' => $color['attr_id'] ?? $parsedUrl['attr_id'],
                ],
            ],
            'quote' => $quote,
            'api_note' => 'Matched local SHEIN catalog JSON (goods_id ' . $catalog['goods_id'] . ').',
        ];
    }

    /**
     * @param array<string, mixed> $catalog
     * @param array<string, mixed> $manual
     * @param array{goods_id: ?string, sku: ?string, main_attr: ?string, attr_id: ?string} $parsedUrl
     * @return array{sku: string, color: string, size: string, price: float}
     */
    private function resolveCatalogSku(array $catalog, array $manual, array $parsedUrl, ?string $skuHint): array
    {
        $colorHint = trim((string) ($manual['color'] ?? ''));
        $sizeHint = trim((string) ($manual['size'] ?? ''));

        if ($skuHint) {
            $bySku = SheinCatalog::findSkuRow($catalog, $skuHint);
            if ($bySku) {
                return $bySku;
            }
        }

        if ($colorHint && $sizeHint) {
            $byPair = SheinCatalog::findSkuByColorSize($catalog, $colorHint, $sizeHint);
            if ($byPair) {
                return $byPair;
            }
        }

        $colorFromAttr = SheinCatalog::colorByAttrId($catalog, $parsedUrl['attr_id']);
        $colorKey = $colorHint
            ?: ($colorFromAttr['key'] ?? ($catalog['default_color'] ?? ($catalog['colors'][0]['key'] ?? null)));

        $defaultSku = SheinCatalog::findSkuRow($catalog, $catalog['default_sku'] ?? null);
        $sizeKey = $sizeHint
            ?: ($defaultSku && $defaultSku['color'] === $colorKey ? $defaultSku['size'] : (($catalog['sizes'][0]['key'] ?? null)));

        if ($colorKey && $sizeKey) {
            $byPair = SheinCatalog::findSkuByColorSize($catalog, $colorKey, $sizeKey);
            if ($byPair) {
                return $byPair;
            }
        }

        if ($defaultSku) {
            return $defaultSku;
        }

        $generated = SheinCatalog::expandSkus($catalog);

        return $generated[0] ?? ['sku' => '', 'color' => '', 'size' => '', 'price' => 0];
    }

    public function detectPlatform(string $url): ?ExternalPlatform
    {
        $host = Str::lower(parse_url($url, PHP_URL_HOST) ?? '');

        $map = [
            'shein' => ['shein.com', 'shein'],
            'trendyol' => ['trendyol.com', 'trendyol'],
            'temu' => ['temu.com', 'temu'],
            'noon' => ['noon.com', 'noon'],
            'amazon' => ['amazon.', 'amzn.'],
        ];

        foreach ($map as $slug => $needles) {
            foreach ($needles as $needle) {
                if (Str::contains($host, $needle)) {
                    return ExternalPlatform::where('slug', $slug)->where('is_active', true)->first();
                }
            }
        }

        return null;
    }

    /**
     * Level 3: best-effort Open Graph / title parse. Often blocked — that is expected.
     */
    private function tryUrlParser(string $url): array
    {
        try {
            $response = Http::timeout(4)
                ->withHeaders([
                    'User-Agent' => 'WaslaBot/1.0 (+https://wasla.local; product-preview)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);

            $effectiveUrl = (string) $response->effectiveUri();
            if (! $response->successful() || Str::contains(Str::lower($effectiveUrl), ['risk/challenge', 'captcha', '/login'])) {
                // Redirected into a bot-check / login wall — HTTP 200 on a page with no product data.
                return ['fetched' => false];
            }

            $html = $response->body();
            $structuredPrice = $this->extractStructuredPrice($html);
            $ogTitle = $this->meta($html, 'og:title');

            // Only trust this as "fetched" when we actually found product-shaped data —
            // a generic <title> on an error/challenge page is not a real signal.
            if (! $ogTitle && $structuredPrice['price'] === null) {
                return ['fetched' => false];
            }

            return [
                'fetched' => true,
                'name' => $ogTitle ?: $this->htmlTitle($html),
                'description' => $this->meta($html, 'og:description'),
                'image' => $this->meta($html, 'og:image'),
                'availability' => 'unknown',
                'variants' => [],
                'external_id' => null,
                'price' => $structuredPrice['price'],
                'currency' => $structuredPrice['currency'],
            ];
        } catch (\Throwable $e) {
            return ['fetched' => false];
        }
    }

    /**
     * Best-effort price extraction from schema.org Product JSON-LD or
     * OpenGraph/Product meta tags. Returns nulls when nothing usable is found
     * (very common — most platforms hide price behind JS or bot-checks).
     */
    private function extractStructuredPrice(string $html): array
    {
        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            foreach ($matches[1] as $block) {
                $data = json_decode(trim($block), true);
                if (! is_array($data)) {
                    continue;
                }

                foreach ($this->flattenJsonLd($data) as $node) {
                    $type = $node['@type'] ?? null;
                    $isProduct = $type === 'Product' || (is_array($type) && in_array('Product', $type, true));
                    if (! $isProduct) {
                        continue;
                    }

                    $offers = $node['offers'] ?? null;
                    $offers = is_array($offers) && isset($offers[0]) ? $offers[0] : $offers;
                    $price = $offers['price'] ?? $offers['priceSpecification']['price'] ?? null;
                    $currency = $offers['priceCurrency'] ?? null;

                    if ($price !== null && is_numeric($price)) {
                        return ['price' => (float) $price, 'currency' => $currency];
                    }
                }
            }
        }

        $metaPrice = $this->meta($html, 'og:price:amount') ?: $this->meta($html, 'product:price:amount');
        $metaCurrency = $this->meta($html, 'og:price:currency') ?: $this->meta($html, 'product:price:currency');

        if ($metaPrice !== null && is_numeric($metaPrice)) {
            return ['price' => (float) $metaPrice, 'currency' => $metaCurrency];
        }

        return ['price' => null, 'currency' => null];
    }

    /** Flatten JSON-LD, which may be a single object, a list, or a @graph wrapper. */
    private function flattenJsonLd(array $data): array
    {
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            return $data['@graph'];
        }

        return isset($data[0]) ? $data : [$data];
    }

    private function meta(string $html, string $property): ?string
    {
        $patterns = [
            '/property=["\']' . preg_quote($property, '/') . '["\']\s+content=["\']([^"\']+)["\']/i',
            '/content=["\']([^"\']+)["\']\s+property=["\']' . preg_quote($property, '/') . '["\']/i',
            '/name=["\']' . preg_quote($property, '/') . '["\']\s+content=["\']([^"\']+)["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                return html_entity_decode(trim($m[1]));
            }
        }

        return null;
    }

    private function htmlTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            return trim(html_entity_decode(strip_tags($m[1])));
        }

        return null;
    }

    private function guessNameFromUrl(string $url): ?string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') {
            return null;
        }

        $segment = Str::afterLast($path, '/');
        $segment = preg_replace('/\.(html?|php).*$/i', '', $segment);
        $segment = preg_replace('/-p-\d+$/i', '', $segment);
        $segment = str_replace(['-', '_'], ' ', $segment);
        $segment = trim(preg_replace('/\s+/', ' ', $segment));

        if (strlen($segment) < 3) {
            return null;
        }

        return Str::title($segment);
    }

    private function estimateBasePrice(?ExternalPlatform $platform): float
    {
        // Reasonable demo estimate until a real adapter/API is connected.
        return match ($platform?->slug) {
            'shein', 'temu' => 18.00,
            'trendyol' => 25.00,
            'noon' => 45.00,
            'amazon' => 35.00,
            default => 20.00,
        };
    }

    private function placeholderImage(string $seed): string
    {
        return 'https://picsum.photos/seed/' . Str::slug(Str::limit($seed, 40, '')) . '/600/600';
    }
}
