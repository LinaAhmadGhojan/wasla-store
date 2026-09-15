<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storefront catalog response cache
    |--------------------------------------------------------------------------
    |
    | Versioned Laravel cache for /api/v1/catalog*. On product/admin writes the
    | content version is bumped so old keys are abandoned immediately.
    | Prefer CACHE_DRIVER=redis in production for high concurrency.
    |
    */

    'enabled' => env('CATALOG_CACHE_ENABLED', true),

    'http_max_age' => (int) env('CATALOG_CACHE_HTTP_MAX_AGE', 30),

    'http_stale_while_revalidate' => (int) env('CATALOG_CACHE_SWR', 90),
];
