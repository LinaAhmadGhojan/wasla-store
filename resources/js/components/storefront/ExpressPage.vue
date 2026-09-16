<template>
  <div class="express-page" dir="rtl">
    <StorefrontNav theme="express" :hide-search="true" />

    <div class="shell">
      <div class="mode-switch" role="tablist" aria-label="اختر نوع التسوق">
        <a href="/shop" class="mode-btn" role="tab" @click="rememberChannel('store')">متجر وصلة</a>
        <a href="/express" class="mode-btn on" role="tab" aria-current="page" @click="rememberChannel('express')">لقمة </a>
      </div>
      <p class="hint">توصيل من مطاعم ومتاجر قريبة · قريباً في دمشق</p>

      <p v-if="loading" class="load-msg">عم نحمّل المتاجر والأطباق…</p>
      <p v-else-if="loadError" class="load-err">{{ loadError }}</p>

      <div
        v-if="filtersOpen"
        class="filters-backdrop mobile-only"
        @click="closeExpressFilters"
      ></div>

      <div class="layout">
        <aside
          class="filters-panel"
          :class="{ open: filtersOpen }"
          role="dialog"
          :aria-modal="filtersOpen ? 'true' : 'false'"
          aria-labelledby="express-filters-title"
        >
          <div class="filters-head">
            <div class="filters-head-text">
              <h2 id="express-filters-title">تصفية النتائج</h2>
              <p class="filters-explain">
                اختاري اللي بدك تشوفيه فقط — مثل نوع الأكل، المتجر، السعر، أو سرعة التوصيل.
              </p>
              <p v-if="activeFilterCount" class="filters-count">{{ activeFilterCount }} خيار مفعّل الآن</p>
            </div>
            <div class="filters-head-actions">
              <button type="button" class="btn-clear" @click="resetExpressFilters">مسح الكل</button>
              <button type="button" class="btn-x mobile-only" aria-label="إغلاق" @click="closeExpressFilters">×</button>
            </div>
          </div>

          <div class="filters-body">
            <div class="fg">
              <h3>التصنيف</h3>
              <p class="fg-hint">مطاعم، مخابز، مشروبات…</p>
              <button
                v-for="c in expressCategories"
                :key="'f-'+c.key"
                type="button"
                class="opt"
                :class="{ on: filters.category === c.key }"
                @click="filters.category = c.key"
              >{{ c.label }}</button>
            </div>

            <div class="fg">
              <h3>المتجر</h3>
              <p class="fg-hint">اختاري مطعم أو محل معيّن</p>
              <button type="button" class="opt" :class="{ on: !filters.storeId }" @click="filters.storeId = null">كل المتاجر</button>
              <button
                v-for="s in storesForFilter"
                :key="'fs-'+s.id"
                type="button"
                class="opt"
                :class="{ on: filters.storeId === s.id }"
                @click="filters.storeId = filters.storeId === s.id ? null : s.id"
              >{{ s.name }}</button>
            </div>

            <div class="fg">
              <h3>نوع المطبخ</h3>
              <button type="button" class="opt" :class="{ on: !filters.cuisine }" @click="filters.cuisine = ''">الكل</button>
              <button
                v-for="c in cuisineOptions"
                :key="c"
                type="button"
                class="opt"
                :class="{ on: filters.cuisine === c }"
                @click="filters.cuisine = filters.cuisine === c ? '' : c"
              >{{ c }}</button>
            </div>

            <div class="fg">
              <h3>سرعة التوصيل</h3>
              <p class="fg-hint">قدّيش بدك يوصل بسرعة؟</p>
              <button
                v-for="e in etaOptions"
                :key="e.key"
                type="button"
                class="opt"
                :class="{ on: filters.eta === e.key }"
                @click="filters.eta = e.key"
              >{{ e.label }}</button>
            </div>

            <div class="fg">
              <h3>التقييم</h3>
              <button
                v-for="r in ratingOptions"
                :key="r.key"
                type="button"
                class="opt"
                :class="{ on: filters.rating === r.key }"
                @click="filters.rating = r.key"
              >{{ r.label }}</button>
            </div>

            <div class="fg">
              <h3>السعر (ليرة سورية)</h3>
              <p class="fg-hint">حدّدي ميزانيتك</p>
              <div class="price-row">
                <label>
                  من
                  <input v-model.number="filters.minPrice" type="number" min="0" step="1000" placeholder="0" />
                </label>
                <label>
                  إلى
                  <input v-model.number="filters.maxPrice" type="number" min="0" step="1000" placeholder="أي سعر" />
                </label>
              </div>
              <div class="price-presets">
                <button
                  v-for="p in pricePresets"
                  :key="p.key"
                  type="button"
                  class="preset"
                  :class="{ on: filters.minPrice === p.min && filters.maxPrice === p.max }"
                  @click="applyPricePreset(p)"
                >{{ p.label }}</button>
              </div>
            </div>

            <div class="fg">
              <h3>خيارات إضافية</h3>
              <label class="check">
                <input v-model="filters.offersOnly" type="checkbox" />
                عروض وخصومات فقط
              </label>
              <label class="check">
                <input v-model="filters.fastOnly" type="checkbox" />
                توصيل خلال 25 دقيقة أو أقل
              </label>
            </div>

            <div class="fg">
              <h3>ترتيب النتائج</h3>
              <button
                v-for="s in sortOptions"
                :key="s.key"
                type="button"
                class="opt"
                :class="{ on: filters.sort === s.key }"
                @click="filters.sort = s.key"
              >{{ s.label }}</button>
            </div>
          </div>

          <div class="filters-foot mobile-only">
            <button type="button" class="btn-apply" @click="closeExpressFilters">
              تم — عرض {{ filteredFoods.length }} نتيجة
            </button>
          </div>
        </aside>

        <div class="results">
          <div class="chips" role="tablist" aria-label="التصنيف السريع">
            <button
              v-for="c in expressCategories"
              :key="c.key"
              type="button"
              class="chip"
              :class="{ on: filters.category === c.key }"
              @click="filters.category = c.key"
            >{{ c.label }}</button>
          </div>

          <div class="toolbar">
            <button type="button" class="btn-filters mobile-only" @click="openExpressFilters">
              تصفية النتائج
              <span v-if="activeFilterCount" class="badge">{{ activeFilterCount }}</span>
            </button>
            <p class="meta">
              ظاهر الآن: <strong>{{ filteredFoods.length }}</strong> طبق
              و <strong>{{ filteredStores.length }}</strong> متجر
            </p>
            <button
              v-if="activeFilterCount"
              type="button"
              class="btn-reset-inline"
              @click="resetExpressFilters"
            >إلغاء التصفية</button>
          </div>
          <p class="filter-help mobile-only">
            اضغطي «تصفية النتائج» لتضييق البحث حسب المتجر أو السعر أو سرعة التوصيل.
          </p>

          <div v-if="activeFilterLabels.length" class="active-tags" aria-label="خيارات مفعّلة">
            <span v-for="tag in activeFilterLabels" :key="tag" class="tag">{{ tag }}</span>
          </div>

          <section class="block" aria-labelledby="stores-title">
            <h2 id="stores-title" class="block-title">متاجر قريبة</h2>
            <div class="store-rail">
              <a
                v-for="s in filteredStores"
                :key="s.id"
                class="store-card"
                :class="{ on: filters.storeId === s.id }"
                :href="`/express/stores/${s.id}`"
              >
                <img
                  :src="s.image"
                  :alt="s.name"
                  class="store-img"
                  loading="lazy"
                  decoding="async"
                  width="88"
                  height="88"
                />
                <span class="store-body">
                  <strong>{{ s.name }}</strong>
                  <em>{{ s.cuisine }} · {{ s.eta }}</em>
                </span>
                <span class="rate">★ {{ s.rating }}</span>
              </a>
            </div>
            <p v-if="!filteredStores.length" class="empty">ما في متاجر بهالخيارات — جرّبي «إلغاء التصفية».</p>
          </section>

          <section class="block" aria-labelledby="foods-title">
            <h2 id="foods-title" class="block-title">أطباق مقترحة</h2>
            <div class="food-grid">
              <a
                v-for="f in filteredFoods"
                :key="f.id"
                class="food-card"
                :href="`/express/items/${f.id}`"
              >
                <div class="food-img-wrap">
                  <img
                    :src="f.image"
                    :alt="f.name"
                    loading="lazy"
                    decoding="async"
                    width="320"
                    height="240"
                  />
                  <span v-if="f.offer" class="food-offer">عرض</span>
                  <span class="food-eta">{{ f.eta }}</span>
                </div>
                <div class="food-body">
                  <strong>{{ f.name }}</strong>
                  <span class="food-store">{{ f.store }}</span>
                  <div class="food-price-row">
                    <span class="food-price">{{ f.price }}</span>
                    <span v-if="f.oldPrice" class="food-old">{{ f.oldPrice }}</span>
                  </div>
                </div>
              </a>
            </div>
            <p v-if="!filteredFoods.length" class="empty">لا نتائج بهالخيارات — امسحي التصفية وجرّبي من جديد.</p>
          </section>

          <p class="note">البيانات من لقمة  — الطلب الحقيقي قيد التجهيز.</p>
        </div>
      </div>
    </div>

    <StorefrontFooter theme="express" />
  </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, reactive, ref, watch } from 'vue';
