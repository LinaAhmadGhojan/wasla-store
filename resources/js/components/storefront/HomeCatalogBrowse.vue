<template>
  <section class="home-catalog" aria-labelledby="wasla-products-title">
    <h2 id="wasla-products-title" class="section-mini-title">منتجات وصلة</h2>

    <div class="section-chips desktop-only">
      <button
        v-for="(label, key) in (facets.sections || defaultSections)"
        :key="key"
        type="button"
        class="chip"
        :class="{ active: filters.section === key || (!filters.section && key === 'all') }"
        @click="setSection(key)"
      >{{ label }}</button>
    </div>

    <div class="mobile-strip mobile-only">
      <div class="mobile-strip-scroll" role="tablist" aria-label="فلترة سريعة">
        <button
          v-for="option in mobileQuickTabs"
          :key="option.key"
          type="button"
          class="mobile-section-tab"
          :class="{ active: mobileQuickTab === option.key }"
          @click="setMobileQuick(option.key)"
        >{{ option.label }}</button>
      </div>
      <button
        type="button"
        class="mobile-filter-cta"
        aria-label="فتح فلاتر التصنيف والسعر"
        @click="openFilters"
      >
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M4 6h16M7 12h10M10 18h4" />
        </svg>
        <span>فلاتر</span>
        <span v-if="activeFilterCount" class="filter-dot">{{ activeFilterCount }}</span>
      </button>
    </div>

    <div v-if="filtersOpen" class="filters-backdrop mobile-only" @click="closeFilters" />

    <div class="catalog-layout">
      <aside
        class="filters"
        :class="{ open: filtersOpen }"
        role="dialog"
        :aria-modal="filtersOpen ? 'true' : 'false'"
        aria-labelledby="home-filters-title"
      >
        <div class="filters-head">
          <div class="filters-head-text">
            <h2 id="home-filters-title">فلاتر المنتجات</h2>
            <p class="filters-hint mobile-only">صنف · سعر · براند · مقاس · ترتيب</p>
            <p v-if="activeFilterCount" class="filters-count">{{ activeFilterCount }} مفعّل</p>
          </div>
          <div class="filters-head-actions">
            <button type="button" class="btn-clear" @click="resetFilters">مسح الكل</button>
            <button type="button" class="btn-close-filters mobile-only" aria-label="إغلاق" @click="closeFilters">×</button>
          </div>
        </div>
        <div class="filters-body">
          <div class="filter-group mobile-only">
            <h3>ترتيب النتائج</h3>
            <select v-model="filters.sort" class="sort-select" @change="reload">
              <option v-for="(label, key) in (facets.sorts || defaultSorts)" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>

          <div v-if="facets.categories?.length" class="filter-group">
            <h3>التصنيف</h3>
            <button type="button" class="opt" :class="{ on: !filters.category_id }" @click="setFilter('category_id', '')">الكل</button>
            <div v-for="cat in facets.categories" :key="cat.id" class="cat-block">
              <button type="button" class="opt" :class="{ on: String(filters.category_id) === String(cat.id) }" @click="setFilter('category_id', cat.id)">{{ cat.name }}</button>
              <button
                v-for="child in (cat.children || [])"
                :key="child.id"
                type="button"
                class="opt sub"
                :class="{ on: String(filters.subcategory_id) === String(child.id) }"
                @click="setSubcategory(child)"
              >{{ child.name }}</button>
            </div>
          </div>

          <div v-if="showFilter('price')" class="filter-group">
            <h3>السعر</h3>
            <div class="price-row">
              <input v-model.number="filters.min_price" type="number" placeholder="من" @change="reload" />
              <input v-model.number="filters.max_price" type="number" placeholder="إلى" @change="reload" />
            </div>
          </div>

          <div v-if="showFilter('brand') && facets.brands?.length" class="filter-group">
            <h3>البراند</h3>
            <button
              v-for="b in facets.brands"
              :key="b.id"
              type="button"
              class="opt"
              :class="{ on: String(filters.brand_id) === String(b.id) }"
              @click="setFilter('brand_id', b.id)"
            >{{ b.name }}</button>
          </div>

          <div v-if="showFilter('color') && facets.colors?.length" class="filter-group">
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

          <div v-if="showFilter('size') && facets.sizes?.length" class="filter-group">
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

          <div v-if="showFilter('discount')" class="filter-group">
            <label class="check">
              <input v-model="filters.discount" type="checkbox" @change="reload" />
              عليه خصم
            </label>
          </div>

          <div v-if="showFilter('availability')" class="filter-group">
            <label class="check">
              <input v-model="filters.in_stock" type="checkbox" @change="reload" />
              متوفر فقط
            </label>
          </div>
        </div>
        <div class="filters-footer mobile-only">
          <button type="button" class="btn-apply" @click="closeFilters">عرض النتائج ({{ meta.total ?? 0 }})</button>
        </div>
      </aside>

      <div class="results">
        <div class="results-toolbar desktop-only">
          <p class="meta">{{ meta.total ?? 0 }} منتج</p>
          <select v-model="filters.sort" @change="reload">
            <option v-for="(label, key) in (facets.sorts || defaultSorts)" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>

        <div v-if="loading" class="loading-row">جاري تحميل المنتجات...</div>
        <div v-else-if="!displayProducts.length" class="empty-row">
          <p>ما في نتائج بهالفلاتر.</p>
          <button v-if="mobileQuickTab === 'favorites'" type="button" class="linkish" @click="resetFilters">عرض الكل</button>
        </div>
        <div v-else class="product-grid">
          <ProductCard v-for="product in displayProducts" :key="product.id" :product="product" />
        </div>

        <div v-if="mobileQuickTab !== 'favorites' && meta.last_page > 1" class="pager desktop-only">
          <button type="button" class="page" :disabled="page <= 1" @click="goPage(page - 1)">السابق</button>
          <span>{{ page }} / {{ meta.last_page }}</span>
          <button type="button" class="page" :disabled="page >= meta.last_page" @click="goPage(page + 1)">التالي</button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import api from '../../storefront/api';
