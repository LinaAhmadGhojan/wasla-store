<template>
  <div class="browse-page">
    <StorefrontNav />

    <div class="browse-shell">
      <a v-if="platformMeta" :href="`/browse/${platformSlug}`" class="back">
        ← رجوع لـ {{ platformMeta.name }}
      </a>

      <div v-if="loading" class="loading-row">جاري تحميل المنتج...</div>
      <div v-else-if="error" class="empty-state error">{{ error }}</div>

      <div v-else-if="product" class="detail">
        <div class="gallery">
          <img :src="activeImage" :alt="product.name" class="main-img" />
          <div class="thumbs" v-if="(product.images || []).length > 1">
            <button
              v-for="(img, idx) in product.images"
              :key="idx"
              type="button"
              class="thumb"
              :class="{ active: activeImage === img }"
              @click="activeImage = img"
            >
              <img :src="img" alt="" />
            </button>
          </div>
        </div>

        <div class="info">
          <div class="source" v-if="platformMeta">
            <img v-if="platformMeta.logo" :src="platformMeta.logo" :alt="platformMeta.name" />
            <span>{{ platformMeta.name }}</span>
          </div>
          <h1>{{ product.name_ar || product.name }}</h1>
          <p class="sub-name" v-if="product.name_ar && product.name !== product.name_ar">
            {{ product.name }}
          </p>

          <div v-if="product.is_demo" class="demo-banner">
            ⚠️ هذا مثال تجريبي للتصفح داخل وصلة — وليس منتجًا حقيقيًا موجودًا فعليًا على
            {{ platformMeta?.name || 'المنصة' }}. الاسم والسعر والصورة أدناه للتوضيح فقط.
            للحصول على هذا المنتج (أو أي منتج حقيقي) بدقة: افتحي البحث الحقيقي أدناه، انسخي رابط
            المنتج الفعلي، وألصقيه في <a href="/buy-from-anywhere">صفحة "اطلبي من أي موقع"</a>.
          </div>

          <p class="desc">{{ product.description }}</p>

          <div class="options" v-if="(product.variants || []).length">
            <span class="label">المقاس</span>
            <div class="chips">
              <button
                v-for="(v, i) in product.variants"
                :key="i"
                type="button"
                class="chip"
                :class="{ active: selectedVariant === v.option_value }"
                @click="selectedVariant = v.option_value"
              >
                {{ v.option_value }}
              </button>
            </div>
          </div>

          <div class="quote-box" v-if="quote">
            <div class="quote-row">
              <span>سعر المنصة</span>
              <span>{{ formatPrice(quote.original_price) }} {{ quote.original_currency }}</span>
            </div>
            <div class="quote-row">
              <span>بالدرهم</span>
              <span>{{ money(quote.original_price_aed) }}</span>
            </div>
            <div class="quote-row">
              <span>سعر وصلة</span>
              <span>{{ money(quote.wasla_price) }}</span>
            </div>
            <div class="quote-row">
              <span>شحن تقديري</span>
              <span>{{ money(quote.shipping) }}</span>
            </div>
            <div class="quote-row">
              <span>خدمة وصلة</span>
              <span>{{ money(quote.service_fee) }}</span>
            </div>
            <div class="quote-row total">
              <span>الإجمالي التقديري</span>
              <span>{{ money(quote.total) }}</span>
            </div>
          </div>

          <div class="actions">
            <button type="button" class="btn btn-primary" :disabled="adding" @click="addToCart">
              {{ adding ? 'جاري الإضافة...' : 'أضف للسلة' }}
            </button>
            <a v-if="added" href="/cart" class="btn btn-secondary">عرض السلة ←</a>
            <a
              v-if="product.external_url"
              :href="product.external_url"
              target="_blank"
              rel="noopener"
              class="ext-link"
            >
              {{ product.is_demo ? '🔎 بحث حقيقي على' : 'فتح في' }} {{ platformMeta?.name || 'المنصة' }} ↗
            </a>
          </div>
          <p v-if="feedback" class="feedback" :class="{ error: feedbackError }">{{ feedback }}</p>
          <p class="note">
            السعر تقديري بالدرهم — الفريق يؤكد السعر النهائي قبل الشراء من المنصة.
            تسجيل الدخول مطلوب فقط عند الدفع.
          </p>
        </div>
      </div>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../storefront/api';
import { addExternalToCart } from '../../storefront/store';
import { money, formatPrice } from '../../storefront/format';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const props = defineProps({
  platform: { type: String, required: true },
  productId: { type: String, required: true },
});

