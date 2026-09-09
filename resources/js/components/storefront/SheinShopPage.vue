<template>
  <div class="shein-shop">
    <header class="shop-header">
      <a href="/browse" class="back">← المنصات</a>
      <div class="title">
        <span class="logo-text">SHEIN</span>
        <span>تسوقي على SHEIN</span>
      </div>
      <div class="mode-btns">
        <button type="button" class="mode-btn" :class="{ active: mode === 'iframe' }" @click="setMode('iframe')">
          داخل وصلة
        </button>
        <button type="button" class="mode-btn" :class="{ active: mode === 'popup' }" @click="openPopup">
          نافذة
        </button>
        <button type="button" class="mode-btn" @click="openTab">تبويب ↗</button>
      </div>
    </header>

    <div v-if="mode === 'iframe'" class="frame-wrap">
      <iframe
        ref="frameRef"
        src="https://ar.shein.com/"
        class="shein-frame"
        title="SHEIN"
        @load="onFrameLoad"
      />
      <div v-if="iframeBlocked" class="frame-blocked">
        <p>SHEIN ما بيسمح يظهر داخل مواقع ثانية (حماية من الموقع).</p>
        <p>جربي <strong>نافذة</strong> أو <strong>تبويب</strong> — نفس فكرة التطبيقات اللي شفتيها.</p>
        <button type="button" class="btn btn-primary" @click="openPopup">افتحي SHEIN بنافذة</button>
      </div>
    </div>

    <div v-else class="popup-hint">
      <p>SHEIN مفتوح بنافذة منفصلة. تصفّحي هناك، انسخي رابط المنتج، والصقيه بالأسفل.</p>
      <button type="button" class="btn btn-primary" @click="openPopup">إعادة فتح SHEIN</button>
    </div>

    <aside class="add-dock" :class="{ expanded: showPreview }">
      <form class="paste-row" @submit.prevent="previewProduct">
        <input
          v-model="url"
          type="url"
          placeholder="الصقي رابط المنتج من SHEIN (…-p-12345.html)"
          required
        />
        <button type="submit" class="btn btn-primary" :disabled="previewing">
          {{ previewing ? '...' : 'معاينة' }}
        </button>
      </form>

      <div v-if="preview" class="preview-mini">
        <div class="gallery">
          <img v-if="activeImage" :src="activeImage" alt="" />
          <div v-if="gallery.length > 1" class="thumbs">
            <button
              v-for="(img, i) in gallery"
              :key="img + i"
              type="button"
              class="thumb"
              :class="{ active: i === galleryIndex }"
              @click="galleryIndex = i"
            >
              <img :src="img" alt="" />
            </button>
          </div>
        </div>
        <div class="preview-body">
          <strong>{{ preview.product?.name }}</strong>
          <span v-if="selectedSku" class="sku">SKU: {{ selectedSku }}</span>
          <span>{{ money(currentQuote?.total) }} إجمالي تقديري</span>

          <div v-if="colors.length" class="opt-block">
            <label>اللون — {{ selectedColor?.name }}</label>
            <div class="swatches">
              <button
                v-for="c in colors"
                :key="c.key"
                type="button"
                class="swatch-wrap"
                :class="{ active: selectedColorKey === c.key }"
                :title="c.name"
                @click="selectColor(c.key)"
              >
                <span class="swatch">
                  <img v-if="c.thumbnail" :src="c.thumbnail" :alt="c.name" />
                  <span v-else class="swatch-dot" :style="{ background: c.hex || '#ccc' }"></span>
                </span>
                <span class="swatch-label">{{ c.name }}</span>
              </button>
            </div>
          </div>

          <div v-if="sizes.length" class="opt-block">
            <label>المقاس</label>
            <div class="size-row">
              <button
                v-for="s in sizes"
                :key="s.key"
                type="button"
                class="size-btn"
                :class="{ active: selectedSizeKey === s.key }"
                @click="selectSize(s.key)"
              >
                {{ s.name }}
              </button>
            </div>
          </div>

          <div v-if="preview.needs_manual_confirmation" class="manual-row">
            <input v-model="manualPrice" type="number" step="0.01" min="0" placeholder="السعر من SHEIN" />
            <button type="button" class="btn btn-secondary" :disabled="!manualPrice || confirming" @click="confirmPrice">
              تأكيد
            </button>
          </div>
          <button type="button" class="btn btn-primary add-btn" :disabled="adding" @click="addToCart">
            {{ adding ? '...' : 'أضف لسلة وصلة' }}
          </button>
          <a v-if="added" href="/cart">عرض السلة ←</a>
        </div>
      </div>
      <p v-if="feedback" class="feedback" :class="{ error: feedbackError }">{{ feedback }}</p>
    </aside>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import api from '../../storefront/api';
