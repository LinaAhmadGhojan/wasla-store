<template>
  <div class="orders-page" dir="rtl">
    <StorefrontNav />

    <div class="orders-shell">
      <header class="orders-hero">
        <img :src="markUrl" alt="" class="orders-mark" />
        <div>
          <h1>طلباتي</h1>
          <p class="subtitle">تتبّعي طلباتك، أعيدي الشراء، أو حمّلي الفاتورة</p>
        </div>
      </header>

      <div v-if="!store.authToken" class="empty-state">
        <p>سجّلي دخولك لعرض طلباتك.</p>
        <a :href="loginHref" class="btn btn-primary">تسجيل الدخول</a>
      </div>

      <template v-else>
        <div class="filter-row" role="tablist" aria-label="تصفية الطلبات">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            role="tab"
            class="filter-pill"
            :class="{ active: filter === tab.key }"
            :aria-selected="filter === tab.key"
            @click="filter = tab.key"
          >
            {{ tab.label }}
            <span v-if="tab.key !== 'all'" class="count">{{ counts[tab.key] || 0 }}</span>
          </button>
        </div>

        <div v-if="loading" class="loading-row">جاري التحميل…</div>

        <div v-else-if="!filteredRows.length" class="empty-state">
          <p>{{ emptyMessage }}</p>
          <a href="/shop" class="btn btn-primary">تسوّقي من وصلة</a>
        </div>

        <ul v-else class="order-list">
          <li v-for="row in filteredRows" :key="row.key" class="order-card">
            <div class="card-top">
              <div class="card-ids">
                <strong class="order-id">#{{ row.id }}</strong>
                <span class="order-date">{{ formatDate(row.created_at) || row.date_label }}</span>
                <span v-if="row.tracking_number" class="track-no" dir="ltr">{{ row.tracking_number }}</span>
              </div>
              <span class="status-pill" :class="row.filter_status || row.filter_group">{{ row.status_label }}</span>
            </div>

            <div class="thumbs" v-if="row.items?.length">
              <div
                v-for="(item, idx) in row.items.slice(0, 5)"
                :key="idx"
                class="thumb"
                :title="item.name"
              >
                <img v-if="item.image" :src="item.image" :alt="item.name" />
                <span v-else class="thumb-fallback">{{ item.source?.[0] || 'و' }}</span>
              </div>
              <span v-if="row.items.length > 5" class="thumb more">+{{ row.items.length - 5 }}</span>
            </div>

            <div class="card-foot">
              <div class="totals">
                <span class="total-label">المجموع</span>
                <strong>{{ money(row.total) }}</strong>
                <small v-if="row.source">{{ row.source }}</small>
              </div>
              <div class="card-actions">
                <a :href="row.details_url || row.track_url" class="btn btn-ghost btn-sm">التفاصيل</a>
                <a :href="row.invoice_url" class="btn btn-ghost btn-sm">الفاتورة</a>
                <button
                  v-if="row.can_reorder"
                  type="button"
                  class="btn btn-ghost btn-sm"
                  :disabled="busyId === row.id"
                  @click="reorder(row)"
                >Buy Again</button>
                <button
                  v-if="row.can_cancel"
                  type="button"
                  class="btn btn-danger btn-sm"
                  :disabled="busyId === row.id"
                  @click="openCancel(row)"
                >إلغاء</button>
                <a :href="row.track_url" class="btn btn-primary btn-sm">تتبع</a>
              </div>
            </div>
            <p v-if="rowMsg[row.id]" class="row-msg" :class="{ err: rowErr[row.id] }">{{ rowMsg[row.id] }}</p>
          </li>
        </ul>
      </template>

      <div v-if="cancelTarget" class="modal-backdrop" @click.self="cancelTarget = null">
        <div class="modal" role="dialog" aria-modal="true">
          <h3>إلغاء الطلب #{{ cancelTarget.id }}</h3>
          <p v-if="cancelTarget.cancel_policy === 'maybe'" class="hint">
            الطلب قيد التجهيز — الإلغاء ممكن وقد يُراجع من الفريق.
          </p>
          <label>سبب الإلغاء</label>
          <textarea v-model="cancelReason" rows="3" placeholder="اكتبي سبب الإلغاء…"></textarea>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" @click="cancelTarget = null">رجوع</button>
            <button type="button" class="btn btn-danger" :disabled="busyId === cancelTarget.id" @click="confirmCancel">
              تأكيد الإلغاء
            </button>
          </div>
        </div>
      </div>

      <p v-if="error" class="feedback error">{{ error }}</p>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { computed, reactive, ref, onMounted } from 'vue';
