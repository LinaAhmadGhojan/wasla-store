/**
 * Guest cart stored in localStorage.
 * Supports local Wasla products and external (SHEIN/etc.) preview quotes.
 * Login is NOT required to add items — only at checkout.
 */

const GUEST_CART_KEY = 'wasla_guest_cart';

function readGuestCart() {
  try {
    return JSON.parse(localStorage.getItem(GUEST_CART_KEY) || '[]');
  } catch (e) {
    return [];
  }
}

function writeGuestCart(items) {
  localStorage.setItem(GUEST_CART_KEY, JSON.stringify(items));
  return items;
}

export function getGuestCart() {
  return readGuestCart();
}

export function guestCartCount() {
  return readGuestCart()
    .filter((item) => !item.saved_for_later)
    .reduce((sum, item) => sum + Number(item.quantity || 0), 0);
}

export function addLocalGuestItem({ product, variant = null, quantity = 1 }) {
  const items = readGuestCart();
  const productId = product.id;
  const variantId = variant?.id || null;
  const existing = items.find(
    (i) => i.type === 'local' && i.product_id === productId && (i.variant_id || null) === variantId
  );

  if (existing) {
    existing.quantity = Number(existing.quantity) + Number(quantity);
  } else {
    items.push({
      id: `local-${productId}-${variantId || 0}-${Date.now()}`,
      type: 'local',
      product_id: productId,
      variant_id: variantId,
      quantity: Number(quantity),
      product,
      variant,
      unit_price: Number(variant?.sale_price || variant?.price || product.sale_price || product.price || 0),
    });
  }

  return writeGuestCart(items);
}

export function addExternalGuestItem({ preview, quantity = 1 }) {
  const items = readGuestCart();
  const url = preview.product?.external_url;
  const selected = preview.product?.selected || {};
  const sku = selected.sku || '';
  const existing = items.find(
    (i) => i.type === 'external' && i.external_url === url && (i.sku || '') === sku
  );

  if (existing) {
    existing.quantity = Number(existing.quantity) + Number(quantity);
  } else {
    const colorName = selected.color_name || selected.color || '';
    const sizeName = selected.size_name || selected.size || '';
    items.push({
      id: `external-${Date.now()}`,
      type: 'external',
      quantity: Number(quantity),
      external_url: url,
      external_product_id: preview.product?.external_product_id || null,
      platform: preview.platform,
      product_name: preview.product?.name,
      image: preview.product?.image,
      quote: preview.quote,
      unit_price: Number(preview.quote?.total || 0),
      sku,
      color: selected.color || null,
      color_name: colorName,
      size: selected.size || null,
      size_name: sizeName,
      goods_id: preview.product?.goods_id || null,
      variant_data: {
        sku,
        color: selected.color || null,
        color_name: colorName,
        size: selected.size || null,
        size_name: sizeName,
        goods_id: preview.product?.goods_id || null,
        image: preview.product?.image || null,
      },
    });
  }

  return writeGuestCart(items);
}

export function updateGuestItemQuantity(id, quantity) {
  const items = readGuestCart().map((item) => {
    if (item.id === id) return { ...item, quantity: Number(quantity) };
    return item;
  }).filter((item) => item.quantity > 0);

  return writeGuestCart(items);
}

export function removeGuestItem(id) {
  return writeGuestCart(readGuestCart().filter((item) => item.id !== id));
}

export function saveGuestItemForLater(id) {
  return writeGuestCart(
    readGuestCart().map((item) => (item.id === id ? { ...item, saved_for_later: true } : item))
  );
}

export function moveGuestItemToCart(id) {
  return writeGuestCart(
    readGuestCart().map((item) => (item.id === id ? { ...item, saved_for_later: false } : item))
  );
}

export function clearGuestCart() {
  return writeGuestCart([]);
}

export function clearGuestLocalItems() {
  return writeGuestCart(readGuestCart().filter((i) => i.type !== 'local'));
}

export function clearGuestExternalItems() {
  return writeGuestCart(readGuestCart().filter((i) => i.type !== 'external'));
}

export { GUEST_CART_KEY };
