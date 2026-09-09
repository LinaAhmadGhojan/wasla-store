<template>
  <div class="shop-page" dir="rtl">
    <StorefrontNav />

    <div class="catalog-shell">
      <header class="catalog-hero">
        <h1>تصفح المنتجات</h1>
        <p>وصلة — شي إن SHEIN والتسوق العالمي مع توصيل لسوريا: دمشق وباقي المحافظات. بحث متقدم، فلاتر، ومقارنة بين المنتجات.</p>
      </header>

      <!-- Advanced search -->
      <div class="search-panel" @keydown.escape="closeSuggest">
        <form class="search-main" @submit.prevent="runSearch">
          <button
            v-if="hasActiveSearch"
            type="button"
            class="clear-search"
            title="مسح البحث والرجوع لكل المنتجات"
            aria-label="مسح البحث والرجوع لكل المنتجات"
            @click="clearSearch"
          >
            <span class="clear-arrow" aria-hidden="true">←</span>
            <span class="clear-text">رجوع</span>
          </button>
          <input
            ref="searchInputEl"
            v-model="searchInput"
            type="search"
            placeholder="ابحثي بالاسم، SKU، البراند، المتجر، التصنيف…"
            autocomplete="off"
            @input="onSearchInput"
            @focus="showSuggest = true"
            @search="onNativeSearchClear"
          />
          <button
            v-if="searchInput || filters.q"
            type="button"
            class="clear-x"
            title="حذف نص البحث"
            aria-label="حذف نص البحث"
            @click="clearSearch"
          >×</button>
          <button type="button" class="icon-btn" title="بحث صوتي" @click="startVoice" :disabled="voiceBusy">
            {{ voiceBusy ? '…' : 'Voice' }}
          </button>
          <button type="button" class="icon-btn" title="بحث بالصورة" @click="triggerImagePick">صورة</button>
          <button type="submit" class="go">بحث</button>
        </form>
        <input ref="imageInputRef" type="file" accept="image/*" class="sr-only" @change="onImagePicked" />

        <div v-if="showSuggest" class="suggest-box">
          <div v-if="suggestions.suggestions?.length" class="suggest-block">
            <h4>اقتراحات</h4>
            <button
              v-for="s in suggestions.suggestions"
              :key="s"
              type="button"
              class="suggest-row"
              @click="pickSuggestion(s)"
            >{{ s }}</button>
          </div>
          <div v-if="recentSearches.length" class="suggest-block">
            <div class="suggest-head">
              <h4>عمليات بحث أخيرة</h4>
              <button type="button" class="linkish" @click="clearRecent">حذف الكل</button>
            </div>
            <div v-for="r in recentSearches" :key="r" class="suggest-row between">
              <button type="button" @click="pickSuggestion(r)">{{ r }}</button>
              <button type="button" class="x" @click.stop="removeRecent(r)">×</button>
            </div>
          </div>
          <div v-if="trending.length" class="suggest-block">
            <h4>الأكثر بحثاً</h4>
            <button
              v-for="t in trending"
              :key="t.query"
              type="button"
              class="suggest-row"
              @click="pickSuggestion(t.query)"
            >{{ t.query }} <span class="hits">{{ t.hits }}</span></button>
          </div>
          <div v-if="suggestions.products?.length" class="suggest-block">
            <h4>منتجات</h4>
            <a
              v-for="p in suggestions.products"
              :key="p.id"
              class="suggest-row"
              :href="`/products/${p.id}`"
            >{{ p.name }}</a>
          </div>
        </div>
      </div>

      <!-- Sections -->
      <div class="section-chips">
        <button
          v-for="(label, key) in (facets.sections || defaultSections)"
          :key="key"
          type="button"
          class="chip"
          :class="{ active: filters.section === key || (!filters.section && key === 'all') }"
          @click="setSection(key)"
        >{{ label }}</button>
      </div>

      <div class="catalog-layout">
        <!-- Filters: sidebar on desktop, bottom sheet popup on mobile -->
        <div
          v-if="filtersOpen"
          class="filters-backdrop"
          @click="closeFilters"
        />
        <aside
          class="filters"
          :class="{ open: filtersOpen }"
          role="dialog"
          :aria-modal="filtersOpen ? 'true' : 'false'"
          aria-labelledby="filters-title"
        >
          <div class="filters-head">
            <div class="filters-head-text">
              <h2 id="filters-title">الفلاتر</h2>
              <p v-if="activeFilterCount" class="filters-count">{{ activeFilterCount }} مفعّل</p>
            </div>
            <div class="filters-head-actions">
              <button type="button" class="btn-clear" @click="resetFilters">مسح الكل</button>
              <button type="button" class="btn-close-filters" aria-label="إغلاق الفلاتر" @click="closeFilters">×</button>
            </div>
          </div>
          <div class="filters-body">

          <div class="filter-group" v-if="facets.categories?.length">
            <h3>التصنيف</h3>
            <button
              type="button"
              class="opt"
              :class="{ on: !filters.category_id }"
              @click="setFilter('category_id', '')"
            >الكل</button>
            <div v-for="cat in facets.categories" :key="cat.id" class="cat-block">
              <button type="button" class="opt" :class="{ on: String(filters.category_id) === String(cat.id) }" @click="setFilter('category_id', cat.id)">
                {{ cat.name }}
              </button>
              <button
                v-for="child in (cat.children || [])"
                :key="child.id"
                type="button"
                class="opt sub"
                :class="{ on: String(filters.subcategory_id) === String(child.id) || String(filters.category_id) === String(child.id) }"
                @click="setSubcategory(child)"
              >{{ child.name }}</button>
            </div>
          </div>

          <div class="filter-group" v-if="facets.collections?.length">
            <h3>المجموعات</h3>
            <button
              v-for="c in facets.collections"
              :key="c.id"
              type="button"
              class="opt"
              :class="{ on: String(filters.collection_id) === String(c.id) }"
              @click="setFilter('collection_id', c.id)"
            >{{ c.name }}</button>
          </div>

          <div class="filter-group" v-if="showFilter('price')">
            <h3>السعر</h3>
            <div class="price-row">
              <input v-model.number="filters.min_price" type="number" placeholder="من" @change="reload" />
              <input v-model.number="filters.max_price" type="number" placeholder="إلى" @change="reload" />
            </div>
          </div>

          <div class="filter-group" v-if="showFilter('brand') && facets.brands?.length">
            <h3>البراند</h3>
            <button
              v-for="b in facets.brands"
              :key="b.id"
              type="button"
              class="opt"
              :class="{ on: String(filters.brand_id) === String(b.id) }"
              @click="setFilter('brand_id', b.id)"
            >{{ b.name }} <span>{{ b.count }}</span></button>
          </div>

          <div class="filter-group" v-if="showFilter('store') && facets.stores?.length">
            <h3>المتجر</h3>
            <button
              v-for="s in facets.stores"
              :key="s.id"
              type="button"
              class="opt"
              :class="{ on: String(filters.store_id) === String(s.id) }"
              @click="setFilter('store_id', s.id)"
            >{{ s.name }}</button>
          </div>

          <div class="filter-group" v-if="showFilter('color') && facets.colors?.length">
            <h3>اللون</h3>
            <button
              v-for="c in facets.colors"
              :key="c.value"
              type="button"
              class="opt"
              :class="{ on: filters.color === c.value }"
              @click="setFilter('color', c.value)"
            >{{ c.value }}</button>
          </div>

          <div class="filter-group" v-if="showFilter('size') && facets.sizes?.length">
            <h3>المقاس</h3>
            <div class="size-grid">
              <button
                v-for="s in facets.sizes"
                :key="s.value"
                type="button"
                class="size"
                :class="{ on: filters.size === s.value }"
                @click="setFilter('size', s.value)"
              >{{ s.value }}</button>
            </div>
          </div>

          <div class="filter-group" v-if="showFilter('rating')">
            <h3>التقييم</h3>
            <button
              v-for="r in [4, 3, 2]"
              :key="r"
              type="button"
              class="opt"
              :class="{ on: Number(filters.rating) === r }"
              @click="setFilter('rating', r)"
            >{{ r }}+ نجوم</button>
          </div>

          <div class="filter-group" v-if="showFilter('discount')">
            <label class="check">
              <input type="checkbox" v-model="filters.discount" @change="reload" />
              عليه خصم
            </label>
          </div>

          <div class="filter-group" v-if="showFilter('availability')">
            <label class="check">
              <input type="checkbox" v-model="filters.in_stock" @change="reload" />
              متوفر فقط
            </label>
          </div>

          <div class="filter-group" v-if="showFilter('gender') && facets.genders">
            <h3>الجنس</h3>
            <button
              v-for="g in facets.genders"
              :key="g.value"
              type="button"
              class="opt"
              :class="{ on: filters.gender === g.value }"
              @click="setFilter('gender', g.value)"
            >{{ g.label }}</button>
          </div>

          <div class="filter-group" v-if="showFilter('fast_delivery')">
            <label class="check">
              <input type="checkbox" v-model="filters.fast_delivery" @change="reload" />
              توصيل سريع
            </label>
          </div>

          <template v-for="specKey in specFilterKeys" :key="specKey">
            <div
              v-if="showFilter(specKey) && (facets.specs?.[specKey]?.length || 0) > 0"
              class="filter-group"
            >
              <h3>{{ facets.labels?.[specKey] || specKey }}</h3>
              <button
                v-for="opt in (facets.specs?.[specKey] || [])"
                :key="opt.value"
                type="button"
                class="opt"
                :class="{ on: filters[specKey] === opt.value }"
                @click="setFilter(specKey, opt.value)"
              >{{ opt.value }}</button>
            </div>
          </template>

          <p v-if="facets.profile && facets.profile !== 'default'" class="profile-hint">
            فلاتر مخصّصة لتصنيف: {{ facets.profile }}
          </p>
          </div>
          <div class="filters-footer">
            <button type="button" class="btn-apply" @click="closeFilters">
              عرض النتائج ({{ meta.total ?? 0 }})
            </button>
          </div>
        </aside>

        <main class="results">
          <div class="results-toolbar">
            <button type="button" class="mobile-filters" @click="openFilters">
              فلاتر
              <span v-if="activeFilterCount" class="filter-badge">{{ activeFilterCount }}</span>
            </button>
            <p class="meta">{{ meta.total ?? 0 }} منتج</p>
            <select v-model="filters.sort" @change="reload">
              <option v-for="(label, key) in (facets.sorts || defaultSorts)" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>

          <p v-if="imageSearchActive" class="image-hint">
            نتائج البحث بالصورة
            <button type="button" class="linkish" @click="clearImageSearch">إلغاء</button>
          </p>
          <p v-if="imageSearchError" class="err">{{ imageSearchError }}</p>

          <div v-if="loading || imageSearching" class="loading">جاري التحميل…</div>
          <div v-else-if="!products.length" class="empty">
            <p>ما في نتائج بهالفلاتر.</p>
            <button type="button" class="linkish" @click="resetFilters">مسح الفلاتر</button>
          </div>
          <div v-else class="product-grid">
            <div v-for="product in products" :key="product.id" class="product-cell">
              <span v-if="imageSearchActive && product._match" class="match">{{ product._match }}</span>
              <ProductCard :product="product" :compare-enabled="true" />
            </div>
          </div>

          <div v-if="!imageSearchActive && meta.last_page > 1" class="pager">
            <button type="button" class="page" :disabled="page <= 1" @click="goPage(page - 1)">السابق</button>
            <span>{{ page }} / {{ meta.last_page }}</span>
            <button type="button" class="page" :disabled="page >= meta.last_page" @click="goPage(page + 1)">التالي</button>
          </div>
        </main>
      </div>
    </div>

    <div v-if="compareIds.length" class="compare-bar">
      <span>{{ compareIds.length }} للمقارنة</span>
      <a :href="compareHref" class="btn">قارن الآن</a>
      <button type="button" class="linkish" @click="clearCompareTray">مسح</button>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, reactive, ref, watch } from 'vue';
