<?php

namespace App\Observers;

use App\Services\Catalog\CatalogCache;

/**
 * Invalidates storefront catalog cache when catalog-related models change.
 * Ignores noisy fields (view_count) so page views do not thrash the cache.
 */
class CatalogCacheObserver
{
    /** @var list<string> */
    private const IGNORE_PRODUCT_FIELDS = [
        'view_count',
        'updated_at',
    ];

    public function created(mixed $model): void
    {
        CatalogCache::bump();
    }

    public function updated(mixed $model): void
    {
        if (method_exists($model, 'getChanges')) {
            $changed = array_keys($model->getChanges());
            $meaningful = array_diff($changed, self::IGNORE_PRODUCT_FIELDS);
            if ($meaningful === []) {
                return;
            }
        }

        CatalogCache::bump();
    }

    public function deleted(mixed $model): void
    {
        CatalogCache::bump();
    }

    public function restored(mixed $model): void
    {
        CatalogCache::bump();
    }
}
