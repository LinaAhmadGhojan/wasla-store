<template>
  <nav
    class="home-bottom-nav"
    :class="{ 'theme-express': channelMode === 'express' }"
    :style="{ '--notch-x': notchX, '--accent': accent }"
    aria-label="التنقل الرئيسي"
  >
    <div class="nav-bar-bg" aria-hidden="true" />

    <a
      v-for="item in navItems"
      :key="item.key"
      :href="item.href"
      :class="[item.className, { active: active === item.key }]"
      @click="item.onClick ? item.onClick($event) : undefined"
    >
      <span v-if="active === item.key" class="nav-fab">
        <span class="nav-fab-circle">
          <span class="nav-icon-slot nav-fab-icon"><span v-html="item.icon" /></span>
          <em
            v-if="item.key === 'cart' && store.cartCount > 0"
            class="nav-cart-badge"
          >{{ store.cartCount }}</em>
        </span>
      </span>
      <span class="nav-slot">
        <span v-if="active !== item.key" class="nav-icon-slot nav-icon-inline" v-html="item.icon" />
      </span>
      <span class="nav-label" :class="{ 'nav-label-active': active === item.key }">{{ item.label }}</span>
    </a>
  </nav>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { rememberChannel } from '../../storefront/channel';
import { store, hydrateUser, refreshCartCount } from '../../storefront/store';

const props = defineProps({
  active: {
    type: String,
    default: 'home',
  },
  channelMode: {
    type: String,
    default: 'store',
    validator: (v) => ['store', 'express'].includes(v),
  },
  /** @deprecated use channelMode */
  theme: {
    type: String,
    default: '',
  },
});

const icons = {
  home: '<svg viewBox="0 0 24 24"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1Z"/></svg>',
  express: '<svg viewBox="0 0 24 24"><path d="M3.5 17h17"/><path d="M6 17V11.5c0-3.3 2.7-6 6-6s6 2.7 6 6V17"/><path d="M12 5.5V3.5"/><path d="M10.5 3.5h3"/></svg>',
  clothes: '<svg viewBox="0 0 24 24"><path d="M6 3 3 7v2h18V7l-3-4H6Z"/><path d="M4 9v11h16V9"/><path d="M9 9c0 2.2 1.5 4 3 4s3-1.8 3-4"/></svg>',
  cart: '<svg viewBox="0 0 24 24"><path d="M6 7h15l-1.5 9h-12L6 7Z"/><path d="M6 7 5 4H2"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>',
  browse: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.2 2.4 3.2 5.4 3.2 9S14.2 17.6 12 21C9.8 17.6 8.8 14.6 8.8 12S9.8 6.4 12 3Z"/></svg>',
  errand: '<svg viewBox="0 0 24 24"><path d="M8 4h8l2 4H6l2-4Z"/><path d="M5 8v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"/><path d="M9 12h6M9 16h4"/></svg>',
  orders: '<svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><path d="M9 3h6v4H9z"/><path d="M9 12h6M9 16h4"/></svg>',
};

const storeNavItems = [
  { key: 'home', href: '/', label: 'الرئيسية', className: '', icon: icons.home, onClick: () => rememberChannel('store') },
  {
    key: 'express',
    href: '/express',
    label: 'أكل',
    className: 'nav-express',
    icon: icons.express,
    onClick: () => rememberChannel('express'),
  },
  { key: 'cart', href: '/cart', label: 'السلة', className: 'nav-cart', icon: icons.cart, onClick: undefined },
  { key: 'browse', href: '/browse', label: 'عالمي', className: '', icon: icons.browse, onClick: undefined },
  { key: 'orders', href: '/my-requests', label: 'متابعة', className: 'nav-orders', icon: icons.orders, onClick: undefined },
];

const expressNavItems = [
  { key: 'home', href: '/express', label: 'الرئيسية', className: '', icon: icons.home, onClick: () => rememberChannel('express') },
  {
    key: 'store',
    href: '/',
    label: 'ملابس',
    className: 'nav-store-switch',
    icon: icons.clothes,
    onClick: () => rememberChannel('store'),
  },
  { key: 'cart', href: '/cart', label: 'السلة', className: 'nav-cart', icon: icons.cart, onClick: undefined },
  {
    key: 'errand',
    href: '/express/errand',
    label: 'على بابك',
    className: 'nav-errand',
    icon: icons.errand,
    onClick: () => rememberChannel('express'),
  },
  { key: 'orders', href: '/my-requests', label: 'متابعة', className: 'nav-orders', icon: icons.orders, onClick: undefined },
];

const mode = computed(() => props.channelMode || (props.theme === 'express' ? 'express' : 'store'));

const navItems = computed(() => (mode.value === 'express' ? expressNavItems : storeNavItems));

