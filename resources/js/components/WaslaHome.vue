<template>
  <div class="wasla-home" dir="rtl">
    <StorefrontNav />

    <section class="hero">
      <div class="hero-content">
        <img :src="markUrl" alt="" class="hero-mark" />
        <h1>متاجر كثيرة… <span>وصلة واحدة</span></h1>
        <p>نجمع لكِ الأفضل من أكثر من مصدر — تسوقي من وصلة ومن أشهر المتاجر العالمية في مكان واحد.</p>
        <div class="hero-actions">
          <a href="/shop" class="btn btn-primary">ابدئي التسوق</a>
          <a href="/browse" class="btn btn-outline">تسوق عالمي</a>
        </div>
      </div>
      <div class="hero-visual">
        <img
          :src="manyStoresUrl"
          alt="متاجر كثيرة وصلة واحدة"
          class="hero-illu"
        />
      </div>
    </section>

    <section class="section platforms-strip">
      <div class="section-header">
        <span>وصلة</span>
        <h2>تسوق من أشهر المتاجر</h2>
      </div>
      <div class="platform-row">
        <a href="/browse" class="platform-chip">SHEIN</a>
        <a href="/browse" class="platform-chip">Temu</a>
        <a href="/browse" class="platform-chip">Noon</a>
        <a href="/browse" class="platform-chip">Amazon</a>
        <a href="/browse" class="platform-chip">Trendyol</a>
        <a href="/buy-from-anywhere" class="platform-chip accent">لصق رابط</a>
      </div>
    </section>

    <section id="shop" class="section cards-section">
      <div class="section-header">
        <span>تصنيفات</span>
        <h2>تسوقي حسب الفئة</h2>
      </div>
      <div v-if="categoriesLoading" class="loading-row">جاري التحميل...</div>
      <div v-else class="card-grid">
        <a
          v-for="category in categories"
          :key="category.slug"
          :href="`/shop?category_id=${category.id ?? ''}`"
          class="card"
        >
          <span class="card-icon">{{ category.icon }}</span>
          <span class="card-name">{{ category.name }}</span>
        </a>
      </div>
    </section>

    <section
      v-for="section in homeSections"
      :key="section.key"
      :id="section.key"
      class="section trending-section"
    >
      <div class="section-header section-header-row">
        <div>
          <span>{{ section.eyebrow }}</span>
          <h2>{{ section.title }}</h2>
        </div>
        <a v-if="section.key === 'favorites'" href="/favorites" class="section-link">عرض الكل</a>
        <a v-else :href="sectionSeeAll(section.key)" class="section-link">عرض الكل</a>
      </div>

      <div v-if="homeLoading" class="loading-row">جاري تحميل المنتجات...</div>
      <div v-else-if="section.products?.length" class="product-grid">
        <ProductCard v-for="product in section.products" :key="`${section.key}-${product.id}`" :product="product" />
      </div>
      <div v-else class="loading-row section-empty">
        <template v-if="section.login_required">
          {{ section.empty_hint || 'سجّلي دخولك لعرض المفضلة.' }}
          <a :href="loginHref" class="empty-link">تسجيل الدخول</a>
        </template>
        <template v-else>
          {{ section.empty_hint || 'لا توجد منتجات حالياً.' }}
        </template>
      </div>
    </section>

    <section class="section idea-section">
      <div class="idea-card">
        <img :src="globeUrl" alt="" class="idea-illu" />
        <div>
          <h2>من أفضل المتاجر… لوصلة واحدة</h2>
          <p>اكتشفي منتجات عالمية بأفضل الأسعار وأسرع توصيل، مع متابعة طلبك من التطبيق والموقع.</p>
          <a href="/browse" class="btn btn-primary">تصفّح المنصات</a>
        </div>
      </div>
    </section>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../storefront/api';
import { hydrateUser } from '../storefront/store';
import { getRecentlyViewedIds } from '../storefront/recentlyViewed';
import { loginUrl } from '../storefront/format';
import StorefrontNav from './storefront/StorefrontNav.vue';
import StorefrontFooter from './storefront/StorefrontFooter.vue';
import ProductCard from './storefront/ProductCard.vue';

const markUrl = '/brand/wasla-id-mark.png?v=6';
const manyStoresUrl = '/brand/illustrations/onboarding-many-stores.png';
const globeUrl = '/brand/illustrations/onboarding-globe-ref.png';
const loginHref = loginUrl('/');

const fallbackCategories = [
  { name: 'نساء', slug: 'women', icon: '👗' },
  { name: 'رجال', slug: 'men', icon: '👔' },
  { name: 'أحذية', slug: 'shoes', icon: '👟' },
  { name: 'حقائب', slug: 'bags', icon: '👜' },
  { name: 'تجميل', slug: 'beauty', icon: '💄' },
  { name: 'المنزل', slug: 'home', icon: '🏠' },
  { name: 'إلكترونيات', slug: 'electronics', icon: '📱' },
];

const categoryIcons = {
  women: '👗',
  men: '👔',
  shoes: '👟',
  bags: '👜',
  beauty: '💄',
  home: '🏠',
  electronics: '📱',
};

const categories = ref(fallbackCategories);
const categoriesLoading = ref(true);
const homeSections = ref([]);

function sectionSeeAll(key) {
  const map = {
    trending: 'bestsellers',
    new_arrivals: 'new',
    recommended: 'recommended',
    top_rated: 'top_rated',
    recently_viewed: 'all',
  };
  const section = map[key] || 'all';
  return `/shop?section=${section}`;
}
const homeLoading = ref(true);

