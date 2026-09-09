<template>
  <div class="cart-page" dir="rtl">
    <StorefrontNav />

    <div class="cart-shell">
      <h1>السلة</h1>

      <div v-if="alerts.length" class="alerts">
        <div v-for="(a, i) in alerts" :key="i" class="alert" :class="a.type">{{ a.message }}</div>
      </div>

      <div v-if="loading" class="loading-row">جاري تحميل السلة…</div>
      <div v-else-if="!allItems.length && !savedItems.length" class="empty-state">
        <p>سلتك فارغة.</p>
        <a href="/shop" class="btn btn-primary">تصفّح المنتجات</a>
      </div>
      <div v-else class="cart-grid">
        <div class="cart-main">
          <section v-if="allItems.length" class="cart-block">
            <h2>المنتجات الحالية</h2>
            <div v-for="(group, vendorName) in groupedItems" :key="vendorName" class="vendor-group">
              <h3 class="vendor-title">{{ vendorName }}</h3>
              <div v-for="item in group" :key="item.key" class="cart-row" :class="{ warn: item.outOfStock || item.priceChanged || item.lowStock }">
                <div class="cart-row-image">
                  <img v-if="item.image" :src="item.image" :alt="item.name" />
                  <span v-else>{{ item.placeholder }}</span>
                </div>
                <div class="cart-row-info">
                  <a v-if="item.href" :href="item.href" class="cart-row-name">{{ item.name }}</a>
                  <span v-else class="cart-row-name">{{ item.name }}</span>
                  <span v-if="item.meta" class="cart-row-variant">{{ item.meta }}</span>
                  <span class="cart-row-unit">{{ money(item.unitPrice, item.pricingKind) }} للقطعة</span>
                  <p v-if="item.outOfStock" class="chip danger">نفد من المخزون</p>
                  <p v-else-if="item.lowStock" class="chip warn">أوشك على النفاد</p>
                  <p v-if="item.priceChanged" class="chip info">تغير السعر</p>
                  <div v-if="item.variants?.length" class="variant-pick">
                    <label>المقاس / الخيار</label>
                    <select
                      :value="item.raw.product_variant_id || ''"
                      :disabled="item.updating"
                      @change="changeVariant(item, $event.target.value)"
                    >
                      <option value="">اختر</option>
                      <option v-for="v in item.variants" :key="v.id" :value="v.id" :disabled="v.stock_qty <= 0">
                        {{ variantLabel(v) }}{{ v.stock_qty <= 0 ? ' (نفد)' : '' }}
                      </option>
                    </select>
                  </div>
                </div>
                <div class="cart-row-qty">
                  <div class="stepper">
                    <button type="button" @click="changeQuantity(item, item.quantity - 1)" :disabled="item.quantity <= 1 || item.updating">&minus;</button>
                    <span>{{ item.quantity }}</span>
                    <button type="button" @click="changeQuantity(item, item.quantity + 1)" :disabled="item.updating || item.outOfStock">&plus;</button>
                  </div>
                </div>
                <div class="cart-row-total">{{ moneyLine(item.unitPrice, item.quantity, item.pricingKind) }}</div>
                <div class="cart-row-actions">
                  <button type="button" class="linkish" :disabled="item.updating" @click="saveForLater(item)">حفظ للاحقاً</button>
                  <button type="button" class="remove-btn" :disabled="item.updating" @click="removeItem(item)">حذف</button>
                </div>
              </div>
            </div>
          </section>

          <section v-if="savedItems.length" class="cart-block saved">
            <h2>حفظ للاحقاً</h2>
            <div v-for="item in savedItems" :key="item.key" class="cart-row saved-row">
              <div class="cart-row-image">
                <img v-if="item.image" :src="item.image" :alt="item.name" />
                <span v-else>{{ item.placeholder }}</span>
              </div>
              <div class="cart-row-info">
                <a v-if="item.href" :href="item.href" class="cart-row-name">{{ item.name }}</a>
                <span v-else class="cart-row-name">{{ item.name }}</span>
                <span v-if="item.meta" class="cart-row-variant">{{ item.meta }}</span>
                <span class="cart-row-unit">{{ money(item.unitPrice, item.pricingKind) }}</span>
              </div>
              <div class="cart-row-actions">
                <button type="button" class="btn btn-secondary sm" :disabled="item.updating" @click="moveToCart(item)">أرجع للسلة</button>
                <button type="button" class="remove-btn" :disabled="item.updating" @click="removeItem(item)">حذف</button>
              </div>
            </div>
          </section>
        </div>

        <aside class="order-summary">
          <h2>ملخص الطلب</h2>
          <div class="coupon-box">
            <label>كوبون الخصم</label>
            <div class="coupon-row">
              <input v-model="couponCode" type="text" placeholder="WASLA10" dir="ltr" :disabled="!!appliedCoupon" />
              <button v-if="!appliedCoupon" type="button" class="btn btn-secondary sm" :disabled="couponLoading" @click="applyCoupon()">تطبيق</button>
              <button v-else type="button" class="btn btn-ghost sm" @click="clearCoupon">إزالة</button>
            </div>
            <p v-if="couponMsg" class="coupon-msg" :class="{ err: couponError }">{{ couponMsg }}</p>
          </div>
          <div class="summary-row">
            <span>المجموع الفرعي</span>
            <span>{{ cartTotalSyp }}</span>
          </div>
          <div v-if="appliedCoupon" class="summary-row discount">
            <span>خصم ({{ appliedCoupon.code }})</span>
            <span>−{{ formatSyp(appliedCoupon.discount_syp) }}</span>
          </div>
          <div class="summary-row">
            <span>الشحن</span>
            <span>يُحسب عند الدفع</span>
          </div>
          <div class="summary-row total-row">
            <span>الإجمالي</span>
            <span>{{ estimatedTotalSyp }}</span>
          </div>
          <a
            v-if="allItems.length"
            href="/checkout"
            class="btn btn-primary checkout-btn"
            :class="{ disabled: hasBlockingAlerts }"
            @click="guardCheckout"
          >متابعة للدفع</a>
          <p v-if="hasBlockingAlerts" class="login-hint">أزيلي المنتجات النافدة قبل إتمام الطلب.</p>
          <p class="login-hint" v-else-if="!store.authToken">تسجيل الدخول مطلوب فقط عند الدفع.</p>
        </aside>
      </div>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../storefront/api';
