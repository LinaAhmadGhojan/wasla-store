<?php

namespace App\Services\ImageSearch;

use Illuminate\Support\Str;

class ImagePathResolver
{
    /**
     * Resolve a stored path/URL to an absolute filesystem path, or null.
     */
    public function resolveAbsolute(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        // Remote URLs are not indexed by the local driver.
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        $candidates = [
            public_path($normalized),
            public_path(ltrim(parse_url($normalized, PHP_URL_PATH) ?: $normalized, '/')),
            base_path($normalized),
            storage_path('app/public/'.$normalized),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Collect indexable image paths for a product (relative web paths preferred).
     *
     * @return list<string>
     */
    public function collectProductImagePaths($product): array
    {
        $paths = [];

        if (! empty($product->image)) {
            $paths[] = (string) $product->image;
        }

        if ($product->relationLoaded('images') || method_exists($product, 'images')) {
            foreach ($product->images ?? [] as $image) {
                if (! empty($image->path)) {
                    $paths[] = (string) $image->path;
                }
            }
        }

        $unique = [];
        foreach ($paths as $p) {
            $p = ltrim(str_replace('\\', '/', $p), '/');
            if ($p !== '' && ! isset($unique[$p]) && $this->resolveAbsolute($p)) {
                $unique[$p] = true;
            }
        }

        return array_keys($unique);
    }
}
