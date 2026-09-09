<template>
  <div class="browse-page">
    <StorefrontNav />

    <section class="browse-bar" v-if="platformMeta">
      <div class="browse-bar-inner">
        <a href="/browse" class="back">← المنصات</a>
        <div class="platform-title">
          <img v-if="platformMeta.logo" :src="platformMeta.logo" :alt="platformMeta.name" />
          <div>
            <h1>{{ platformMeta.name }}</h1>
            <p>تصفّحي الأقسام والمنتجات داخل وصلة · الأسعار بالليرة السورية</p>
          </div>
        </div>
        <a href="/buy-from-anywhere" class="paste-link">أو الصقي رابط</a>
      </div>
    </section>

    <div class="browse-shell">
      <aside class="cats">
        <h3>الأقسام</h3>
        <button
          type="button"
          class="cat-btn"
          :class="{ active: !selectedCategory }"
          @click="selectCategory(null)"
        >
          الكل
        </button>
        <div v-for="cat in categories" :key="cat.id" class="cat-group">
          <button
            type="button"
            class="cat-btn"
            :class="{ active: selectedCategory === cat.id }"
            @click="selectCategory(cat.id)"
          >
            {{ cat.name_ar || cat.name }}
          </button>
          <button
            v-for="child in cat.children || []"
            :key="child.id"
            type="button"
            class="cat-btn child"
            :class="{ active: selectedCategory === child.id }"
            @click="selectCategory(child.id)"
          >
            {{ child.name_ar || child.name }}
          </button>
        </div>
      </aside>

      <main class="main">
        <form class="search-row" @submit.prevent="runSearch">
          <input v-model="searchInput" type="search" placeholder="ابحثي داخل المنصة..." />
          <button type="submit" class="btn btn-primary">بحث</button>
        </form>

        <div class="demo-banner">
          <p>
            ⚠️ المنتجات أدناه أمثلة تجريبية للتصفح داخل وصلة وليست بيانات حقيقية من {{ platformMeta?.name || 'المنصة' }} —
            روابطها تفتح بحث حقيقي على الموقع الأصلي، لكنها ليست نفس المنتج بالضبط.
          </p>
          <a
            :href="realSearchUrl"
            target="_blank"
            rel="noopener"
            class="btn btn-secondary real-search-btn"
          >
            🔎 بحث حقيقي على {{ platformMeta?.name || 'الموقع الأصلي' }} ↗
          </a>
          <p class="tip">
            لقيتي المنتج المطلوب؟ انسخي رابطه وارجعي <a href="/buy-from-anywhere">لصفحة "اطلبي من أي موقع"</a> لإضافته الفعلية للسلة.
          </p>
        </div>

        <p class="meta" v-if="!loading">
          {{ meta.total ?? products.length }} منتج تجريبي
          <span v-if="query"> لـ "{{ query }}"</span>
        </p>

        <div v-if="loading" class="loading-row">جاري التحميل...</div>
        <div v-else-if="error" class="empty-state error">{{ error }}</div>
        <div v-else-if="!products.length" class="empty-state">ما في منتجات بهالقسم حالياً.</div>
        <div v-else class="product-grid">
          <a
            v-for="product in products"
            :key="product.id"
            :href="`/browse/${platformSlug}/product/${product.id}`"
            class="product-card"
          >
            <div class="thumb">
              <span v-if="product.is_demo" class="demo-badge">تجريبي</span>
              <img :src="product.image" :alt="product.name" loading="lazy" />
            </div>
            <div class="body">
              <h3>{{ product.name_ar || product.name }}</h3>
              <p class="price">{{ money(product.price) }}</p>
              <p class="rating" v-if="product.rating">★ {{ product.rating }}</p>
            </div>
          </a>
        </div>

        <div v-if="meta.last_page > 1" class="pagination">
          <button
            type="button"
            class="page-btn"
            :disabled="page <= 1"
            @click="goPage(page - 1)"
          >
            السابق
          </button>
          <span>{{ page }} / {{ meta.last_page }}</span>
          <button
            type="button"
            class="page-btn"
            :disabled="page >= meta.last_page"
            @click="goPage(page + 1)"
          >
            التالي
          </button>
        </div>
      </main>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../storefront/api';
import { money } from '../../storefront/format';
import { buildPlatformSearchUrl } from '../../storefront/platformSearch';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const props = defineProps({
  platform: { type: String, required: true },
});

const platformSlug = props.platform;
const platformMeta = ref(null);
const categories = ref([]);
const products = ref([]);
const meta = ref({ page: 1, last_page: 1, total: 0 });
const selectedCategory = ref(null);
const searchInput = ref('');
const query = ref('');
const page = ref(1);
const loading = ref(true);
const error = ref('');

const realSearchUrl = computed(() => buildPlatformSearchUrl(platformSlug, query.value || searchInput.value));

async function loadCategories() {
  const { data } = await api.get(`/v1/browse/${platformSlug}/categories`);
  platformMeta.value = data.platform;
  categories.value = data.categories || [];
}