import {
  store,
  refreshCartCount,
  updateGuestItemQuantity,
  removeGuestItem,
  getGuestCart,
  saveGuestItemForLater,
  moveGuestItemToCart,
} from '../../storefront/store';
import { money, moneyLine, formatSyp, aedToSypAmount } from '../../storefront/format';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const serverItems = ref([]);
const savedServerItems = ref([]);
const guestItems = ref([]);
const alerts = ref([]);
const loading = ref(true);
const couponCode = ref(sessionStorage.getItem('wasla_coupon') || '');
const appliedCoupon = ref(null);
const couponLoading = ref(false);
const couponMsg = ref('');
const couponError = ref(false);

const placeholders = ['👗', '👕', '👟', '👜', '💄', '🏠', '📱', '🧥', '👖', '💍'];

function unitPriceServer(item) {
  if (item.unit_price_now != null) return Number(item.unit_price_now);
  const variant = item.variant;
  const product = item.product;
  if (variant) return variant.sale_price ? Number(variant.sale_price) : Number(variant.price);
  if (product) return product.sale_price ? Number(product.sale_price) : Number(product.price);
  return 0;
}

function variantLabel(v) {
  if (v.metadata?.color_name || v.metadata?.size) {
    return [v.metadata.color_name, v.metadata.size || v.option_value].filter(Boolean).join(' · ');
  }
  return `${v.option_name}: ${v.option_value}`;
}

function mapServer(item) {
  return {
    key: `server-${item.id}`,
    source: 'server',
    raw: item,
    name: item.product?.name || 'منتج',
    image: item.product?.image || null,
    href: `/product/${item.product_id}`,
    meta: item.variant
      ? (item.variant.metadata?.color_name
        ? `${item.variant.metadata.color_name} · ${item.variant.metadata.size || item.variant.option_value}`
        : `${item.variant.option_name}: ${item.variant.option_value}`)
      : null,
    unitPrice: unitPriceServer(item),
    pricingKind: item.product?.pricing_kind || 'product',
    quantity: item.quantity,
    updating: !!item.updating,
    group: item.product?.vendor?.store_name || 'Wasla Store',
    placeholder: placeholders[(item.product_id || 0) % placeholders.length],
    variants: item.product?.variants || [],
    outOfStock: !!item.out_of_stock,
    lowStock: !!item.low_stock,
    priceChanged: !!item.price_changed,
  };
}