import api from '../../storefront/api';
import { store, hydrateUser, refreshCartCount } from '../../storefront/store';
import { money } from '../../storefront/format';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const markUrl = '/brand/wasla-id-mark.png?v=6';
const loginHref = `/login?redirect=${encodeURIComponent('/my-requests')}`;
const rows = ref([]);
const loading = ref(true);
const error = ref('');
const filter = ref('all');
const busyId = ref(null);
const rowMsg = reactive({});
const rowErr = reactive({});
const cancelTarget = ref(null);
const cancelReason = ref('');

const tabs = [
  { key: 'all', label: 'الكل' },
  { key: 'pending', label: 'Pending' },
  { key: 'processing', label: 'Processing' },
  { key: 'shipped', label: 'Shipped' },
  { key: 'delivered', label: 'Delivered' },
  { key: 'cancelled', label: 'Cancelled' },
  { key: 'returned', label: 'Returned' },
  { key: 'refunded', label: 'Refunded' },
];

const counts = computed(() => {
  const c = {};
  for (const tab of tabs) {
    if (tab.key === 'all') continue;
    c[tab.key] = rows.value.filter((r) => r.filter_status === tab.key).length;
  }
  return c;
});

const filteredRows = computed(() => {
  if (filter.value === 'all') return rows.value;
  return rows.value.filter((r) => r.filter_status === filter.value);
});

const emptyMessage = computed(() => {
  if (!rows.value.length) return 'ما عندك طلبات بعد.';
  return 'ما في طلبات ضمن هذا التصنيف.';
});

