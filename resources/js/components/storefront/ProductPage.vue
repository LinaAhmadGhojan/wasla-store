<template>
  <div class="product-page">
    <StorefrontNav />

    <div v-if="loading" class="loading-row">Loading product...</div>
    <div v-else-if="!product" class="empty-state">
      <p>Product not found.</p>
      <a href="/shop" class="btn btn-secondary">Back to shop</a>
    </div>
    <div v-else class="product-shell" dir="rtl">
      <nav class="breadcrumb">
        <a href="/">الرئيسية</a>
        <span>/</span>
        <a href="/shop">المتجر</a>
        <template v-if="product.category">
          <span>/</span>
          <a :href="`/shop?category_id=${product.category.id}`">{{ product.category.name }}</a>
        </template>
      </nav>

      <div class="product-stage">
        <div class="gallery-cluster">
          <aside v-if="galleryImages.length" class="thumbs-rail">
            <button
              v-for="(img, i) in galleryImages"
              :key="img + i"
              type="button"
              class="thumb"
              :class="{ active: i === galleryIndex }"
              @click="galleryIndex = i; imageBroken = false"
            >
              <img :src="img" alt="">
            </button>
          </aside>

          <div
            class="hero-shot"
            @touchstart.passive="onGalleryTouchStart"
            @touchend.passive="onGalleryTouchEnd"
          >
            <img
              v-if="activeImage && !imageBroken"
              :src="activeImage"
              :alt="product.name"
              class="hero-img"
              draggable="false"
              @error="imageBroken = true"
            />
            <span v-else>{{ imagePlaceholder }}</span>
            <div v-if="galleryImages.length > 1" class="gallery-dots" aria-hidden="true">
              <span
                v-for="(img, i) in galleryImages"
                :key="'dot-'+i"
                class="dot"
                :class="{ on: i === galleryIndex }"
              />
            </div>
            <p v-if="galleryImages.length > 1" class="swipe-hint">اسحبي يمين/يسار لتغيير الصورة</p>
          </div>
          <div v-if="product.video_url" class="video-box">
            <video :src="product.video_url" controls playsinline poster=""></video>
          </div>
        </div>

        <div class="buy-column">
        <div class="buy-panel">
          <div class="buy-panel-body">
          <div class="top-actions">
            <span v-if="product.brand" class="brand-tag">{{ product.brand.name }}</span>
            <div class="icon-actions">
              <button type="button" class="icon-act" :class="{ on: wished }" @click="toggleWish">{{ wished ? '♥ محفوظ' : '♡ مفضلة' }}</button>
              <button type="button" class="icon-act" @click="shareProduct">مشاركة</button>
            </div>
          </div>
          <h1>{{ product.name }}</h1>
          <p v-if="selectedSku" class="sku-line">SKU: {{ selectedSku }}</p>
          <p class="meta-line">
            <span :class="outOfStock ? 'stock out' : 'stock in'">{{ outOfStock ? 'غير متوفر' : `متوفر · ${stockQty} قطعة` }}</span>
            <span v-if="selectedWeight">الوزن: {{ selectedWeight }}</span>
          </p>

          <div class="price-row">
            <span v-if="discountPercent" class="discount-badge">-{{ discountPercent }}%</span>
            <span class="price-now">{{ money(currentPrice, product.pricing_kind || 'product') }}</span>
            <span v-if="currentSale" class="price-old">{{ money(currentBasePrice, product.pricing_kind || 'product') }}</span>
          </div>
          <p v-if="reviewSummary.count" class="rating-line">
            <span class="stars-inline">{{ starsText(Math.round(reviewSummary.average)) }}</span>
            {{ Number(reviewSummary.average).toFixed(1) }} · {{ reviewSummary.count }} تقييم
          </p>
          <p v-if="product.return_policy_label" class="policy-line">{{ product.return_policy_label }}</p>

          <div v-if="colorOptions.length" class="variant-group">
            <label>اللون: {{ selectedColorName }}</label>
            <div class="swatches">
              <button
                v-for="c in colorOptions"
                :key="c.key"
                type="button"
                class="swatch"
                :class="{ active: selectedColorKey === c.key }"
                :title="c.name"
                @click="selectColor(c.key)"
              >
                <img v-if="c.thumbnail" :src="c.thumbnail" :alt="c.name">
                <span v-else class="swatch-dot" :style="{ background: c.hex || '#ccc' }"></span>
              </button>
            </div>
          </div>

          <div v-if="sizeOptions.length" class="variant-group">
            <div class="size-head">
              <label>المقاس</label>
              <a v-if="showSizeGuide" href="#size-guide" class="size-guide" @click.prevent="scrollToChart">دليل المقاسات</a>
            </div>
            <div class="variant-options">
              <button
                v-for="s in sizeOptions"
                :key="s.key"
                type="button"
                class="variant-btn"
                :class="{
                  active:    selectedSizeKey === s.key,
                  disabled:  !s.available,
                  soldout:   !s.available,
                }"
                :disabled="!s.available"
                :title="!s.available ? 'غير متوفر بهذا اللون' : ''"
                @click="selectedSizeKey = s.key"
              >
                {{ s.name }}
              </button>
            </div>
          </div>

          <div v-else-if="optionGroups.length" class="variant-groups">
            <div v-for="group in optionGroups" :key="group.name" class="variant-group">
              <label>{{ group.name }}</label>
              <div class="variant-options">
                <button
                  v-for="variant in group.variants"
                  :key="variant.id"
                  type="button"
                  class="variant-btn"
                  :class="{ active: selectedVariantId === variant.id, disabled: variant.stock_qty <= 0 }"
                  :disabled="variant.stock_qty <= 0"
                  @click="selectedVariantId = variant.id"
                >
                  {{ variant.option_value }}
                </button>
              </div>
            </div>
          </div>

          <div class="quantity-row">
            <label>الكمية</label>
            <div class="stepper">
              <button type="button" @click="decrementQty" :disabled="quantity <= 1">&minus;</button>
              <span>{{ quantity }}</span>
              <button type="button" @click="incrementQty">&plus;</button>
            </div>
          </div>
          </div>

          <div class="buy-panel-footer">
            <div class="actions-row">
              <button
                type="button"
                class="btn btn-primary add-to-cart"
                :disabled="addingToCart || outOfStock"
                @click="addToCart"
              >
                {{ outOfStock ? 'غير متوفر' : (justAdded ? 'تمت الإضافة إلى السلة' : (addingToCart ? 'جاري الإضافة...' : 'أضف إلى السلة')) }}
              </button>
            </div>
            <p v-if="feedback" class="feedback" :class="{ error: feedbackIsError }">{{ feedback }}</p>
            <a href="/cart" class="go-cart" v-if="justAdded">الذهاب إلى السلة ←</a>
          </div>
        </div>

      <section v-if="specRows.length" class="detail-section" dir="rtl">
        <h2>تفاصيل المنتج</h2>
        <table class="spec-table">
          <tbody>
            <tr v-for="(row, i) in specRows" :key="i">
              <th>{{ row.label }}</th>
              <td>{{ row.value }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <SizeGuide
        v-if="showSizeGuide"
        :chart="sizeGuideChart"
        @suggested="onSizeSuggested"
      />
        </div>
      </div>

      <section v-if="product.brand" class="detail-section brand-block" dir="rtl">
        <h2>حول المتجر</h2>
        <a :href="`/shop?brand_id=${product.brand.id}`" class="brand-store-card">
          <img v-if="product.brand.logo" :src="product.brand.logo" :alt="product.brand.name" class="brand-logo">
          <div class="brand-store-body">
            <strong>{{ product.brand.name }}</strong>
            <span v-if="product.brand.products_count">قطع: {{ product.brand.products_count }}</span>
            <span class="see-products">رؤية المنتجات ←</span>
          </div>
        </a>
      </section>

      <section v-if="product.vendor" class="detail-section brand-block" dir="rtl">
        <h2>المتجر البائع</h2>
        <div class="brand-store-card vendor-card">
          <div class="brand-store-body">
            <strong>{{ product.vendor.store_name }}</strong>
            <a :href="`/stores/${product.vendor.id}`" class="see-products">عرض المتجر ←</a>
          </div>
          <button type="button" class="btn-follow" :class="{ on: followingStore }" @click="toggleFollowStore">
            {{ followingStore ? 'أتابع' : 'متابعة المتجر' }}
          </button>
        </div>
      </section>

      <section class="detail-section" dir="rtl">
        <h2>الشحن والإرجاع</h2>
        <ul class="info-list">
          <li>الشحن: حسب مدينة التوصيل — يظهر التكلفة عند الدفع.</li>
          <li v-if="product.fast_delivery">توصيل سريع متاح لهذا المنتج.</li>
          <li>{{ product.return_policy_label || 'سياسة الإرجاع حسب إعدادات المنتج.' }}</li>
        </ul>
      </section>

      <section class="detail-section qa-section" dir="rtl" id="qa">
        <h2>أسئلة وأجوبة</h2>
        <div v-if="!questions.length" class="reviews-empty">ما في أسئلة مجابة بعد.</div>
        <ul v-else class="qa-list">
          <li v-for="q in questions" :key="q.id">
            <strong>س: {{ q.question }}</strong>
            <p>ج: {{ q.answer }}</p>
          </li>
        </ul>
        <div class="qa-compose" v-if="store.authToken">
          <textarea v-model="questionText" rows="2" placeholder="اكتبي سؤالك عن المنتج..." maxlength="1000" />
          <button type="button" class="btn-review-submit" :disabled="asking" @click="askQuestion">
            {{ asking ? '...' : 'إرسال السؤال' }}
          </button>
          <p v-if="askFeedback" class="feedback">{{ askFeedback }}</p>
        </div>
        <p v-else class="reviews-empty"><a :href="loginUrl(`/products/${product.id}`)">سجّلي دخولك</a> لتطرحي سؤالاً.</p>
      </section>

      <section class="reviews-section" dir="rtl" id="reviews">
        <div class="reviews-head">
          <img :src="markUrl" alt="" class="reviews-mark" />
          <div>
            <h2>آراء الواصلين</h2>
            <p class="reviews-avg">
              <strong>{{ Number(reviewSummary.average || 0).toFixed(1) }}</strong>
              {{ reviewSummary.label || 'تقييم الوصلة' }}
              <span class="stars-inline gold">{{ starsText(Math.round(reviewSummary.average || 0)) }}</span>
            </p>
          </div>
        </div>

        <div class="review-filters">
          <button type="button" :class="{ active: reviewSort === 'newest' }" @click="setReviewSort('newest')">الأحدث</button>
          <button type="button" :class="{ active: reviewSort === 'highest' }" @click="setReviewSort('highest')">الأعلى تقييماً</button>
          <button type="button" :class="{ active: reviewSort === 'all' }" @click="setReviewSort('all')">الكل</button>
        </div>

        <div v-if="reviewsLoading" class="loading-row">جاري تحميل التقييمات...</div>
        <div v-else-if="!reviews.length" class="reviews-empty">لا توجد تقييمات بعد — كوني أول واصِلة تقيّم!</div>
        <ul v-else class="review-list">
          <li v-for="r in reviews" :key="r.id" class="review-card">
            <div class="review-avatar" aria-hidden="true">{{ r.avatar_letter }}</div>
            <div class="review-body">
              <div class="review-meta">
                <span class="stars-inline gold">{{ starsText(r.rating) }}</span>
                <span class="review-who">{{ r.masked_identity }}</span>
              </div>
              <p v-if="r.body" class="review-text">«{{ r.body }}»</p>
              <a v-if="r.image_url" :href="r.image_url" target="_blank" class="review-photo">
                <img :src="r.image_url" alt="صورة التقييم" />
              </a>
            </div>
          </li>
        </ul>

        <div v-if="showReviewForm" class="review-compose">
          <h3>أضيفي تقييمك</h3>
          <p class="reward-hint">
            مكافأة: {{ reviewRewards.text_syp }} ل.س للنص ·
            {{ reviewRewards.with_photo_syp }} ل.س مع صورة — تُضاف لرصيدك.
          </p>
          <div class="star-pick">
            <button
              v-for="n in 5"
              :key="n"
              type="button"
              class="star-btn"
              :class="{ on: reviewForm.rating >= n }"
              @click="reviewForm.rating = n"
            >★</button>
          </div>
          <textarea v-model="reviewForm.body" rows="3" placeholder="اكتبي رأيك بالمنتج..." maxlength="2000"></textarea>
          <label class="file-label">
            صورة (اختياري)
            <input type="file" accept="image/*" @change="onReviewImage" />
          </label>
          <p v-if="reviewForm.imageName" class="file-name">{{ reviewForm.imageName }}</p>
          <button type="button" class="btn-review-submit" :disabled="submittingReview" @click="submitReview">
            {{ submittingReview ? 'جاري الإرسال...' : 'نشر التقييم' }}
          </button>
          <p v-if="reviewFeedback" class="feedback" :class="{ error: reviewFeedbackError }">{{ reviewFeedback }}</p>
        </div>

        <button
          v-else-if="canReview"
          type="button"
          class="btn-add-review"
          @click="showReviewForm = true"
        >
          أضف تقييمك
        </button>
      </section>

      <section v-if="relatedProducts.length" class="related-section">
        <h2>منتجات مشابهة</h2>
        <div class="product-grid">
          <ProductCard v-for="p in relatedProducts" :key="p.id" :product="p" :compare-enabled="true" />
        </div>
      </section>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../storefront/api';
import { store, addLocalToCart, hydrateUser } from '../../storefront/store';
import { formatPrice, money, loginUrl, sortSizes, isPlaceholderSize } from '../../storefront/format';
import { trackProductView } from '../../storefront/recentlyViewed';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';
import ProductCard from './ProductCard.vue';
import SizeGuide from './SizeGuide.vue';

const props = defineProps({
  productId: { type: [String, Number], required: true },
});

const product = ref(null);
const loading = ref(true);
const relatedProducts = ref([]);
const selectedVariantId = ref(null);
const selectedColorKey = ref('');
const selectedSizeKey = ref('');
const galleryIndex = ref(0);
const quantity = ref(1);
const addingToCart = ref(false);
const justAdded = ref(false);
const feedback = ref('');
const feedbackIsError = ref(false);
const imageBroken = ref(false);
const galleryTouchX = ref(null);
const wished = ref(false);
const followingStore = ref(false);
const questions = ref([]);
const questionText = ref('');
const asking = ref(false);
const askFeedback = ref('');
const wishlistLists = ref([]);

const markUrl = '/brand/wasla-id-mark.png?v=6';
const reviews = ref([]);
const reviewsLoading = ref(true);
const reviewSort = ref('newest');
const reviewSummary = ref({ average: 0, count: 0, label: 'تقييم الوصلة' });
const reviewRewards = ref({ text_syp: 500, with_photo_syp: 1000 });
const canReview = ref(false);
const reviewableOrderItemId = ref(null);
const showReviewForm = ref(false);
const submittingReview = ref(false);
const reviewFeedback = ref('');
const reviewFeedbackError = ref(false);
const reviewForm = ref({
  rating: 5,
  body: '',
  image: null,
  imageName: '',
});

function starsText(n) {
  const filled = Math.max(0, Math.min(5, Number(n) || 0));
  return '★'.repeat(filled) + '☆'.repeat(5 - filled);
}

function onReviewImage(e) {
  const file = e.target.files?.[0] || null;
  reviewForm.value.image = file;
  reviewForm.value.imageName = file?.name || '';
}

async function loadReviews() {
  reviewsLoading.value = true;
  try {
    const sort = reviewSort.value === 'all' ? 'newest' : reviewSort.value;
    const { data } = await api.get(`/v1/products/${props.productId}/reviews`, { params: { sort } });
    reviewSummary.value = data.summary || reviewSummary.value;
    reviewRewards.value = data.rewards || reviewRewards.value;
    canReview.value = !!data.can_review;
    reviewableOrderItemId.value = data.reviewable_order_item_id || null;
    const page = data.reviews;
    reviews.value = page?.data || page || [];
  } catch (_) {
    reviews.value = [];
  } finally {
    reviewsLoading.value = false;
  }
}

function setReviewSort(sort) {
  reviewSort.value = sort;
  loadReviews();
}

async function submitReview() {
  reviewFeedback.value = '';
  reviewFeedbackError.value = false;
  if (!reviewableOrderItemId.value) {
    reviewFeedbackError.value = true;
    reviewFeedback.value = 'لا يوجد طلب مستلم قابل للتقييم لهذا المنتج.';
    return;
  }
  if (!reviewForm.value.body.trim() && !reviewForm.value.image) {
    reviewFeedbackError.value = true;
    reviewFeedback.value = 'اكتبي تعليقاً أو ارفعي صورة.';
    return;
  }
  submittingReview.value = true;
  try {
    const fd = new FormData();
    fd.append('order_item_id', String(reviewableOrderItemId.value));
    fd.append('rating', String(reviewForm.value.rating));
    if (reviewForm.value.body.trim()) fd.append('body', reviewForm.value.body.trim());
    if (reviewForm.value.image) fd.append('image', reviewForm.value.image);
    const { data } = await api.post('/v1/reviews', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    reviewFeedback.value = data.message || 'تم نشر التقييم.';
    showReviewForm.value = false;
    reviewForm.value = { rating: 5, body: '', image: null, imageName: '' };
    canReview.value = false;
    await loadReviews();
    if (product.value && data.review) {
      product.value.rating = reviewSummary.value.average;
      product.value.total_reviews = reviewSummary.value.count;
    }
  } catch (e) {
    reviewFeedbackError.value = true;
    const errs = e?.response?.data?.errors;
    reviewFeedback.value = errs
      ? Object.values(errs).flat().join(' ')
      : (e?.response?.data?.message || 'تعذّر نشر التقييم.');
  } finally {
    submittingReview.value = false;
  }
}

const placeholders = ['👗', '👕', '👟', '👜', '💄', '🏠', '📱', '🧥', '👖', '💍'];
const imagePlaceholder = computed(() => {
  if (!product.value || imageBroken.value) {
    if (!product.value) return '🛍️';
  }
  if (imageBroken.value) {
    return placeholders[(product.value.id || 0) % placeholders.length];
  }
  return placeholders[(product.value.id || 0) % placeholders.length];
});

const stockQty = computed(() => {
  if (selectedVariant.value) return Number(selectedVariant.value.stock_qty || 0);
  const variants = product.value?.variants || [];
  if (!variants.length) return 1;
  return variants.reduce((sum, v) => sum + Number(v.stock_qty || 0), 0);
});

const selectedWeight = computed(() => {
  const w = selectedVariant.value?.weight;
  if (w == null || w === '') return '';
  return `${w} كغ`;
});

function onGalleryTouchStart(e) {
  galleryTouchX.value = e.changedTouches?.[0]?.clientX ?? null;
}

function onGalleryTouchEnd(e) {
  if (galleryTouchX.value == null) return;
  const endX = e.changedTouches?.[0]?.clientX;
  if (endX == null) return;
  const dx = endX - galleryTouchX.value;
  galleryTouchX.value = null;
  if (Math.abs(dx) < 45) return;
  const total = galleryImages.value.length;
  if (total <= 1) return;
  // swipe left => next, swipe right => previous
  if (dx < 0) {
    galleryIndex.value = Math.min(total - 1, galleryIndex.value + 1);
  } else {
    galleryIndex.value = Math.max(0, galleryIndex.value - 1);
  }
  imageBroken.value = false;
}

async function toggleWish() {
  await hydrateUser();
  if (!store.authToken) {
    window.location.href = loginUrl(`/products/${props.productId}`);
    return;
  }
  try {
    if (wished.value) {
      await api.delete(`/v1/wishlist/${props.productId}`);
      wished.value = false;
    } else {
      let listId = null;
      if (!wishlistLists.value.length) {
        const { data } = await api.get('/v1/wishlist/lists');
        wishlistLists.value = data.lists || [];
      }
      if (wishlistLists.value.length > 1) {
        const names = wishlistLists.value.map((l, i) => `${i + 1}) ${l.name}`).join('\n');
        const pick = prompt(`أضيفي إلى أي قائمة؟\n${names}\nاكتبي الرقم (أو Enter للافتراضي):`);
        const idx = pick ? Number(pick) - 1 : 0;
        listId = wishlistLists.value[idx]?.id || wishlistLists.value.find((l) => l.is_default)?.id;
      }
      await api.post('/v1/wishlist', { product_id: Number(props.productId), list_id: listId || undefined });
      wished.value = true;
    }
  } catch (e) {
    feedback.value = e?.response?.data?.message || 'تعذّر تحديث المفضلة.';
    feedbackIsError.value = true;
  }
}

async function shareProduct() {
  const url = window.location.href;
  const title = product.value?.name || 'منتج وصلة';
  if (navigator.share) {
    try {
      await navigator.share({ title, url, text: title });
      return;
    } catch (_) { /* cancelled */ }
  }
  await navigator.clipboard?.writeText(url);
  feedback.value = 'تم نسخ رابط المنتج.';
  feedbackIsError.value = false;
}

async function toggleFollowStore() {
  await hydrateUser();
  if (!store.authToken) {
    window.location.href = loginUrl(`/products/${props.productId}`);
    return;
  }
  const vendorId = product.value?.vendor?.id;
  if (!vendorId) return;
  if (followingStore.value) {
    await api.delete(`/v1/stores/${vendorId}/follow`);
    followingStore.value = false;
  } else {
    await api.post(`/v1/stores/${vendorId}/follow`);
    followingStore.value = true;
  }
}

async function loadQuestions() {
  try {
    const { data } = await api.get(`/v1/products/${props.productId}/questions`);
    questions.value = data.questions || [];
  } catch (_) {
    questions.value = [];
  }
}

async function askQuestion() {
  if (!questionText.value.trim()) return;
  asking.value = true;
  askFeedback.value = '';
  try {
    const { data } = await api.post(`/v1/products/${props.productId}/questions`, {
      question: questionText.value.trim(),
    });
    askFeedback.value = data.message || 'تم الإرسال.';
    questionText.value = '';
  } catch (e) {
    askFeedback.value = e?.response?.data?.message || 'تعذّر إرسال السؤال.';
  } finally {
    asking.value = false;
  }
}

const optionGroups = computed(() => {
  if (colorOptions.value.length) return [];
  if (!product.value?.variants?.length) return [];
  const groups = {};
  for (const variant of product.value.variants) {
    const key = variant.option_name || 'Option';
    if (!groups[key]) groups[key] = [];
    groups[key].push(variant);
  }
  return Object.entries(groups).map(([name, variants]) => ({ name, variants }));
});

const colorOptions = computed(() => {
  const map = new Map();
  for (const img of product.value?.images || []) {
    if (!img.color_key) continue;
    if (!map.has(img.color_key)) {
      map.set(img.color_key, {
        key: img.color_key,
        name: img.color_name || img.color_key,
        thumbnail: img.url || img.path,
        hex: null,
      });
    }
  }
  for (const variant of product.value?.variants || []) {
    const meta = variant.metadata || {};
    if (!meta.color_key) continue;
    const existing = map.get(meta.color_key) || {
      key: meta.color_key,
      name: meta.color_name || meta.color_key,
      thumbnail: (meta.images && meta.images[0]) || null,
      hex: meta.color_hex || null,
    };
    existing.hex = existing.hex || meta.color_hex || null;
    existing.name = meta.color_name || existing.name;
    map.set(meta.color_key, existing);
  }
  return [...map.values()];
});

const sizeOptions = computed(() => {
  // جمع كل المقاسات من كل ألوان المنتج
  const allMap = new Map();
  for (const variant of product.value?.variants || []) {
    const meta = variant.metadata || {};
    const key  = meta.size_key || meta.size;
    const name = meta.size || key;
    if (!key || isPlaceholderSize(name) || isPlaceholderSize(key)) continue;
    if (!allMap.has(key)) allMap.set(key, { key, name });
  }
  if (!allMap.size) return [];

  // تحديد أي منها متوفر للون المختار
  const availableKeys = new Set(
    (product.value?.variants || [])
      .filter((v) => v.metadata?.color_key === selectedColorKey.value)
      .map((v) => v.metadata?.size_key || v.metadata?.size)
      .filter(Boolean)
  );

  return sortSizes(
    [...allMap.values()].map((s) => ({ ...s, available: availableKeys.has(s.key) })),
    (item) => item.name || item.key
  );
});

const selectedColorName = computed(() =>
  colorOptions.value.find((c) => c.key === selectedColorKey.value)?.name || ''
);

const galleryImages = computed(() => {
  const images = product.value?.images || [];
  const byColor = images
    .filter((img) => img.color_key === selectedColorKey.value)
    .map((img) => img.url || img.path);
  if (byColor.length) return byColor;
  const metaImages = selectedVariant.value?.metadata?.images;
  if (metaImages?.length) return metaImages;
  if (product.value?.image) return [product.value.image];
  return [];
});

const activeImage = computed(() => galleryImages.value[galleryIndex.value] || galleryImages.value[0] || product.value?.image);

function variantFor(colorKey, sizeKey) {
  return (product.value?.variants || []).find((v) => {
    const meta = v.metadata || {};
    return meta.color_key === colorKey && (meta.size_key === sizeKey || meta.size === sizeKey);
  }) || null;
}

function selectColor(key) {
  selectedColorKey.value = key;
  galleryIndex.value = 0;
  imageBroken.value = false;

  const sizesForColor = (product.value?.variants || [])
    .filter((v) => v.metadata?.color_key === key)
    .map((v) => v.metadata?.size_key || v.metadata?.size)
    .filter((sizeKey) => sizeKey && !isPlaceholderSize(sizeKey));

  // حافظ على المقاس المختار لو متوفر، وإلا اختر أول مقاس متوفر
  const nextSize = sizesForColor.includes(selectedSizeKey.value)
    ? selectedSizeKey.value
    : sizesForColor[0] || '';

  selectedSizeKey.value = nextSize;

  const match = (nextSize ? variantFor(key, nextSize) : null)
    || (product.value?.variants || []).find((v) => v.metadata?.color_key === key);

  if (match) {
    selectedVariantId.value = match.id;
    if (match.metadata?.size_key && !isPlaceholderSize(match.metadata.size_key)) {
      selectedSizeKey.value = match.metadata.size_key;
    }
  }
}

const selectedSku = computed(() => selectedVariant.value?.sku || '');

const specRows = computed(() => {
  const rows = (product.value?.specs || []).filter((row) => row.label || row.value);
  if (selectedSku.value && !rows.some((row) => String(row.label || '').toUpperCase() === 'SKU')) {
    return [...rows, { label: 'SKU', value: selectedSku.value }];
  }
  return rows.map((row) => (
    String(row.label || '').toUpperCase() === 'SKU' && selectedSku.value
      ? { ...row, value: selectedSku.value }
      : row
  ));
});

const sizeChart = computed(() => ({
  unit: product.value?.size_chart?.unit || 'cm',
  note: product.value?.size_chart?.note || '',
  columns: product.value?.size_chart?.columns || [],
  rows: product.value?.size_chart?.rows || [],
  fashion_rows: product.value?.size_chart?.fashion_rows || null,
  measurements: product.value?.size_chart?.measurements || null,
}));

const isFashionProduct = computed(() => {
  const gender = String(product.value?.gender || '').toLowerCase();
  if (['women', 'men', 'kids', 'unisex', 'female', 'male'].includes(gender)) return true;
  const slug = [
    product.value?.category?.slug,
    product.value?.category?.parent?.slug,
    product.value?.category?.name,
  ].filter(Boolean).join(' ').toLowerCase();
  return /fashion|clothing|apparel|ملابس|أزياء|ازياء|فساتين|عبا|shirt|dress|pants/.test(slug);
});

const showSizeGuide = computed(() => {
  if (sizeOptions.value.length) return true;
  if (sizeChart.value.columns.length && sizeChart.value.rows.length) return true;
  if (sizeChart.value.fashion_rows?.length) return true;
  return isFashionProduct.value;
});

const sizeGuideChart = computed(() => {
  if (sizeChart.value.fashion_rows?.length || (sizeChart.value.columns.length && sizeChart.value.rows.length)) {
    return sizeChart.value;
  }
  // Default fashion chart for apparel without admin chart
  return isFashionProduct.value || sizeOptions.value.length
    ? { unit: 'cm', note: 'جدول مقاسات أزياء افتراضي — قد يختلف حسب القصة.' }
    : sizeChart.value;
});

function onSizeSuggested(size) {
  if (!size || !sizeOptions.value.length) return;
  const match = sizeOptions.value.find(
    (s) => String(s.name).toUpperCase() === String(size).toUpperCase()
      || String(s.key).toUpperCase() === String(size).toUpperCase()
  );
  if (match && match.available) {
    selectedSizeKey.value = match.key;
  }
}

const selectedVariant = computed(() => {
  if (colorOptions.value.length && selectedColorKey.value && selectedSizeKey.value) {
    const match = variantFor(selectedColorKey.value, selectedSizeKey.value);
    if (match) return match;
  }
  if (!product.value?.variants?.length) return null;
  return product.value.variants.find((v) => v.id === selectedVariantId.value) || null;
});

const outOfStock = computed(() => {
  if (selectedVariant.value) return selectedVariant.value.stock_qty <= 0;
  return false;
});

const currentPrice = computed(() => {
  const source = selectedVariant.value || product.value;
  if (!source) return 0;
  return source.sale_price ? Number(source.sale_price) : Number(source.price);
});

const currentBasePrice = computed(() => {
  const source = selectedVariant.value || product.value;
  return source ? Number(source.price) : 0;
});

const currentSale = computed(() => {
  const source = selectedVariant.value || product.value;
  return !!source?.sale_price && Number(source.sale_price) < Number(source.price);
});

const discountPercent = computed(() => {
  if (!currentSale.value || !currentBasePrice.value) return 0;
  return Math.round((1 - currentPrice.value / currentBasePrice.value) * 100);
});

function scrollToChart() {
  document.getElementById('size-guide')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function incrementQty() {
  quantity.value += 1;
}
function decrementQty() {
  if (quantity.value > 1) quantity.value -= 1;
}

async function loadProduct() {
  loading.value = true;
  try {
    const { data } = await api.get(`/products/${props.productId}`);
    product.value = data;
    trackProductView(data);
    api.post(`/v1/products/${props.productId}/view`).catch(() => {});
    loadSimilar(data.id);
    loadQuestions();
    await hydrateUser();
    if (store.authToken) {
      try {
        const ids = await api.get('/v1/wishlist/ids');
        wished.value = (ids.data.product_ids || []).map(Number).includes(Number(props.productId));
        const lists = await api.get('/v1/wishlist/lists');
        wishlistLists.value = lists.data.lists || [];
      } catch (_) { /* ignore */ }
      if (data.vendor?.id) {
        try {
          const st = await api.get(`/v1/stores/${data.vendor.id}/follow-status`);
          followingStore.value = !!st.data.following;
        } catch (_) { /* ignore */ }
      }
    }
    if (data.variants?.length) {
      const preferred = data.variants.find((v) => v.sku === 'sa2501022348506000')
        || data.variants.find((v) => v.metadata?.color_key === 'multicolor-navy' && v.stock_qty > 0)
        || data.variants.find((v) => v.stock_qty > 0)
        || data.variants[0];
      selectedVariantId.value = preferred.id;
      const meta = preferred.metadata || {};
      selectedColorKey.value = meta.color_key || '';
      selectedSizeKey.value = meta.size_key || meta.size || '';
      galleryIndex.value = 0;
    }
  } catch (e) {
    product.value = null;
  } finally {
    loading.value = false;
  }
}

async function loadSimilar(productId) {
  try {
    const { data } = await api.get(`/v1/products/${productId}/similar`, { params: { limit: 8 } });
    relatedProducts.value = data.data || [];
  } catch (e) {
    relatedProducts.value = [];
  }
}

async function addToCart() {
  feedback.value = '';
  feedbackIsError.value = false;

  addingToCart.value = true;
  try {
    await addLocalToCart({
      product: product.value,
      variant: selectedVariant.value,
      quantity: quantity.value,
    });
    justAdded.value = true;
    feedback.value = store.authToken
      ? 'تمت الإضافة إلى السلة'
      : 'تمت الإضافة — سجّلي الدخول عند الدفع';
  } catch (e) {
    feedbackIsError.value = true;
    feedback.value = e?.response?.data?.message || 'Could not add to cart. Please try again.';
  } finally {
    addingToCart.value = false;
  }
}

onMounted(() => {
  loadProduct();
  loadReviews();
});
</script>

<style scoped>
.product-page {
  color: #132f37;
}
.loading-row, .empty-state {
  padding: 4rem 0;
  text-align: center;
  color: #4d6b72;
}
.empty-state a {
  display: inline-block;
  margin-top: 1rem;
}
.product-shell {
  max-width: 1280px;
  margin: 0 auto;
  padding: 1.25rem 1.5rem 3rem;
}
.breadcrumb {
  font-size: 0.85rem;
  color: #4d6b72;
  margin-bottom: 1.25rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}
.breadcrumb a {
  color: #1c7282;
  text-decoration: none;
}
.product-stage {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(300px, 400px);
  gap: 1.5rem;
  align-items: start;
}
.gallery-cluster {
  display: grid;
  grid-template-columns: 72px minmax(0, 1fr);
  gap: 8px;
  align-self: start;
  position: sticky;
  top: 1rem;
  height: calc(100vh - 6.5rem);
  min-height: 520px;
  min-width: 0;
  overflow: hidden;
}
.thumbs-rail {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 72px;
  height: 100%;
  overflow-x: hidden;
  overflow-y: auto;
}
.hero-shot {
  min-width: 0;
  height: 100%;
  background: #f7fcfd;
  overflow: hidden;
  position: relative;
  border-radius: 1rem;
  touch-action: pan-y;
  user-select: none;
  -webkit-user-select: none;
}
.hero-shot img {
  display: block;
  width: 100%;
  height: auto;
  object-fit: contain;
  pointer-events: none;
}
.thumb {
  width: 64px;
  height: 80px;
  padding: 0;
  border: 2px solid transparent;
  border-radius: 0.4rem;
  overflow: hidden;
  cursor: pointer;
  background: #f5fbfc;
  flex-shrink: 0;
}
.thumb.active { border-color: #1c7282; }
.thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.buy-column {
  width: 100%;
  max-width: 400px;
  min-width: 0;
  display: flex;
  flex-direction: column;
}
.buy-column .detail-section {
  margin-top: 1.5rem;
}
.buy-panel {
  width: 100%;
  max-width: 100%;
  height: auto;
  overflow: visible;
  box-sizing: border-box;
  padding: 0;
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 1rem;
  background: #fff;
  z-index: 1;
}
.buy-panel-body {
  flex: 0 1 auto;
}
.buy-panel-footer {
  flex: 0 0 auto;
  margin-top: 0.85rem;
  padding-top: 0;
}
.brand-tag {
  color: #26b4c3;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 0.78rem;
  letter-spacing: 0.04em;
}
.buy-panel h1 {
  margin: 0.35rem 0 0.4rem;
  font-size: 1.2rem;
  font-weight: 700;
  line-height: 1.45;
}
.sku-line {
  margin: 0 0 0.85rem;
  font-family: ui-monospace, monospace;
  font-size: 0.78rem;
  color: #4d6b72;
}
.price-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.55rem;
  margin-bottom: 1.15rem;
}
.discount-badge {
  background: #1c7282;
  color: #fff;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.2rem 0.45rem;
  border-radius: 0.25rem;
}
.price-now {
  font-size: 1.65rem;
  font-weight: 800;
  color: #1c7282;
}
.price-old {
  font-size: 1rem;
  color: #9aabaf;
  text-decoration: line-through;
}
.variant-group {
  margin-bottom: 1.05rem;
}
.variant-group label {
  display: block;
  font-weight: 700;
  font-size: 0.88rem;
  margin-bottom: 0.5rem;
}
.size-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}
.size-head label { margin: 0; }
.size-guide {
  color: #1c7282;
  font-size: 0.8rem;
  font-weight: 700;
  text-decoration: none;
}
.swatches {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}
.swatch {
  width: 52px;
  height: 52px;
  padding: 0;
  border: 2px solid rgba(15, 90, 107, 0.18);
  border-radius: 0.3rem;
  overflow: hidden;
  background: #fff;
  cursor: pointer;
}
.swatch.active {
  border-color: #1c7282;
  box-shadow: 0 0 0 1px #1c7282;
}
.swatch img,
.swatch-dot { width: 100%; height: 100%; object-fit: cover; display: block; }
.variant-options {
  display: flex;
  gap: 0.45rem;
  flex-wrap: wrap;
  overflow: visible;
}
.variant-btn {
  padding: 0.5rem 0.85rem;
  border-radius: 0.35rem;
  border: 1.5px solid rgba(15, 90, 107, 0.22);
  background: white;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.82rem;
  color: #132f37;
  white-space: nowrap;
}
.variant-btn.active {
  border-color: #1c7282;
  background: #1c7282;
  color: white;
}
.variant-btn.disabled, .variant-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  color: #9aabaf;
  border-color: rgba(15,90,107,0.12);
}
.variant-btn.soldout {
  position: relative;
  overflow: hidden;
}
.variant-btn.soldout::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top left,
    transparent calc(50% - 1px),
    rgba(154,171,175,0.6) calc(50% - 1px),
    rgba(154,171,175,0.6) calc(50% + 1px),
    transparent calc(50% + 1px)
  );
  pointer-events: none;
}
.quantity-row {
  margin-bottom: 1rem;
}
.quantity-row label {
  display: block;
  font-weight: 700;
  font-size: 0.85rem;
  margin-bottom: 0.45rem;
}
.stepper {
  display: inline-flex;
  align-items: center;
  border: 1.5px solid rgba(15, 90, 107, 0.2);
  border-radius: 0.5rem;
  overflow: hidden;
}
.stepper button {
  width: 2.4rem;
  height: 2.4rem;
  border: none;
  background: #f5fbfc;
  font-size: 1.1rem;
  cursor: pointer;
  color: #1c7282;
}
.stepper button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.stepper span {
  min-width: 2.4rem;
  text-align: center;
  font-weight: 700;
}
.actions-row {
  margin-top: 0.35rem;
  width: 100%;
}
.add-to-cart {
  width: 100%;
  min-height: 52px;
  height: auto;
  padding: 0.75rem 1.25rem;
  border: none;
  border-radius: 0.35rem;
  background: #1c7282;
  color: #fff;
  font-weight: 800;
  font-size: 0.95rem;
  line-height: 1.3;
  cursor: pointer;
  white-space: normal;
  overflow: visible;
}
.add-to-cart:disabled { opacity: 0.55; cursor: not-allowed; }
.feedback {
  margin-top: 0.7rem;
  color: #1c7282;
  font-weight: 600;
}
.feedback.error { color: #a82626; }
.go-cart {
  display: inline-block;
  margin-top: 0.55rem;
  color: #26b4c3;
  font-weight: 700;
  text-decoration: none;
}
.detail-section {
  margin-top: 2.5rem;
}
.detail-section h2 {
  font-size: 1.15rem;
  margin: 0 0 1rem;
}
.spec-table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 1rem;
  overflow: hidden;
}
.spec-table th,
.spec-table td {
  padding: 0.7rem 1rem;
  border-bottom: 1px solid rgba(15, 90, 107, 0.08);
  font-size: 0.92rem;
}
.spec-table th {
  width: 38%;
  color: #4d6b72;
  font-weight: 600;
  background: #f7fcfd;
}
.spec-table td { color: #132f37; font-weight: 600; }
.size-chart-head {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.85rem;
}
.unit-pill {
  background: #1c7282;
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
}
.table-scroll { overflow-x: auto; }
.size-chart-table {
  width: 100%;
  min-width: 0;
  border-collapse: collapse;
  background: #fff;
}
.size-chart-table th,
.size-chart-table td {
  padding: 0.65rem 0.8rem;
  border-bottom: 1px solid rgba(15, 90, 107, 0.1);
  text-align: center;
  font-size: 0.88rem;
  white-space: nowrap;
}
.size-chart-table th { background: #f5fbfc; color: #4d6b72; }
.size-chart-table td:first-child,
.size-chart-table th:first-child { font-weight: 700; }
.chart-note {
  margin-top: 0.65rem;
  color: #7a9096;
  font-size: 0.8rem;
}
.brand-store-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.15rem;
  background: #fff;
  border-radius: 1rem;
  text-decoration: none;
  color: inherit;
  border: 1px solid rgba(15, 90, 107, 0.08);
}
.brand-logo {
  width: 72px;
  height: 72px;
  object-fit: cover;
  border-radius: 0.85rem;
  background: #ff4d8d;
}
.brand-store-body {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.brand-store-body strong { font-size: 1.15rem; }
.brand-store-body span { color: #4d6b72; font-size: 0.9rem; }
.see-products { color: #1a73e8 !important; font-weight: 700; }
.rating-line {
  margin: 0.35rem 0 0;
  color: #4d6b72;
  font-size: 0.9rem;
  font-weight: 700;
}
.policy-line {
  margin: 0.45rem 0 0;
  color: #006871;
  font-size: 0.86rem;
  font-weight: 700;
  background: #eef8f9;
  border-radius: 0.65rem;
  padding: 0.45rem 0.7rem;
  display: inline-block;
}
.stars-inline { letter-spacing: 0.05em; color: #c9a227; }
.stars-inline.gold { color: #d4a017; }
.reviews-section {
  max-width: 720px;
  margin: 2.5rem auto;
  padding: 0 0 1rem;
}
.reviews-head {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}
.reviews-mark {
  width: 56px;
  height: 56px;
  object-fit: contain;
  background: #1c7282;
  border-radius: 50%;
  padding: 8px;
}
.reviews-head h2 {
  margin: 0;
  color: #006871;
  font-size: 1.65rem;
}
.reviews-avg {
  margin: 0.25rem 0 0;
  color: #4d6b72;
  font-weight: 700;
}
.reviews-avg strong { color: #0b3d44; font-size: 1.15rem; margin-inline-start: 0.25rem; }
.review-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1.15rem;
}
.review-filters button {
  border: 1px solid rgba(0,104,113,.2);
  background: #fff;
  color: #006871;
  border-radius: 999px;
  padding: 0.45rem 0.95rem;
  font-weight: 800;
  cursor: pointer;
}
.review-filters button.active {
  background: #006871;
  color: #fff;
  border-color: #006871;
}
.reviews-empty { color: #4d6b72; padding: 1rem 0; }
.review-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.85rem; }
.review-card {
  display: grid;
  grid-template-columns: 48px 1fr;
  gap: 0.85rem;
  background: #fff;
  border: 1px solid rgba(0,104,113,.1);
  border-radius: 1.15rem;
  padding: 1rem;
  box-shadow: 0 8px 20px rgba(0,104,113,.06);
}
.review-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #e8f6f8;
  color: #006871;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: 1.1rem;
}
.review-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 0.85rem;
  align-items: center;
  margin-bottom: 0.35rem;
}
.review-who { color: #4d6b72; font-size: 0.82rem; font-weight: 700; direction: ltr; }
.review-text { margin: 0; color: #132f37; line-height: 1.55; }
.review-photo {
  display: block;
  margin-top: 0.65rem;
  border-radius: 0.85rem;
  overflow: hidden;
  max-width: 180px;
}
.review-photo img { width: 100%; display: block; object-fit: cover; aspect-ratio: 1; }
.btn-add-review {
  width: 100%;
  margin-top: 1.25rem;
  border: 0;
  background: #006871;
  color: #fff;
  border-radius: 999px;
  padding: 0.95rem 1.25rem;
  font-weight: 900;
  font-size: 1rem;
  cursor: pointer;
}
.review-compose {
  margin-top: 1.25rem;
  padding: 1.15rem;
  border-radius: 1.15rem;
  background: #f5fbfc;
  border: 1px solid rgba(0,104,113,.14);
}
.review-compose h3 { margin: 0 0 0.5rem; color: #0b3d44; }
.reward-hint { margin: 0 0 0.85rem; color: #1c7282; font-weight: 700; font-size: 0.88rem; }
.star-pick { display: flex; gap: 0.25rem; margin-bottom: 0.75rem; }
.star-btn {
  border: 0;
  background: transparent;
  color: #c5d8dc;
  font-size: 1.6rem;
  cursor: pointer;
  line-height: 1;
}
.star-btn.on { color: #d4a017; }
.review-compose textarea {
  width: 100%;
  box-sizing: border-box;
  border: 1.5px solid rgba(28,114,130,.22);
  border-radius: 0.85rem;
  padding: 0.75rem;
  margin-bottom: 0.65rem;
  resize: vertical;
}
.file-label { display: block; font-weight: 700; color: #0b3d44; font-size: 0.88rem; margin-bottom: 0.35rem; }
.file-name { margin: 0 0 0.65rem; color: #1c7282; font-size: 0.85rem; }
.btn-review-submit {
  border: 0;
  background: #006871;
  color: #fff;
  border-radius: 999px;
  padding: 0.75rem 1.35rem;
  font-weight: 900;
  cursor: pointer;
}
.related-section {
  margin-top: 4rem;
}
.related-section h2 {
  margin-bottom: 1.5rem;
}
.product-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1.25rem;
}
@media (max-width: 960px) {
  .product-stage {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }
  .gallery-cluster {
    position: static;
    height: auto;
    min-height: 0;
    max-height: none;
    overflow: visible;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .hero-shot {
    order: 1;
    height: auto;
    max-height: none;
    overflow: hidden;
    min-height: 280px;
    touch-action: pan-y;
  }
  .hero-shot img {
    width: 100%;
    height: auto;
    max-height: 70vh;
    margin: 0 auto;
  }
  .thumbs-rail {
    order: 2;
    flex-direction: row;
    width: auto;
    height: auto;
    max-height: none;
    overflow-x: auto;
    overflow-y: hidden;
    gap: 8px;
    padding: 2px 2px 6px;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;
    touch-action: pan-x;
  }
  .thumb {
    scroll-snap-align: start;
  }
  .swipe-hint { display: block; }
  .buy-column {
    order: 3;
    width: 100%;
    max-width: none;
  }
  .buy-panel {
    position: static;
    width: 100%;
    height: auto;
  }
  .buy-panel-footer {
    margin-top: 1rem;
  }
  .product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 480px) {
  .product-grid { grid-template-columns: 1fr; }
  .buy-column { min-width: 0 !important; }
}
.hero-img {
  display: block;
}
.gallery-dots {
  position: absolute;
  bottom: 0.65rem;
  inset-inline: 0;
  display: flex;
  justify-content: center;
  gap: 0.35rem;
  pointer-events: none;
  z-index: 2;
}
.gallery-dots .dot {
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.55);
  box-shadow: 0 0 0 1px rgba(19, 47, 55, 0.2);
}
.gallery-dots .dot.on {
  background: #1c7282;
  width: 1rem;
}
.swipe-hint {
  display: none;
  position: absolute;
  top: 0.55rem;
  inset-inline: 0.55rem;
  margin: 0;
  font-size: 0.72rem;
  font-weight: 800;
  color: #fff;
  background: rgba(19, 47, 55, 0.5);
  border-radius: 999px;
  padding: 0.25rem 0.65rem;
  text-align: center;
  pointer-events: none;
  z-index: 2;
}
.video-box { margin-top: .75rem; border-radius: 1rem; overflow: hidden; background: #000; }
.video-box video { width: 100%; max-height: 280px; display: block; }
.top-actions { display: flex; justify-content: space-between; align-items: center; gap: .5rem; flex-wrap: wrap; }
.icon-actions { display: flex; gap: .35rem; }
.icon-act { border: 1.5px solid rgba(28,114,130,.25); background: #fff; border-radius: 999px; padding: .35rem .7rem; font-weight: 800; cursor: pointer; color: #1c7282; font-size: .8rem; }
.icon-act.on { background: #1c7282; color: #fff; }
.meta-line { display: flex; gap: .75rem; flex-wrap: wrap; margin: .35rem 0 .5rem; font-size: .88rem; font-weight: 700; color: #4d6b72; }
.stock.in { color: #1c7282; }
.stock.out { color: #a82626; }
.info-list { margin: 0; padding-inline-start: 1.1rem; color: #4d6b72; line-height: 1.7; }
.vendor-card { display: flex; align-items: center; justify-content: space-between; gap: 1rem; text-decoration: none; }
.btn-follow { border: 2px solid #1c7282; background: #fff; color: #1c7282; border-radius: 999px; padding: .45rem .9rem; font-weight: 800; cursor: pointer; }
.btn-follow.on { background: #1c7282; color: #fff; }
.qa-list { list-style: none; padding: 0; margin: 0 0 1rem; }
.qa-list li { background: #fff; border: 1px solid rgba(28,114,130,.12); border-radius: .85rem; padding: .85rem 1rem; margin-bottom: .55rem; }
.qa-list p { margin: .35rem 0 0; color: #4d6b72; }
.qa-compose textarea { width: 100%; box-sizing: border-box; border: 1.5px solid rgba(28,114,130,.22); border-radius: .75rem; padding: .7rem; margin-bottom: .55rem; }
</style>