function mapGuest(item) {
  return {
    key: item.id,
    source: 'guest',
    raw: item,
    name: item.type === 'external' ? item.product_name : (item.product?.name || 'منتج'),
    image: item.type === 'external' ? item.image : (item.product?.image || null),
    href: item.type === 'local' ? `/product/${item.product_id}` : null,
    meta: item.type === 'external'
      ? [item.color_name, item.size_name, item.sku ? `SKU ${item.sku}` : null].filter(Boolean).join(' · ')
        || `خارجي · ${item.platform?.name || 'Store'}`
      : (item.variant
        ? (item.variant.metadata?.color_name
          ? `${item.variant.metadata.color_name} · ${item.variant.metadata.size || item.variant.option_value}`
          : `${item.variant.option_name}: ${item.variant.option_value}`)
        : 'سلة زائر'),
    unitPrice: Number(item.unit_price || 0),
    pricingKind: item.type === 'external' ? 'raw' : (item.product?.pricing_kind || 'product'),
    quantity: item.quantity,
    updating: false,
    group: item.type === 'external' ? `🌐 ${item.platform?.name || 'خارجي'}` : (item.product?.vendor?.store_name || 'Wasla Store'),
    placeholder: '🛍️',
    variants: item.product?.variants || [],
    outOfStock: false,
    lowStock: false,
    priceChanged: false,
  };
}

const allItems = computed(() => {
  const local = serverItems.value.map(mapServer);
  const guest = guestItems.value.filter((i) => !i.saved_for_later).map(mapGuest);
  return [...local, ...guest];
});

const savedItems = computed(() => {
  const local = savedServerItems.value.map(mapServer);
  const guest = guestItems.value.filter((i) => i.saved_for_later).map(mapGuest);
  return [...local, ...guest];
});

const groupedItems = computed(() => {
  const groups = {};
  for (const item of allItems.value) {
    if (!groups[item.group]) groups[item.group] = [];
    groups[item.group].push(item);
  }
  return groups;
});

const cartSubtotalAed = computed(() =>
  allItems.value.reduce((sum, item) => sum + item.unitPrice * item.quantity, 0)
);

const cartTotalSypAmount = computed(() =>
  allItems.value.reduce(
    (sum, item) => sum + aedToSypAmount(item.unitPrice, item.pricingKind) * item.quantity,
    0
  )
);

const cartTotalSyp = computed(() => formatSyp(cartTotalSypAmount.value));

const estimatedTotalSyp = computed(() => {
  const disc = appliedCoupon.value?.discount_syp || 0;
  return formatSyp(Math.max(0, cartTotalSypAmount.value - disc));
});

const hasBlockingAlerts = computed(() => allItems.value.some((i) => i.outOfStock));

async function loadCart() {
  loading.value = true;
  guestItems.value = getGuestCart();
  alerts.value = [];

  if (store.authToken) {
    try {
      const { data } = await api.get('/cart');
      if (Array.isArray(data)) {
        serverItems.value = data.map((item) => ({ ...item, updating: false }));
        savedServerItems.value = [];
      } else {
        serverItems.value = (data.items || []).map((item) => ({ ...item, updating: false }));
        savedServerItems.value = (data.saved_for_later || []).map((item) => ({ ...item, updating: false }));
        alerts.value = data.meta?.alerts || [];
      }
    } catch (e) {
      serverItems.value = [];
      savedServerItems.value = [];
    }
  } else {
    serverItems.value = [];
    savedServerItems.value = [];
  }

  loading.value = false;
  await refreshCartCount();
  if (couponCode.value && !appliedCoupon.value) {
    await applyCoupon(true);
  }
}

async function changeQuantity(item, newQty) {
  if (newQty < 1) return;

  if (item.source === 'guest') {
    updateGuestItemQuantity(item.raw.id, newQty);
    guestItems.value = getGuestCart();
    await refreshCartCount();
    return;
  }

  item.raw.updating = true;
  try {
    await api.patch(`/cart/items/${item.raw.id}`, { quantity: newQty });
    item.raw.quantity = newQty;
    await loadCart();
  } finally {
    item.raw.updating = false;
  }
}

async function changeVariant(item, variantId) {
  if (item.source !== 'server') return;
  item.raw.updating = true;
  try {
    await api.patch(`/cart/items/${item.raw.id}`, {
      variant_id: variantId ? Number(variantId) : null,
    });
    await loadCart();
  } finally {
    item.raw.updating = false;
  }
}

async function removeItem(item) {
  if (item.source === 'guest') {
    removeGuestItem(item.raw.id);
    guestItems.value = getGuestCart();
    await refreshCartCount();
    return;
  }

  item.raw.updating = true;
  try {
    await api.delete(`/cart/items/${item.raw.id}`);
    await loadCart();
  } catch (e) {
    item.raw.updating = false;
  }
}

