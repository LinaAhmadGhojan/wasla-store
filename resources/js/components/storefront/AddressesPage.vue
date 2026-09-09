<template>
  <div class="addr-page" dir="rtl">
    <StorefrontNav />

    <div class="shell">
      <div v-if="!store.authToken" class="empty">
        <p>سجّلي دخولك لإدارة العناوين.</p>
        <a :href="loginHref" class="btn primary">دخول</a>
      </div>

      <template v-else>
        <div class="head">
          <div>
            <h1>عناويني</h1>
            <p class="sub">احفظي عدة عناوين، حدّدي الموقع على الخريطة، وأضيفي ملاحظات للمندوب.</p>
          </div>
          <button v-if="!editing" class="btn primary" type="button" @click="startCreate">+ عنوان جديد</button>
        </div>

        <div v-if="loading" class="empty">جاري التحميل…</div>

        <AddressForm
          v-if="editing"
          class="panel"
          :title="formInitial?.id ? 'تعديل عنوان' : 'إضافة عنوان'"
          :initial="formInitial"
          :saving="saving"
          :error="error"
          @submit="save"
          @cancel="editing = false"
        />

        <div v-else-if="!rows.length" class="empty panel">
          <p>ما في عناوين بعد. أضيفي عنوانك الأول مع تحديده على الخريطة.</p>
          <button class="btn primary" type="button" @click="startCreate">إضافة عنوان</button>
        </div>

        <div v-else class="list">
          <article v-for="row in rows" :key="row.id" class="card" :class="{ default: row.is_default }">
            <div class="top">
              <span class="type">{{ row.label || row.label_type_ar }}</span>
              <span v-if="row.is_default" class="badge">افتراضي</span>
            </div>
            <p class="who">{{ row.recipient_name }} · {{ row.phone }}</p>
            <p class="line">{{ row.street_address }}</p>
            <p class="muted">{{ [row.state, row.city, row.country].filter(Boolean).join(' · ') }}</p>
            <p v-if="row.courier_notes" class="notes">ملاحظات المندوب: {{ row.courier_notes }}</p>
            <p class="pin" :class="{ ok: row.has_coordinates }">
              {{ row.has_coordinates ? 'الموقع محدد على الخريطة' : 'لم يُحدد موقع بعد' }}
            </p>
            <div class="actions">
              <button type="button" class="btn outline" @click="startEdit(row)">تعديل</button>
              <button v-if="!row.is_default" type="button" class="btn outline" @click="makeDefault(row)">افتراضي</button>
              <button type="button" class="btn danger" @click="remove(row)">حذف</button>
            </div>
          </article>
        </div>
      </template>
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
import AddressForm from './AddressForm.vue';

const loginHref = `/login?redirect=${encodeURIComponent('/addresses')}`;
const rows = ref([]);
const loading = ref(true);
const editing = ref(false);
const saving = ref(false);
const error = ref('');
const formInitial = ref(null);

async function load() {
  loading.value = true;
  try {
    await hydrateUser();
    if (!store.authToken) return;
    const { data } = await api.get('/addresses');
    rows.value = data;
  } finally {
    loading.value = false;
  }
}

function startCreate() {
  formInitial.value = {
    is_default: rows.value.length === 0,
    country: 'سوريا',
    label_type: 'home',
    label: 'البيت',
  };
  editing.value = true;
  error.value = '';
}

function startEdit(row) {
  formInitial.value = { ...row };
  editing.value = true;
  error.value = '';
}

async function save(payload) {
  saving.value = true;
  error.value = '';
  try {
    const body = { ...payload };
    const id = body.id;
    delete body.id;
    delete body.label_type_ar;
    delete body.has_coordinates;
    delete body.created_at;
    delete body.updated_at;
    delete body.user_id;
    if (id) {
      await api.put(`/addresses/${id}`, body);
    } else {
      await api.post('/addresses', body);
    }
    editing.value = false;
    await load();
  } catch (e) {
    const errs = e?.response?.data?.errors;
    error.value = errs ? Object.values(errs).flat().join(' ') : 'تعذّر الحفظ.';
  } finally {
    saving.value = false;
  }
}

async function makeDefault(row) {
  await api.post(`/addresses/${row.id}/default`);
  await load();
}

async function remove(row) {
  if (!confirm('حذف هذا العنوان؟')) return;
  await api.delete(`/addresses/${row.id}`);
  await load();
}

onMounted(load);
</script>

<style scoped>
.addr-page { min-height: 100vh; background: linear-gradient(180deg, #eaf6f8 0%, #f5fbfc 40%, #f8fafb 100%); color: #132f37; }
.shell { max-width: 880px; margin: 0 auto; padding: 2rem 1.25rem 3.5rem; }
.head { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; margin-bottom: 1.25rem; flex-wrap: wrap; }
h1 { margin: 0; color: #0b3d44; font-size: 1.65rem; }
.sub { margin: .4rem 0 0; color: #4d6b72; max-width: 36rem; }
.list { display: grid; gap: .9rem; }
.panel, .card {
  background: #fff;
  border: 1px solid rgba(28,114,130,.12);
  border-radius: 1.2rem;
  padding: 1.15rem 1.25rem;
  box-shadow: 0 10px 28px rgba(19, 47, 55, 0.04);
}
.card.default { border-color: rgba(28,114,130,.35); }
.top { display: flex; gap: .5rem; align-items: center; margin-bottom: .35rem; }
.type { font-weight: 900; color: #0b3d44; }
.badge { background: #e8f6f8; color: #1c7282; border-radius: 999px; padding: .15rem .55rem; font-size: .75rem; font-weight: 800; }
.who { margin: .15rem 0; font-weight: 700; }
.line { margin: .15rem 0; }
.muted { color: #4d6b72; margin: .15rem 0; }
.notes { margin: .45rem 0 0; color: #0b3d44; background: #f5fbfc; border-radius: .75rem; padding: .55rem .7rem; font-size: .9rem; }
.pin { margin: .55rem 0 0; font-size: .88rem; font-weight: 700; color: #a87400; }
.pin.ok { color: #1c7282; }
.actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .85rem; }
.btn {
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: 999px; padding: .55rem 1rem; font-weight: 800;
  cursor: pointer; text-decoration: none; border: 0;
}
.btn.primary { background: #1c7282; color: #fff; }
.btn.outline { background: #fff; color: #1c7282; border: 2px solid #1c7282; }
.btn.danger { background: #fff; color: #a82626; border: 2px solid #a82626; }
.empty { text-align: center; padding: 2.5rem 1rem; color: #4d6b72; }
.empty .btn { margin-top: 1rem; }
</style>
