<?php

namespace App\Contracts;

interface ExternalCatalogBrowserInterface
{
    /**
     * Browse categories for a platform (Women, Men, Dresses...).
     *
     * @return array<int, array{id: string, name: string, name_ar: string, image: ?string, parent_id: ?string}>
     */
    public function categories(string $platformSlug): array;

    /**
     * Search / list products inside a platform category.
     *
     * @return array{data: array<int, array>, meta: array{page: int, per_page: int, total: int, last_page: int}}
     */
    public function products(string $platformSlug, ?string $categoryId = null, ?string $query = null, int $page = 1): array;

    /**
     * Get a single external product detail.
     *
     * @return array<string, mixed>|null
     */
    public function product(string $platformSlug, string $externalProductId): ?array;
}