import ProductCard from './ProductCard.vue';

const defaultSections = {
  all: 'كل المنتجات',
  new: 'المنتجات الجديدة',
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
};

const mobileQuickTabs = [
  { key: 'all', label: 'الكل', section: 'all' },
  { key: 'new', label: 'وصل حديثاً', section: 'new' },
  { key: 'popular', label: 'الأكثر مبيعاً', section: 'bestsellers' },
  { key: 'favorites', label: 'المفضلة', section: 'favorites' },
];

const specFilterKeys = ['material', 'type', 'occasion', 'age', 'fit', 'pattern', 'length', 'heel_height', 'style'];

const products = ref([]);
const favoriteProducts = ref([]);
const facets = ref({});
const meta = ref({ total: 0, last_page: 1 });
const loading = ref(true);
const filtersOpen = ref(false);
const mobileQuickTab = ref('all');
const urlParams = new URLSearchParams(window.location.search);
const page = ref(Number(urlParams.get('page') || 1));

const filters = reactive({
  q: urlParams.get('q') || '',
  section: urlParams.get('section') || 'all',
  sort: urlParams.get('sort') || 'newest',
  category_id: urlParams.get('category_id') || '',
  subcategory_id: urlParams.get('subcategory_id') || '',
  brand_id: urlParams.get('brand_id') || '',
  store_id: urlParams.get('store_id') || urlParams.get('vendor_id') || '',
  collection_id: urlParams.get('collection_id') || '',
  min_price: urlParams.get('min_price') ? Number(urlParams.get('min_price')) : null,
  max_price: urlParams.get('max_price') ? Number(urlParams.get('max_price')) : null,
  color: urlParams.get('color') || '',
  size: urlParams.get('size') || '',
  rating: urlParams.get('rating') || '',
  gender: urlParams.get('gender') || '',
  discount: urlParams.get('discount') === '1' || urlParams.get('on_sale') === '1',
  in_stock: urlParams.get('in_stock') === '1',
  fast_delivery: urlParams.get('fast_delivery') === '1',
});

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
  if (filters.discount) n += 1;
  if (filters.in_stock) n += 1;
  if (filters.fast_delivery) n += 1;
  specFilterKeys.forEach((k) => { if (filters[k]) n += 1; });
  return n;
});

const displayProducts = computed(() => {
  if (mobileQuickTab.value === 'favorites') return favoriteProducts.value;
  return products.value;
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

async function loadFavorites() {
  try {
    const { data } = await api.get('/v1/home');
    favoriteProducts.value = data.sections?.find((s) => s.key === 'favorites')?.products || [];
  } catch (_) {
    favoriteProducts.value = [];
  }
}

function syncUrl() {
  const p = new URLSearchParams();
  Object.entries(queryParams()).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') p.set(k, String(v));
  });
  const qs = p.toString();
  window.history.replaceState({}, '', `${window.location.pathname}${qs ? `?${qs}` : ''}`);
}

async function reload() {
  if (mobileQuickTab.value !== 'favorites') {
    syncUrl();
    await Promise.all([loadFacets(), loadProducts()]);
  }
}

function setFilter(key, value) {
  filters[key] = String(filters[key]) === String(value) ? '' : value;
  page.value = 1;
  mobileQuickTab.value = 'all';
  filters.section = 'all';
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
  mobileQuickTab.value = key === 'bestsellers' ? 'popular' : (key === 'new' ? 'new' : 'all');
  if (key === 'bestsellers') filters.sort = 'bestsellers';
  if (key === 'most_viewed') filters.sort = 'most_viewed';
  if (key === 'top_rated') filters.sort = 'top_rated';
  if (key === 'new') filters.sort = 'newest';
  page.value = 1;
  reload();
}