import api from '../../storefront/api';
import { rememberChannel } from '../../storefront/channel';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

rememberChannel('express');

const filtersOpen = ref(false);
const loading = ref(true);
const loadError = ref('');
const expressCategories = ref([{ key: 'all', label: 'الكل' }]);
const expressStores = ref([]);
const expressFoods = ref([]);
const cuisineOptions = ref([]);

const filters = reactive({
  category: 'all',
  storeId: null,
  cuisine: '',
  eta: 'all',
  rating: 'all',
  minPrice: null,
  maxPrice: null,
  offersOnly: false,
  fastOnly: false,
  sort: 'recommended',
});

const etaOptions = [
  { key: 'all', label: 'الكل', maxMin: null },
  { key: 'fast', label: 'خلال 20 دقيقة', maxMin: 20 },
  { key: 'medium', label: 'خلال 35 دقيقة', maxMin: 35 },
  { key: 'any', label: 'أي وقت', maxMin: null },
];

const ratingOptions = [
  { key: 'all', label: 'الكل', min: 0 },
  { key: '4.5', label: '4.5 فأكثر', min: 4.5 },
  { key: '4', label: '4 فأكثر', min: 4 },
  { key: '3.5', label: '3.5 فأكثر', min: 3.5 },
];

const pricePresets = [
  { key: 'low', label: 'حتى 20 ألف', min: null, max: 20000 },
  { key: 'mid', label: '20–50 ألف', min: 20000, max: 50000 },
  { key: 'high', label: 'أكثر من 50', min: 50000, max: null },
];