async function loadProducts() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get(`/v1/browse/${platformSlug}/products`, {
      params: {
        category: selectedCategory.value || undefined,
        q: query.value || undefined,
        page: page.value,
      },
    });
    platformMeta.value = data.platform;
    products.value = data.products || [];
    meta.value = data.meta || { page: 1, last_page: 1, total: 0 };
  } catch (e) {
    products.value = [];
    error.value = e.response?.data?.message || 'تعذر تحميل المنتجات';
  } finally {
    loading.value = false;
  }
}

function selectCategory(id) {
  selectedCategory.value = id;
  page.value = 1;
  loadProducts();
}

function runSearch() {
  query.value = searchInput.value.trim();
  page.value = 1;
  loadProducts();
}

function goPage(next) {
  page.value = next;
  loadProducts();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

onMounted(async () => {
  try {
    await loadCategories();
  } catch (e) {
    error.value = e.response?.data?.message || 'منصة غير متاحة';
  }
  await loadProducts();
});
</script>

<style scoped>
.browse-bar {
  background: #1c7282;
  color: #fff;
  padding: 1.25rem 1.5rem;
}
.browse-bar-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  flex-wrap: wrap;
}
.back, .paste-link {
  color: #c9eef2;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
}
.paste-link { margin-inline-start: auto; }
.platform-title {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}
.platform-title img {
  width: 3.5rem;
  height: 2rem;
  object-fit: contain;
  background: #fff;
  border-radius: 0.5rem;
  padding: 0.25rem 0.4rem;
}
.platform-title h1 {
  margin: 0;
  font-size: 1.35rem;
}
.platform-title p {
  margin: 0.15rem 0 0;
  opacity: 0.85;
  font-size: 0.85rem;
}
.browse-shell {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1.5rem;
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 1.5rem;
}
.cats h3 {
  margin: 0 0 0.75rem;
  color: #1c7282;
}
.cat-btn {
  display: block;
  width: 100%;
  text-align: start;
  border: none;
  background: transparent;
  padding: 0.45rem 0.6rem;
  border-radius: 0.5rem;
  cursor: pointer;
  color: #132f37;
  font-weight: 600;
}
.cat-btn.child {
  padding-inline-start: 1.1rem;
  font-weight: 500;
  color: #4d6b72;
  font-size: 0.9rem;
}
.cat-btn.active, .cat-btn:hover {
  background: rgba(15, 90, 107, 0.08);
  color: #1c7282;
}
.search-row {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.search-row input {
  flex: 1;
  border: 1px solid rgba(15, 90, 107, 0.18);
  border-radius: 999px;
  padding: 0.65rem 1rem;
  outline: none;
}
.demo-banner {
  background: #fff8ee;
  border: 1px dashed #e0a94b;
  border-radius: 0.85rem;
  padding: 0.9rem 1rem;
  margin-bottom: 1rem;
  color: #6b4c1e;
  font-size: 0.85rem;
  line-height: 1.5;
}
.demo-banner p { margin: 0 0 0.5rem; }
.demo-banner .tip { margin: 0.5rem 0 0; font-size: 0.8rem; }
.demo-banner .tip a { color: #1c7282; font-weight: 700; }
.real-search-btn {
  display: inline-block;
  background: #1c7282;
  color: #fff;
  text-decoration: none;
  padding: 0.55rem 1rem;
  border-radius: 0.65rem;
  font-weight: 700;
  font-size: 0.85rem;
}
.meta { color: #4d6b72; margin: 0 0 1rem; }
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
}
.product-card {
  text-decoration: none;
  color: inherit;
  border: 1px solid rgba(15, 90, 107, 0.1);
  border-radius: 0.85rem;
  overflow: hidden;
  background: #fff;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(15, 90, 107, 0.1);
}
.thumb {
  position: relative;
  aspect-ratio: 1;
  background: #f5fbfc;
}
.demo-badge {
  position: absolute;
  top: 0.4rem;
  inset-inline-start: 0.4rem;
  background: rgba(107, 76, 30, 0.85);
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
  z-index: 1;
}
.thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.body { padding: 0.75rem; }
.body h3 {
  margin: 0 0 0.35rem;
  font-size: 0.92rem;
  line-height: 1.35;
  font-weight: 700;
  color: #132f37;
}
.price {
  margin: 0;
  color: #1c7282;
  font-weight: 800;
}
.rating {
  margin: 0.25rem 0 0;
  color: #b8860b;
  font-size: 0.8rem;
}
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1.5rem;
}
.page-btn {
  border: 1px solid rgba(15, 90, 107, 0.2);
  background: #fff;
  padding: 0.45rem 0.9rem;
  border-radius: 999px;
  cursor: pointer;
}
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.loading-row, .empty-state {
  padding: 2rem 0;
  color: #4d6b72;
}
.empty-state.error { color: #a82626; }
@media (max-width: 800px) {
  .browse-shell { grid-template-columns: 1fr; }
  .cats {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
  }
  .cats h3 { width: 100%; }
  .cat-group { display: contents; }
  .cat-btn { width: auto; }
}
</style>
