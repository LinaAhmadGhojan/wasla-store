<?php

namespace App\Services\ExternalShopping;

use App\Contracts\ExternalPlatformServiceInterface;

/**
 * Fallback implementation used until real platform adapters (SHEIN, Trendyol,
 * Temu, Noon, Amazon, ...) are built. It never talks to any external site and
 * simply signals that a Wasla staff member must review the request manually.
 *
 * Future adapters should implement ExternalPlatformServiceInterface per
 * platform and be resolved (e.g. by platform slug) instead of this class.
 */
class ManualReviewPlatformService implements ExternalPlatformServiceInterface
{
    public function getProduct(string $url): array
    {
        return [
            'name' => null,
            'description' => null,
            'image' => null,
            'price' => null,
            'currency' => null,
            'status' => 'manual_review_required',
        ];
    }

    public function getVariants(string $externalProductId): array
    {
        return [];
    }

    public function checkAvailability(string $externalProductId): bool
    {
        return false;
    }

    public function getPrice(string $externalProductId): ?float
    {
        return null;
    }

    public function createPurchase(array $data): array
    {
        return [
            'success' => false,
            'reference' => null,
            'message' => 'Automatic purchasing is not available yet. This request requires manual review by Wasla staff.',
        ];
    }
}