async function saveForLater(item) {
  if (item.source === 'guest') {
    saveGuestItemForLater(item.raw.id);
    guestItems.value = getGuestCart();
    await refreshCartCount();
    return;
  }
  item.raw.updating = true;
  try {
    await api.post(`/cart/items/${item.raw.id}/save-for-later`);
    await loadCart();
  } catch (e) {
    item.raw.updating = false;
  }
}

async function moveToCart(item) {
  if (item.source === 'guest') {
    moveGuestItemToCart(item.raw.id);
    guestItems.value = getGuestCart();
    await refreshCartCount();
    return;
  }
  item.raw.updating = true;
  try {
    await api.post(`/cart/items/${item.raw.id}/move-to-cart`);
    await loadCart();
  } catch (e) {
    item.raw.updating = false;
  }
}

async function applyCoupon(silent = false) {
  const code = couponCode.value.trim();
  if (!code) return;
  couponLoading.value = true;
  couponError.value = false;
  couponMsg.value = silent ? '' : 'جاري التحقق…';
  try {
    const { data } = await api.get('/v1/coupons/preview', {
      params: { code, subtotal: cartSubtotalAed.value || 1 },
    });
    if (!data.ok && !data.valid) {
      appliedCoupon.value = null;
      sessionStorage.removeItem('wasla_coupon');
      couponError.value = true;
      couponMsg.value = data.message || 'الكوبون غير صالح.';
      return;
    }
    const discountAed = Number(data.discount || 0);
    appliedCoupon.value = {
      code: data.code || code.toUpperCase(),
      discount: discountAed,
      discount_syp: aedToSypAmount(discountAed, 'product'),
    };
    sessionStorage.setItem('wasla_coupon', appliedCoupon.value.code);
    couponCode.value = appliedCoupon.value.code;
    couponMsg.value = data.name ? `تم تطبيق: ${data.name}` : 'تم تطبيق الكوبون.';
  } catch (e) {
    couponError.value = true;
    const errs = e?.response?.data?.errors;
    couponMsg.value = errs
      ? Object.values(errs).flat().join(' ')
      : (e?.response?.data?.message || 'تعذّر التحقق من الكوبون.');
    appliedCoupon.value = null;
  } finally {
    couponLoading.value = false;
  }
}

function clearCoupon() {
  appliedCoupon.value = null;
  couponCode.value = '';
  couponMsg.value = '';
  couponError.value = false;
  sessionStorage.removeItem('wasla_coupon');
}

function guardCheckout(e) {
  if (hasBlockingAlerts.value) {
    e.preventDefault();
  }
}

onMounted(loadCart);
</script>

