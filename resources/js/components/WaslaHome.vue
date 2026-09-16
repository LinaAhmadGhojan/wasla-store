<template>
  <div class="wasla-home" dir="rtl">
    <StorefrontNav theme="home" />

    <main class="mobile-home-shell">
      <a href="/express" class="home-banner" aria-label="وصلة السريعة - اطلب الآن">
        <img :src="expressImageUrl" alt="وصلة السريعة - وجباتك المفضلة" class="banner-food" />
        <span class="express-cta">اطلب الآن</span>
      </a>

      <div class="home-platforms">
        <a v-for="platform in quickPlatforms" :key="platform.name" :href="platform.href" class="home-platform">
          <span class="platform-logo" :class="platform.className">{{ platform.short }}</span><small>{{ platform.name }}</small>
        </a>
      </div>

      <section class="fresh-section">
        <div class="fresh-title"><a href="/shop?section=new">‹ عرض الكل</a><h2>وصلات حديثة <b>⚡</b></h2></div>
        <div v-if="homeLoading" class="loading-row">جاري تحميل المنتجات...</div>
        <div v-else class="fresh-grid">
          <ProductCard v-for="product in freshProducts" :key="product.id" :product="product" />
        </div>
      </section>
    </main>

    <nav class="home-bottom-nav" aria-label="التنقل الرئيسي">
      <a href="/profile"><span><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 21c.8-4 3.2-6 7.5-6s6.7 2 7.5 6"/></svg></span>حسابي</a>
      <a href="/favorites"><span><svg viewBox="0 0 24 24"><path d="M20.8 8.7c0 5.1-8.8 10.1-8.8 10.1S3.2 13.8 3.2 8.7A4.7 4.7 0 0 1 12 6.1a4.7 4.7 0 0 1 8.8 2.6Z"/></svg></span>المفضلة</a>
      <a href="/my-requests"><span><svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M9 12h6M9 16h4"/></svg></span>طلب جديد</a>
      <a href="/" class="active"><span><svg viewBox="0 0 24 24"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1Z"/></svg></span>الرئيسية</a>
    </nav>

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

    <StorefrontFooter theme="home" />
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
const expressImageUrl = '/images/express.png';
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
const quickPlatforms = [
  { name: 'SHEIN', short: 'SHEIN', className: 'shein', href: '/browse?platform=shein' },
  { name: 'Trendyol', short: 'trend', className: 'trendyol', href: '/browse?platform=trendyol' },
  { name: 'Temu', short: 'TEMU', className: 'temu', href: '/browse?platform=temu' },
  { name: 'Amazon', short: 'amazon', className: 'amazon', href: '/browse?platform=amazon' },
  { name: 'Noon', short: 'نون', className: 'noon', href: '/browse?platform=noon' },
];
const freshProducts = ref([]);

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
    freshProducts.value = sections.find((section) => section.key === 'new_arrivals')?.products?.slice(0, 4) || [];
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
.wasla-home > .hero,
.wasla-home > .section { display: none; }
.mobile-home-shell { max-width: 760px; margin: 0 auto; padding: 1rem 0.75rem 4rem; background: #fff; }
.home-modes { display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem; }
.home-mode { min-height: 3.6rem; border-radius: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 0.45rem; color: #fff; text-decoration: none; font-weight: 900; font-size: 1rem; }
.home-mode span { font-size: 1.5rem; }
.store-mode { background: #087b8d; }
.express-mode { background: #aa5115; }
.home-banner {
  position: relative;
  display: block;
  overflow: hidden;
  height: 5rem;
  margin: 0.75rem 0 0.9rem;
  border-radius: 0.9rem;
  background: #fff0e3;
  color: #fff;
  text-decoration: none;
}
.banner-food {
  position: absolute;
  inset: 0;
  width: 104%;
  height: 126%;
  display: block;
  object-fit: cover;
  object-position: center;
  background: #fff0e3;
  transform: scale(1.02);
}
.express-cta {
  position: absolute;
  right: 0.6rem;
  bottom: 0.2rem;
  z-index: 2;
  background: #087b8d;
  color: #fff;
  border-radius: 0.55rem;
  padding: 0.32rem 0.7rem;
  font-size: 0.7rem;
  font-weight: 900;
  box-shadow: 0 2px 5px rgba(0,0,0,.14);
}
.home-platforms { direction: ltr; display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.45rem; margin: 0.5rem 0 1.4rem; }
.home-platform { display: flex; flex-direction: column; align-items: center; gap: 0.35rem; color: #155a68; text-decoration: none; font-weight: 800; font-size: 0.72rem; }
.platform-logo { width: 3.8rem; height: 3.8rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 900; font-size: 0.7rem; text-align: center; }
.platform-logo.shein { background: #111c21; letter-spacing: 0.05em; }.platform-logo.trendyol { background: #ff4c18; font-size: 0.6rem; }.platform-logo.temu { background: #ff6800; }.platform-logo.amazon { background: #17252d; font-size: 0.6rem; }.platform-logo.noon { background: #ffe600; color: #101010; font-size: 1.1rem; }
.fresh-title { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }.fresh-title h2 { margin: 0; color: #0a5161; font-size: 1.4rem; }.fresh-title h2 b { color: #f5a400; }.fresh-title a { color: #087b8d; text-decoration: none; font-weight: 800; }
.fresh-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.65rem; }
.home-bottom-nav { display: grid; grid-template-columns: repeat(4, 1fr); position: fixed; z-index: 45; bottom: 0; left: 0; right: 0; max-width: 760px; margin: 0 auto; min-height: 4.35rem; padding: 0.35rem 0.6rem calc(0.35rem + env(safe-area-inset-bottom, 0px)); background: #087b8d; border-top: 1px solid #075d6b; box-shadow: 0 -4px 16px rgba(15,79,90,.2); }
.home-bottom-nav a { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.18rem; color: rgba(255,255,255,.82); text-decoration: none; font-size: 0.68rem; font-weight: 800; }
.home-bottom-nav a span { height: 1.55rem; line-height: 1; }
.home-bottom-nav svg { width: 1.45rem; height: 1.45rem; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.home-bottom-nav a.active { color: #fff; }
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
