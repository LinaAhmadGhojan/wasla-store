<template>
  <div class="buy-page">
    <StorefrontNav />

    <div class="buy-shell">
      <h1>اطلبي من أي موقع</h1>
      <p class="lead">
        الصقي رابط منتج من SHEIN أو Trendyol أو Temu أو Noon أو Amazon.
        وصلة بتعملك معاينة بالدرهم الإماراتي، وتقدري تضيفيه للسلة بدون تسجيل دخول.
        أو <a href="/browse">تصفّحي داخل المنصة</a> بدون رابط.
      </p>

      <div class="platform-grid">
        <button
          v-for="platform in platforms"
          :key="platform.id || platform.slug"
          type="button"
          class="platform-card"
          :class="[`platform-${platform.slug}`, { active: selectedPlatformId === platform.id }]"
          @click="selectedPlatformId = platform.id"
        >
          <img v-if="platform.logo" :src="platform.logo" :alt="platform.name" class="platform-logo" />
          <span class="platform-name">{{ platform.name }}</span>
        </button>
      </div>

      <form class="buy-form" @submit.prevent="previewProduct">
        <label>
          رابط المنتج
          <input v-model="url" type="url" required placeholder="https://..." />
        </label>
        <button type="submit" class="btn btn-primary" :disabled="previewing">
          {{ previewing ? 'جاري المعاينة...' : 'معاينة المنتج' }}
        </button>
      </form>

      <div v-if="preview" class="preview-card">
        <img v-if="preview.product?.image" :src="preview.product.image" class="preview-img" />
        <div>
          <h3>{{ preview.product?.name }}</h3>
          <p>{{ preview.message }}</p>

          <div v-if="preview.needs_manual_confirmation" class="manual-form">
            <a :href="url" target="_blank" rel="noopener" class="btn btn-secondary open-link-btn">
              فتح صفحة المنتج بالموقع الأصلي ↗
            </a>
            <label>
              اسم المنتج (كما هو بالموقع)
              <input v-model="manualName" type="text" placeholder="اسم المنتج" />
            </label>
            <label>
              السعر الحقيقي ({{ preview.platform?.currency || 'USD' }})
              <input v-model="manualPrice" type="number" min="0" step="0.01" placeholder="مثال: 25.00" />
            </label>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="confirming || !manualPrice"
              @click="confirmManualDetails"
            >
              {{ confirming ? 'جاري التحديث...' : 'تأكيد السعر وتحديث المعاينة' }}
            </button>
          </div>

          <div class="quote-row"><span>Original</span><span>{{ preview.quote.original_price }} {{ preview.quote.original_currency }}</span></div>
          <div class="quote-row"><span>Wasla</span><span>{{ money(preview.quote.wasla_price) }}</span></div>
          <div class="quote-row"><span>Shipping</span><span>{{ money(preview.quote.shipping) }}</span></div>
          <div class="quote-row"><span>Service</span><span>{{ money(preview.quote.service_fee) }}</span></div>
          <div class="quote-row total"><span>Total</span><span>{{ money(preview.quote.total) }}</span></div>
          <button type="button" class="btn btn-primary" @click="addToCart" :disabled="adding">
            {{ adding ? '...' : 'أضف للسلة' }}
          </button>
          <a v-if="added" href="/cart" class="back-link">عرض السلة ←</a>
        </div>
      </div>

      <p v-if="feedback" class="feedback" :class="{ error: feedbackError }">{{ feedback }}</p>
      <a href="/" class="back-link">← رجوع للمتجر</a>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../storefront/api';
import { addExternalToCart } from '../../storefront/store';
import { money } from '../../storefront/format';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const platforms = ref([]);
const selectedPlatformId = ref(null);
const url = ref('');
const previewing = ref(false);
const preview = ref(null);
const adding = ref(false);
const added = ref(false);
const feedback = ref('');
const feedbackError = ref(false);
const manualName = ref('');
const manualPrice = ref('');
const confirming = ref(false);

async function loadPlatforms() {
  try {
    const { data } = await api.get('/v1/external-platforms');
    platforms.value = data;
    if (data.length) selectedPlatformId.value = data[0].id;
  } catch (e) {
    platforms.value = [];
  }
}

async function previewProduct() {
  feedback.value = '';
  preview.value = null;
  added.value = false;
  manualName.value = '';
  manualPrice.value = '';
  previewing.value = true;
  try {
    const { data } = await api.post('/v1/external-products/preview', { url: url.value });
    preview.value = data;
    if (data.platform?.id) selectedPlatformId.value = data.platform.id;
    if (data.needs_manual_confirmation) manualName.value = data.product?.name || '';
  } catch (e) {
    feedbackError.value = true;
    feedback.value = e.response?.data?.message || 'تعذر المعاينة';
  } finally {
    previewing.value = false;
  }
}