import api from '../../storefront/api';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';
import ProductCard from './ProductCard.vue';
import {
  getRecentSearches, pushRecentSearch, removeRecentSearch, clearRecentSearches,
} from '../../storefront/searchHistory';
import { getCompareIds, clearCompare, compareUrl } from '../../storefront/compareTray';

const params = new URLSearchParams(window.location.search);

const defaultSections = {
  all: 'كل المنتجات',
  new: 'الجديدة',
  bestsellers: 'الأكثر مبيعاً',
  most_viewed: 'الأكثر مشاهدة',
  top_rated: 'الأعلى تقييماً',
  offers: 'العروض',
  flash: 'Flash Sale',
  recommended: 'المقترحة',
};
const defaultSorts = {
  newest: 'الأحدث',
  bestsellers: 'الأكثر مبيعاً',
  most_viewed: 'الأكثر مشاهدة',
  top_rated: 'الأعلى تقييماً',
  price_asc: 'السعر ↑',
  price_desc: 'السعر ↓',
  discount: 'أكبر خصم',
  trending: 'الرائج',
};

const products = ref([]);
const facets = ref({});
const meta = ref({ total: 0, last_page: 1 });
const loading = ref(true);
const page = ref(Number(params.get('page') || 1));
const filtersOpen = ref(false);
const searchInput = ref(params.get('q') || '');
const showSuggest = ref(false);
const suggestions = ref({ suggestions: [], products: [] });
const recentSearches = ref(getRecentSearches());
const trending = ref([]);
const voiceBusy = ref(false);
const searchInputEl = ref(null);
const imageInputRef = ref(null);
const imageSearching = ref(false);
const imageSearchActive = ref(false);
const imageSearchError = ref('');
const compareIds = ref(getCompareIds());
const compareHref = computed(() => compareUrl());

