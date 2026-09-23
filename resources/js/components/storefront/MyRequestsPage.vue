<template>
  <div class="track-hub" dir="rtl">
    <StorefrontNav />

    <div class="hub-shell">
      <header class="hub-head">
        <h1>متابعة</h1>
        <p class="sub">طلبات نشطة فقط — تسوق وصلة وعلى بابك</p>
      </header>

      <div v-if="!store.authToken" class="empty">
        <p>سجّلي دخولك لمتابعة طلباتك.</p>
        <a :href="loginHref" class="btn primary">تسجيل الدخول</a>
      </div>

      <template v-else>
        <div class="hub-tabs">
          <button type="button" class="tab" :class="{ on: mode === 'active' }" @click="setMode('active')">
            نشطة
            <em v-if="counts.active">{{ counts.active }}</em>
          </button>
          <button type="button" class="tab" :class="{ on: mode === 'history' }" @click="setMode('history')">
            السابقة
            <em v-if="counts.history">{{ counts.history }}</em>
          </button>
        </div>

        <div v-if="loading" class="empty">جاري التحميل…</div>

        <div v-else-if="!rows.length" class="empty">
          <p>{{ mode === 'active' ? 'ما في طلبات نشطة هلّق 🎉' : 'ما في طلبات سابقة.' }}</p>
          <a v-if="mode === 'active'" href="/" class="btn primary">تسوّقي الآن</a>
        </div>

        <ul v-else class="cards">
          <li
            v-for="row in rows"
            :key="row.key"
            class="card"
            :class="{ errand: row.type === 'errand' }"
          >
            <div class="card-top">
              <span class="channel">{{ row.channel_label || row.source }}</span>
              <span class="status" :class="row.status_key">{{ row.status_label }}</span>
            </div>

            <div class="progress-wrap">
              <div class="progress">
                <span :style="{ width: `${row.progress_percent || 0}%` }" />
              </div>
              <p class="progress-text">
                <template v-if="row.eta_hint">{{ row.eta_hint }}</template>
                <template v-else-if="row.minutes_remaining">~{{ row.minutes_remaining }} دقيقة</template>
                <template v-else>{{ row.status_label }}</template>
              </p>
            </div>

            <div v-if="row.items?.length" class="preview">
              <div v-for="(item, idx) in row.items.slice(0, 3)" :key="idx" class="thumb">
                <img v-if="item.image" :src="item.image" :alt="item.name" />
                <span v-else class="ph">{{ (item.name || '؟')[0] }}</span>
              </div>
              <p class="names">{{ itemNames(row) }}</p>
            </div>

            <div class="card-foot">
              <div class="ids">
                <strong>#{{ row.type === 'errand' ? row.reference : row.id }}</strong>
                <span>{{ row.date_label }}</span>
                <span v-if="row.total != null" class="total">{{ money(row.total) }}</span>
              </div>
              <a :href="row.track_url" class="btn track">
                {{ row.type === 'order' ? 'تتبّع' : 'التفاصيل' }}
              </a>
            </div>
          </li>
        </ul>
      </template>

      <p v-if="error" class="err">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../../storefront/api';
import { store, hydrateUser } from '../../storefront/store';
import { money } from '../../storefront/format';
import StorefrontNav from './StorefrontNav.vue';

const loginHref = `/login?redirect=${encodeURIComponent('/my-requests')}`;
const rows = ref([]);
const loading = ref(true);
const error = ref('');
const mode = ref('active');
const counts = ref({ active: 0, history: 0 });

function itemNames(row) {
  return (row.items || []).map((i) => i.name).filter(Boolean).slice(0, 2).join(' · ');
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const scope = mode.value === 'history' ? 'history' : 'active';
    const { data } = await api.get('/v1/my-orders', { params: { scope } });
    rows.value = data.data ?? [];
    counts.value = data.counts ?? { active: rows.value.length, history: 0 };
  } catch {
    error.value = 'تعذّر تحميل الطلبات.';
    rows.value = [];
  } finally {
    loading.value = false;
  }
}

function setMode(next) {
  if (mode.value === next) return;
  mode.value = next;
  load();
}

onMounted(async () => {
  await hydrateUser();
  await load();
});
</script>

