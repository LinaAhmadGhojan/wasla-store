import { createApp } from 'vue';
import HomeBottomNav from '../components/storefront/HomeBottomNav.vue';
import { resolveChannelMode } from './channel';

export function resolveMobileNavState(pathname = window.location.pathname) {
  const path = pathname || '/';

  if (path.startsWith('/admin')) {
    return null;
  }

  const channelMode = resolveChannelMode(path);

  if (channelMode === 'express') {
    let active = 'home';
    if (path.startsWith('/express/errand')) {
      active = 'errand';
    } else if (path.startsWith('/express')) {
      active = 'home';
    } else if (path.startsWith('/cart') || path.startsWith('/checkout')) {
      active = 'cart';
    } else if (path.startsWith('/my-requests') || path.startsWith('/orders/')) {
      active = 'orders';
    }
    return { active, channelMode: 'express' };
  }

  let active = 'home';
  if (path === '/' || path.startsWith('/shop')) {
    active = 'home';
  } else if (path.startsWith('/express')) {
    active = 'express';
  } else if (path.startsWith('/cart') || path.startsWith('/checkout')) {
    active = 'cart';
  } else if (path.startsWith('/product/')) {
    active = 'home';
  } else if (path.startsWith('/browse') || path.startsWith('/buy-from-anywhere')) {
    active = 'browse';
  } else if (path.startsWith('/my-requests') || path.startsWith('/orders/')) {
    active = 'orders';
  }

  return { active, channelMode: 'store' };
}

export function applyStorefrontChannel(pathname = window.location.pathname) {
  const path = pathname || '/';
  document.body.classList.remove('wasla-channel-express', 'wasla-channel-store');
  if (path.startsWith('/admin')) {
    return;
  }
  if (resolveChannelMode(path) === 'express') {
    document.body.classList.add('wasla-channel-express');
  } else {
    document.body.classList.add('wasla-channel-store');
  }
}

export function mountMobileBottomNav() {
  applyStorefrontChannel();

  if (document.querySelector('.home-bottom-nav')) {
    return;
  }

  const state = resolveMobileNavState();
  if (!state) {
    return;
  }

  const host = document.createElement('div');
  host.id = 'wasla-mobile-bottom-nav';
  document.body.appendChild(host);
  document.body.classList.add('wasla-mobile-nav');

  createApp(HomeBottomNav, {
    active: state.active,
    channelMode: state.channelMode,
  }).mount(host);
}