const filters = reactive({
  q: params.get('q') || '',
  section: params.get('section') || 'all',
  sort: params.get('sort') || 'newest',
  category_id: params.get('category_id') || '',
  subcategory_id: params.get('subcategory_id') || '',
  brand_id: params.get('brand_id') || '',
  store_id: params.get('store_id') || params.get('vendor_id') || '',
  collection_id: params.get('collection_id') || '',
  min_price: params.get('min_price') ? Number(params.get('min_price')) : null,
  max_price: params.get('max_price') ? Number(params.get('max_price')) : null,
  color: params.get('color') || '',
  size: params.get('size') || '',
  rating: params.get('rating') || '',
  gender: params.get('gender') || '',
  material: params.get('material') || '',
  type: params.get('type') || '',
  occasion: params.get('occasion') || '',
  age: params.get('age') || '',
  fit: params.get('fit') || '',
  pattern: params.get('pattern') || '',
  length: params.get('length') || '',
  heel_height: params.get('heel_height') || '',
  style: params.get('style') || '',
  discount: params.get('discount') === '1' || params.get('on_sale') === '1',
  in_stock: params.get('in_stock') === '1',
  fast_delivery: params.get('fast_delivery') === '1',
});

const specFilterKeys = ['material', 'type', 'occasion', 'age', 'fit', 'pattern', 'length', 'heel_height', 'style'];

