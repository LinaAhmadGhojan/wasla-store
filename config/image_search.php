<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Visual / image search
    |--------------------------------------------------------------------------
    | driver:
    |   local — PHP GD (dHash + color histogram) — works without Python
    |   clip  — external Python CLIP microservice (higher quality)
    */
    'driver' => env('IMAGE_SEARCH_DRIVER', 'local'),

    'clip' => [
        'base_url' => env('IMAGE_SEARCH_CLIP_URL', 'http://127.0.0.1:3010'),
        'timeout' => (int) env('IMAGE_SEARCH_CLIP_TIMEOUT', 180),
        // CLIP cosine similarity threshold (normalized embeddings)
        'min_score' => (float) env('IMAGE_SEARCH_CLIP_MIN_SCORE', 0.58),
    ],

    /*
    | Max results returned to the storefront.
    */
    'limit' => (int) env('IMAGE_SEARCH_LIMIT', 24),

    /*
    | Minimum combined similarity score (0–1) to include a product.
    */
    'min_score' => (float) env('IMAGE_SEARCH_MIN_SCORE', 0.45),

    /*
    | Weighting for local driver: structure (dHash) vs color histogram.
    */
    'local' => [
        'structure_weight' => 0.55,
        'color_weight' => 0.45,
    ],
];
