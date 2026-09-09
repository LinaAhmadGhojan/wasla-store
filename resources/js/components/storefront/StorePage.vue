<template>
  <div class="store-page" dir="rtl">
    <StorefrontNav />
    <div v-if="loading" class="empty">جاري التحميل…</div>
    <div v-else-if="!storeData" class="empty">المتجر غير موجود.</div>
    <div v-else class="shell">
      <header class="hero">
        <img v-if="storeData.banner" :src="storeData.banner" class="banner" alt="" />
        <div class="hero-body">
          <img v-if="storeData.logo" :src="storeData.logo" class="logo" :alt="storeData.store_name" />
          <div>
            <h1>{{ storeData.store_name }}</h1>
            <p v-if="storeData.description">{{ storeData.description }}</p>
            <p v-if="newLast7" class="new-pill">أضاف {{ newLast7 }} منتجات جديدة خلال أسبوع</p>
          </div>
          <button
            type="button"
            class="btn"
            :class="storeData.following ? 'outline' : 'primary'"
            @click="toggleFollow"
          >
            {{ storeData.following ? 'إلغاء المتابعة' : 'متابعة المتجر' }}
          </button>
        </div>
      </header>

      <div class="tabs">
        <button type="button" :class="{ on: tab === 'all' }" @click="setTab('all')">كل المنتجات</button>
        <button type="button" :class="{ on: tab === 'offers' }" @click="setTab('offers')">العروض</button>
        <button type="button" :class="{ on: tab === 'new' }" @click="setTab('new')">الجديد</button>
      </div>

      <div v-if="loadingProducts" class="empty">جاري التحميل…</div>
      <div v-else-if="!products.length" class="empty">ما في منتجات بهالتبويب.</div>
      <div v-else class="grid">
        <ProductCard v-for="p in products" :key="p.id" :product="p" :compare-enabled="true" />
      </div>
    </div>
    <StorefrontFooter />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../../storefront/api';
import { store, hydrateUser } from '../../storefront/store';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';
import ProductCard from './ProductCard.vue';

const props = defineProps({
  storeId: { type: [String, Number], required: true },
});

const params = new URLSearchParams(window.location.search);
const tab = ref(params.get('tab') || 'all');
const storeData = ref(null);
const products = ref([]);
const newLast7 = ref(0);
const loading = ref(true);
const loadingProducts = ref(false);

async function load() {
  loading.value = true;
  try {
    await hydrateUser();
    const { data } = await api.get(`/v1/stores/${props.storeId}`, { params: { tab: tab.value } });
    storeData.value = data.store;
    products.value = data.products?.data || [];
    newLast7.value = data.new_last_7_days || 0;
  } catch (_) {
    storeData.value = null;
  } finally {
    loading.value = false;
  }
}

async function setTab(next) {
  tab.value = next;
  window.history.replaceState({}, '', `/stores/${props.storeId}?tab=${next}`);
  loadingProducts.value = true;
  try {
    const { data } = await api.get(`/v1/stores/${props.storeId}`, { params: { tab: next } });
    products.value = data.products?.data || [];
    storeData.value = data.store;
    newLast7.value = data.new_last_7_days || 0;
  } finally {
    loadingProducts.value = false;
  }
}

async function toggleFollow() {
  if (!store.authToken) {
    window.location.href = `/login?redirect=${encodeURIComponent(`/stores/${props.storeId}`)}`;
    return;
  }
  if (storeData.value.following) {
    await api.delete(`/v1/stores/${props.storeId}/follow`);
    storeData.value.following = false;
  } else {
    await api.post(`/v1/stores/${props.storeId}/follow`);
    storeData.value.following = true;
  }
}

onMounted(load);
</script>

<style scoped>
.store-page { min-height: 100vh; background: #f5fbfc; color: #132f37; }
.shell { max-width: 1100px; margin: 0 auto; padding: 1.5rem 1.25rem 3rem; }
.hero { background: #fff; border-radius: 1.2rem; overflow: hidden; border: 1px solid rgba(28,114,130,.12); margin-bottom: 1rem; }
.banner { width: 100%; height: 160px; object-fit: cover; display: block; background: #dceef1; }
.hero-body { display: flex; gap: 1rem; align-items: center; padding: 1rem 1.25rem; flex-wrap: wrap; }
.logo { width: 72px; height: 72px; border-radius: 1rem; object-fit: cover; background: #eef8f9; }
.hero-body h1 { margin: 0 0 .25rem; font-size: 1.4rem; }
.hero-body p { margin: 0; color: #4d6b72; }
.new-pill { margin-top: .4rem !important; color: #1c7282 !important; font-weight: 800; }
.btn { border: 0; border-radius: 999px; padding: .65rem 1.1rem; font-weight: 800; cursor: pointer; margin-inline-start: auto; }
.btn.primary { background: #1c7282; color: #fff; }
.btn.outline { background: #fff; color: #1c7282; border: 2px solid #1c7282; }
.tabs { display: flex; gap: .45rem; flex-wrap: wrap; margin-bottom: 1rem; }
.tabs button {
  border: 1.5px solid rgba(28,114,130,.2); background: #fff; border-radius: 999px;
  padding: .4rem .9rem; font-weight: 800; cursor: pointer;
}
.tabs button.on { background: #1c7282; color: #fff; border-color: #1c7282; }
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
.empty { text-align: center; padding: 3rem 1rem; color: #4d6b72; }
</style>
