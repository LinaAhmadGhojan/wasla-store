<?php

namespace App\Services\Express;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Separate versioned cache for Wasla Express — never shares keys with fashion catalog.
 */
class ExpressCache
{
    public const VERSION_KEY = 'express:content_version';

    public const TTL_BROWSE = 60;

    public const TTL_FACETS = 90;

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    public static function remember(string $bucket, array $params, int $ttl, Closure $callback): mixed
    {
        ksort($params);
        $key = sprintf(
            'express:v%d:%s:%s',
            self::version(),
            $bucket,
            md5((string) json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        );

        return Cache::remember($key, $ttl, $callback);
    }
}