const hasActiveSearch = computed(() => Boolean(
  filters.q
  || searchInput.value.trim()
  || imageSearchActive.value
));

const activeFilterCount = computed(() => {
  let n = 0;
  if (filters.category_id) n += 1;
  if (filters.subcategory_id) n += 1;
  if (filters.brand_id) n += 1;
  if (filters.store_id) n += 1;
  if (filters.collection_id) n += 1;
  if (filters.min_price != null && filters.min_price !== '') n += 1;
  if (filters.max_price != null && filters.max_price !== '') n += 1;
  if (filters.color) n += 1;
  if (filters.size) n += 1;
  if (filters.rating) n += 1;
  if (filters.gender) n += 1;
  if (filters.discount) n += 1;
  if (filters.in_stock) n += 1;
  if (filters.fast_delivery) n += 1;
  specFilterKeys.forEach((k) => { if (filters[k]) n += 1; });
  return n;
});

function openFilters() {
  filtersOpen.value = true;
}

function closeFilters() {
  filtersOpen.value = false;
}

function onFiltersKeydown(e) {
  if (e.key === 'Escape') closeFilters();
}

watch(filtersOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : '';
});

function showFilter(key) {
  const available = facets.value.available_filters;
  if (!available || !available.length) return true;
  return available.includes(key);
}

function queryParams() {
  const p = { page: page.value, per_page: 16 };
  Object.entries(filters).forEach(([k, v]) => {
    if (v === '' || v === null || v === false) return;
    if (k === 'section' && v === 'all') return;
    if (k === 'discount') { p.discount = 1; return; }
    if (k === 'in_stock') { p.in_stock = 1; return; }
    if (k === 'fast_delivery') { p.fast_delivery = 1; return; }
    p[k] = v;
  });
  return p;
}

function syncUrl() {
  const p = new URLSearchParams();
  Object.entries(queryParams()).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') p.set(k, String(v));
  });
  const qs = p.toString();
  window.history.replaceState({}, '', `${window.location.pathname}${qs ? `?${qs}` : ''}`);
}

async function loadFacets() {
  const { data } = await api.get('/v1/catalog/facets', { params: queryParams() });
  facets.value = data;
}

async function loadProducts() {
  loading.value = true;
  try {
    const { data } = await api.get('/v1/catalog', { params: queryParams() });
    products.value = data.data || [];
    meta.value = data.meta || { total: 0, last_page: 1 };
  } finally {
    loading.value = false;
  }
}