const tabOrder = computed(() => navItems.value.map((i) => i.key));

const accent = computed(() => (mode.value === 'express' ? '#8a4b12' : '#087b8d'));

const notchX = computed(() => {
  const idx = tabOrder.value.indexOf(props.active);
  const safeIdx = idx >= 0 ? idx : 0;
  const rtlIndex = tabOrder.value.length - 1 - safeIdx;
  return `${((rtlIndex + 0.5) / tabOrder.value.length) * 100}%`;
});

function onCartChanged() {
  refreshCartCount();
}

onMounted(async () => {
  await hydrateUser();
  await refreshCartCount();
  window.addEventListener('wasla-cart-changed', onCartChanged);
});

onBeforeUnmount(() => {
  window.removeEventListener('wasla-cart-changed', onCartChanged);
});
</script>

<style scoped>
.home-bottom-nav {
  --fab: 3.45rem;
  --fab-overlap: 6px;
  --accent: #087b8d;
  --bar-top: 1.15rem;

  display: grid;
  grid-template-columns: repeat(5, 1fr);
  position: fixed;
  z-index: 45;
  bottom: 0;
  left: 0;
  right: 0;
  max-width: 760px;
  margin: 0 auto;
  min-height: 4.1rem;
  padding: var(--bar-top) 0.35rem calc(0.38rem + env(safe-area-inset-bottom, 0px));
  overflow: visible;
  filter: drop-shadow(0 -4px 18px rgba(19, 47, 55, 0.12));
  background: transparent;
}

.nav-bar-bg {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  top: var(--bar-top);
  pointer-events: none;
  z-index: 0;
  background: #fff;
  border-radius: 1.15rem 1.15rem 0 0;
  box-shadow: 0 -1px 0 rgba(19, 47, 55, 0.06);
  -webkit-mask-image: radial-gradient(
    circle calc(var(--fab) / 2 - var(--fab-overlap)) at var(--notch-x) 0,
    transparent 99%,
    #000 100%
  );
  mask-image: radial-gradient(
    circle calc(var(--fab) / 2 - var(--fab-overlap)) at var(--notch-x) 0,
    transparent 99%,
    #000 100%
  );
}

.home-bottom-nav a {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 0.22rem;
  min-height: 3.15rem;
  padding-bottom: 0.1rem;
  color: var(--accent);
  text-decoration: none;
  font-size: 0.56rem;
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: 0.02em;
  opacity: 0.92;
  -webkit-tap-highlight-color: transparent;
}

.home-bottom-nav a.active {
  opacity: 1;
}

.nav-slot {
  position: relative;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  width: 100%;
  min-height: 1.45rem;
}

.home-bottom-nav a.active .nav-slot {
  min-height: 0.2rem;
  visibility: hidden;
}

.home-bottom-nav a.active .nav-fab {
  position: absolute;
  left: 50%;
  top: 0;
  bottom: auto;
  transform: translate(-50%, calc(-50% + var(--fab-overlap) / 2));
  width: var(--fab);
  height: var(--fab);
  pointer-events: none;
  z-index: 4;
}

.nav-fab-circle {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: var(--accent);
  box-shadow:
    0 0 0 1px var(--accent),
    0 4px 12px rgba(8, 123, 141, 0.22);
  display: flex;
  align-items: center;
  justify-content: center;
}

.home-bottom-nav.theme-express .nav-fab-circle {
  box-shadow:
    0 0 0 1px #8a4b12,
    0 4px 12px rgba(138, 75, 18, 0.28);
}

.nav-fab-icon {
  color: #fff;
}

.nav-icon-inline {
  width: 1.48rem;
  height: 1.48rem;
  margin-bottom: 0.05rem;
  color: var(--accent);
}

.nav-icon-slot :deep(svg),
.nav-icon-slot svg {
  width: 1.4rem;
  height: 1.4rem;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.85;
  stroke-linecap: round;
  stroke-linejoin: round;
  display: block;
}

.nav-fab-icon :deep(svg),
.nav-fab-icon svg {
  width: 1.5rem;
  height: 1.5rem;
}

.nav-label-active {
  color: var(--accent);
  font-weight: 900;
  margin-top: 0.05rem;
}

.nav-cart-badge {
  position: absolute;
  top: 0;
  inset-inline-end: 0.05rem;
  min-width: 1rem;
  height: 1rem;
  padding: 0 0.22rem;
  border-radius: 999px;
  background: #e53935;
  color: #fff;
  font-size: 0.55rem;
  font-weight: 900;
  font-style: normal;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  border: 2px solid #fff;
  box-sizing: border-box;
  pointer-events: none;
}

@media (min-width: 1024px) {
  .home-bottom-nav {
    display: none;
  }
}
</style>
