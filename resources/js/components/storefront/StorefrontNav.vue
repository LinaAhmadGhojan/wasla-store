<template>
  <header
    class="storefront-nav"
    :class="{
      'theme-express': theme === 'express',
      'theme-home': theme === 'home' || theme === 'store',
    }"
    dir="rtl"
  >
    <div class="nav-top">
      <div class="nav-bar-row">
        <a href="/" class="brand" aria-label="وصلة — تسوق شي إن في سوريا">
          <img :src="logoUrl" alt="وصلة WASLA — توصيل شي إن لسوريا" class="brand-logo" />
          <span v-if="theme === 'home' || theme === 'store'" class="store-channel-tag desktop-only">تسوق</span>
          <span v-if="theme === 'express'" class="express-channel-tag desktop-only">طلباتي</span>
        </a>

        <div class="nav-mobile-tools mobile-only">
          <a
            v-if="store.currentUser"
            href="/profile"
            class="nav-account"
            aria-label="حسابي"
            title="حسابي"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 21c.8-4 3.2-6 7.5-6s6.7 2 7.5 6"/></svg>
          </a>
          <a
            v-else
            :href="loginHref"
            class="nav-account"
            aria-label="تسجيل الدخول"
            title="تسجيل الدخول"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 21c.8-4 3.2-6 7.5-6s6.7 2 7.5 6"/></svg>
          </a>
          <a href="/cart" class="nav-cart-icon" aria-label="سلة التسوق" title="السلة">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="9" cy="20" r="1.4" fill="currentColor" stroke="none"/>
              <circle cx="18" cy="20" r="1.4" fill="currentColor" stroke="none"/>
              <path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.3a1.5 1.5 0 0 0 1.5-1.2L20 8H7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span v-if="store.cartCount > 0" class="nav-cart-badge">{{ store.cartCount }}</span>
          </a>
          <button
            type="button"
            class="menu-toggle"
            :aria-expanded="menuOpen ? 'true' : 'false'"
            aria-controls="storefront-menu"
            :aria-label="menuOpen ? 'إغلاق القائمة' : 'فتح القائمة'"
            @click="menuOpen = !menuOpen"
          >
            <span class="burger" aria-hidden="true">
              <span /><span /><span />
            </span>
          </button>
        </div>

        <a
          v-if="theme === 'home' || theme === 'store'"
          href="/express"
          class="nav-channel-jump nav-channel-express desktop-only"
          title="طلباتي · أكل وتوصيل"
        >طلباتي</a>
        <a
          v-if="theme === 'express'"
          href="/"
          class="nav-channel-jump nav-channel-store desktop-only"
          title="تسوق وصلة · ملابس ومنتجات"
        >تسوق</a>

        <div class="nav-actions desktop-only">
          <a v-if="theme === 'home' || theme === 'store'" href="/favorites" class="nav-favorite" aria-label="المفضلة">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 8.7c0 5.1-8.8 10.1-8.8 10.1S3.2 13.8 3.2 8.7A4.7 4.7 0 0 1 12 6.1a4.7 4.7 0 0 1 8.8 2.6Z" /></svg>
          </a>
        </div>

        <a v-if="theme === 'home' || theme === 'store'" href="/browse" class="locale-pill desktop-only" aria-label="اختيار اللغة">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c2.2 2.4 3.2 5.4 3.2 9S14.2 17.6 12 21C9.8 17.6 8.8 14.6 8.8 12S9.8 6.4 12 3Z" /></svg>
          <span>العربية</span><b>⌄</b>
        </a>

        <div v-if="theme === 'express'" class="express-desktop-actions desktop-only">
          <a href="/" class="nav-icon-btn" title="تسوق وصلة · ملابس">
            <svg viewBox="0 0 24 24"><path d="M6 7h15l-1.5 9h-12L6 7Z"/><path d="M6 7 5 4H2"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
            <span>وصلة</span>
          </a>
          <a href="/express" class="nav-icon-btn nav-icon-active" title="طلباتي · أكل">
            <svg viewBox="0 0 24 24"><path d="M3.5 17h17"/><path d="M6 17V11.5c0-3.3 2.7-6 6-6s6 2.7 6 6V17"/><path d="M12 5.5V3.5"/><path d="M10.5 3.5h3"/></svg>
            <span>طلباتي</span>
          </a>
          <a href="/favorites" class="nav-icon-btn" title="المفضلة">
            <svg viewBox="0 0 24 24"><path d="M20.8 8.7c0 5.1-8.8 10.1-8.8 10.1S3.2 13.8 3.2 8.7A4.7 4.7 0 0 1 12 6.1a4.7 4.7 0 0 1 8.8 2.6Z"/></svg>
            <span>المفضلة</span>
          </a>
          <a href="/cart" class="nav-icon-btn nav-icon-cart" title="السلة">
            <svg viewBox="0 0 24 24"><path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.3a1.5 1.5 0 0 0 1.5-1.2L20 8H7"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
            <span>السلة</span>
            <em v-if="store.cartCount > 0" class="nav-icon-badge">{{ store.cartCount }}</em>
          </a>
          <a v-if="store.currentUser" href="/profile" class="nav-icon-btn" title="حسابي">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 21c.8-4 3.2-6 7.5-6s6.7 2 7.5 6"/></svg>
            <span>حسابي</span>
          </a>
          <a v-else href="/login" class="nav-icon-btn nav-icon-login express-login" title="دخول">
            <span>دخول</span>
          </a>
        </div>

        <div v-if="theme === 'home' || theme === 'store'" class="home-desktop-actions desktop-only">
          <a href="/express" class="nav-icon-btn" title="طلباتي · أكل">
            <svg viewBox="0 0 24 24"><path d="M3.5 17h17"/><path d="M6 17V11.5c0-3.3 2.7-6 6-6s6 2.7 6 6V17"/><path d="M12 5.5V3.5"/><path d="M10.5 3.5h3"/></svg>
            <span>أكل</span>
          </a>
          <a href="/browse" class="nav-icon-btn" title="تسوق عالمي">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.2 2.4 3.2 5.4 3.2 9S14.2 17.6 12 21C9.8 17.6 8.8 14.6 8.8 12S9.8 6.4 12 3Z"/></svg>
            <span>عالمي</span>
          </a>
          <a href="/favorites" class="nav-icon-btn" title="المفضلة">
            <svg viewBox="0 0 24 24"><path d="M20.8 8.7c0 5.1-8.8 10.1-8.8 10.1S3.2 13.8 3.2 8.7A4.7 4.7 0 0 1 12 6.1a4.7 4.7 0 0 1 8.8 2.6Z"/></svg>
            <span>المفضلة</span>
          </a>
          <a href="/cart" class="nav-icon-btn nav-icon-cart" title="السلة">
            <svg viewBox="0 0 24 24"><path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.3a1.5 1.5 0 0 0 1.5-1.2L20 8H7"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
            <span>السلة</span>
            <em v-if="store.cartCount > 0" class="nav-icon-badge">{{ store.cartCount }}</em>
          </a>
          <a v-if="store.currentUser" href="/profile" class="nav-icon-btn" title="حسابي">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 21c.8-4 3.2-6 7.5-6s6.7 2 7.5 6"/></svg>
            <span>حسابي</span>
          </a>
          <a v-else href="/login" class="nav-icon-btn nav-icon-login" title="دخول">
            <span>دخول</span>
          </a>
        </div>
      </div>

      <form
        v-if="theme === 'express' && !hideSearch"
        class="nav-search express-nav-search"
        action="/express"
        method="get"
      >
        <button type="submit" aria-label="بحث">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" /><path d="m16 16 5 5" /></svg>
        </button>
        <input
          type="search"
          name="q"
          :value="initialQuery"
          placeholder="ابحث عن مطعم أو طبق…"
          aria-label="بحث في طلباتي"
        />
      </form>

      <form v-else-if="!hideSearch" class="nav-search" action="/" method="get">
        <a
          v-if="initialQuery"
          href="/"
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
          placeholder="ابحثي عن منتج أو براند"
          aria-label="بحث عن منتج"
        />
      </form>

      <nav
        id="storefront-menu"
        class="nav-links"
        :class="{ open: menuOpen }"
      >
        <p class="menu-title mobile-only">روابط سريعة</p>
        <a href="/" @click="closeMenu">تصفح المنتجات</a>
        <a href="/express" @click="closeMenu">طلباتي </a>
        <a v-if="theme === 'express'" href="/express/errand" @click="closeMenu">مشوار وشحن</a>
        <a href="/browse" @click="closeMenu">تسوق شي إن والعالمي</a>
        <a href="/browse" class="mobile-only" @click="closeMenu">اللغة · العربية</a>
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
          <a href="/my-requests" @click="closeMenu">متابعة الطلبات</a>
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
    default: 'home',
    validator: (v) => ['store', 'express', 'home'].includes(v),
  },
  hideSearch: {
    type: Boolean,
    default: false,
  },
});