async function reload() {
  syncUrl();
  await Promise.all([loadFacets(), loadProducts()]);
}

function setFilter(key, value) {
  filters[key] = String(filters[key]) === String(value) ? '' : value;
  page.value = 1;
  reload();
}

function setSubcategory(child) {
  filters.category_id = child.parent_id || filters.category_id;
  filters.subcategory_id = child.id;
  page.value = 1;
  reload();
}

function setSection(key) {
  filters.section = key;
  if (key === 'bestsellers') filters.sort = 'bestsellers';
  if (key === 'most_viewed') filters.sort = 'most_viewed';
  if (key === 'top_rated') filters.sort = 'top_rated';
  if (key === 'new') filters.sort = 'newest';
  if (key === 'flash' || key === 'offers') filters.sort = 'discount';
  page.value = 1;
  reload();
}

function resetFilters() {
  Object.keys(filters).forEach((k) => {
    if (typeof filters[k] === 'boolean') filters[k] = false;
    else if (k === 'sort') filters[k] = 'newest';
    else if (k === 'section') filters[k] = 'all';
    else filters[k] = k.includes('price') ? null : '';
  });
  searchInput.value = '';
  page.value = 1;
  clearImageSearch();
  reload();
}

function goPage(n) {
  page.value = n;
  reload();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

let suggestTimer = null;
let clearSearchTimer = null;

function clearSearch() {
  clearTimeout(suggestTimer);
  clearTimeout(clearSearchTimer);
  searchInput.value = '';
  filters.q = '';
  showSuggest.value = false;
  suggestions.value = { suggestions: [], products: [] };
  page.value = 1;
  imageSearchActive.value = false;
  imageSearchError.value = '';
  reload();
}

function onNativeSearchClear(e) {
  // Native type=search clear (×) fires search event with empty value
  if (!String(e?.target?.value || '').trim()) {
    clearSearch();
  }
}

function onSearchInput() {
  clearTimeout(suggestTimer);
  clearTimeout(clearSearchTimer);
  const q = searchInput.value.trim();

  // Clearing the field restores the previous catalog results
  if (!q) {
    suggestions.value = { suggestions: [], products: [] };
    if (filters.q || imageSearchActive.value) {
      clearSearchTimer = setTimeout(() => clearSearch(), 80);
    }
    return;
  }

  suggestTimer = setTimeout(async () => {
    const { data } = await api.get('/v1/catalog/suggestions', { params: { q } });
    suggestions.value = data;
    showSuggest.value = true;
  }, 220);
}

function runSearch() {
  filters.q = searchInput.value.trim();
  if (!filters.q) {
    clearSearch();
    return;
  }
  recentSearches.value = pushRecentSearch(filters.q);
  showSuggest.value = false;
  page.value = 1;
  reload();
}

function pickSuggestion(s) {
  searchInput.value = s;
  runSearch();
}

function removeRecent(r) {
  recentSearches.value = removeRecentSearch(r);
}

function clearRecent() {
  recentSearches.value = clearRecentSearches();
}

function closeSuggest() {
  showSuggest.value = false;
}

function startVoice() {
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SR) {
    alert('البحث الصوتي غير مدعوم بهذا المتصفح.');
    return;
  }
  const rec = new SR();
  rec.lang = 'ar-SY';
  voiceBusy.value = true;
  rec.onresult = (e) => {
    const text = e.results?.[0]?.[0]?.transcript || '';
    searchInput.value = text;
    voiceBusy.value = false;
    runSearch();
  };
  rec.onerror = () => { voiceBusy.value = false; };
  rec.onend = () => { voiceBusy.value = false; };
  rec.start();
}

function triggerImagePick() {
  imageInputRef.value?.click();
}

async function onImagePicked(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  imageSearchError.value = '';
  imageSearching.value = true;
  imageSearchActive.value = true;
  try {
    const fd = new FormData();
    fd.append('image', file);
    const { data } = await api.post('/v1/products/search-by-image', fd);
    products.value = (data.products || data.data || []).map((p) => ({
      ...p,
      _match: p.score ? `${Math.round(p.score * 100)}%` : null,
    }));
    meta.value = { total: products.value.length, last_page: 1 };
    if (data.message) imageSearchError.value = data.message;
  } catch (e) {
    imageSearchError.value = e.response?.data?.message || 'تعذر البحث بالصورة.';
  } finally {
    imageSearching.value = false;
    event.target.value = '';
  }
}

