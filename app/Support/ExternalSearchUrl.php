<?php

namespace App\Support;

/**
 * Builds a real, working search-results URL on the retailer's own live site.
 *
 * Wasla has no free public product API for these platforms, so instead of
 * fabricating fake product links (which 404), we send shoppers to the
 * platform's own real search page — genuine data, genuine links.
 */
class ExternalSearchUrl
{
    public static function build(string $platformSlug, string $query): string
    {
        $q = rawurlencode(trim($query) !== '' ? trim($query) : $platformSlug);

        return match ($platformSlug) {
            'shein' => "https://ar.shein.com/pdsearch/{$q}/",
            'amazon' => "https://www.amazon.ae/s?k={$q}",
            'noon' => "https://www.noon.com/uae-en/search/?q={$q}",
            'trendyol' => "https://www.trendyol.com/sr?q={$q}",
            'temu' => "https://www.temu.com/search_result.html?search_key={$q}",
            default => "https://www.google.com/search?q={$q}",
        };
    }
}