const params = new URLSearchParams(window.location.search);
const initialQuery = params.get('q') || '';
const isHomeLike = computed(() => props.theme === 'home' || props.theme === 'store');
const logoUrl = computed(() => (isHomeLike.value || props.theme === 'express')
  ? '/brand/wasla-id-horizontal-white.png?v=6'
  : '/brand/wasla-id-horizontal.png?v=6');
const menuOpen = ref(false);
const loginHref = computed(() => {
  const path = `${window.location.pathname}${window.location.search}`;
  return `/login?redirect=${encodeURIComponent(path)}`;
});

function closeMenu() {
  menuOpen.value = false;
}

function onCartChanged() {
  refreshCartCount();
}

onMounted(async () => {
  await hydrateUser();
  await refreshCartCount();
  window.addEventListener('resize', onResize);
  window.addEventListener('wasla-cart-changed', onCartChanged);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize);
  window.removeEventListener('wasla-cart-changed', onCartChanged);
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
  padding: 0.3rem 1.1rem;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  border-inline: 1px solid rgba(255, 255, 255, 0.12);
}
.nav-bar-row {
  display: contents;
}
.brand {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  text-decoration: none;
}
.brand-logo {
  height: 34px;
  width: auto;
  max-width: min(135px, 40vw);
  display: block;
  object-fit: contain;
  background: transparent;
}
.store-channel-tag,
.express-channel-tag {
  margin-inline-start: 0.35rem;
  padding: 0.18rem 0.45rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.22);
  color: #fff;
  font-size: 0.62rem;
  font-weight: 900;
  line-height: 1.2;
  white-space: nowrap;
}
.nav-channel-jump {
  flex-shrink: 0;
  text-decoration: none;
  font-size: 0.72rem;
  font-weight: 900;
  padding: 0.38rem 0.62rem;
  border-radius: 999px;
  line-height: 1.1;
  white-space: nowrap;
  border: 1.5px solid transparent;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
}
.nav-channel-express {
  background: #c4681a;
  color: #fff;
  border-color: rgba(255, 255, 255, 0.35);
}
.nav-channel-store {
  background: #087b8d;
  color: #fff;
  border-color: rgba(255, 255, 255, 0.35);
}
.nav-mobile-tools {
  display: none;
  align-items: center;
  gap: 0.15rem;
  flex-shrink: 0;
}
.nav-mobile-tools .menu-toggle {
  padding: 0.35rem;
  border: 0;
  background: transparent;
  color: #fff;
  border-radius: 0.5rem;
}
.nav-mobile-tools .burger {
  width: 18px;
  gap: 4px;
}
.nav-mobile-tools .burger span {
  height: 2.5px;
  border-radius: 99px;
  background: currentColor;
}
.nav-cart-icon {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2rem;
  min-height: 2rem;
  padding: 0.2rem;
  color: inherit;
  text-decoration: none;
}
.nav-cart-icon svg {
  width: 1.25rem;
  height: 1.25rem;
}
.nav-cart-badge {
  position: absolute;
  top: 0.05rem;
  inset-inline-start: 0.1rem;
  min-width: 0.95rem;
  height: 0.95rem;
  padding: 0 0.18rem;
  border-radius: 999px;
  background: #e53935;
  color: #fff;
  font-size: 0.58rem;
  font-weight: 900;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1.5px solid currentColor;
  border-color: rgba(255, 255, 255, 0.85);
}
.theme-express .nav-cart-badge {
  border-color: #8a4b12;
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
  padding: 0.42rem 0.75rem;
  outline: none;
  font-size: 0.86rem;
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
.desktop-only { display: none !important; }
.desktop-inline { display: inline-flex !important; }
.home-desktop-actions,
.express-desktop-actions { display: none; align-items: center; gap: 0.35rem; flex-shrink: 0; }
.nav-icon-btn.nav-icon-active {
  background: rgba(255, 255, 255, 0.22);
  color: #fff;
}
.nav-icon-login.express-login {
  background: #fff;
  color: #7a4518 !important;
}
.nav-icon-btn {
  position: relative;
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.12rem;
  min-width: 3.1rem;
  padding: 0.35rem 0.45rem;
  border-radius: 0.75rem;
  text-decoration: none;
  color: rgba(255, 255, 255, 0.92);
  font-size: 0.62rem;
  font-weight: 800;
  line-height: 1.1;
  transition: background 0.15s ease;
}
.nav-icon-btn:hover { background: rgba(255, 255, 255, 0.12); color: #fff; }
.nav-icon-btn svg {
  width: 1.35rem;
  height: 1.35rem;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}
.nav-icon-btn svg path[fill] { fill: currentColor; stroke: none; }
.nav-icon-cart svg { fill: none; }
.nav-icon-login {
  min-width: auto;
  padding: 0.45rem 0.85rem;
  border-radius: 999px;
  background: #fff;
  color: #087b8d !important;
  font-size: 0.78rem;
}
.nav-icon-login:hover { filter: brightness(0.98); background: #fff; }
.nav-icon-badge {
  position: absolute;
  top: 0.15rem;
  inset-inline-start: 0.35rem;
  min-width: 1rem;
  height: 1rem;
  padding: 0 0.2rem;
  border-radius: 999px;
  background: #e53935;
  color: #fff;
  font-style: normal;
  font-size: 0.58rem;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* طلباتي  — ثيم بني */
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
.theme-home .nav-top { max-width: 760px; padding: 0.28rem 0.7rem; }
.theme-home .brand { order: 1; }
.theme-home .brand-logo { height: 38px; max-width: 155px; }
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
.theme-home .nav-links a {
  line-height: 1.1;
}
.locale-pill {
  order: 2;
  display: inline-flex;
  align-items: center;
  gap: 0.22rem;
  padding: 0.38rem 0.62rem;
  border-radius: 999px;
  background: rgba(255,255,255,.16);
  color: #fff;
  font-size: 0.74rem;
  font-weight: 800;
  text-decoration: none;
  white-space: nowrap;
  flex-shrink: 0;
}
.locale-pill span { white-space: nowrap; }
.locale-pill b { font-size: 0.9rem; line-height: 1; font-weight: 900; }
.theme-home .nav-actions { order: 2; }
.theme-home .cart-pill { color: #0f5a66 !important; }
.theme-home .cart-ico { color: #1c7282; }
.theme-home .nav-favorite { color: #fff !important; }
.theme-home .nav-actions,
.theme-home .nav-actions button,
.theme-home .nav-actions a,
.theme-home .locale-pill,
.theme-home .brand { color: #fff; }
.nav-favorite,
.nav-account { text-decoration: none; line-height: 1; color: #fff; }
.nav-account {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.35rem;
  min-width: 2.5rem;
  min-height: 2.5rem;
  border-radius: 999px;
}
.nav-account svg {
  width: 1.35rem;
  height: 1.35rem;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}
.nav-account svg path:last-child { fill: none; }
.nav-login-chip {
  font-size: 0.72rem;
  font-weight: 900;
  padding: 0.38rem 0.55rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.35);
}
.theme-express .nav-login-chip {
  background: rgba(255, 255, 255, 0.92);
  color: #5c3210;
  border-color: #fff;
}

@media (max-width: 860px) {
  .nav-top {
    flex-direction: column;
    align-items: stretch;
    flex-wrap: nowrap;
    border-inline: 0;
    gap: 0.4rem;
    padding: 0.45rem 0.65rem 0.5rem;
  }
  .nav-bar-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    gap: 0.5rem;
    min-height: 2.85rem;
  }
  .nav-mobile-tools {
    display: inline-flex !important;
    margin-inline-start: auto;
  }
  .nav-bar-row .brand {
    flex: 0 1 auto;
    min-width: 0;
    justify-content: flex-start;
  }
  .nav-mobile-tools .nav-account,
  .nav-mobile-tools .nav-cart-icon {
    min-width: 2.35rem;
    min-height: 2.35rem;
    padding: 0.28rem;
    background: transparent;
    border: 0;
  }
  .nav-mobile-tools .nav-account svg {
    width: 1.28rem;
    height: 1.28rem;
  }
  .nav-mobile-tools .nav-cart-icon svg {
    width: 1.32rem;
    height: 1.32rem;
  }
  .brand-logo { height: 42px; max-width: 158px; }
  .mobile-only { display: inline-flex !important; }
  .desktop-inline { display: none !important; }
  .nav-search {
    order: unset;
    max-width: 100%;
    flex-basis: auto;
    width: 100%;
  }
  .nav-search input {
    padding: 0.38rem 0.65rem;
    font-size: 0.82rem;
  }
  .nav-search button { padding: 0 0.65rem; }
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
  .theme-express .cart-pill {
    background: #fff;
    color: #5c3210 !important;
    border-color: #fff;
    padding: 0.42rem 0.78rem 0.42rem 0.65rem;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.14);
  }
  .theme-express .cart-label { display: inline; font-size: 0.82rem; }
  .theme-express .cart-ico { color: #8a4b12; }
  .theme-express .cart-badge { border-color: #fff; }
  .theme-home .nav-top,
  .theme-express .nav-top {
    gap: 0.35rem;
    padding: 0.42rem 0.6rem 0.48rem;
  }
  .theme-home .brand-logo,
  .theme-express .brand-logo {
    height: 44px;
    max-width: 168px;
  }
  .theme-home .nav-mobile-tools,
  .theme-express .nav-mobile-tools {
    color: #fff;
  }
  .theme-home .nav-search,
  .theme-express .nav-search {
    margin-top: 0;
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.38);
  }
  .theme-express .express-nav-search input { color: #fff; }
  .theme-express .express-nav-search input::placeholder { color: rgba(255, 255, 255, 0.75); }
  .theme-express .express-nav-search button { color: #fff; }
}

@media (min-width: 861px) {
  .desktop-only { display: inline-flex !important; }
  .theme-home .nav-top {
    max-width: 1200px;
    margin: 0 auto;
    flex-wrap: nowrap;
    align-items: center;
    padding: 0.55rem 1.25rem;
    gap: 0.85rem;
  }
  .theme-home .brand { order: 1; flex-shrink: 0; }
  .theme-home .brand-logo { height: 42px; max-width: 170px; }
  .theme-home .nav-search {
    order: 2;
    flex: 1 1 auto;
    flex-basis: auto;
    max-width: min(520px, 42vw);
    margin: 0;
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(255, 255, 255, 0.95);
  }
  .theme-home .nav-search input { color: #132f37; }
  .theme-home .nav-search input::placeholder { color: #7a9197; }
  .theme-home .nav-search button { color: #087b8d; }
  .theme-home .home-desktop-actions {
    order: 3;
    display: inline-flex !important;
    margin-inline-start: auto;
  }
  .theme-home .nav-actions { display: none !important; }
  .theme-home .nav-links {
    order: 4;
    display: none !important;
  }

  .theme-express .nav-top {
    max-width: 1200px;
    margin: 0 auto;
    flex-wrap: nowrap;
    align-items: center;
    padding: 0.55rem 1.25rem;
    gap: 0.85rem;
  }
  .theme-express .brand { order: 1; flex-shrink: 0; }
  .theme-express .brand-logo { height: 42px; max-width: 170px; }
  .theme-express .express-nav-search {
    order: 2;
    flex: 1 1 auto;
    flex-basis: auto;
    max-width: min(480px, 40vw);
    display: flex !important;
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(255, 255, 255, 0.95);
  }
  .theme-express .express-nav-search input { color: #3d2817; }
  .theme-express .express-nav-search input::placeholder { color: #9a7a5c; }
  .theme-express .express-nav-search button { color: #7a4518; }
  .theme-express .express-desktop-actions {
    order: 3;
    display: inline-flex !important;
    margin-inline-start: auto;
  }
  .theme-express .nav-actions { display: none !important; }
  .theme-express .nav-links { display: none !important; }
  .theme-express .nav-search:not(.express-nav-search) {
    order: 2;
    flex: 1;
    max-width: min(480px, 40vw);
    background: rgba(255, 255, 255, 0.95);
  }
}
</style>
