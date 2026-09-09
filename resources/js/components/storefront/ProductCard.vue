<template>
  <div class="product-card">
    <a :href="`/products/${product.id}`" class="product-image-wrap">
      <img
        v-if="product.image"
        :src="product.image"
        :alt="product.name"
        loading="lazy"
        @error="onImgError"
      />
      <span v-else class="img-placeholder">{{ imagePlaceholder }}</span>
      <span v-if="hasDiscount(product)" class="badge-sale">خصم</span>
      <span v-if="product.is_flash_sale" class="badge-flash">Flash</span>
    </a>
    <div class="product-body">
      <h3 class="product-name">{{ product.name }}</h3>
      <div class="product-price">
        <span class="price-now">{{ money(effectivePrice(product), product.pricing_kind || 'product') }}</span>
        <span v-if="hasDiscount(product)" class="price-old">{{ money(product.price, product.pricing_kind || 'product') }}</span>
      </div>
      <div class="card-actions">
        <button class="btn-wishlist" :class="{ wished: wished }" @click.prevent="toggleWish" title="حفظ">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 21C12 21 3 14.5 3 8.5C3 5.42 5.42 3 8.5 3C10.24 3 11.91 3.81 13 5.08C14.09 3.81 15.76 3 17.5 3C20.58 3 23 5.42 23 8.5C23 14.5 12 21 12 21Z"
              :fill="wished ? '#1c7282' : 'none'"
              stroke="#1c7282"
              stroke-width="1.8"
              stroke-linejoin="round"
            />
          </svg>
        </button>
        <button
          v-if="compareEnabled"
          type="button"
          class="btn-compare"
          :class="{ on: compared }"
          @click.prevent="toggleCompareClick"
          title="مقارنة"
        >{{ compared ? 'بالمقارنة' : 'قارن' }}</button>
        <a :href="`/products/${product.id}`" class="btn-view">عرض</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { formatPrice, effectivePrice, hasDiscount, money } from '../../storefront/format';
import api from '../../storefront/api';
import { store, hydrateUser } from '../../storefront/store';
import { isInCompare, toggleCompare } from '../../storefront/compareTray';

const props = defineProps({
  product: { type: Object, required: true },
  compareEnabled: { type: Boolean, default: false },
});

const imgFailed = ref(false);
const wished = ref(!!props.product?.wished);
const compared = ref(isInCompare(props.product?.id));
const placeholders = ['👗', '👕', '👟', '👜', '💄', '🏠', '📱', '🧥', '👖', '💍'];
const imagePlaceholder = computed(() => {
  const idx = (props.product.id || 0) % placeholders.length;
  return placeholders[idx];
});

function toggleCompareClick() {
  const ids = toggleCompare(props.product.id);
  compared.value = ids.includes(Number(props.product.id));
}

let wishlistIdsCache = null;
let wishlistIdsPromise = null;
async function ensureWishlistIds() {
  if (wishlistIdsCache) return wishlistIdsCache;
  if (wishlistIdsPromise) return wishlistIdsPromise;
  wishlistIdsPromise = (async () => {
    if (!store.authToken) {
      wishlistIdsCache = new Set();
      return wishlistIdsCache;
    }
    const { data } = await api.get('/v1/wishlist/ids');
    wishlistIdsCache = new Set((data.product_ids || []).map(Number));
    return wishlistIdsCache;
  })().finally(() => {
    wishlistIdsPromise = null;
  });
  return wishlistIdsPromise;
}

onMounted(async () => {
  if (props.product?.wished) {
    wished.value = true;
    return;
  }
  try {
    await hydrateUser();
    const ids = await ensureWishlistIds();
    wished.value = ids.has(Number(props.product.id));
  } catch (e) {}
});

async function toggleWish() {
  if (!store.authToken) {
    window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
    return;
  }
  const next = !wished.value;
  wished.value = next;
  try {
    if (next) {
      await api.post('/v1/wishlist', { product_id: props.product.id });
      (await ensureWishlistIds()).add(Number(props.product.id));
    } else {
      await api.delete(`/v1/wishlist/${props.product.id}`);
      (await ensureWishlistIds()).delete(Number(props.product.id));
    }
  } catch (e) {
    wished.value = !next;
  }
}
function onImgError(e) {
  imgFailed.value = true;
  e.target.style.display = 'none';
  if (!e.target.parentElement.querySelector('.img-fallback')) {
    const span = document.createElement('span');
    span.className = 'img-fallback';
    span.textContent = imagePlaceholder.value;
    e.target.parentElement.appendChild(span);
  }
}
</script>

<style scoped>
.product-card {
  display: flex;
  flex-direction: column;
  background: #fff;
  border-radius: 1rem;
  overflow: hidden;
  border: 1px solid rgba(15,90,107,0.07);
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  transition: transform 0.18s, box-shadow 0.18s;
}
.product-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 24px rgba(15,90,107,0.12);
}

/* صورة */
.product-image-wrap {
  position: relative;
  aspect-ratio: 1 / 1;
  display: block;
  overflow: hidden;
  background: #f0f8f9;
}
.product-image-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.3s;
}
.product-card:hover .product-image-wrap img { transform: scale(1.04); }
.img-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  font-size: 3rem;
}
.badge-sale {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background: #e0455a;
  color: #fff;
  font-size: 0.68rem;
  font-weight: 800;
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
}
.badge-flash {
  position: absolute;
  top: 0.5rem;
  left: 0.5rem;
  background: #132f37;
  color: #fff;
  font-size: 0.68rem;
  font-weight: 800;
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
}
.btn-compare {
  background: #fff;
  color: #1c7282;
  border: 1.5px solid #1c7282;
  border-radius: 0.5rem;
  padding: 0.35rem 0.45rem;
  font-weight: 800;
  font-size: 0.72rem;
  cursor: pointer;
}
.btn-compare.on { background: #1c7282; color: #fff; }

/* Body */
.product-body {
  padding: 0.65rem 0.75rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  flex: 1;
}
.product-name {
  margin: 0;
  font-size: 0.82rem;
  font-weight: 600;
  color: #132f37;
  line-height: 1.35;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.product-price {
  display: flex;
  align-items: baseline;
  gap: 0.4rem;
}
.price-now {
  font-weight: 800;
  font-size: 0.9rem;
  color: #1c7282;
}
.price-old {
  font-size: 0.75rem;
  color: #aaa;
  text-decoration: line-through;
}

/* أزرار */
.card-actions {
  display: flex;
  gap: 0.4rem;
  margin-top: 0.35rem;
}
.btn-wishlist {
  background: #f0f8f9;
  border: 1px solid rgba(28,114,130,0.25);
  border-radius: 0.5rem;
  padding: 0.38rem 0.5rem;
  cursor: pointer;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.btn-wishlist svg { width: 1.1rem; height: 1.1rem; display: block; transition: transform 0.15s; }
.btn-wishlist:hover { background: #e0f5f7; }
.btn-wishlist:hover svg { transform: scale(1.15); }
.btn-wishlist.wished { background: #e8f6f8; }
.btn-view {
  flex: 1;
  background: #1c7282;
  color: #fff;
  text-decoration: none;
  text-align: center;
  padding: 0.42rem 0.5rem;
  border-radius: 0.5rem;
  font-size: 0.78rem;
  font-weight: 700;
  transition: background 0.15s;
}
.btn-view:hover { background: #155f6f; }
</style>