import { addExternalToCart } from '../../storefront/store';
import { money, sortSizes } from '../../storefront/format';

const SHEIN_URL = 'https://ar.shein.com/';

const mode = ref('iframe');
const frameRef = ref(null);
const iframeBlocked = ref(false);
const popupRef = ref(null);

const url = ref('');
const previewing = ref(false);
const preview = ref(null);
const manualPrice = ref('');
const confirming = ref(false);
const adding = ref(false);
const added = ref(false);
const feedback = ref('');
const feedbackError = ref(false);
const showPreview = ref(false);
const selectedColorKey = ref('');
const selectedSizeKey = ref('');
const galleryIndex = ref(0);

const colors = computed(() => preview.value?.product?.colors || []);
const sizes = computed(() => sortSizes(preview.value?.product?.sizes || [], (item) => item.name || item.key));
const selectedColor = computed(() => colors.value.find((c) => c.key === selectedColorKey.value) || colors.value[0] || null);
const gallery = computed(() => selectedColor.value?.images?.length
  ? selectedColor.value.images
  : (preview.value?.product?.gallery || [preview.value?.product?.image].filter(Boolean)));
const activeImage = computed(() => gallery.value[galleryIndex.value] || gallery.value[0] || preview.value?.product?.image);

const currentVariant = computed(() => {
  const list = preview.value?.product?.variants || [];
  return list.find((v) => v.color === selectedColorKey.value && v.size === selectedSizeKey.value)
    || list.find((v) => v.sku === preview.value?.product?.selected?.sku)
    || null;
});
const selectedSku = computed(() => currentVariant.value?.sku || preview.value?.product?.selected?.sku || '');
const currentQuote = computed(() => currentVariant.value?.quote || preview.value?.quote);

function applySelectionFromPreview(data) {
  const selected = data?.product?.selected || {};
  selectedColorKey.value = selected.color || data?.product?.colors?.[0]?.key || '';
  selectedSizeKey.value = selected.size || data?.product?.sizes?.[0]?.key || '';
  galleryIndex.value = 0;
}

function selectColor(key) {
  selectedColorKey.value = key;
  galleryIndex.value = 0;
  added.value = false;
}

function selectSize(key) {
  selectedSizeKey.value = key;
  added.value = false;
}

function setMode(next) {
  mode.value = next;
  if (next === 'popup') openPopup();
}

function openTab() {
  window.open(SHEIN_URL, '_blank', 'noopener,noreferrer');
  mode.value = 'popup';
}

function openPopup() {
  mode.value = 'popup';
  if (popupRef.value && !popupRef.value.closed) {
    popupRef.value.focus();
    return;
  }
  const w = Math.min(480, window.screen.width);
  const h = Math.min(900, window.screen.height);
  const left = window.screen.width - w - 24;
  const top = 24;
  popupRef.value = window.open(
    SHEIN_URL,
    'wasla_shein_shop',
    `width=${w},height=${h},left=${left},top=${top},scrollbars=yes,resizable=yes`
  );
}

