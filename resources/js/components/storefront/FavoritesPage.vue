<template>
  <div class="fav-page" dir="rtl">
    <StorefrontNav />
    <div class="shell">
      <header class="head">
        <div>
          <h1>مفضلتي</h1>
          <p class="sub">قوائم متعددة · إضافة للسلة · مشاركة</p>
        </div>
        <button v-if="store.authToken" type="button" class="btn primary" @click="createList">+ قائمة جديدة</button>
      </header>

      <div v-if="!store.authToken" class="empty">
        <p>سجّلي دخولك لعرض المفضلة.</p>
        <a :href="loginHref" class="btn primary">دخول</a>
      </div>

      <template v-else>
        <div v-if="feed.length" class="feed">
          <h2>تحديثات المتاجر التي تتابعينها</h2>
          <a v-for="u in feed" :key="u.vendor_id" :href="u.url" class="feed-row">{{ u.message }}</a>
        </div>

        <div class="lists">
          <button
            v-for="l in lists"
            :key="l.id"
            type="button"
            class="list-chip"
            :class="{ on: activeListId === l.id }"
            @click="selectList(l.id)"
          >
            {{ l.name }}
            <span>{{ l.items_count }}</span>
          </button>
        </div>

        <div v-if="loading" class="empty">جاري التحميل…</div>
        <div v-else-if="!items.length" class="empty">
          <p>هالقائمة فاضية.</p>
          <a href="/shop" class="btn primary">تسوّقي</a>
        </div>
        <div v-else class="grid">
          <article v-for="row in items" :key="row.id" class="card">
            <a :href="row.product?.url" class="img-wrap">
              <img v-if="row.product?.image" :src="row.product.image" :alt="row.product.name" />
            </a>
            <div class="body">
              <h2>{{ row.product?.name }}</h2>
              <p v-if="row.product?.brand" class="muted">{{ row.product.brand }}</p>
              <div class="actions">
                <button type="button" class="btn primary" :disabled="!row.product?.in_stock" @click="addToCart(row)">
                  {{ row.product?.in_stock ? 'أضف للسلة' : 'غير متوفر' }}
                </button>
                <button type="button" class="btn outline" @click="shareProduct(row)">مشاركة</button>
                <button type="button" class="btn outline" @click="movePrompt(row)">نقل</button>
                <button type="button" class="btn danger" @click="remove(row)">إزالة</button>
              </div>
            </div>
          </article>
        </div>

        <section class="followed" v-if="followedStores.length">
          <h2>متاجر أتابعها</h2>
          <div class="store-row">
            <a v-for="s in followedStores" :key="s.id" :href="s.url" class="store-card">
              <img v-if="s.logo" :src="s.logo" :alt="s.store_name" />
              <strong>{{ s.store_name }}</strong>
            </a>
          </div>
        </section>
      </template>
    </div>
    <StorefrontFooter />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../../storefront/api';
import { store, hydrateUser, refreshCartCount } from '../../storefront/store';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const loginHref = `/login?redirect=${encodeURIComponent('/favorites')}`;
const lists = ref([]);
const activeListId = ref(null);
const items = ref([]);
const loading = ref(true);
const feed = ref([]);
const followedStores = ref([]);

async function loadLists() {
  const { data } = await api.get('/v1/wishlist/lists');
  lists.value = data.lists || [];
  if (!activeListId.value && lists.value.length) {
    activeListId.value = lists.value.find((l) => l.is_default)?.id || lists.value[0].id;
  }
}

async function loadItems() {
  if (!activeListId.value) return;
  loading.value = true;
  try {
    const { data } = await api.get('/v1/wishlist', { params: { list_id: activeListId.value } });
    items.value = data.items || [];
  } finally {
    loading.value = false;
  }
}

async function loadExtras() {
  try {
    const [f, s] = await Promise.all([
      api.get('/v1/store-follows/feed'),
      api.get('/v1/store-follows'),
    ]);
    feed.value = f.data.updates || [];
    followedStores.value = s.data.stores || [];
  } catch (_) { /* ignore */ }
}

function selectList(id) {
  activeListId.value = id;
  loadItems();
}

async function createList() {
  const name = prompt('اسم القائمة (مثال: ملابس، أحذية، هدايا)');
  if (!name?.trim()) return;
  const { data } = await api.post('/v1/wishlist/lists', { name: name.trim() });
  await loadLists();
  if (data.list?.id) selectList(data.list.id);
}

async function remove(row) {
  await api.delete(`/v1/wishlist/${row.product_id}`, { params: { list_id: activeListId.value } });
  await loadLists();
  await loadItems();
}