<style scoped>
.track-hub {
  min-height: 100vh;
  background: #f4f9fa;
  color: #132f37;
  padding-bottom: 5rem;
}
.hub-shell {
  max-width: 520px;
  margin: 0 auto;
  padding: 1rem 0.85rem 2rem;
}
.hub-head h1 {
  margin: 0;
  font-size: 1.45rem;
  font-weight: 900;
  color: #0b3d44;
}
.sub {
  margin: 0.25rem 0 0.85rem;
  font-size: 0.82rem;
  color: #5a7a80;
  font-weight: 600;
}
.hub-tabs {
  display: flex;
  gap: 0.45rem;
  margin-bottom: 1rem;
  background: #fff;
  padding: 0.35rem;
  border-radius: 999px;
  border: 1px solid rgba(28, 114, 130, 0.12);
}
.tab {
  flex: 1;
  border: 0;
  background: transparent;
  border-radius: 999px;
  padding: 0.5rem 0.65rem;
  font-weight: 800;
  font-size: 0.82rem;
  color: #5a7a80;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
}
.tab.on {
  background: #1c7282;
  color: #fff;
}
.tab em {
  font-style: normal;
  font-size: 0.72rem;
  background: rgba(255, 255, 255, 0.22);
  padding: 0.1rem 0.4rem;
  border-radius: 999px;
}
.tab:not(.on) em {
  background: #e8f4f6;
  color: #1c7282;
}
.cards {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.card {
  background: #fff;
  border-radius: 1rem;
  padding: 0.85rem 0.9rem;
  border: 1px solid rgba(28, 114, 130, 0.1);
  box-shadow: 0 6px 18px rgba(19, 47, 55, 0.05);
}
.card.errand {
  border-color: rgba(138, 75, 18, 0.15);
  box-shadow: 0 6px 18px rgba(92, 50, 16, 0.06);
}
.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.55rem;
}
.channel {
  font-size: 0.72rem;
  font-weight: 800;
  color: #1c7282;
}
.card.errand .channel {
  color: #8a4b12;
}
.status {
  font-size: 0.72rem;
  font-weight: 900;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  background: #e8f4f6;
  color: #1c7282;
}
.status.out_for_delivery,
.status.shipped {
  background: #fff4e6;
  color: #b45309;
}
.status.preparing,
.status.processing,
.status.accepted,
.status.shopping {
  background: #eef6ff;
  color: #1a4d8c;
}
.progress-wrap {
  margin-bottom: 0.65rem;
}
.progress {
  height: 6px;
  background: #eef4f5;
  border-radius: 999px;
  overflow: hidden;
}
.card.errand .progress span {
  background: linear-gradient(90deg, #8a4b12, #b45309);
}
.progress span {
  display: block;
  height: 100%;
  background: linear-gradient(90deg, #1c7282, #0a9aad);
  border-radius: 999px;
}
.progress-text {
  margin: 0.3rem 0 0;
  font-size: 0.75rem;
  font-weight: 800;
  color: #5a7a80;
}
.preview {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-bottom: 0.65rem;
}
.thumb {
  width: 40px;
  height: 40px;
  border-radius: 0.55rem;
  overflow: hidden;
  background: #f0f7f8;
  flex-shrink: 0;
}
.thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.ph {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  font-weight: 900;
  color: #1c7282;
}
.names {
  margin: 0;
  font-size: 0.78rem;
  font-weight: 700;
  color: #4d6b72;
  line-height: 1.35;
}
.card-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}
.ids {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  font-size: 0.72rem;
  color: #8a9da3;
}
.ids strong {
  color: #0b3d44;
  font-size: 0.82rem;
}
.total {
  color: #1c7282;
  font-weight: 900;
}
.btn {
  text-decoration: none;
  border-radius: 999px;
  padding: 0.45rem 0.9rem;
  font-weight: 900;
  font-size: 0.78rem;
  border: 0;
  cursor: pointer;
}
.btn.primary {
  background: #1c7282;
  color: #fff;
}
.btn.track {
  background: #1c7282;
  color: #fff;
  white-space: nowrap;
}
.card.errand .btn.track {
  background: #8a4b12;
}
.empty {
  text-align: center;
  padding: 2rem 1rem;
  color: #5a7a80;
}
.err {
  color: #b42318;
  font-weight: 700;
  margin-top: 1rem;
}
</style>
