<template>
  <header class="storefront-nav" :class="{ 'theme-express': theme === 'express', 'theme-home': theme === 'home' }" dir="rtl">
    <div class="nav-top">
      <a href="/" class="brand" aria-label="وصلة — تسوق شي إن في سوريا">
        <img :src="logoUrl" alt="وصلة WASLA — توصيل شي إن لسوريا" class="brand-logo" />
      </a>

      <div class="nav-actions">
        <a v-if="theme === 'home'" href="/favorites" class="nav-favorite mobile-only" aria-label="المفضلة">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 8.7c0 5.1-8.8 10.1-8.8 10.1S3.2 13.8 3.2 8.7A4.7 4.7 0 0 1 12 6.1a4.7 4.7 0 0 1 8.8 2.6Z" /></svg>
        </a>
        <a href="/cart" class="cart-pill mobile-only" aria-label="سلة التسوق">
          <span class="cart-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="9" cy="20" r="1.4" fill="currentColor" stroke="none"/>
              <circle cx="18" cy="20" r="1.4" fill="currentColor" stroke="none"/>
              <path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.3a1.5 1.5 0 0 0 1.5-1.2L20 8H7"/>
            </svg>
          </span>
          <span class="cart-label">السلة</span>
          <span v-if="store.cartCount > 0" class="cart-badge">{{ store.cartCount }}</span>
        </a>
        <button
          type="button"
          class="menu-toggle mobile-only"
          :aria-expanded="menuOpen ? 'true' : 'false'"
          aria-controls="storefront-menu"
          :aria-label="menuOpen ? 'إغلاق القائمة' : 'فتح القائمة'"
          @click="menuOpen = !menuOpen"
        >
          <span class="burger" aria-hidden="true">
            <span /><span /><span />
          </span>
          <span class="menu-label">{{ menuOpen ? 'إغلاق' : 'المزيد' }}</span>
        </button>
      </div>

      <form v-if="!hideSearch" class="nav-search" action="/shop" method="get">
        <a
          v-if="initialQuery"
          href="/shop"
          class="nav-clear-search"
          title="مسح البحث والرجوع"
          aria-label="مسح البحث والرجوع"
        >←</a>
        <button type="submit" aria-label="بحث">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" /><path d="m16 16 5 5" /></svg>
        </button>
        <input
          type="search"
          name="q"
          :value="initialQuery"
          :placeholder="theme === 'home' ? 'ابحثي عن منتج أو براند' : 'ابحثي عن منتج… شي إن، براند، SKU'"
          aria-label="بحث عن منتج"
        />
      </form>

      <a v-if="theme === 'home'" href="/browse" class="locale-pill" aria-label="اختيار اللغة">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c2.2 2.4 3.2 5.4 3.2 9S14.2 17.6 12 21C9.8 17.6 8.8 14.6 8.8 12S9.8 6.4 12 3Z" /></svg>
        <span>العربية</span><b>⌄</b>
      </a>

      <nav
        id="storefront-menu"
        class="nav-links"
        :class="{ open: menuOpen }"
      >
        <p class="menu-title mobile-only">روابط سريعة</p>
        <a href="/shop" @click="closeMenu">تصفح المنتجات</a>
        <a href="/express" @click="closeMenu">وصلة السريعة</a>
        <a href="/browse" @click="closeMenu">تسوق شي إن والعالمي</a>
        <a href="/buy-from-anywhere" @click="closeMenu">لصق رابط منتج</a>
        <a href="/compare" @click="closeMenu">مقارنة</a>
        <a href="/cart" class="cart-pill desktop-inline" @click="closeMenu">
          <span class="cart-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="9" cy="20" r="1.4" fill="currentColor" stroke="none"/>
              <circle cx="18" cy="20" r="1.4" fill="currentColor" stroke="none"/>
              <path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.3a1.5 1.5 0 0 0 1.5-1.2L20 8H7"/>
            </svg>
          </span>
          <span class="cart-label">السلة</span>
          <span v-if="store.cartCount > 0" class="cart-badge">{{ store.cartCount }}</span>
        </a>
        <template v-if="store.currentUser">
          <a href="/my-requests" @click="closeMenu">طلباتي</a>
          <a href="/favorites" @click="closeMenu">المفضلة</a>
          <a href="/profile" @click="closeMenu">حسابي</a>
          <span class="nav-user">{{ store.currentUser.name }}</span>
          <a href="#" class="nav-logout" @click.prevent="onLogout">خروج</a>
        </template>
        <template v-else>
          <a href="/login" class="btn-login" @click="closeMenu">دخول</a>
        </template>
      </nav>
    </div>
    <div v-if="menuOpen" class="menu-backdrop mobile-only" @click="closeMenu" />
  </header>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue';
