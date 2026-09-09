export function formatPrice(value) {
    const num = Number(value ?? 0);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function aedToSypRate() {
    return Number(window.WASLA_AED_TO_SYP || 14500);
}

function feeSyp(kind) {
    if (kind === 'accessory') {
        return Number(window.WASLA_ACCESSORY_FEE_AED ?? 50);
    }
    if (kind === 'product') {
        return Number(window.WASLA_PRODUCT_FEE_AED ?? 75);
    }
    return 0;
}

/** Customer SYP: (dirham × rate) + fee. Fee is 0 when kind is 'raw'. */
export function aedToSypAmount(aed, kind = 'raw') {
    return Math.round(Number(aed || 0) * aedToSypRate()) + feeSyp(kind);
}

export function formatSyp(value) {
    const num = Math.round(Number(value ?? 0));
    return `${num.toLocaleString('en-US')} ل.س`;
}

/** Customer-facing: pass 'product' or 'accessory' to include Wasla fee. */
export function money(value, kind = 'raw') {
    return formatSyp(aedToSypAmount(value, kind));
}

export function moneyLine(unitAed, quantity = 1, kind = 'raw') {
    return formatSyp(aedToSypAmount(unitAed, kind) * Number(quantity || 1));
}

/** Admin / procurement display in AED. */
export function moneyAed(value) {
    return `${formatPrice(value)} د.إ`;
}

export function effectivePrice(entity) {
    if (!entity) return 0;
    return entity.sale_price ? Number(entity.sale_price) : Number(entity.price);
}

export function hasDiscount(entity) {
    return !!entity?.sale_price && Number(entity.sale_price) < Number(entity.price);
}

export function sizeRank(label) {
  const raw = String(label || '').trim();
  const key = raw.replace(/\s+/g, '').toUpperCase();
  const letters = {
    XXXXS: 1, '3XS': 1, XXS: 2, '2XS': 2, XS: 3,
    S: 4, M: 5, L: 6, XL: 7, XXL: 8, '2XL': 8,
    XXXL: 9, '3XL': 9, '4XL': 10, '5XL': 11,
    ONESIZE: 50, OS: 50, FREE: 50,
  };
  if (letters[key] != null) return [0, letters[key], 0];

  const range = raw.match(/(\d+)\s*[-–/]\s*(\d+)\s*(Y|YEARS?|سنة|سنوات|M|MO|MONTHS?|شهر|شهور)?/i);
  if (range) {
    const unit = String(range[3] || 'M').toUpperCase();
    const year = unit.startsWith('Y') || unit.includes('سنة');
    const mult = year ? 12 : 1;
    return [1, Number(range[1]) * mult, Number(range[2]) * mult];
  }

  const num = raw.match(/^(\d+(?:\.\d+)?)/);
  if (num) return [2, Math.round(Number(num[1]) * 10), 0];
  return [9, 0, 0];
}

export function sortSizes(items, nameOf = (item) => item?.name || item?.key || item) {
  return [...items].sort((a, b) => {
    const left = sizeRank(nameOf(a));
    const right = sizeRank(nameOf(b));
    for (let i = 0; i < left.length; i++) {
      if (left[i] !== right[i]) return left[i] - right[i];
    }
    return String(nameOf(a)).localeCompare(String(nameOf(b)), 'ar');
  });
}

/** Hidden in storefront UI — used internally for color-only products (bags). */
export function isPlaceholderSize(label) {
  const normalized = String(label || '').trim().toLowerCase();
  return ['os', 'one size', 'one-size', 'onesize', 'default', 'free size', 'free-size', 'مقاس واحد', '—', '-'].includes(normalized);
}

export function loginUrl(redirectTo) {
    const target = redirectTo || (window.location.pathname + window.location.search);
    return `/login?redirect=${encodeURIComponent(target)}`;
}
