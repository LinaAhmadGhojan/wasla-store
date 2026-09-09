<?php

namespace App\Contracts;

interface ExternalPlatformServiceInterface
{
    /**
     * Fetch a snapshot of a product from the external platform by URL.
     *
     * @return array{name: ?string, description: ?string, image: ?string, price: ?float, currency: ?string, status: string}
     */
    public function getProduct(string $url): array;

    /**
     * Fetch the available variants (color/size/etc) for a previously fetched product.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getVariants(string $externalProductId): array;

    /**
     * Check whether the external product is currently available/in-stock.
     */
    public function checkAvailability(string $externalProductId): bool;

    /**
     * Get the current price for the external product, in the platform's currency.
     */
    public function getPrice(string $externalProductId): ?float;

    /**
     * Trigger the actual purchase of an item on the external platform on Wasla's behalf.
     *
     * @return array{success: bool, reference: ?string, message: string}
     */
    public function createPurchase(array $data): array;
}
