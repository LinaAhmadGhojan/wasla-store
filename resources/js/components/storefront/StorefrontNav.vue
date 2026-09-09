<template>
  <header class="storefront-nav" dir="rtl">
    <div class="nav-top">
      <a href="/" class="brand" aria-label="وصلة">
        <img :src="logoUrl" alt="وصلة WASLA" class="brand-logo" />
      </a>

      <form class="nav-search" action="/shop" method="get">
        <button type="submit" aria-label="بحث">بحث</button>
        <input
          type="search"
          name="q"
          :value="initialQuery"
          placeholder="بحث متقدم: اسم، SKU، براند…"
          aria-label="بحث عن منتج"
        />
      </form>

      <nav class="nav-links">
        <a href="/shop">تصفح</a>
        <a href="/compare">مقارنة</a>
        <a href="/browse">تسوق عالمي</a>
        <a href="/buy-from-anywhere">لصق رابط</a>
        <a href="/cart" class="cart-link">
          السلة
          <span v-if="store.cartCount > 0" class="cart-badge">{{ store.cartCount }}</span>
        </a>
        <template v-if="store.currentUser">
          <a href="/my-requests">طلباتي</a>
            <a href="/favorites">المفضلة</a>
            <a href="/compare">مقارنة</a>
          <a href="/profile">حسابي</a>
          <span class="nav-user">{{ store.currentUser.name }}</span>
          <a href="#" class="nav-logout" @click.prevent="onLogout">خروج</a>
        </template>
        <template v-else>
          <a href="/login" class="btn-login">دخول</a>
        </template>
      </nav>
    </div>
  </header>
</template>

<script setup>
import { onMounted } from 'vue';
import { store, hydrateUser, refreshCartCount, logout } from '../../storefront/store';

const params = new URLSearchParams(window.location.search);
const initialQuery = params.get('q') || '';
const logoUrl = '/brand/wasla-id-horizontal.png?v=6';

onMounted(async () => {
  await hydrateUser();
  await refreshCartCount();
});

async function onLogout() {
  try {
    const api = (await import('../../storefront/api')).default;
    await api.post('/auth/logout');
  } catch (e) {
    // ignore
  }
  logout();
  window.location.href = '/';
}
</script>

<style scoped>
.storefront-nav {
  background: #1c7282;
  border-bottom: 3px solid #0f4f5a;
  box-shadow: 0 2px 0 rgba(255, 255, 255, 0.12), 0 6px 18px rgba(15, 79, 90, 0.22);
  position: sticky;
  top: 0;
  z-index: 50;
}
.nav-top {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0.65rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  border-inline: 1px solid rgba(255, 255, 255, 0.12);
}
.brand {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  text-decoration: none;
}
.brand-logo {
  height: 48px;
  width: auto;
  display: block;
  background: transparent;
}
.nav-search {
  flex: 1;
  display: flex;
  max-width: 34rem;
  border: 1.5px solid rgba(255, 255, 255, 0.35);
  border-radius: 999px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.14);
  align-items: center;
}
.nav-search input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 0.65rem 1rem;
  outline: none;
  font-size: 0.92rem;
  color: #ffffff;
}
.nav-search input::placeholder { color: rgba(255, 255, 255, 0.72); }
.nav-search button {
  border: none;
  background: transparent;
  padding: 0 0.85rem;
  cursor: pointer;
  font-size: 1rem;
  color: #ffffff;
}
.nav-links {
  display: flex;
  align-items: center;
  gap: 1.1rem;
  flex-shrink: 0;
  font-weight: 700;
  font-size: 0.92rem;
}
.nav-links a {
  color: #ffffff;
  text-decoration: none;
}
.nav-links a:hover { color: rgba(255, 255, 255, 0.82); }
.cart-link { position: relative; }
.cart-badge {
  position: absolute;
  top: -0.55rem;
  left: -0.75rem;
  background: #e0455a;
  color: white;
  font-size: 0.68rem;
  padding: 0.05rem 0.38rem;
  border-radius: 999px;
  font-weight: 800;
}
.nav-user { color: rgba(255, 255, 255, 0.92); font-weight: 600; }
.nav-logout { color: #ffb3b3 !important; }
.btn-login {
  padding: 0.45rem 1.15rem !important;
  border-radius: 999px;
  background: #ffffff;
  color: #1c7282 !important;
  border: 1.5px solid #ffffff;
}
@media (max-width: 860px) {
  .nav-top { flex-wrap: wrap; border-inline: 0; }
  .nav-search { order: 3; max-width: 100%; flex-basis: 100%; }
  .nav-links { gap: 0.75rem; font-size: 0.85rem; }
  .brand-logo { height: 42px; }
}
</style>
