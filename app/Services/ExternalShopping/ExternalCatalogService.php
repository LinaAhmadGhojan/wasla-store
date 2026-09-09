<?php

namespace App\Services\ExternalShopping;

use App\Contracts\ExternalCatalogBrowserInterface;
use App\Models\ExternalPlatform;
use InvalidArgumentException;

class ExternalCatalogService
{
    public function __construct(private ExternalCatalogBrowserInterface $browser)
    {
    }

    public function assertPlatform(string $slug): ExternalPlatform
    {
        $platform = ExternalPlatform::where('slug', $slug)->where('is_active', true)->first();

        if (! $platform) {
            throw new InvalidArgumentException("Unknown or inactive platform [{$slug}]");
        }

        return $platform;
    }

    public function categories(string $platformSlug): array
    {
        $platform = $this->assertPlatform($platformSlug);

        return [
            'platform' => $this->platformPayload($platform),
            'categories' => $this->browser->categories($platformSlug),
        ];
    }

    public function products(string $platformSlug, ?string $categoryId, ?string $query, int $page): array
    {
        $platform = $this->assertPlatform($platformSlug);
        $result = $this->browser->products($platformSlug, $categoryId, $query, $page);

        return [
            'platform' => $this->platformPayload($platform),
            'products' => $result['data'],
            'meta' => $result['meta'],
        ];
    }

    public function product(string $platformSlug, string $externalProductId): array
    {
        $platform = $this->assertPlatform($platformSlug);
        $product = $this->browser->product($platformSlug, $externalProductId);

        if (! $product) {
            throw new InvalidArgumentException('Product not found');
        }

        $pricing = app(PricingEngine::class);
        $quote = $pricing->quote(
            originalPrice: (float) ($product['price'] ?? 0),
            originalCurrency: $product['currency'] ?? 'AED',
            markupRate: (float) ($platform->markup_rate ?? 15),
            serviceFee: 10.0,
            shipping: 15.0
        );

        return [
            'platform' => $this->platformPayload($platform),
            'product' => $product,
            'quote' => $quote,
            'import_level' => config('external_shopping.catalog_driver', 'mock'),
        ];
    }

    private function platformPayload(ExternalPlatform $platform): array
    {
        return [
            'id' => $platform->id,
            'name' => $platform->name,
            'slug' => $platform->slug,
            'logo' => $platform->logo,
            'website' => $platform->website,
            'currency' => $platform->currency,
            'country' => $platform->country,
        ];
    }
}
