<template>
  <div class="ex-page" dir="rtl">
    <StorefrontNav theme="express" />

    <div class="shell">
      <p v-if="loading" class="state">عم نحمّل المتجر…</p>
      <div v-else-if="!expressStore" class="state err">
        <p>ما لقينا هالمتجر.</p>
        <a href="/express" class="btn">رجوع للقمة </a>
      </div>
      <template v-else>
        <nav class="crumbs">
          <a href="/express">لقمة </a>
          <span>/</span>
          <span>{{ expressStore.name }}</span>
        </nav>

        <header class="hero">
          <img
            v-if="expressStore.image"
            :src="expressStore.image"
            :alt="expressStore.name"
            width="1200"
            height="420"
            decoding="async"
            fetchpriority="high"
          />
          <div class="hero-copy">
            <div class="hero-row">
              <div>
                <h1>{{ expressStore.name }}</h1>
                <p>
                  {{ expressStore.cuisine }} · {{ expressStore.eta }}
                  · ★ {{ expressStore.rating }}
                  <span v-if="expressStore.total_reviews">({{ expressStore.total_reviews }})</span>
                </p>
                <p v-if="expressStore.description" class="desc">{{ expressStore.description }}</p>
              </div>
              <button type="button" class="btn follow" :class="{ on: following }" @click="toggleFollow">
                {{ following ? 'أتابع' : 'متابعة' }}
              </button>
            </div>
          </div>
        </header>

        <div class="tabs" role="tablist">
          <button type="button" :class="{ on: tab === 'all' }" @click="setTab('all')">الكل</button>
          <button type="button" :class="{ on: tab === 'offers' }" @click="setTab('offers')">عروض</button>
          <button type="button" :class="{ on: tab === 'new' }" @click="setTab('new')">جديد</button>
        </div>

        <h2 class="sec-title">القائمة</h2>
        <div class="grid">
          <a
            v-for="f in items"
            :key="f.id"
            class="card"
            :href="`/express/items/${f.id}`"
          >
            <div class="img-wrap">
              <img :src="f.image" :alt="f.name" loading="lazy" decoding="async" width="320" height="240" />
              <span v-if="f.offer" class="offer">عرض</span>
            </div>
            <div class="body">
              <strong>{{ f.name }}</strong>
              <span class="price">{{ f.price }}</span>
              <span v-if="f.oldPrice" class="old">{{ f.oldPrice }}</span>
              <span class="rate">★ {{ f.rating }}</span>
            </div>
          </a>
        </div>
        <p v-if="!items.length" class="state">ما في أطباق بهالخيار حالياً.</p>
      </template>
    </div>

    <StorefrontFooter theme="express" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../../storefront/api';
import { rememberChannel } from '../../storefront/channel';
import { isLoggedIn as checkAuth, store as authStore } from '../../storefront/store';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const props = defineProps({
  storeId: { type: [String, Number], required: true },
});

rememberChannel('express');

const loading = ref(true);
const expressStore = ref(null);
const items = ref([]);
const tab = ref('all');
const following = ref(false);

const isLoggedIn = computed(() => checkAuth() || !!authStore.authToken);
const loginUrl = computed(() => `/login?redirect=${encodeURIComponent(`/express/stores/${props.storeId}`)}`);

async function loadStore() {
  const { data } = await api.get(`/v1/express/stores/${props.storeId}`, { params: { tab: tab.value } });
  expressStore.value = data.store;
  items.value = data.items || [];
}

async function loadFollow() {
  try {
    const { data } = await api.get(`/v1/express/stores/${props.storeId}/follow-status`);
    following.value = !!data.following;
  } catch (_) { /* guest */ }
}

async function setTab(next) {
  tab.value = next;
  loading.value = true;
  try {
    await loadStore();
  } finally {
    loading.value = false;
  }
}