async function fillRecentlyViewed(sections) {
  const section = sections.find((s) => s.key === 'recently_viewed');
  if (!section) return;
  const ids = getRecentlyViewedIds().slice(0, 8);
  if (!ids.length) {
    section.products = [];
    return;
  }
  try {
    const { data } = await api.get('/products', { params: { ids: ids.join(','), per_page: 24 } });
    const map = new Map((data.data || data || []).map((p) => [p.id, p]));
    section.products = ids.map((id) => map.get(id)).filter(Boolean);
  } catch (_) {
    section.products = [];
  }
}

onMounted(async () => {
  await hydrateUser();

  try {
    const { data } = await api.get('/categories');
    const topLevel = (data || []).filter((c) => !c.parent_id);
    if (topLevel.length) {
      categories.value = topLevel.map((c) => ({
        id: c.id,
        name: c.name,
        slug: c.slug,
        icon: c.icon || categoryIcons[c.slug] || '🛍️',
      }));
    }
  } catch (e) {
    // keep fallback
  } finally {
    categoriesLoading.value = false;
  }

  try {
    const { data } = await api.get('/v1/home');
    const sections = data.sections || [];
    await fillRecentlyViewed(sections);
    // Hide empty client sections optionally? Keep visible with hint for favorites/recent.
    homeSections.value = sections;
  } catch (e) {
    homeSections.value = [];
  } finally {
    homeLoading.value = false;
  }
});
</script>

<style scoped>
.wasla-home { color: #132f37; background: #fff; }
.hero {
  display: grid;
  grid-template-columns: 1.05fr 0.95fr;
  gap: 2rem;
  align-items: center;
  padding: 3.5rem 2rem 2.5rem;
  max-width: 1200px;
  margin: 0 auto;
}
.hero-mark {
  width: 80px;
  height: 80px;
  object-fit: contain;
  margin-bottom: 1rem;
  background: #1c7282;
  border-radius: 16px;
  padding: 8px;
}
.hero h1 {
  font-size: clamp(2rem, 4vw, 3.2rem);
  margin: 0;
  line-height: 1.25;
  color: #0b3d44;
}
.hero h1 span { color: #006871; }
.hero p {
  margin: 1.15rem 0 1.75rem;
  color: #4d6b72;
  max-width: 36rem;
  font-size: 1.05rem;
  line-height: 1.7;
}
.hero-actions { display: flex; gap: 0.85rem; flex-wrap: wrap; }
.btn {
  padding: 0.95rem 1.6rem;
  border-radius: 999px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  transition: transform .2s ease;
}
.btn:hover { transform: translateY(-2px); }
.btn-primary { background: #006871; color: #fff; }
.btn-outline {
  background: #fff;
  color: #006871;
  border: 2px solid #006871;
}
.hero-visual { text-align: center; }
.hero-illu {
  width: min(100%, 420px);
  height: auto;
  filter: drop-shadow(0 18px 40px rgba(0, 104, 113, 0.12));
}
.section {
  padding: 2.75rem 2rem;
  max-width: 1200px;
  margin: 0 auto;
}
.section-header span {
  color: #006871;
  font-weight: 800;
  letter-spacing: .04em;
}
.section-header h2 {
  margin: 0.35rem 0 0;
  font-size: 1.75rem;
  color: #0b3d44;
}
.section-header-row {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
}
.section-link {
  color: #006871;
  font-weight: 800;
  text-decoration: none;
  white-space: nowrap;
}
.section-empty {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
}
.empty-link {
  color: #006871;
  font-weight: 800;
}
.platform-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1.25rem;
}
.platform-chip {
  padding: 0.7rem 1.15rem;
  border-radius: 1rem;
  background: #f5fbfc;
  border: 1px solid rgba(0, 104, 113, 0.14);
  color: #006871;
  font-weight: 800;
  text-decoration: none;
}
.platform-chip.accent { background: #006871; color: #fff; border-color: #006871; }
.loading-row { margin-top: 1.5rem; color: #4d6b72; }
.card-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 1rem;
  margin-top: 1.5rem;
}
.card {
  min-height: 8rem;
  padding: 1.25rem 0.85rem;
  border-radius: 1.25rem;
  background: #fff;
  border: 1px solid rgba(0, 104, 113, .1);
  color: #132f37;
  font-weight: 700;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.65rem;
  text-decoration: none;
  transition: transform .2s ease, box-shadow .2s ease;
}
.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 16px 28px rgba(0, 104, 113, .1);
}
.card-icon { font-size: 2rem; line-height: 1; }
.product-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1.15rem;
  margin-top: 1.5rem;
}
.idea-card {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 1.75rem;
  align-items: center;
  padding: 1.75rem;
  border-radius: 1.5rem;
  background: linear-gradient(135deg, #eef8f9, #fff);
  border: 1px solid rgba(0, 104, 113, 0.1);
}
.idea-illu { width: 100%; height: auto; }
.idea-card h2 { margin: 0 0 0.65rem; color: #006871; font-size: 1.55rem; }
.idea-card p { margin: 0 0 1.25rem; color: #4d6b72; line-height: 1.7; }
@media (max-width: 960px) {
  .hero { grid-template-columns: 1fr; padding-top: 2rem; }
  .card-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .idea-card { grid-template-columns: 1fr; text-align: center; }
  .idea-illu { max-width: 220px; margin: 0 auto; }
}
</style>