function onFrameLoad() {
  setTimeout(() => {
    try {
      const doc = frameRef.value?.contentDocument;
      if (!doc || !doc.body?.children?.length) {
        iframeBlocked.value = true;
      }
    } catch {
      // Cross-origin: often blank when frame-ancestors blocks embedding
      iframeBlocked.value = true;
    }
  }, 800);
}

async function previewProduct() {
  feedback.value = '';
  preview.value = null;
  added.value = false;
  manualPrice.value = '';
  previewing.value = true;
  showPreview.value = true;
  try {
    const { data } = await api.post('/v1/external-products/preview', { url: url.value });
    preview.value = data;
    applySelectionFromPreview(data);
  } catch (e) {
    feedbackError.value = true;
    feedback.value = e.response?.data?.message || 'تعذر المعاينة';
  } finally {
    previewing.value = false;
  }
}

async function confirmPrice() {
  confirming.value = true;
  try {
    const { data } = await api.post('/v1/external-products/preview', {
      url: url.value,
      manual_price: manualPrice.value,
      sku: selectedSku.value || undefined,
      color: selectedColorKey.value || undefined,
      size: selectedSizeKey.value || undefined,
    });
    preview.value = data;
    applySelectionFromPreview(data);
  } catch (e) {
    feedbackError.value = true;
    feedback.value = 'تعذر تحديث السعر';
  } finally {
    confirming.value = false;
  }
}

async function addToCart() {
  if (!preview.value) return;
  adding.value = true;
  try {
    await addExternalToCart({
      preview: {
        ...preview.value,
        quote: currentQuote.value,
        product: {
          ...preview.value.product,
          image: activeImage.value,
          selected: {
            sku: selectedSku.value,
            color: selectedColorKey.value,
            color_name: selectedColor.value?.name,
            size: selectedSizeKey.value,
            size_name: sizes.value.find((s) => s.key === selectedSizeKey.value)?.name,
          },
        },
      },
      quantity: 1,
    });
    added.value = true;
    feedbackError.value = false;
    feedback.value = 'تمت الإضافة للسلة';
  } catch {
    feedbackError.value = true;
    feedback.value = 'فشل الإضافة';
  } finally {
    adding.value = false;
  }
}

onMounted(() => {
  iframeBlocked.value = false;
});

onBeforeUnmount(() => {
  if (popupRef.value && !popupRef.value.closed) {
    popupRef.value.close();
  }
});
</script>

