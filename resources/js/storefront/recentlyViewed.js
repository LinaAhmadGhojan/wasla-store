const KEY = 'wasla_recently_viewed';
const MAX = 16;

export function trackProductView(product) {
  if (!product?.id) return;
  const id = Number(product.id);
  const list = getRecentlyViewedIds().filter((x) => x !== id);
  list.unshift(id);
  try {
    localStorage.setItem(KEY, JSON.stringify(list.slice(0, MAX)));
  } catch (_) {
    /* ignore quota */
  }
}

export function getRecentlyViewedIds() {
  try {
    const raw = JSON.parse(localStorage.getItem(KEY) || '[]');
    if (!Array.isArray(raw)) return [];
    return raw.map(Number).filter((n) => n > 0);
  } catch (_) {
    return [];
  }
}
