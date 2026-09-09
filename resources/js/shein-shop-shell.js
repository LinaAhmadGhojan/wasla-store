/**
 * SHEIN inside Wasla — opens SHEIN directly (no enter button on web).
 */
import { addExternalGuestItem, guestCartCount } from './storefront/guestCart';
import api from './storefront/api';

const SHEIN = 'https://ar.shein.com/';

function updateCartBadge() {
  const el = document.getElementById('cartBadge');
  if (el) el.textContent = String(guestCartCount());
}

async function addUrlToWaslaCart(productUrl, manualPrice = null) {
  const payload = { url: productUrl };
  if (manualPrice) payload.manual_price = manualPrice;

  const { data: preview } = await api.post('/v1/external-products/preview', payload);

  if (preview.needs_manual_confirmation && !manualPrice) {
    return { needsPrice: true, preview };
  }

  addExternalGuestItem({ preview, quantity: 1 });
  updateCartBadge();
  return { needsPrice: false, preview };
}

async function openNativeShein() {
  if (!window.Capacitor?.Plugins?.SheinBrowser) return null;
  const result = await window.Capacitor.Plugins.SheinBrowser.open({ startUrl: SHEIN });
  return result?.url || null;
}

async function tryClipboard() {
  try {
    const text = await navigator.clipboard.readText();
    if (text && text.includes('shein.com') && text.includes('-p-')) return text.trim();
  } catch (_) {}
  return '';
}

function showSheet(open = true) {
  document.getElementById('sheet')?.classList.toggle('open', open);
}

function setStatus(msg, isError = false) {
  const el = document.getElementById('sheetStatus');
  if (!el) return;
  el.textContent = msg;
  el.className = 'sheet-status' + (isError ? ' error' : '');
}

async function handleSubmitLink() {
  const urlInput = document.getElementById('productUrl');
  let url = urlInput?.value?.trim() || '';
  if (!url) url = await tryClipboard();
  if (url) urlInput.value = url;

  showSheet(true);
  if (!url) {
    setStatus('انسخي رابط المنتج من SHEIN والصقيه هون');
    return;
  }

  const priceRow = document.getElementById('priceRow');
  priceRow.style.display = 'none';
  setStatus('جاري الإضافة لسلة وصلة...');

  try {
    const result = await addUrlToWaslaCart(url);
    if (result.needsPrice) {
      priceRow.style.display = 'flex';
      document.getElementById('manualPrice').value = '';
      setStatus('اكتبي السعر يلي شفتيه على SHEIN');
      return;
    }
    setStatus('تمت الإضافة لسلة وصلة!');
    document.getElementById('sheetSuccess').style.display = 'block';
  } catch (e) {
    setStatus(e.response?.data?.message || 'تعذر الإضافة', true);
  }
}

async function confirmPriceAndAdd() {
  const url = document.getElementById('productUrl').value.trim();
  const price = document.getElementById('manualPrice').value;
  if (!url || !price) return;

  setStatus('جاري التأكيد...');
  try {
    await addUrlToWaslaCart(url, price);
    setStatus('تمت الإضافة لسلة وصلة!');
    document.getElementById('sheetSuccess').style.display = 'block';
    document.getElementById('priceRow').style.display = 'none';
  } catch (e) {
    setStatus(e.response?.data?.message || 'تعذر الإضافة', true);
  }
}

function init() {
  const frame = document.getElementById('sheinFrame');
  const blocked = document.getElementById('blocked');
  const isNative = window.Capacitor?.isNativePlatform?.();

  updateCartBadge();

  if (isNative) {
    openNativeShein().then((url) => {
      if (url) {
        document.getElementById('productUrl').value = url;
        handleSubmitLink();
      }
    });
  } else if (sessionStorage.getItem('wasla_shein_return') !== '1') {
    sessionStorage.setItem('wasla_shein_return', '1');
    window.location.replace(SHEIN);
    return;
  } else {
    sessionStorage.removeItem('wasla_shein_return');
    if (blocked) {
      blocked.style.display = 'flex';
      blocked.querySelector('p').innerHTML =
        'رجعتي من SHEIN — اضغطي <strong>Submit Link</strong> والصقي رابط المنتج.';
    }
    const enterBtn = document.getElementById('enterShein');
    if (enterBtn) enterBtn.style.display = 'none';
    if (frame) frame.style.display = 'none';
  }

  document.getElementById('submitFab')?.addEventListener('click', handleSubmitLink);
  document.getElementById('sheet')?.addEventListener('click', (e) => {
    if (e.target.id === 'sheet') showSheet(false);
  });
  document.getElementById('confirmPriceBtn')?.addEventListener('click', confirmPriceAndAdd);
  document.getElementById('goPreview')?.addEventListener('click', handleSubmitLink);

  const returned = new URLSearchParams(window.location.search).get('shein_url');
  if (returned) {
    document.getElementById('productUrl').value = returned;
    handleSubmitLink();
  }
}

init();