const sortOptions = [
  { key: 'recommended', label: 'المقترح' },
  { key: 'fastest', label: 'الأسرع توصيلاً' },
  { key: 'price_asc', label: 'السعر من الأقل' },
  { key: 'price_desc', label: 'السعر من الأعلى' },
  { key: 'rating', label: 'الأعلى تقييماً' },
];

function browseParams() {
  const params = {
    category: filters.category,
    sort: filters.sort,
  };
  if (filters.storeId) params.store_id = filters.storeId;
  if (filters.cuisine) params.cuisine = filters.cuisine;
  if (filters.eta && filters.eta !== 'all') params.eta = filters.eta;
  if (filters.rating && filters.rating !== 'all') params.rating = filters.rating;
  if (filters.minPrice != null && filters.minPrice !== '') params.min_price = filters.minPrice;
  if (filters.maxPrice != null && filters.maxPrice !== '') params.max_price = filters.maxPrice;
  if (filters.offersOnly) params.offers_only = 1;
  if (filters.fastOnly) params.fast_only = 1;
  return params;
}

let fetchTimer = null;
async function fetchBrowse() {
  loading.value = true;
  loadError.value = '';
  try {
    const { data } = await api.get('/v1/express', { params: browseParams() });
    if (Array.isArray(data.categories) && data.categories.length) {
      expressCategories.value = data.categories;
    }
    expressStores.value = data.stores || [];
    expressFoods.value = data.items || [];
  } catch (e) {
    loadError.value = e?.response?.data?.message || 'تعذّر تحميل لقمة . جرّبي لاحقاً.';
    expressStores.value = [];
    expressFoods.value = [];
  } finally {
    loading.value = false;
  }
}

function scheduleFetch() {
  clearTimeout(fetchTimer);
  fetchTimer = setTimeout(fetchBrowse, 180);
}