import { store, hydrateUser, refreshCartCount, logout } from '../../storefront/store';

const props = defineProps({
  theme: {
    type: String,
    default: 'store',
    validator: (v) => ['store', 'express', 'home'].includes(v),
  },
  hideSearch: {
    type: Boolean,
    default: false,
  },
});

const params = new URLSearchParams(window.location.search);
const initialQuery = params.get('q') || '';
const logoUrl = computed(() => props.theme === 'home'
  ? '/brand/wasla-id-horizontal-white.png?v=6'
  : '/brand/wasla-id-horizontal.png?v=6');
const menuOpen = ref(false);

function closeMenu() {
  menuOpen.value = false;
}

onMounted(async () => {
  await hydrateUser();
  await refreshCartCount();
  window.addEventListener('resize', onResize);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize);
});

function onResize() {
  if (window.innerWidth > 860) menuOpen.value = false;
}

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
  max-width: min(160px, 42vw);
  display: block;
  object-fit: contain;
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
  min-width: 0;
}
.nav-search input {
  flex: 1;
  min-width: 0;
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
  flex-shrink: 0;
}
.nav-clear-search {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  margin-inline-start: 0.35rem;
  border-radius: 999px;
  background: #ffffff;
  color: #1c7282;
  text-decoration: none;
  font-weight: 900;
  font-size: 1rem;
  line-height: 1;
}
.nav-links {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-shrink: 0;
  font-weight: 700;
  font-size: 0.92rem;
}
.nav-links a {
  color: #ffffff;
  text-decoration: none;
}
.nav-links a:hover { color: rgba(255, 255, 255, 0.82); }
.menu-title { display: none; }
.cart-pill {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.85rem 0.4rem 0.7rem;
  border-radius: 999px;
  background: #ffffff;
  color: #0f5a66 !important;
  font-weight: 900;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
  border: 1.5px solid #ffffff;
  line-height: 1;
}
.cart-pill:hover { color: #0b3d44 !important; filter: brightness(0.98); }
.cart-ico {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #1c7282;
}
.cart-label { font-size: 0.88rem; }
.cart-badge {
  position: absolute;
  top: -0.45rem;
  inset-inline-start: -0.35rem;
  background: #e0455a;
  color: white;
  font-size: 0.68rem;
  min-width: 1.15rem;
  height: 1.15rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.28rem;
  border-radius: 999px;
  font-weight: 900;
  border: 2px solid #fff;
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
.nav-actions {
  display: none;
  align-items: center;
  gap: 0.5rem;
  margin-inline-start: auto;
}
.menu-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border: 1.5px solid rgba(255, 255, 255, 0.65);
  background: rgba(255, 255, 255, 0.16);
  color: #fff;
  border-radius: 999px;
  padding: 0.42rem 0.8rem 0.42rem 0.65rem;
  font-weight: 900;
  cursor: pointer;
  font-size: 0.82rem;
}
.burger {
  display: inline-flex;
  flex-direction: column;
  gap: 3px;
  width: 16px;
}
.burger span {
  display: block;
  height: 2px;
  width: 100%;
  background: #fff;
  border-radius: 99px;
}
.menu-label { letter-spacing: 0; }
.menu-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(11, 40, 46, 0.35);
  z-index: 40;
}
.mobile-only { display: none !important; }
.desktop-inline { display: inline-flex !important; }