function setMobileQuick(key) {
  mobileQuickTab.value = key;
  const tab = mobileQuickTabs.find((t) => t.key === key);
  if (key === 'favorites') {
    loadFavorites();
    return;
  }
  if (tab?.section) {
    filters.section = tab.section === 'favorites' ? 'all' : tab.section;
    if (tab.section === 'bestsellers') filters.sort = 'bestsellers';
    if (tab.section === 'new') filters.sort = 'newest';
    if (tab.section === 'all') filters.sort = 'newest';
    page.value = 1;
    reload();
  }
}

function goPage(n) {
  page.value = n;
  reload();
  document.getElementById('wasla-products-title')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetFilters() {
  Object.keys(filters).forEach((k) => {
    if (typeof filters[k] === 'boolean') filters[k] = false;
    else if (k === 'sort') filters[k] = 'newest';
    else if (k === 'section') filters[k] = 'all';
    else filters[k] = k.includes('price') ? null : '';
  });
  mobileQuickTab.value = 'all';
  page.value = 1;
  reload();
}

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

onMounted(async () => {
  await Promise.all([reload(), loadFavorites()]);
  window.addEventListener('keydown', onFiltersKeydown);
});

onBeforeUnmount(() => {
  document.body.style.overflow = '';
  window.removeEventListener('keydown', onFiltersKeydown);
});
</script>

<style scoped>
.section-mini-title {
  margin: 0 0 0.55rem;
  font-size: 0.82rem;
  font-weight: 900;
  color: #5a7278;
}
.section-chips.desktop-only,
.results-toolbar.desktop-only {
  display: none !important;
}
.mobile-only { display: block; }

.section-chips {
  display: flex;
  gap: 0.45rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}
.chip {
  border: 1.5px solid rgba(28, 114, 130, 0.2);
  background: #fff;
  border-radius: 999px;
  padding: 0.4rem 0.85rem;
  font-weight: 800;
  cursor: pointer;
  color: #0b3d44;
  font-size: 0.82rem;
}
.chip.active {
  background: #087b8d;
  color: #fff;
  border-color: #087b8d;
}

.mobile-strip {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 0.65rem;
  min-height: 2.35rem;
}
.mobile-strip-scroll {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex: 1;
  min-width: 0;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  padding-bottom: 0.1rem;
}
.mobile-strip-scroll::-webkit-scrollbar { display: none; }
.mobile-section-tab {
  flex: 0 0 auto;
  border: 1px solid #d9edf1;
  background: #f6fbfc;
  color: #0d5b68;
  border-radius: 999px;
  padding: 0.45rem 0.8rem;
  font-weight: 800;
  font-size: 0.72rem;
  white-space: nowrap;
  cursor: pointer;
}
.mobile-section-tab.active {
  background: #087b8d;
  border-color: #087b8d;
  color: #fff;
}
.mobile-filter-cta {
  position: relative;
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.28rem;
  height: 2.15rem;
  padding: 0 0.65rem;
  border: 0;
  border-radius: 999px;
  background: #087b8d;
  color: #fff;
  font-weight: 900;
  font-size: 0.72rem;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(8, 123, 141, 0.28);
}
.mobile-filter-cta svg {
  width: 1.05rem;
  height: 1.05rem;
  fill: none;
  stroke: currentColor;
  stroke-width: 2.2;
  stroke-linecap: round;
}
.mobile-filter-cta .filter-dot {
  position: static;
  min-width: 1.05rem;
  height: 1.05rem;
  padding: 0 0.22rem;
  border-radius: 999px;
  background: #fff;
  color: #087b8d;
  font-size: 0.58rem;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.filters-hint {
  margin: 0;
  font-size: 0.72rem;
  font-weight: 700;
  color: #4d6b72;
  line-height: 1.35;
}
.sort-select {
  width: 100%;
  box-sizing: border-box;
  border: 1.5px solid rgba(28, 114, 130, 0.28);
  border-radius: 0.65rem;
  padding: 0.55rem 0.65rem;
  background: #fff;
  font-weight: 800;
  color: #132f37;
}

.catalog-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  align-items: start;
}
.filters-backdrop { display: none; }
.filters {
  background: #fff;
  border-radius: 1.15rem;
  border: 1.5px solid rgba(28, 114, 130, 0.18);
  box-shadow: 0 10px 28px rgba(19, 47, 55, 0.06);
  padding: 0;
  display: none;
  flex-direction: column;
  overflow: hidden;
}
.filters-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1rem 0.75rem;
  border-bottom: 1px solid rgba(28, 114, 130, 0.12);
  background: #f3fafb;
}
.filters h2 { margin: 0; font-size: 1.1rem; color: #0b3d44; }
.filters-count { margin: 0; font-size: 0.8rem; font-weight: 800; color: #1c7282; }
.filters-head-actions { display: flex; align-items: center; gap: 0.35rem; }
.btn-clear {
  border: 1.5px solid rgba(28, 114, 130, 0.25);
  background: #fff;
  color: #1c7282;
  border-radius: 999px;
  padding: 0.35rem 0.75rem;
  font-weight: 800;
  cursor: pointer;
  font-size: 0.82rem;
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
  cursor: pointer;
}
.filters-body { padding: 0.85rem 1rem 1rem; overflow: auto; flex: 1; min-height: 0; max-height: 50vh; }
.filters-footer { display: none; }
.filter-group {
  margin: 0 0 0.85rem;
  padding: 0.75rem;
  border-radius: 0.85rem;
  background: #f8fcfd;
  border: 1px solid rgba(28, 114, 130, 0.1);
}
.filter-group h3 { margin: 0 0 0.55rem; font-size: 0.9rem; color: #0b3d44; font-weight: 900; }
.opt {
  display: block;
  width: 100%;
  text-align: right;
  border: 1.5px solid transparent;
  background: #fff;
  padding: 0.5rem 0.65rem;
  cursor: pointer;
  border-radius: 0.6rem;
  margin-bottom: 0.3rem;
  color: #132f37;
  font-weight: 700;
}
.opt.on { background: #e7f6f8; border-color: #1c7282; font-weight: 900; }
.opt.sub { padding-inline-start: 1.1rem; font-size: 0.9rem; color: #4d6b72; }
.price-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem; }
.price-row input {
  width: 100%;
  box-sizing: border-box;
  border: 1.5px solid rgba(28, 114, 130, 0.28);
  border-radius: 0.65rem;
  padding: 0.55rem 0.6rem;
}
.size-grid { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.size {
  border: 1.5px solid rgba(28, 114, 130, 0.28);
  background: #fff;
  border-radius: 0.55rem;
  padding: 0.4rem 0.65rem;
  cursor: pointer;
  font-weight: 800;
}
.size.on { background: #1c7282; color: #fff; }
.check { display: flex; gap: 0.55rem; align-items: center; font-weight: 800; cursor: pointer; }

.results-toolbar {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  margin-bottom: 0.85rem;
}
.meta { margin: 0; color: #4d6b72; font-weight: 700; flex: 1; }
.results-toolbar select {
  border: 1.5px solid rgba(28, 114, 130, 0.22);
  border-radius: 999px;
  padding: 0.45rem 0.8rem;
  background: #fff;
}
.product-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.65rem;
}
.loading-row, .empty-row {
  text-align: center;
  padding: 2rem 0.5rem;
  color: #4d6b72;
  font-weight: 700;
}
.pager {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1.25rem;
  font-weight: 800;
  color: #4d6b72;
}
.page {
  border: 1.5px solid rgba(28, 114, 130, 0.25);
  background: #fff;
  color: #087b8d;
  border-radius: 999px;
  padding: 0.45rem 1rem;
  font-weight: 900;
  cursor: pointer;
}
.page:disabled { opacity: 0.45; cursor: not-allowed; }
.linkish {
  border: 0;
  background: transparent;
  color: #087b8d;
  font-weight: 900;
  cursor: pointer;
}
.btn-apply {
  width: 100%;
  border: 0;
  background: #087b8d;
  color: #fff;
  border-radius: 999px;
  padding: 0.85rem 1rem;
  font-weight: 900;
  cursor: pointer;
}

@media (min-width: 1024px) {
  .section-chips.desktop-only,
  .results-toolbar.desktop-only {
    display: flex !important;
  }
  .mobile-only { display: none !important; }
  .catalog-layout {
    grid-template-columns: 280px 1fr;
  }
  .filters {
    display: flex;
    position: sticky;
    top: 5rem;
    max-height: calc(100vh - 6rem);
  }
  .filters-body { max-height: none; }
  .product-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.85rem;
  }
}

@media (max-width: 1023px) {
  .filters-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(11, 40, 46, 0.48);
    z-index: 70;
  }
  .filters.open {
    display: flex;
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    top: auto;
    max-height: min(88vh, 720px);
    border-radius: 1.35rem 1.35rem 0 0;
    z-index: 80;
    margin: 0 auto;
    max-width: 760px;
  }
  .btn-close-filters { display: inline-flex; align-items: center; justify-content: center; }
  .filters-footer { display: block; padding: 0.75rem 1rem calc(0.9rem + env(safe-area-inset-bottom, 0px)); border-top: 1px solid rgba(28, 114, 130, 0.14); }
}
</style>