async function fetchFacets() {
  try {
    const { data } = await api.get('/v1/express/facets');
    if (Array.isArray(data.categories) && data.categories.length) {
      expressCategories.value = data.categories;
    }
    cuisineOptions.value = data.cuisines || [];
  } catch (_) {
    /* browse still works */
  }
}

const storesForFilter = computed(() => {
  let list = expressStores.value;
  if (filters.category !== 'all') list = list.filter((s) => s.category === filters.category);
  if (filters.cuisine) list = list.filter((s) => s.cuisine === filters.cuisine);
  return list;
});

const filteredStores = computed(() => expressStores.value);
const filteredFoods = computed(() => expressFoods.value);

const activeFilterCount = computed(() => {
  let n = 0;
  if (filters.category !== 'all') n += 1;
  if (filters.storeId) n += 1;
  if (filters.cuisine) n += 1;
  if (filters.eta !== 'all' && filters.eta !== 'any') n += 1;
  if (filters.rating !== 'all') n += 1;
  if (filters.minPrice != null && filters.minPrice !== '') n += 1;
  if (filters.maxPrice != null && filters.maxPrice !== '') n += 1;
  if (filters.offersOnly) n += 1;
  if (filters.fastOnly) n += 1;
  if (filters.sort !== 'recommended') n += 1;
  return n;
});

const activeFilterLabels = computed(() => {
  const tags = [];
  if (filters.category !== 'all') {
    tags.push(expressCategories.value.find((c) => c.key === filters.category)?.label || filters.category);
  }
  if (filters.storeId) {
    tags.push(expressStores.value.find((s) => s.id === filters.storeId)?.name || 'متجر');
  }
  if (filters.cuisine) tags.push(filters.cuisine);
  if (filters.eta !== 'all' && filters.eta !== 'any') {
    tags.push(etaOptions.find((e) => e.key === filters.eta)?.label || '');
  }
  if (filters.rating !== 'all') {
    tags.push(ratingOptions.find((r) => r.key === filters.rating)?.label || '');
  }
  if (filters.minPrice != null && filters.minPrice !== '') tags.push(`من ${Number(filters.minPrice).toLocaleString('ar')}`);
  if (filters.maxPrice != null && filters.maxPrice !== '') tags.push(`إلى ${Number(filters.maxPrice).toLocaleString('ar')}`);
  if (filters.offersOnly) tags.push('عروض فقط');
  if (filters.fastOnly) tags.push('توصيل سريع');
  if (filters.sort !== 'recommended') {
    tags.push(sortOptions.find((s) => s.key === filters.sort)?.label || '');
  }
  return tags.filter(Boolean);
});

function applyPricePreset(p) {
  filters.minPrice = p.min;
  filters.maxPrice = p.max;
}

function openExpressFilters() {
  filtersOpen.value = true;
}

function closeExpressFilters() {
  filtersOpen.value = false;
}

function resetExpressFilters() {
  filters.category = 'all';
  filters.storeId = null;
  filters.cuisine = '';
  filters.eta = 'all';
  filters.rating = 'all';
  filters.minPrice = null;
  filters.maxPrice = null;
  filters.offersOnly = false;
  filters.fastOnly = false;
  filters.sort = 'recommended';
}

function onKeydown(e) {
  if (e.key === 'Escape') closeExpressFilters();
}

function onResize() {
  if (window.innerWidth > 900 && filtersOpen.value) {
    filtersOpen.value = false;
  }
}

watch(() => filters.category, () => {
  if (!filters.storeId) return;
  const stillVisible = storesForFilter.value.some((s) => s.id === filters.storeId);
  if (!stillVisible) filters.storeId = null;
});

watch(filters, () => scheduleFetch(), { deep: true });

watch(filtersOpen, (open) => {
  const isMobile = window.innerWidth <= 900;
  document.body.style.overflow = open && isMobile ? 'hidden' : '';
});

onMounted(async () => {
  document.addEventListener('keydown', onKeydown);
  window.addEventListener('resize', onResize);
  await fetchFacets();
  await fetchBrowse();
});

onBeforeUnmount(() => {
  clearTimeout(fetchTimer);
  document.removeEventListener('keydown', onKeydown);
  window.removeEventListener('resize', onResize);
  document.body.style.overflow = '';
});
</script>

