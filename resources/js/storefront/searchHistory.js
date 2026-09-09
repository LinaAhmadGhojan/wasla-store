/** Recent searches — localStorage */
const KEY = 'wasla_recent_searches';
const MAX = 12;

export function getRecentSearches() {
  try {
    const raw = JSON.parse(localStorage.getItem(KEY) || '[]');
    return Array.isArray(raw) ? raw.filter((x) => typeof x === 'string' && x.trim()) : [];
  } catch {
    return [];
  }
}

export function pushRecentSearch(q) {
  const term = String(q || '').trim();
  if (term.length < 2) return getRecentSearches();
  const next = [term, ...getRecentSearches().filter((x) => x.toLowerCase() !== term.toLowerCase())].slice(0, MAX);
  localStorage.setItem(KEY, JSON.stringify(next));
  return next;
}

export function removeRecentSearch(q) {
  const term = String(q || '').trim().toLowerCase();
  const next = getRecentSearches().filter((x) => x.toLowerCase() !== term);
  localStorage.setItem(KEY, JSON.stringify(next));
  return next;
}

export function clearRecentSearches() {
  localStorage.removeItem(KEY);
  return [];
}