async function confirmManualDetails() {
  if (!manualPrice.value) return;
  confirming.value = true;
  feedback.value = '';
  try {
    const { data } = await api.post('/v1/external-products/preview', {
      url: url.value,
      manual_name: manualName.value || undefined,
      manual_price: manualPrice.value,
    });
    preview.value = data;
  } catch (e) {
    feedbackError.value = true;
    feedback.value = e.response?.data?.message || 'تعذر تحديث المعاينة';
  } finally {
    confirming.value = false;
  }
}

async function addToCart() {
  if (!preview.value) return;
  adding.value = true;
  try {
    await addExternalToCart({ preview: preview.value, quantity: 1 });
    added.value = true;
    feedbackError.value = false;
    feedback.value = 'تمت الإضافة للسلة. تسجيل الدخول مطلوب فقط عند الدفع.';
  } catch (e) {
    feedbackError.value = true;
    feedback.value = 'فشل إضافة السلة';
  } finally {
    adding.value = false;
  }
}

onMounted(() => {
  loadPlatforms();
  const params = new URLSearchParams(window.location.search);
  const prefill = params.get('url');
  if (prefill) {
    url.value = prefill;
    previewProduct();
  }
});
</script>

<style scoped>
.buy-page { color: #132f37; }
.buy-shell { max-width: 720px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }
h1 { margin: 0 0 0.75rem; }
.lead { color: #4d6b72; margin: 0 0 1.75rem; }
.lead a { color: #1c7282; font-weight: 700; }
.platform-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.85rem; margin-bottom: 1.5rem; }
.platform-card {
  border: 3px solid transparent;
  border-radius: 1rem;
  padding: 0.9rem 0.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  box-shadow: 0 8px 18px rgba(0,0,0,0.08);
}
.platform-card.active { border-color: #1c7282; }
.platform-logo { width: 100%; height: 48px; object-fit: contain; border-radius: 0.55rem; }
.platform-name { font-weight: 800; font-size: 0.85rem; color: #132f37; }
.platform-shein { background: #f3f3f3; }
.platform-trendyol { background: #fff4eb; }
.platform-temu { background: #fff1e6; }
.platform-noon { background: #fffce0; }
.platform-amazon { background: #eef2f6; }
.buy-form, .preview-card {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  background: white;
  border: 1px solid rgba(15, 90, 107, 0.08);
  border-radius: 1.25rem;
  padding: 1.5rem;
  margin-bottom: 1rem;
}
.preview-card { display: grid; grid-template-columns: 160px 1fr; }
.preview-img { width: 100%; border-radius: 0.85rem; aspect-ratio: 1; object-fit: cover; }
.buy-form label { display: flex; flex-direction: column; gap: 0.4rem; font-weight: 700; font-size: 0.9rem; }
.buy-form input {
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  border: 1px solid rgba(15, 90, 107, 0.2);
  font-weight: 400;
}
.manual-form {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  background: #fff8ee;
  border: 1px dashed #e0a94b;
  border-radius: 0.85rem;
  padding: 0.9rem;
  margin: 0.75rem 0;
}
.manual-form label { display: flex; flex-direction: column; gap: 0.3rem; font-weight: 700; font-size: 0.82rem; color: #6b4c1e; }
.manual-form input {
  padding: 0.6rem 0.8rem;
  border-radius: 0.6rem;
  border: 1px solid rgba(15, 90, 107, 0.2);
  font-weight: 400;
}
.open-link-btn { background: white; color: #1c7282; border: 1.5px solid #1c7282; text-align: center; text-decoration: none; }
.quote-row { display: flex; justify-content: space-between; color: #4d6b72; font-size: 0.9rem; }
.quote-row.total { font-weight: 800; color: #1c7282; }
.btn { padding: 0.9rem 1.25rem; border-radius: 0.85rem; font-weight: 700; border: none; cursor: pointer; }
.btn-primary { background: #1c7282; color: white; }
.feedback { margin: 0.75rem 0 0; color: #1c7282; font-weight: 600; }
.feedback.error { color: #c0392b; }
.back-link { display: inline-block; margin-top: 1rem; color: #1c7282; font-weight: 700; text-decoration: none; }
@media (max-width: 700px) {
  .platform-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .preview-card { grid-template-columns: 1fr; }
}
</style>