<style scoped>
.express-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f3e6d6 0%, #faf6f1 28%, #fff 100%);
  color: #3d2817;
}
.shell {
  max-width: 1180px;
  margin: 0 auto;
  padding: 1rem 1rem 3.5rem;
}
.mode-switch {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem;
  margin-bottom: 0.35rem;
}
.mode-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 2.55rem;
  padding: 0.45rem 0.75rem;
  border-radius: 999px;
  border: 1.5px solid rgba(138, 75, 18, 0.25);
  background: #fff;
  color: #5c3210;
  font-weight: 900;
  font-size: 0.9rem;
  text-decoration: none;
}
.mode-btn.on {
  background: #8a4b12;
  border-color: #8a4b12;
  color: #fff8ef;
  box-shadow: 0 6px 16px rgba(138, 75, 18, 0.22);
}
.hint {
  margin: 0 0 0.85rem;
  color: #9a7a5c;
  font-size: 0.78rem;
  font-weight: 700;
  line-height: 1.4;
}
.load-msg, .load-err {
  margin: 0 0 0.85rem;
  font-size: 0.9rem;
  font-weight: 700;
}
.load-msg { color: #8a4b12; }
.load-err { color: #a33; }

.layout {
  display: grid;
  grid-template-columns: 280px minmax(0, 1fr);
  gap: 1.15rem;
  align-items: start;
}

.filters-backdrop {
  display: none;
}

/* لابتوب: فلاتر جانبية طبيعية بجانب المحتوى */
.filters-panel {
  position: sticky;
  top: 5.5rem;
  z-index: 1;
  width: 100%;
  max-height: calc(100vh - 6.5rem);
  background: #fff;
  border-radius: 1.15rem;
  border: 1.5px solid rgba(138, 75, 18, 0.18);
  box-shadow: 0 10px 28px rgba(92, 50, 16, 0.06);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.filters-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.95rem 1rem 0.8rem;
  border-bottom: 1px solid rgba(138, 75, 18, 0.12);
  background: #faf3e8;
  flex-shrink: 0;
}
.filters-head-text { min-width: 0; }
.filters-head h2 {
  margin: 0 0 0.25rem;
  font-size: 1.05rem;
  color: #3d2817;
}
.filters-explain {
  margin: 0;
  font-size: 0.75rem;
  line-height: 1.45;
  color: #9a7a5c;
  font-weight: 700;
}
.filters-count {
  margin: 0.35rem 0 0;
  font-size: 0.78rem;
  font-weight: 900;
  color: #8a4b12;
}
.filters-head-actions {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  flex-shrink: 0;
}
.btn-clear {
  border: 1.5px solid rgba(138, 75, 18, 0.28);
  background: #fff;
  color: #8a4b12;
  border-radius: 999px;
  padding: 0.3rem 0.7rem;
  font-weight: 800;
  font-size: 0.8rem;
  cursor: pointer;
  white-space: nowrap;
}
.btn-x {
  width: 2.1rem;
  height: 2.1rem;
  border: 0;
  border-radius: 999px;
  background: #5c3210;
  color: #fff;
  font-size: 1.35rem;
  line-height: 1;
  cursor: pointer;
  font-weight: 700;
}
.filters-body {
  padding: 0.85rem 1rem 1rem;
  overflow: auto;
  flex: 1;
  min-height: 0;
  -webkit-overflow-scrolling: touch;
}
.filters-foot {
  display: none;
}
.fg {
  margin: 0 0 0.85rem;
  padding: 0.7rem;
  border-radius: 0.85rem;
  background: #faf6f1;
  border: 1px solid rgba(138, 75, 18, 0.1);
}
.fg h3 {
  margin: 0 0 0.2rem;
  font-size: 0.9rem;
  font-weight: 900;
  color: #3d2817;
}
.fg-hint {
  margin: 0 0 0.45rem;
  font-size: 0.72rem;
  color: #9a7a5c;
  font-weight: 700;
}
.opt {
  display: block;
  width: 100%;
  text-align: right;
  border: 1.5px solid transparent;
  background: #fff;
  padding: 0.5rem 0.65rem;
  border-radius: 0.55rem;
  margin-bottom: 0.28rem;
  cursor: pointer;
  font-weight: 700;
  color: #3d2817;
  font: inherit;
}
.opt.on {
  background: #f3e6d4;
  border-color: #8a4b12;
  font-weight: 900;
}
.price-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.45rem;
  margin-bottom: 0.55rem;
}
.price-row label {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.75rem;
  font-weight: 800;
  color: #8a735c;
}
.price-row input {
  width: 100%;
  box-sizing: border-box;
  border: 1.5px solid rgba(138, 75, 18, 0.22);
  border-radius: 0.65rem;
  padding: 0.5rem 0.6rem;
  font-weight: 700;
  font-size: 0.88rem;
  color: #3d2817;
  background: #fff;
}
.price-presets { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.preset {
  border: 1.5px solid rgba(138, 75, 18, 0.2);
  background: #fff;
  color: #5c3210;
  border-radius: 999px;
  padding: 0.32rem 0.65rem;
  font-weight: 800;
  font-size: 0.75rem;
  cursor: pointer;
}
.preset.on {
  background: #8a4b12;
  border-color: #8a4b12;
  color: #fff;
}
.check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 800;
  font-size: 0.88rem;
  margin-bottom: 0.45rem;
  cursor: pointer;
}
.check input {
  width: 1.05rem;
  height: 1.05rem;
  accent-color: #8a4b12;
}
.btn-apply {
  width: 100%;
  border: 0;
  background: #8a4b12;
  color: #fff;
  border-radius: 999px;
  padding: 0.85rem 1rem;
  font-weight: 900;
  font-size: 1rem;
  cursor: pointer;
}

.chips {
  display: flex;
  gap: 0.4rem;
  overflow-x: auto;
  padding-bottom: 0.2rem;
  margin-bottom: 0.75rem;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.chips::-webkit-scrollbar { display: none; }
.chip {
  flex: 0 0 auto;
  border: 1.5px solid rgba(138, 75, 18, 0.2);
  background: #fff;
  color: #5c3210;
  border-radius: 999px;
  padding: 0.4rem 0.85rem;
  font-weight: 800;
  font-size: 0.84rem;
  cursor: pointer;
  white-space: nowrap;
}
.chip.on {
  background: #8a4b12;
  border-color: #8a4b12;
  color: #fff;
}

.toolbar {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 0.45rem;
  flex-wrap: wrap;
}
.btn-filters {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 0;
  background: #8a4b12;
  color: #fff;
  border-radius: 999px;
  padding: 0.5rem 1rem;
  font-weight: 900;
  font-size: 0.86rem;
  cursor: pointer;
}
.badge {
  background: #fff;
  color: #8a4b12;
  border-radius: 999px;
  min-width: 1.15rem;
  height: 1.15rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: 900;
}
.meta {
  margin: 0;
  color: #9a7a5c;
  font-size: 0.8rem;
  font-weight: 700;
  flex: 1;
}
.meta strong { color: #8a4b12; font-weight: 900; }
.btn-reset-inline {
  border: 0;
  background: transparent;
  color: #8a4b12;
  font-weight: 900;
  font-size: 0.8rem;
  cursor: pointer;
  padding: 0.25rem 0.35rem;
}
.filter-help {
  margin: 0 0 0.75rem;
  color: #9a7a5c;
  font-size: 0.75rem;
  font-weight: 700;
  line-height: 1.4;
}
.active-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-bottom: 0.85rem;
}
.tag {
  background: #fff4e4;
  color: #5c3210;
  border: 1px solid rgba(138, 75, 18, 0.18);
  border-radius: 999px;
  padding: 0.22rem 0.6rem;
  font-size: 0.72rem;
  font-weight: 800;
}

.block { margin-bottom: 1.35rem; }
.block-title {
  margin: 0 0 0.65rem;
  font-size: 1.05rem;
  font-weight: 900;
  color: #3d2817;
}
.store-rail {
  display: flex;
  gap: 0.65rem;
  overflow-x: auto;
  padding: 0.1rem 0.1rem 0.35rem;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: thin;
}
.store-card {
  flex: 0 0 auto;
  width: min(72vw, 220px);
  display: grid;
  grid-template-columns: 56px minmax(0, 1fr);
  grid-template-rows: auto auto;
  gap: 0.2rem 0.55rem;
  padding: 0.5rem;
  border-radius: 1rem;
  background: #fff;
  border: 1.5px solid rgba(138, 75, 18, 0.14);
  cursor: pointer;
  text-align: right;
  font: inherit;
  color: inherit;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(92, 50, 16, 0.05);
}
.store-card.on {
  border-color: #8a4b12;
  box-shadow: 0 0 0 3px rgba(138, 75, 18, 0.12);
}
.store-img {
  grid-row: 1 / span 2;
  width: 56px;
  height: 56px;
  border-radius: 0.75rem;
  object-fit: cover;
  display: block;
}
.store-body {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.store-body strong {
  font-size: 0.82rem;
  font-weight: 900;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.store-body em {
  font-style: normal;
  font-size: 0.7rem;
  color: #9a7a5c;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.rate {
  grid-column: 2;
  justify-self: start;
  font-size: 0.7rem;
  font-weight: 900;
  color: #8a4b12;
  background: #fff4e4;
  border-radius: 999px;
  padding: 0.12rem 0.4rem;
  width: fit-content;
}

.food-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}
.food-card {
  background: #fff;
  border-radius: 1rem;
  overflow: hidden;
  border: 1px solid rgba(138, 75, 18, 0.12);
  box-shadow: 0 6px 16px rgba(92, 50, 16, 0.05);
  min-width: 0;
  text-decoration: none;
  color: inherit;
  display: block;
}
.food-img-wrap {
  position: relative;
  aspect-ratio: 4 / 3;
  background: #efe4d6;
  overflow: hidden;
}
.food-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.food-eta {
  position: absolute;
  inset-inline-start: 0.45rem;
  bottom: 0.45rem;
  background: rgba(61, 40, 23, 0.78);
  color: #fff;
  font-size: 0.68rem;
  font-weight: 800;
  border-radius: 999px;
  padding: 0.18rem 0.45rem;
}
.food-offer {
  position: absolute;
  inset-inline-end: 0.45rem;
  top: 0.45rem;
  background: #8a4b12;
  color: #fff;
  font-size: 0.68rem;
  font-weight: 900;
  border-radius: 999px;
  padding: 0.18rem 0.5rem;
}
.food-body {
  padding: 0.65rem 0.7rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}
.food-body strong {
  font-size: 0.88rem;
  font-weight: 900;
  line-height: 1.3;
}
.food-store {
  font-size: 0.72rem;
  color: #9a7a5c;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.food-price-row {
  margin-top: 0.25rem;
  display: flex;
  align-items: baseline;
  gap: 0.4rem;
}
.food-price {
  color: #8a4b12;
  font-weight: 900;
  font-size: 0.86rem;
}
.food-old {
  color: #b09a82;
  font-size: 0.72rem;
  font-weight: 700;
  text-decoration: line-through;
}
.empty, .note {
  margin: 0.5rem 0 0;
  color: #9a7a5c;
  font-size: 0.8rem;
  font-weight: 700;
}

.mobile-only { display: none !important; }

@media (min-width: 700px) {
  .food-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; }
  .store-card { width: 240px; }
}

/* الجوال فقط: الفلاتر نافذة من الأسفل — بدون ما تاخذ مساحة */
@media (max-width: 900px) {
  .shell { padding: 0.85rem 0.85rem 3.5rem; }
  .layout {
    display: block;
  }
  .mobile-only { display: inline-flex !important; }
  .filter-help.mobile-only { display: block !important; }
  .filters-backdrop.mobile-only {
    display: block !important;
    position: fixed;
    inset: 0;
    background: rgba(45, 28, 14, 0.45);
    z-index: 80;
  }
  .filters-panel {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    top: auto;
    z-index: 90;
    width: 100%;
    max-width: none;
    max-height: min(88vh, 720px);
    margin: 0;
    border-radius: 1.35rem 1.35rem 0 0;
    border: 0;
    box-shadow: 0 -16px 40px rgba(0, 0, 0, 0.22);
    transform: translateY(110%);
    visibility: hidden;
    pointer-events: none;
    transition: transform 0.28s ease, visibility 0.28s;
  }
  .filters-panel.open {
    transform: translateY(0);
    visibility: visible;
    pointer-events: auto;
  }
  .filters-foot.mobile-only {
    display: block !important;
    padding: 0.75rem 1rem calc(0.9rem + env(safe-area-inset-bottom, 0px));
    border-top: 1px solid rgba(138, 75, 18, 0.14);
    background: #fff;
    flex-shrink: 0;
  }
  .btn-x.mobile-only {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
  }
  .store-rail { scrollbar-width: none; }
  .store-rail::-webkit-scrollbar { display: none; }
  .food-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