function clearImageSearch() {
  imageSearchActive.value = false;
  imageSearchError.value = '';
  reload();
}

function clearCompareTray() {
  compareIds.value = clearCompare();
}

function onCompareChanged(e) {
  compareIds.value = e.detail || getCompareIds();
}

function onDocClick(e) {
  if (!e.target.closest('.search-panel')) showSuggest.value = false;
}

onMounted(async () => {
  window.addEventListener('wasla:compare-changed', onCompareChanged);
  document.addEventListener('click', onDocClick);
  document.addEventListener('keydown', onFiltersKeydown);
  try {
    const { data } = await api.get('/v1/catalog/trending-searches');
    trending.value = data.data || [];
  } catch (_) { /* ignore */ }
  await reload();
});

onBeforeUnmount(() => {
  window.removeEventListener('wasla:compare-changed', onCompareChanged);
  document.removeEventListener('click', onDocClick);
  document.removeEventListener('keydown', onFiltersKeydown);
  document.body.style.overflow = '';
});
</script>

<style scoped>
.shop-page { min-height: 100vh; background: linear-gradient(180deg, #eaf6f8, #f7fbfc 30%, #fff); color: #132f37; }
.catalog-shell { max-width: 1200px; margin: 0 auto; padding: 1.5rem 1.25rem 5rem; }
.catalog-hero h1 { margin: 0; color: #0b3d44; font-size: 1.7rem; }
.catalog-hero p { margin: .35rem 0 1rem; color: #4d6b72; }
.search-panel { position: relative; margin-bottom: 1rem; }
.search-main {
  display: flex; gap: .4rem; background: #fff; border-radius: 999px;
  border: 1.5px solid rgba(28,114,130,.2); padding: .35rem;
  box-shadow: 0 10px 28px rgba(19,47,55,.05);
  align-items: center;
}
.search-main input {
  flex: 1; border: 0; outline: none; padding: .7rem 1rem; background: transparent; font: inherit; min-width: 0;
}
.clear-search {
  display: inline-flex;
  align-items: center;
  gap: .25rem;
  flex-shrink: 0;
  border: 0;
  border-radius: 999px;
  background: #eef7f8;
  color: #0b3d44;
  font-weight: 900;
  padding: .5rem .75rem;
  cursor: pointer;
  font-size: .85rem;
}
.clear-search:hover { background: #d8eef1; }
.clear-arrow {
  display: inline-flex;
  font-size: 1.05rem;
  line-height: 1;
  font-weight: 900;
}
.clear-x {
  flex-shrink: 0;
  width: 2rem;
  height: 2rem;
  border: 0;
  border-radius: 999px;
  background: #f0f4f5;
  color: #132f37;
  font-size: 1.25rem;
  font-weight: 800;
  line-height: 1;
  cursor: pointer;
}
.clear-x:hover { background: #e4ecee; }
.icon-btn, .go {
  border: 0; border-radius: 999px; padding: .55rem .9rem; font-weight: 800; cursor: pointer;
}
.icon-btn { background: #eef7f8; color: #1c7282; }
.go { background: #1c7282; color: #fff; }
.suggest-box {
  position: absolute; inset-inline: 0; top: calc(100% + .4rem); z-index: 20;
  background: #fff; border-radius: 1rem; border: 1px solid rgba(28,114,130,.15);
  box-shadow: 0 18px 40px rgba(19,47,55,.12); padding: .85rem; max-height: 420px; overflow: auto;
}
.suggest-block { margin-bottom: .75rem; }
.suggest-block h4 { margin: 0 0 .35rem; color: #1c7282; font-size: .85rem; }
.suggest-head { display: flex; justify-content: space-between; align-items: center; }
.suggest-row {
  display: block; width: 100%; text-align: right; border: 0; background: transparent;
  padding: .45rem .35rem; border-radius: .5rem; cursor: pointer; color: #132f37; text-decoration: none;
}
.suggest-row:hover { background: #f5fbfc; }
.suggest-row.between { display: flex; justify-content: space-between; align-items: center; }
.suggest-row .x { border: 0; background: transparent; cursor: pointer; color: #a82626; font-size: 1.1rem; }
.hits { color: #9aabaf; font-size: .8rem; margin-inline-start: .35rem; }
.section-chips { display: flex; gap: .45rem; flex-wrap: wrap; margin-bottom: 1rem; }
.chip {
  border: 1.5px solid rgba(28,114,130,.2); background: #fff; border-radius: 999px;
  padding: .4rem .85rem; font-weight: 800; cursor: pointer; color: #0b3d44;
}
.chip.active { background: #1c7282; color: #fff; border-color: #1c7282; }
.catalog-layout { display: grid; grid-template-columns: 280px 1fr; gap: 1.25rem; align-items: start; }
.filters-backdrop { display: none; }
.filters {
  background: #fff;
  border-radius: 1.15rem;
  border: 1.5px solid rgba(28,114,130,.18);
  box-shadow: 0 10px 28px rgba(19,47,55,.06);
  padding: 0;
  position: sticky;
  top: 5rem;
  max-height: calc(100vh - 6rem);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.filters-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: .75rem;
  padding: 1rem 1rem .75rem;
  border-bottom: 1px solid rgba(28,114,130,.12);
  background: #f3fafb;
}
.filters-head-text { display: flex; flex-direction: column; gap: .15rem; }
.filters h2 { margin: 0; font-size: 1.1rem; color: #0b3d44; }
.filters-count { margin: 0; font-size: .8rem; font-weight: 800; color: #1c7282; }
.filters-head-actions { display: flex; align-items: center; gap: .35rem; }
.btn-clear {
  border: 1.5px solid rgba(28,114,130,.25);
  background: #fff;
  color: #1c7282;
  border-radius: 999px;
  padding: .35rem .75rem;
  font-weight: 800;
  cursor: pointer;
  font-size: .82rem;
}
.btn-close-filters {
  display: none;
  width: 2.2rem;
  height: 2.2rem;
  border: 0;
  border-radius: 999px;
  background: #132f37;
  color: #fff;
  font-size: 1.4rem;
  line-height: 1;
  cursor: pointer;
  font-weight: 700;
}
.filters-body { padding: .85rem 1rem 1rem; overflow: auto; flex: 1; min-height: 0; }
.filters-footer { display: none; }
.filter-group {
  margin: 0 0 .85rem;
  padding: .75rem;
  border-radius: .85rem;
  background: #f8fcfd;
  border: 1px solid rgba(28,114,130,.1);
}
.filter-group h3 {
  margin: 0 0 .55rem;
  font-size: .9rem;
  color: #0b3d44;
  font-weight: 900;
}
.opt {
  display: flex;
  justify-content: space-between;
  width: 100%;
  text-align: right;
  border: 1.5px solid transparent;
  background: #fff;
  padding: .5rem .65rem;
  cursor: pointer;
  border-radius: .6rem;
  margin-bottom: .3rem;
  color: #132f37;
  font-weight: 700;
}
.opt:hover { border-color: rgba(28,114,130,.25); }
.opt.on {
  background: #e7f6f8;
  color: #0b3d44;
  border-color: #1c7282;
  font-weight: 900;
}
.opt.sub { padding-inline-start: 1.1rem; font-size: .9rem; color: #4d6b72; }
.price-row { display: grid; grid-template-columns: 1fr 1fr; gap: .4rem; }
.price-row input {
  width: 100%; box-sizing: border-box; border: 1.5px solid rgba(28,114,130,.28);
  border-radius: .65rem; padding: .55rem .6rem; background: #fff; font-weight: 700;
}
.size-grid { display: flex; flex-wrap: wrap; gap: .4rem; }
.size {
  border: 1.5px solid rgba(28,114,130,.28); background: #fff; border-radius: .55rem;
  padding: .4rem .65rem; cursor: pointer; font-weight: 800;
}
.size.on { background: #1c7282; color: #fff; border-color: #1c7282; }
.check {
  display: flex; gap: .55rem; align-items: center; font-weight: 800; cursor: pointer;
  padding: .35rem .15rem;
}
.check input { width: 1.05rem; height: 1.05rem; accent-color: #1c7282; }
.profile-hint { font-size: .8rem; color: #4d6b72; margin: .35rem 0 0; font-weight: 700; }
.results-toolbar { display: flex; gap: .75rem; align-items: center; margin-bottom: .85rem; flex-wrap: wrap; }
.meta { margin: 0; color: #4d6b72; font-weight: 700; flex: 1; }
.results-toolbar select {
  border: 1.5px solid rgba(28,114,130,.22); border-radius: 999px; padding: .45rem .8rem; background: #fff;
}
.mobile-filters {
  display: none;
  position: relative;
  border: 0;
  background: #1c7282;
  color: #fff;
  border-radius: 999px;
  padding: .55rem 1rem;
  font-weight: 900;
  cursor: pointer;
  align-items: center;
  gap: .4rem;
}
.filter-badge {
  background: #fff;
  color: #1c7282;
  border-radius: 999px;
  min-width: 1.25rem;
  height: 1.25rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: .75rem;
  font-weight: 900;
  padding: 0 .3rem;
}
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
.product-cell { position: relative; }
.match {
  position: absolute; z-index: 2; top: .5rem; inset-inline-start: .5rem;
  background: #1c7282; color: #fff; border-radius: 999px; padding: .15rem .45rem; font-size: .72rem; font-weight: 800;
}
.loading, .empty { text-align: center; padding: 3rem 1rem; color: #4d6b72; }
.pager { display: flex; justify-content: center; gap: .75rem; align-items: center; margin-top: 1.5rem; }
.page {
  border: 0; background: #1c7282; color: #fff; border-radius: 999px; padding: .45rem 1rem; font-weight: 800; cursor: pointer;
}
.page:disabled { opacity: .45; }
.compare-bar {
  position: fixed; bottom: 1rem; inset-inline: 1rem; max-width: 520px; margin: 0 auto;
  background: #132f37; color: #fff; border-radius: 999px; padding: .65rem 1rem;
  display: flex; gap: .75rem; align-items: center; justify-content: center; z-index: 40;
  box-shadow: 0 12px 30px rgba(0,0,0,.2);
}
.compare-bar .btn {
  background: #1c7282; color: #fff; text-decoration: none; border-radius: 999px;
  padding: .4rem .9rem; font-weight: 800;
}
.linkish { border: 0; background: transparent; color: #1c7282; font-weight: 800; cursor: pointer; }
.compare-bar .linkish { color: #b7e2e8; }
.err { color: #a82626; font-weight: 700; }
.image-hint { color: #1c7282; font-weight: 700; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }
@media (max-width: 900px) {
  .catalog-shell { padding: 1rem 0.85rem 4.5rem; }
  .catalog-hero h1 { font-size: 1.35rem; }
  .catalog-hero p { font-size: 0.92rem; }
  .search-main {
    flex-wrap: wrap;
    border-radius: 1rem;
    padding: 0.45rem;
  }
  .search-main input {
    flex: 1 1 calc(100% - 3rem);
    padding: 0.65rem 0.75rem;
    order: 2;
  }
  .clear-search {
    order: 1;
    flex: 1 1 auto;
    justify-content: center;
    min-height: 2.6rem;
  }
  .clear-x { order: 2; }
  .icon-btn, .go {
    flex: 1 1 auto;
    min-height: 2.6rem;
    order: 3;
  }
  .section-chips {
    flex-wrap: nowrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 0.25rem;
    margin-inline: -0.25rem;
    padding-inline: 0.25rem;
    scrollbar-width: thin;
  }
  .chip { flex: 0 0 auto; white-space: nowrap; }
  .catalog-layout { grid-template-columns: 1fr; }
  .filters-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(11, 40, 46, 0.48);
    z-index: 70;
  }
  .filters {
    display: none;
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    top: auto;
    width: 100%;
    max-width: none;
    max-height: min(88vh, 720px);
    margin: 0;
    border-radius: 1.35rem 1.35rem 0 0;
    border: 0;
    box-shadow: 0 -16px 40px rgba(0, 0, 0, 0.22);
    z-index: 80;
    padding: 0;
  }
  .filters.open { display: flex; }
  .btn-close-filters { display: inline-flex; align-items: center; justify-content: center; }
  .filters-footer {
    display: block;
    padding: .75rem 1rem calc(.9rem + env(safe-area-inset-bottom, 0px));
    border-top: 1px solid rgba(28,114,130,.14);
    background: #fff;
  }
  .btn-apply {
    width: 100%;
    border: 0;
    background: #1c7282;
    color: #fff;
    border-radius: 999px;
    padding: .85rem 1rem;
    font-weight: 900;
    font-size: 1rem;
    cursor: pointer;
  }
  .mobile-filters { display: inline-flex; }
  .product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
  .compare-bar {
    inset-inline: 0.65rem;
    bottom: 0.75rem;
    border-radius: 1rem;
    flex-wrap: wrap;
    padding: 0.7rem 0.85rem;
  }
}
@media (max-width: 420px) {
  .product-grid { grid-template-columns: 1fr; }
}
</style>