async function movePrompt(row) {
  const options = lists.value.filter((l) => l.id !== activeListId.value);
  if (!options.length) {
    alert('أنشئي قائمة ثانية أولاً.');
    return;
  }
  const names = options.map((l, i) => `${i + 1}) ${l.name}`).join('\n');
  const pick = prompt(`انقلي إلى:\n${names}\nاكتبي الرقم:`);
  const idx = Number(pick) - 1;
  if (!options[idx]) return;
  await api.post('/v1/wishlist/move', {
    product_id: row.product_id,
    from_list_id: activeListId.value,
    to_list_id: options[idx].id,
  });
  await loadLists();
  await loadItems();
}

async function addToCart(row) {
  try {
    await api.post('/cart/items', {
      product_id: row.product_id,
      variant_id: row.product.default_variant_id || undefined,
      quantity: 1,
    });
    await refreshCartCount();
    alert('تمت الإضافة للسلة.');
  } catch (err) {
    alert(err?.response?.data?.message || 'تعذّرت الإضافة للسلة.');
  }
}

async function shareProduct(row) {
  const url = row.product?.url || `${window.location.origin}/products/${row.product_id}`;
  const title = row.product?.name || 'منتج من وصلة';
  if (navigator.share) {
    try {
      await navigator.share({ title, url, text: title });
      return;
    } catch (_) { /* cancelled */ }
  }
  await navigator.clipboard?.writeText(url);
  alert('تم نسخ رابط المنتج.');
}

onMounted(async () => {
  await hydrateUser();
  if (!store.authToken) {
    loading.value = false;
    return;
  }
  await loadLists();
  await Promise.all([loadItems(), loadExtras()]);
});
</script>

<style scoped>
.fav-page { min-height: 100vh; background: linear-gradient(180deg,#eaf6f8,#f7fbfc 35%,#fff); color: #132f37; }
.shell { max-width: 980px; margin: 0 auto; padding: 2rem 1.25rem 3rem; }
.head { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; flex-wrap: wrap; margin-bottom: 1rem; }
h1 { margin: 0; color: #0b3d44; }
.sub { margin: .35rem 0 0; color: #4d6b72; }
.lists { display: flex; flex-wrap: wrap; gap: .45rem; margin-bottom: 1rem; }
.list-chip {
  border: 1.5px solid rgba(28,114,130,.2); background: #fff; border-radius: 999px;
  padding: .4rem .85rem; font-weight: 800; cursor: pointer; display: inline-flex; gap: .4rem;
}
.list-chip span { color: #4d6b72; font-size: .8rem; }
.list-chip.on { background: #1c7282; color: #fff; border-color: #1c7282; }
.list-chip.on span { color: #d7eef1; }
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
.card { background: #fff; border: 1px solid rgba(28,114,130,.12); border-radius: 1.1rem; overflow: hidden; }
.img-wrap { display: block; aspect-ratio: 1; background: #eef8f9; }
.img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.body { padding: 1rem; }
.body h2 { margin: 0 0 .35rem; font-size: 1rem; }
.muted { margin: 0 0 .75rem; color: #4d6b72; font-size: .85rem; }
.actions { display: flex; flex-wrap: wrap; gap: .4rem; }
.btn { border-radius: 999px; padding: .5rem .85rem; font-weight: 800; text-decoration: none; border: 0; cursor: pointer; }
.btn.primary { background: #1c7282; color: #fff; }
.btn.outline { background: #fff; color: #1c7282; border: 2px solid #1c7282; }
.btn.danger { background: #fff; color: #a82626; border: 2px solid #a82626; }
.btn:disabled { opacity: .55; }
.empty { text-align: center; padding: 3rem 1rem; color: #4d6b72; }
.feed { background: #fff; border-radius: 1rem; border: 1px solid rgba(28,114,130,.12); padding: 1rem; margin-bottom: 1rem; }
.feed h2, .followed h2 { margin: 0 0 .65rem; font-size: 1rem; color: #1c7282; }
.feed-row { display: block; padding: .45rem 0; color: #0b3d44; text-decoration: none; font-weight: 700; border-bottom: 1px solid #eef4f5; }
.followed { margin-top: 2rem; }
.store-row { display: flex; flex-wrap: wrap; gap: .75rem; }
.store-card {
  display: flex; align-items: center; gap: .55rem; background: #fff; border-radius: .9rem;
  border: 1px solid rgba(28,114,130,.12); padding: .65rem .85rem; text-decoration: none; color: inherit;
}
.store-card img { width: 36px; height: 36px; border-radius: 999px; object-fit: cover; }
</style>