/* وصلة السريعة — ثيم بني */
.storefront-nav.theme-express {
  background: #8a4b12;
  border-bottom-color: #5c3210;
  box-shadow: 0 2px 0 rgba(255, 255, 255, 0.1), 0 6px 18px rgba(92, 50, 16, 0.28);
}
.theme-express .nav-clear-search { color: #8a4b12; }
.theme-express .cart-pill { color: #5c3210 !important; }
.theme-express .cart-pill:hover { color: #3d2817 !important; }
.theme-express .cart-ico { color: #8a4b12; }
.theme-express .btn-login { color: #8a4b12 !important; }

.theme-home {
  background: #087b8d;
  border-bottom: 3px solid #075d6b;
  box-shadow: 0 4px 16px rgba(15, 79, 90, 0.2);
}
.theme-home .nav-top { max-width: 760px; padding: 0.6rem 1rem; }
.theme-home .brand { order: 1; }
.theme-home .brand-logo { height: 48px; }
.theme-home .nav-search {
  order: 3;
  flex-basis: 100%;
  max-width: none;
  background: rgba(255,255,255,.14);
  border-color: rgba(255,255,255,.42);
}
.theme-home .nav-search input { color: #fff; }
.theme-home .nav-search input::placeholder { color: rgba(255,255,255,.78); }
.theme-home .nav-search button { color: #fff; }
.nav-search button svg, .nav-favorite svg, .locale-pill svg { width: 1.25rem; height: 1.25rem; display: block; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.theme-home .nav-links { order: 4; }
.locale-pill {
  order: 2;
  display: inline-flex;
  align-items: center;
  gap: 0.28rem;
  padding: 0.58rem 0.8rem;
  border-radius: 999px;
  background: rgba(255,255,255,.16);
  color: #fff;
  font-size: 0.78rem;
  font-weight: 800;
  text-decoration: none;
  white-space: nowrap;
  flex-shrink: 0;
}
.locale-pill span { white-space: nowrap; }
.locale-pill b { font-size: 0.9rem; line-height: 1; font-weight: 900; }
.theme-home .nav-actions { order: 2; }
.nav-favorite { color: #fff; text-decoration: none; line-height: 1; }

@media (max-width: 860px) {
  .nav-top {
    flex-wrap: wrap;
    border-inline: 0;
    gap: 0.75rem;
    padding: 0.65rem 0.9rem;
  }
  .brand-logo { height: 40px; }
  .nav-actions { display: flex !important; }
  .mobile-only { display: inline-flex !important; }
  .desktop-inline { display: none !important; }
  .nav-search {
    order: 3;
    max-width: 100%;
    flex-basis: 100%;
  }
  .storefront-nav:not(:has(.nav-search)) .nav-top {
    flex-wrap: nowrap;
  }
  .nav-links {
    display: none;
    position: absolute;
    top: calc(100% + 0px);
    inset-inline: 0.75rem;
    flex-direction: column;
    align-items: stretch;
    gap: 0.15rem;
    background: #0f5a66;
    border-radius: 1rem;
    padding: 0.65rem;
    box-shadow: 0 18px 40px rgba(15, 79, 90, 0.35);
    z-index: 60;
    max-height: min(70vh, 520px);
    overflow: auto;
  }
  .nav-links.open { display: flex; }
  .menu-title {
    display: block;
    margin: 0.15rem 0.5rem 0.45rem;
    color: #a9d7de;
    font-size: 0.78rem;
    font-weight: 800;
  }
  .nav-links a,
  .nav-links .nav-user,
  .nav-links .btn-login {
    padding: 0.8rem 0.9rem;
    border-radius: 0.75rem;
  }
  .nav-links a:hover { background: rgba(255, 255, 255, 0.08); }
  .btn-login {
    text-align: center;
    margin-top: 0.25rem;
  }
  .cart-pill {
    padding: 0.45rem 0.8rem;
  }
  .cart-label { font-size: 0.84rem; }

  .theme-express .nav-links {
    background: #5c3210;
    box-shadow: 0 18px 40px rgba(61, 40, 23, 0.4);
  }
  .theme-express .menu-title { color: #e8c9a0; }
  .theme-express .menu-backdrop { background: rgba(61, 40, 23, 0.4); }
  .theme-home .nav-top { gap: 0.5rem; padding: 0.55rem 0.75rem 0.7rem; }
  .theme-home .brand-logo { height: 42px; max-width: 135px; }
  .theme-home .nav-actions { margin-inline-start: 0; }
  .theme-home .cart-pill { padding: 0; background: transparent; border: 0; box-shadow: none; font-size: 1.8rem; }
  .theme-home .cart-label { display: none; }
  .theme-home .cart-ico svg { width: 28px; height: 28px; }
  .theme-home .cart-badge { top: -0.25rem; inset-inline-start: -0.25rem; }
  .theme-home .menu-toggle { order: 0; margin-inline-end: auto; padding: 0.35rem; border: 0; background: transparent; }
  .theme-home .menu-label { display: none; }
  .theme-home .burger span { background: #fff; height: 2px; }
  .theme-home .locale-pill { order: 1; }
  .theme-home .locale-pill { padding: 0.48rem 0.62rem; font-size: 0.72rem; }
  .theme-home .locale-pill svg { width: 1rem; height: 1rem; }
  .theme-home .nav-search { margin-top: 0.15rem; }
}
</style>
