/** Storefront PDP path — use singular /product/ to avoid clashing with public/products/{id}/ image dirs. */
export function productUrl(id) {
  const n = Number(id);
  if (!Number.isFinite(n) || n <= 0) return '/';
  return `/product/${n}`;
}