<style scoped>
.shein-shop {
  display: flex;
  flex-direction: column;
  height: 100vh;
  max-height: 100dvh;
  background: #f5fbfc;
  color: #132f37;
}
.shop-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  padding: 0.65rem 1rem;
  background: #1c7282;
  color: #fff;
  flex-shrink: 0;
}
.back { color: #c9eef2; text-decoration: none; font-weight: 600; font-size: 0.85rem; }
.title { display: flex; align-items: center; gap: 0.5rem; font-weight: 800; flex: 1; }
.logo-text {
  background: #fff;
  color: #000;
  font-weight: 900;
  font-size: 0.75rem;
  padding: 0.2rem 0.45rem;
  border-radius: 0.35rem;
  letter-spacing: 0.04em;
}
.mode-btns { display: flex; gap: 0.35rem; }
.mode-btn {
  border: 1px solid rgba(255,255,255,0.35);
  background: transparent;
  color: #fff;
  padding: 0.35rem 0.65rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}
.mode-btn.active { background: #fff; color: #1c7282; }
.frame-wrap {
  flex: 1;
  position: relative;
  min-height: 0;
  background: #fff;
}
.shein-frame {
  width: 100%;
  height: 100%;
  border: none;
}
.frame-blocked {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1.5rem;
  text-align: center;
  background: rgba(245, 251, 252, 0.97);
  color: #4d6b72;
  line-height: 1.55;
}
.popup-hint {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 2rem;
  text-align: center;
  color: #4d6b72;
}
.add-dock {
  flex-shrink: 0;
  background: #fff;
  border-top: 2px solid rgba(15, 90, 107, 0.12);
  padding: 0.75rem 1rem 1rem;
  box-shadow: 0 -4px 20px rgba(15, 90, 107, 0.08);
  max-height: 58vh;
  overflow-y: auto;
}
.paste-row {
  display: flex;
  gap: 0.5rem;
}
.paste-row input {
  flex: 1;
  min-width: 0;
  padding: 0.65rem 0.85rem;
  border: 1px solid rgba(15, 90, 107, 0.2);
  border-radius: 0.65rem;
  font-size: 0.85rem;
}
.preview-mini {
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: 0.75rem;
  margin-top: 0.65rem;
  padding-top: 0.65rem;
  border-top: 1px dashed rgba(15, 90, 107, 0.15);
}
.gallery img {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border-radius: 0.6rem;
}
.thumbs {
  display: flex;
  gap: 0.25rem;
  margin-top: 0.35rem;
  flex-wrap: wrap;
}
.thumb {
  width: 36px;
  height: 36px;
  padding: 0;
  border: 2px solid transparent;
  border-radius: 0.35rem;
  overflow: hidden;
  cursor: pointer;
  background: none;
}
.thumb.active { border-color: #1c7282; }
.thumb img { width: 100%; height: 100%; object-fit: cover; }
.preview-body {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.82rem;
}
.preview-body strong { font-size: 0.85rem; line-height: 1.3; }
.sku { font-family: ui-monospace, monospace; color: #4d6b72; font-size: 0.75rem; }
.opt-block { display: flex; flex-direction: column; gap: 0.3rem; }
.opt-block label { font-weight: 700; font-size: 0.75rem; }
.swatches { display: flex; gap: 0.45rem; flex-wrap: wrap; }
.swatch-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
  width: 64px;
  padding: 0;
  border: none;
  background: none;
  cursor: pointer;
  color: inherit;
}
.swatch {
  width: 56px;
  height: 56px;
  padding: 0;
  border: 2px solid rgba(15, 90, 107, 0.2);
  border-radius: 0.45rem;
  overflow: hidden;
  background: #fff;
  display: block;
}
.swatch-wrap.active .swatch { border-color: #132f37; }
.swatch img { width: 100%; height: 100%; object-fit: cover; display: block; }
.swatch-dot { display: block; width: 100%; height: 100%; }
.swatch-label { font-size: 0.62rem; text-align: center; line-height: 1.2; color: #4d6b72; font-weight: 600; }
.size-row { display: flex; gap: 0.35rem; flex-wrap: wrap; }
.size-btn {
  padding: 0.3rem 0.55rem;
  border-radius: 0.45rem;
  border: 1.5px solid rgba(15, 90, 107, 0.2);
  background: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}
.size-btn.active { background: #1c7282; color: #fff; border-color: #1c7282; }
.manual-row { display: flex; gap: 0.35rem; }
.manual-row input {
  flex: 1;
  padding: 0.4rem 0.6rem;
  border-radius: 0.45rem;
  border: 1px solid rgba(15, 90, 107, 0.2);
}
.add-btn { align-self: flex-start; margin-top: 0.25rem; }
.preview-body a { color: #1c7282; font-weight: 700; font-size: 0.8rem; }
.btn {
  padding: 0.55rem 0.9rem;
  border-radius: 0.6rem;
  font-weight: 700;
  border: none;
  cursor: pointer;
  font-size: 0.82rem;
  white-space: nowrap;
}
.btn-primary { background: #1c7282; color: #fff; }
.btn-secondary { background: #eef4f5; color: #1c7282; }
.btn:disabled { opacity: 0.55; cursor: not-allowed; }
.feedback { margin: 0.5rem 0 0; font-size: 0.82rem; font-weight: 600; color: #1c7282; }
.feedback.error { color: #a82626; }
@media (max-width: 640px) {
  .mode-btns { width: 100%; justify-content: flex-end; }
  .paste-row { flex-direction: column; }
}
</style>