function formatDate(iso) {
  if (!iso) return '';
  try {
    return new Date(iso).toLocaleDateString('ar', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  } catch (_) {
    return iso;
  }
}

async function load() {
  await hydrateUser();
  if (!store.authToken) {
    loading.value = false;
    return;
  }
  try {
    const { data } = await api.get('/v1/my-orders');
    rows.value = data.data ?? data;
  } catch (e) {
    error.value = 'تعذّر تحميل الطلبات.';
  } finally {
    loading.value = false;
  }
}

async function reorder(row) {
  busyId.value = row.id;
  rowMsg[row.id] = '';
  rowErr[row.id] = false;
  try {
    const { data } = await api.post(`/orders/${row.id}/reorder`);
    rowMsg[row.id] = data.message || 'تمت الإضافة للسلة.';
    await refreshCartCount();
    if (data.added_count > 0) {
      window.location.href = '/cart';
    }
  } catch (e) {
    rowErr[row.id] = true;
    rowMsg[row.id] = e?.response?.data?.message || 'تعذّرت إعادة الطلب.';
  } finally {
    busyId.value = null;
  }
}

function openCancel(row) {
  cancelTarget.value = row;
  cancelReason.value = '';
}

async function confirmCancel() {
  const row = cancelTarget.value;
  if (!row) return;
  if (cancelReason.value.trim().length < 3) {
    rowErr[row.id] = true;
    rowMsg[row.id] = 'اكتبي سبب الإلغاء (٣ أحرف على الأقل).';
    return;
  }
  busyId.value = row.id;
  try {
    await api.post(`/orders/${row.id}/cancel`, { reason: cancelReason.value.trim() });
    cancelTarget.value = null;
    await load();
  } catch (e) {
    rowErr[row.id] = true;
    rowMsg[row.id] = e?.response?.data?.message
      || e?.response?.data?.errors?.reason?.[0]
      || 'تعذّر إلغاء الطلب.';
  } finally {
    busyId.value = null;
  }
}

onMounted(load);
</script>

<style scoped>
.orders-page {
  color: #132f37;
  background: linear-gradient(180deg, #eef8f9 0%, #f7fbfc 40%, #fff 100%);
  min-height: 100vh;
}
.orders-shell {
  max-width: 760px;
  margin: 0 auto;
  padding: 1.75rem 1.25rem 4rem;
}
.orders-hero {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  margin-bottom: 1.35rem;
}
.orders-mark {
  width: 56px;
  height: 56px;
  object-fit: contain;
  background: #1c7282;
  border-radius: 50%;
  padding: 8px;
  flex-shrink: 0;
}
h1 {
  margin: 0;
  color: #006871;
  font-size: clamp(1.75rem, 4vw, 2.15rem);
  line-height: 1.2;
}
.subtitle {
  margin: 0.3rem 0 0;
  color: #4d6b72;
  font-size: 0.95rem;
}
.filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-bottom: 1.25rem;
}
.filter-pill {
  border: 1.5px solid rgba(28,114,130,.18);
  background: #fff;
  border-radius: 999px;
  padding: 0.4rem 0.75rem;
  font-weight: 800;
  font-size: 0.82rem;
  color: #4d6b72;
  cursor: pointer;
}
.filter-pill.active {
  background: #1c7282;
  color: #fff;
  border-color: #1c7282;
}
.filter-pill .count {
  margin-inline-start: 0.25rem;
  opacity: 0.85;
}
.loading-row, .empty-state {
  text-align: center;
  padding: 2.5rem 1rem;
  color: #4d6b72;
}
.empty-state .btn { margin: 0.5rem; }
.order-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.9rem; }
.order-card {
  background: #fff;
  border: 1px solid rgba(28,114,130,.1);
  border-radius: 1.1rem;
  padding: 1rem 1.1rem;
  box-shadow: 0 10px 24px rgba(19,47,55,.04);
}
.card-top { display: flex; justify-content: space-between; gap: .75rem; align-items: flex-start; }
.card-ids { display: flex; flex-direction: column; gap: .15rem; }
.order-id { color: #0b3d44; }
.order-date, .track-no { font-size: .8rem; color: #4d6b72; }
.status-pill {
  border-radius: 999px;
  padding: .25rem .65rem;
  font-size: .75rem;
  font-weight: 800;
  background: #e8f6f8;
  color: #1c7282;
  white-space: nowrap;
}
.status-pill.pending { background: #fff7e8; color: #8a5a00; }
.status-pill.processing { background: #eef6ff; color: #1a4d8c; }
.status-pill.shipped { background: #e8f6f8; color: #1c7282; }
.status-pill.delivered, .status-pill.completed { background: #e9f8ef; color: #1b7a45; }
.status-pill.cancelled, .status-pill.returned, .status-pill.refunded { background: #fdeeee; color: #a82626; }
.thumbs { display: flex; gap: .4rem; margin: .85rem 0; flex-wrap: wrap; }
.thumb {
  width: 48px; height: 48px; border-radius: .65rem; overflow: hidden;
  background: #f5fbfc; display: flex; align-items: center; justify-content: center;
}
.thumb img { width: 100%; height: 100%; object-fit: cover; }
.thumb.more { font-size: .8rem; font-weight: 800; color: #1c7282; }
.card-foot { display: flex; justify-content: space-between; gap: .75rem; align-items: center; flex-wrap: wrap; }
.totals { display: flex; flex-direction: column; gap: .1rem; }
.total-label { font-size: .75rem; color: #9aabaf; }
.card-actions { display: flex; flex-wrap: wrap; gap: .35rem; justify-content: flex-end; }
.btn {
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: 999px; padding: .55rem 1rem; font-weight: 800;
  text-decoration: none; border: 0; cursor: pointer;
}
.btn-sm { padding: .4rem .7rem; font-size: .8rem; }
.btn-primary { background: #1c7282; color: #fff; }
.btn-ghost { background: #fff; color: #1c7282; border: 1.5px solid rgba(28,114,130,.28); }
.btn-danger { background: #fff; color: #a82626; border: 1.5px solid rgba(168,38,38,.35); }
.btn:disabled { opacity: .5; cursor: not-allowed; }
.row-msg { margin: .55rem 0 0; font-size: .82rem; color: #1c7282; }
.row-msg.err { color: #a82626; }
.feedback.error { color: #a82626; margin-top: 1rem; }
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(11,61,68,.45);
  display: flex; align-items: center; justify-content: center; padding: 1rem; z-index: 50;
}
.modal {
  background: #fff; border-radius: 1rem; padding: 1.25rem; width: min(420px, 100%);
  box-shadow: 0 20px 50px rgba(0,0,0,.18);
}
.modal h3 { margin: 0 0 .5rem; color: #0b3d44; }
.modal .hint { color: #8a5a00; font-size: .88rem; }
.modal label { display: block; font-weight: 800; margin: .75rem 0 .35rem; }
.modal textarea {
  width: 100%; box-sizing: border-box; border: 1.5px solid rgba(28,114,130,.22);
  border-radius: .75rem; padding: .65rem .75rem; resize: vertical;
}
.modal-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1rem; }
</style>