<style scoped>
.cart-page { color: #132f37; background: linear-gradient(180deg, #eaf6f8 0%, #f7fbfc 50%, #fff 100%); min-height: 100vh; }
.cart-shell { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem; }
.cart-shell h1 { margin: 0 0 1.25rem; color: #0b3d44; }
.alerts { display: flex; flex-direction: column; gap: .45rem; margin-bottom: 1rem; }
.alert {
  padding: .7rem .9rem; border-radius: .75rem; font-size: .88rem; font-weight: 700;
  background: #fff7e8; color: #8a5a00; border: 1px solid rgba(180,120,20,.2);
}
.alert.out_of_stock { background: #fdeeee; color: #a82626; }
.alert.price_changed { background: #eef6ff; color: #1a4d8c; }
.loading-row, .empty-state { padding: 4rem 0; text-align: center; color: #4d6b72; }
.empty-state a { display: inline-block; margin-top: 1rem; }
.cart-grid { display: grid; grid-template-columns: 1fr 320px; gap: 2rem; align-items: start; }
.cart-block { margin-bottom: 1.5rem; }
.cart-block h2 { margin: 0 0 .85rem; font-size: 1.05rem; color: #0b3d44; }
.vendor-group {
  background: white;
  border-radius: 1.25rem;
  border: 1px solid rgba(15, 90, 107, 0.08);
  padding: 1.25rem;
  margin-bottom: 1.25rem;
}
.vendor-title { margin: 0 0 1rem; font-size: 0.95rem; color: #1c7282; }
.cart-row {
  display: grid;
  grid-template-columns: 3.5rem 1fr auto auto auto;
  gap: 1rem;
  align-items: center;
  padding: 0.85rem 0;
  border-top: 1px solid #eef4f5;
}
.cart-row.warn { background: #fffaf5; margin: 0 -.5rem; padding-inline: .5rem; border-radius: .5rem; }
.cart-row:first-of-type { border-top: none; }
.saved-row { grid-template-columns: 3.5rem 1fr auto; }
.cart-row-image {
  width: 3.5rem; height: 3.5rem; border-radius: 0.75rem;
  background: linear-gradient(135deg, #eef9fa, #f7fcfd);
  display: flex; align-items: center; justify-content: center; font-size: 1.5rem; overflow: hidden;
}
.cart-row-image img { width: 100%; height: 100%; object-fit: cover; }
.cart-row-info { display: flex; flex-direction: column; gap: 0.2rem; }
.cart-row-name { color: #132f37; font-weight: 700; text-decoration: none; font-size: 0.9rem; }
.cart-row-variant { font-size: 0.78rem; color: #4d6b72; word-break: break-all; }
.cart-row-unit { font-size: 0.78rem; color: #9aabaf; }
.chip { margin: .15rem 0 0; font-size: .75rem; font-weight: 800; }
.chip.danger { color: #a82626; }
.chip.warn { color: #b06a00; }
.chip.info { color: #1a4d8c; }
.variant-pick { margin-top: .35rem; }
.variant-pick label { display: block; font-size: .75rem; color: #4d6b72; margin-bottom: .2rem; }
.variant-pick select {
  border: 1.5px solid rgba(28,114,130,.22); border-radius: .55rem; padding: .35rem .5rem; max-width: 100%;
}
.stepper { display: inline-flex; align-items: center; border: 1.5px solid rgba(15, 90, 107, 0.2); border-radius: 0.6rem; overflow: hidden; }
.stepper button { width: 2rem; height: 2rem; border: none; background: #f5fbfc; cursor: pointer; }
.stepper button:disabled { opacity: 0.4; cursor: not-allowed; }
.stepper span { min-width: 2rem; text-align: center; font-weight: 700; font-size: 0.85rem; }
.cart-row-total { font-weight: 800; color: #1c7282; white-space: nowrap; font-size: 0.9rem; }
.cart-row-actions { display: flex; flex-direction: column; gap: .35rem; align-items: flex-end; }
.linkish { border: 0; background: transparent; color: #1c7282; font-weight: 700; cursor: pointer; font-size: .8rem; }
.remove-btn { border: none; background: transparent; color: #a82626; cursor: pointer; font-size: .82rem; font-weight: 700; }
.order-summary {
  background: white; border-radius: 1.25rem; border: 1px solid rgba(15, 90, 107, 0.08);
  padding: 1.5rem; position: sticky; top: 5.5rem;
}
.order-summary h2 { margin-top: 0; font-size: 1.1rem; }
.coupon-box { margin-bottom: 1rem; }
.coupon-box label { display: block; font-size: .85rem; font-weight: 700; margin-bottom: .35rem; }
.coupon-row { display: flex; gap: .4rem; }
.coupon-row input {
  flex: 1; border: 1.5px solid rgba(28,114,130,.22); border-radius: .65rem; padding: .5rem .65rem;
}
.coupon-msg { margin: .4rem 0 0; font-size: .8rem; color: #c62828; }
.coupon-msg.err { color: #a82626; }
.summary-row { display: flex; justify-content: space-between; font-size: 0.9rem; color: #4d6b72; margin-bottom: 0.75rem; }
.summary-row.discount { color: #c62828; font-weight: 700; }
.total-row { font-weight: 800; color: #132f37; font-size: 1.1rem; border-top: 1px solid #eef4f5; padding-top: 0.75rem; }
.checkout-btn { width: 100%; margin-top: 1rem; padding: 0.9rem; display: block; text-align: center; text-decoration: none; background: #1c7282; color: white; border-radius: 0.85rem; font-weight: 700; }
.checkout-btn.disabled { opacity: .5; pointer-events: none; }
.login-hint { margin: 0.75rem 0 0; font-size: 0.8rem; color: #4d6b72; text-align: center; }
.btn-primary { background: #1c7282; color: white; padding: 0.85rem 1.25rem; border-radius: 0.85rem; text-decoration: none; font-weight: 700; border: 0; cursor: pointer; }
.btn-secondary { background: #e8f2f4; color: #0b3d44; border: 0; border-radius: .65rem; padding: .5rem .75rem; font-weight: 800; cursor: pointer; }
.btn-ghost { background: transparent; border: 1.5px solid rgba(28,114,130,.25); color: #1c7282; border-radius: .65rem; padding: .45rem .7rem; font-weight: 800; cursor: pointer; }
.sm { font-size: .82rem; padding: .4rem .65rem; }
@media (max-width: 860px) {
  .cart-grid { grid-template-columns: 1fr; }
  .cart-row { grid-template-columns: 3rem 1fr; }
  .cart-row-qty, .cart-row-total, .cart-row-actions { grid-column: 2; }
}
</style>
