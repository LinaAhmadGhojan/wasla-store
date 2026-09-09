/**
 * Builds a real, working search-results URL on the retailer's own live site.
 * Mirrors app/Support/ExternalSearchUrl.php — keep both in sync.
 */
export function buildPlatformSearchUrl(platformSlug, query) {
  const q = encodeURIComponent((query || '').trim() || platformSlug || '');

  switch (platformSlug) {
    case 'shein':
      return `https://ar.shein.com/pdsearch/${q}/`;
    case 'amazon':
      return `https://www.amazon.ae/s?k=${q}`;
    case 'noon':
      return `https://www.noon.com/uae-en/search/?q=${q}`;
    case 'trendyol':
      return `https://www.trendyol.com/sr?q=${q}`;
    case 'temu':
      return `https://www.temu.com/search_result.html?search_key=${q}`;
    default:
      return `https://www.google.com/search?q=${q}`;
  }
}