async function toggleFollow() {
  if (!isLoggedIn.value) {
    window.location.href = loginUrl.value;
    return;
  }
  try {
    if (following.value) {
      await api.delete(`/v1/express/stores/${props.storeId}/follow`);
      following.value = false;
    } else {
      await api.post(`/v1/express/stores/${props.storeId}/follow`);
      following.value = true;
    }
  } catch (_) { /* ignore */ }
}

onMounted(async () => {
  try {
    await loadStore();
    await loadFollow();
  } catch (_) {
    expressStore.value = null;
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.ex-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f3e6d6 0%, #faf6f1 30%, #fff 100%);
  color: #3d2817;
}
.shell {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1rem 1rem 3rem;
  box-sizing: border-box;
  width: 100%;
  overflow-x: clip;
}
.state { font-weight: 800; color: #8a4b12; }
.state.err { text-align: center; padding: 3rem 1rem; }
.crumbs { display: flex; gap: 0.35rem; font-size: 0.82rem; margin-bottom: 1rem; color: #9a7a5c; }
.crumbs a { color: #8a4b12; font-weight: 800; text-decoration: none; }
.hero {
  position: relative;
  border-radius: 1.25rem;
  overflow: hidden;
  min-height: 200px;
  background: #5c3210;
  margin-bottom: 1rem;
}
.hero img {
  width: 100%;
  height: 240px;
  object-fit: cover;
  display: block;
  opacity: 0.85;
}
.hero-copy {
  position: absolute; inset: auto 0 0 0;
  padding: 1.1rem 1.25rem;
  background: linear-gradient(transparent, rgba(40,20,8,.82));
  color: #fff8ef;
}
.hero-row { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
.hero-copy h1 { margin: 0 0 0.25rem; font-size: 1.45rem; }
.hero-copy p { margin: 0; opacity: 0.92; font-weight: 700; }
.hero-copy .desc { margin-top: 0.35rem; font-weight: 600; opacity: 0.88; font-size: 0.88rem; max-width: 40rem; }
.btn {
  display: inline-flex; align-items: center; justify-content: center;
  min-height: 2.4rem; padding: 0.45rem 1rem; border-radius: 999px;
  font-weight: 900; text-decoration: none; border: 0; cursor: pointer;
  background: #8a4b12; color: #fff8ef;
}
.btn.follow { background: rgba(255,248,239,.95); color: #8a4b12; }
.btn.follow.on { background: #8a4b12; color: #fff8ef; border: 1.5px solid #fff8ef; }
.tabs { display: flex; gap: 0.4rem; margin-bottom: 1rem; flex-wrap: wrap; }
.tabs button {
  border: 1.5px solid rgba(138,75,18,.25); background: #fff; color: #8a4b12;
  border-radius: 999px; padding: 0.4rem 0.9rem; font-weight: 800; cursor: pointer;
}
.tabs button.on { background: #8a4b12; color: #fff8ef; }
.sec-title { margin: 0 0 0.75rem; color: #5c3210; font-size: 1.15rem; }
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 0.85rem;
}
.card {
  background: #fff;
  border-radius: 1.1rem;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  border: 1.5px solid rgba(138,75,18,.14);
}
.img-wrap { position: relative; aspect-ratio: 4/3; background: #efe2d2; }
.img-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
.offer {
  position: absolute; top: 0.5rem; right: 0.5rem;
  background: #8a4b12; color: #fff; font-size: 0.72rem; font-weight: 900;
  padding: 0.2rem 0.45rem; border-radius: 999px;
}
.body { padding: 0.65rem 0.75rem 0.85rem; }
.body strong { display: block; color: #5c3210; font-size: 0.92rem; margin-bottom: 0.25rem; }
.price { color: #8a4b12; font-weight: 900; font-size: 0.88rem; }
.old { display: block; text-decoration: line-through; color: #b59a80; font-size: 0.78rem; }
.rate { display: block; margin-top: 0.2rem; color: #9a7a5c; font-size: 0.78rem; font-weight: 700; }
</style>
