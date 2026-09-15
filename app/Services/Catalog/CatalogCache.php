<?php

namespace App\Services\Catalog;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned catalog cache — works with file/redis without needing cache tags.
 * Bumping the version instantly invalidates all catalog payloads.
 */
class CatalogCache
{
    public const VERSION_KEY = 'catalog:content_version';

    public const TTL_BROWSE = 60;

    public const TTL_FACETS = 90;

    public const TTL_SUGGESTIONS = 45;

    public const TTL_TRENDING = 120;

    public const TTL_SIMILAR = 180;

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bump(): void
    {
        $next = self::version() + 1;
        Cache::forever(self::VERSION_KEY, $next);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function remember(string $bucket, array $params, int $ttl, Closure $callback): mixed
    {
        $key = self::key($bucket, $params);

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function key(string $bucket, array $params = []): string
    {
        ksort($params);

        return sprintf(
            'catalog:v%d:%s:%s',
            self::version(),
            $bucket,
            md5((string) json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        );
    }
}
