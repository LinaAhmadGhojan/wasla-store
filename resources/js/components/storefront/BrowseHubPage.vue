<template>
  <div class="browse-page">
    <StorefrontNav />

    <section class="browse-hero">
      <div class="browse-hero-inner">
        <h1>تصفّحي داخل المنصة</h1>
        <p>
          اختاري SHEIN أو Trendyol أو Temu أو Noon أو Amazon — تصفّحي الأقسام والمنتجات داخل وصلة،
          مع تسعير بالدرهم الإماراتي. أو <a href="/buy-from-anywhere">الصقي رابط منتج</a>.
        </p>
      </div>
    </section>

    <div class="browse-shell">
      <div v-if="loading" class="loading-row">جاري تحميل المنصات...</div>
      <div v-else class="platform-grid">
        <a
          v-for="platform in platforms"
          :key="platform.id || platform.slug"
          :href="`/browse/${platform.slug}`"
          class="platform-card"
          :class="`platform-${platform.slug}`"
        >
          <img
            v-if="platform.logo"
            :src="platform.logo"
            :alt="platform.name"
            class="platform-logo"
          />
          <span class="platform-name">{{ platform.name }}</span>
          <span class="platform-cta">ادخلي للتصفح ←</span>
        </a>
      </div>

      <p class="hint">
        حالياً التصفح يعمل بكتالوج تجريبي داخل وصلة. عند ربط SearchAPI/OTAPI تظهر نتائج حقيقية من المنصات.
      </p>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../storefront/api';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const platforms = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get('/v1/external-platforms');
    platforms.value = data;
  } catch (e) {
    platforms.value = [];
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.browse-hero {
  background: linear-gradient(135deg, #1c7282 0%, #1a8a9c 55%, #26b4c3 100%);
  color: #fff;
  padding: 2.5rem 1.5rem;
}
.browse-hero-inner {
  max-width: 1100px;
  margin: 0 auto;
}
.browse-hero h1 {
  margin: 0 0 0.5rem;
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
}
.browse-hero p {
  margin: 0;
  max-width: 40rem;
  opacity: 0.95;
  line-height: 1.6;
}
.browse-hero a {
  color: #fff;
  font-weight: 700;
  text-decoration: underline;
}
.browse-shell {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 1.5rem 3rem;
}
.loading-row {
  color: #4d6b72;
  padding: 2rem 0;
}
.platform-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 1rem;
}
.platform-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.65rem;
  padding: 1.35rem 1rem;
  border-radius: 1rem;
  text-decoration: none;
  color: #132f37;
  border: 1px solid rgba(15, 90, 107, 0.12);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.platform-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(15, 90, 107, 0.12);
}
.platform-logo {
  width: 4.5rem;
  height: 2.5rem;
  object-fit: contain;
}
.platform-name {
  font-weight: 800;
  font-size: 1.05rem;
}
.platform-cta {
  font-size: 0.8rem;
  color: #1c7282;
  font-weight: 600;
}
.platform-shein { background: #f3f3f3; }
.platform-trendyol { background: #fff4eb; }
.platform-temu { background: #fff1e6; }
.platform-noon { background: #fffce0; }
.platform-amazon { background: #eef2f6; }
.hint {
  margin-top: 2rem;
  color: #4d6b72;
  font-size: 0.9rem;
  line-height: 1.5;
}
</style>
