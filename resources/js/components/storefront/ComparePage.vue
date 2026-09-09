<template>
  <div class="compare-page" dir="rtl">
    <StorefrontNav />

    <div class="shell">
      <header>
        <h1>مقارنة المنتجات</h1>
        <p>اختاري حتى 3 منتجات وقارني السعر والتقييم والمواد والمتجر.</p>
      </header>

      <div v-if="loading" class="empty">جاري التحميل…</div>
      <div v-else-if="!products.length" class="empty">
        <p>ما في منتجات للمقارنة بعد.</p>
        <a href="/shop" class="btn">تصفح المنتجات</a>
      </div>
      <template v-else>
        <div class="cards">
          <article v-for="p in products" :key="p.id" class="card">
            <button type="button" class="rm" @click="remove(p.id)">إزالة</button>
            <a :href="`/products/${p.id}`">
              <img v-if="p.image" :src="p.image" :alt="p.name" />
              <div v-else class="ph">وصلة</div>
              <h2>{{ p.name }}</h2>
            </a>
          </article>
          <a v-if="products.length < 3" href="/shop" class="card add">+ أضيفي منتجاً</a>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>الميزة</th>
                <th v-for="p in products" :key="p.id">{{ p.name }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.key">
                <th>{{ row.label }}</th>
                <td v-for="(val, idx) in row.values" :key="idx">{{ val }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../../storefront/api';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';
import { getCompareIds, removeCompare } from '../../storefront/compareTray';

function writeCompareIdsFallback(ids) {
  localStorage.setItem('wasla_compare_ids', JSON.stringify(ids));
  window.dispatchEvent(new CustomEvent('wasla:compare-changed', { detail: ids }));
}

const products = ref([]);
const rows = ref([]);
const loading = ref(true);

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams(window.location.search);
    let ids = (params.get('ids') || '').split(',').map(Number).filter(Boolean);
    if (!ids.length) ids = getCompareIds();
    if (!ids.length) {
      products.value = [];
      rows.value = [];
      return;
    }
    const { data } = await api.get('/v1/catalog/compare', { params: { ids: ids.join(',') } });
    products.value = data.products || [];
    rows.value = data.rows || [];
    writeCompareIdsFallback(products.value.map((p) => p.id));
  } finally {
    loading.value = false;
  }
}

function remove(id) {
  removeCompare(id);
  const next = products.value.filter((p) => p.id !== id).map((p) => p.id);
  const qs = next.length ? `?ids=${next.join(',')}` : '';
  window.history.replaceState({}, '', `/compare${qs}`);
  load();
}

onMounted(load);
</script>

<style scoped>
.compare-page { min-height: 100vh; background: #f5fbfc; color: #132f37; }
.shell { max-width: 1100px; margin: 0 auto; padding: 2rem 1.25rem 3rem; }
header h1 { margin: 0; color: #0b3d44; }
header p { color: #4d6b72; }
.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin: 1.25rem 0; }
.card {
  position: relative; background: #fff; border-radius: 1rem;
  border: 1px solid rgba(28,114,130,.12); padding: 1rem; text-align: center;
}
.card a { color: inherit; text-decoration: none; }
.card img, .ph {
  width: 100%; height: 160px; object-fit: cover; border-radius: .75rem; background: #e8f2f4;
  display: flex; align-items: center; justify-content: center; color: #1c7282; font-weight: 800;
}
.card h2 { font-size: .95rem; margin: .65rem 0 0; }
.card.add {
  display: flex; align-items: center; justify-content: center; font-weight: 900;
  color: #1c7282; text-decoration: none; border-style: dashed;
}
.rm {
  position: absolute; top: .5rem; inset-inline-start: .5rem; border: 0;
  background: #fff; color: #a82626; border-radius: 999px; padding: .2rem .55rem;
  font-weight: 800; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.table-wrap { overflow: auto; background: #fff; border-radius: 1rem; border: 1px solid rgba(28,114,130,.12); }
table { width: 100%; border-collapse: collapse; min-width: 640px; }
th, td { padding: .85rem 1rem; border-bottom: 1px solid #eef4f5; text-align: right; vertical-align: top; }
thead th { background: #f5fbfc; color: #0b3d44; }
tbody th { width: 160px; color: #1c7282; font-weight: 800; background: #fafefe; }
.empty { text-align: center; padding: 3rem 1rem; color: #4d6b72; }
.btn {
  display: inline-flex; margin-top: 1rem; background: #1c7282; color: #fff;
  text-decoration: none; border-radius: 999px; padding: .65rem 1.2rem; font-weight: 800;
}
</style>