const platformSlug = props.platform;
const platformMeta = ref(null);
const product = ref(null);
const quote = ref(null);
const activeImage = ref('');
const selectedVariant = ref('');
const loading = ref(true);
const error = ref('');
const adding = ref(false);
const added = ref(false);
const feedback = ref('');
const feedbackError = ref(false);

onMounted(async () => {
  try {
    const { data } = await api.get(`/v1/browse/${platformSlug}/products/${props.productId}`);
    platformMeta.value = data.platform;
    product.value = data.product;
    quote.value = data.quote;
    activeImage.value = data.product?.image || (data.product?.images?.[0] ?? '');
    selectedVariant.value = data.product?.variants?.[0]?.option_value || '';
  } catch (e) {
    error.value = e.response?.data?.message || 'المنتج غير موجود';
  } finally {
    loading.value = false;
  }
});

async function addToCart() {
  if (!product.value || !quote.value) return;
  adding.value = true;
  feedback.value = '';
  try {
    await addExternalToCart({
      preview: {
        platform: platformMeta.value,
        product: {
          ...product.value,
          external_product_id: product.value.id,
          name: selectedVariant.value
            ? `${product.value.name} (${selectedVariant.value})`
            : product.value.name,
        },
        quote: quote.value,
      },
      quantity: 1,
    });
    added.value = true;
    feedbackError.value = false;
    feedback.value = 'تمت الإضافة للسلة.';
  } catch (e) {
    feedbackError.value = true;
    feedback.value = 'فشل إضافة السلة';
  } finally {
    adding.value = false;
  }
}
</script>

<style scoped>
.browse-shell {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1.5rem 1.5rem 3rem;
}
.back {
  display: inline-block;
  margin-bottom: 1.25rem;
  color: #1c7282;
  font-weight: 600;
  text-decoration: none;
}
.detail {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}
.main-img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: 1rem;
  background: #f5fbfc;
}
.thumbs {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
}
.thumb {
  width: 4rem;
  height: 4rem;
  padding: 0;
  border: 2px solid transparent;
  border-radius: 0.5rem;
  overflow: hidden;
  cursor: pointer;
  background: none;
}
.thumb.active { border-color: #1c7282; }
.thumb img { width: 100%; height: 100%; object-fit: cover; }
.source {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  color: #4d6b72;
  font-weight: 600;
}
.source img {
  height: 1.5rem;
  object-fit: contain;
}
.info h1 {
  margin: 0 0 0.35rem;
  font-size: 1.6rem;
  color: #132f37;
}
.sub-name {
  margin: 0 0 0.75rem;
  color: #4d6b72;
  font-size: 0.95rem;
}
.desc {
  color: #3a555c;
  line-height: 1.55;
  margin: 0 0 1.25rem;
}
.demo-banner {
  background: #fff8ee;
  border: 1px dashed #e0a94b;
  border-radius: 0.85rem;
  padding: 0.9rem 1rem;
  margin-bottom: 1rem;
  color: #6b4c1e;
  font-size: 0.85rem;
  line-height: 1.55;
}
.demo-banner a { color: #1c7282; font-weight: 700; }
.label {
  display: block;
  font-weight: 700;
  margin-bottom: 0.45rem;
  color: #1c7282;
}
.chips { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-bottom: 1.25rem; }
.chip {
  border: 1px solid rgba(15, 90, 107, 0.25);
  background: #fff;
  padding: 0.4rem 0.85rem;
  border-radius: 999px;
  cursor: pointer;
  font-weight: 600;
}
.chip.active {
  background: #1c7282;
  color: #fff;
  border-color: #1c7282;
}
.quote-box {
  background: #f5fbfc;
  border: 1px solid rgba(15, 90, 107, 0.12);
  border-radius: 0.85rem;
  padding: 1rem;
  margin-bottom: 1.25rem;
}
.quote-row {
  display: flex;
  justify-content: space-between;
  padding: 0.35rem 0;
  color: #3a555c;
}
.quote-row.total {
  border-top: 1px solid rgba(15, 90, 107, 0.15);
  margin-top: 0.35rem;
  padding-top: 0.65rem;
  font-weight: 800;
  color: #1c7282;
  font-size: 1.05rem;
}
.actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
}
.ext-link {
  color: #4d6b72;
  font-size: 0.9rem;
}
.feedback { margin-top: 0.75rem; color: #1c7282; font-weight: 600; }
.feedback.error { color: #a82626; }
.note {
  margin-top: 1rem;
  font-size: 0.85rem;
  color: #4d6b72;
  line-height: 1.45;
}
.loading-row, .empty-state { padding: 2rem 0; color: #4d6b72; }
.empty-state.error { color: #a82626; }
@media (max-width: 800px) {
  .detail { grid-template-columns: 1fr; }
}
</style>
