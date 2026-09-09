import { reactive } from 'vue';
import api, { TOKEN_KEY } from './api';
import {
  guestCartCount,
  getGuestCart,
  addLocalGuestItem,
  addExternalGuestItem,
  updateGuestItemQuantity,
  removeGuestItem,
  clearGuestLocalItems,
  saveGuestItemForLater,
  moveGuestItemToCart,
} from './guestCart';

const USER_KEY = 'wasla_user';

const state = reactive({
  authToken: localStorage.getItem(TOKEN_KEY) || null,
  currentUser: JSON.parse(localStorage.getItem(USER_KEY) || 'null'),
  cartCount: 0,
  cartItems: [],
  guestItems: getGuestCart(),
});

function isLoggedIn() {
  return !!state.authToken;
}

function login(token, user) {
  state.authToken = token;
  state.currentUser = user;
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(user));
  syncGuestLocalCartToServer().finally(() => refreshCartCount());
}

function logout() {
  state.authToken = null;
  state.currentUser = null;
  state.cartCount = 0;
  state.cartItems = [];
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
  refreshCartCount();
}

async function hydrateUser() {
  if (!state.authToken) {
    refreshCartCount();
    return null;
  }

  try {
    const { data } = await api.get('/auth/profile');
    state.currentUser = data;
    localStorage.setItem(USER_KEY, JSON.stringify(data));
    await syncGuestLocalCartToServer();
    await refreshCartCount();
    return data;
  } catch (error) {
    if (error?.response?.status === 401) {
      logout();
    }
    return null;
  }
}

async function syncGuestLocalCartToServer() {
  if (!state.authToken) return;

  const guestLocal = getGuestCart().filter((i) => i.type === 'local');
  for (const item of guestLocal) {
    try {
      await api.post('/cart/items', {
        product_id: item.product_id,
        variant_id: item.variant_id || null,
        quantity: item.quantity,
        saved_for_later: !!item.saved_for_later,
      });
    } catch (e) {
      // keep going; leftover items remain in guest cart
    }
  }

  if (guestLocal.length) {
    clearGuestLocalItems();
    state.guestItems = getGuestCart();
  }
}

async function refreshCartCount() {
  state.guestItems = getGuestCart();
  let serverCount = 0;
  state.cartItems = [];

  if (state.authToken) {
    try {
      const { data } = await api.get('/cart');
      const items = Array.isArray(data) ? data : (data?.items || []);
      state.cartItems = items;
      serverCount = items.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    } catch (error) {
      if (error?.response?.status === 401) {
        logout();
        return [];
      }
    }
  }

  state.cartCount = serverCount + guestCartCount();
  return state.cartItems;
}

async function addLocalToCart({ product, variant = null, quantity = 1 }) {
  if (state.authToken) {
    await api.post('/cart/items', {
      product_id: product.id,
      variant_id: variant?.id || null,
      quantity,
    });
  } else {
    addLocalGuestItem({ product, variant, quantity });
  }
  await refreshCartCount();
}

async function addExternalToCart({ preview, quantity = 1 }) {
  addExternalGuestItem({ preview, quantity });
  await refreshCartCount();
}

export const store = state;

export {
  isLoggedIn,
  login,
  logout,
  hydrateUser,
  refreshCartCount,
  addLocalToCart,
  addExternalToCart,
  updateGuestItemQuantity,
  removeGuestItem,
  getGuestCart,
  saveGuestItemForLater,
  moveGuestItemToCart,
};
