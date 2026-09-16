<template>
  <div class="ex-page" dir="rtl">
    <StorefrontNav theme="express" />

    <div class="shell">
      <p v-if="loading" class="state">عم نحمّل الطبق…</p>
      <div v-else-if="!item" class="state err">
        <p>ما لقينا هالطبق.</p>
        <a href="/express" class="btn">رجوع للقمة </a>
      </div>
      <template v-else>
        <nav class="crumbs">
          <a href="/express">لقمة </a>
          <span>/</span>
          <a v-if="expressStore" :href="`/express/stores/${expressStore.id}`">{{ expressStore.name }}</a>
          <span v-if="expressStore">/</span>
          <span>{{ item.name }}</span>
        </nav>

        <div class="stage">
          <div class="media">
            <img
              v-if="item.image"
              :src="item.image"
              :alt="item.name"
              width="640"
              height="480"
              decoding="async"
              fetchpriority="high"
            />
            <span v-else class="ph">🍽️</span>
            <span v-if="item.offer" class="badge">عرض</span>
          </div>

          <div class="buy">
            <div class="top-actions">
              <button type="button" class="icon-act" :class="{ on: wished }" @click="toggleWish">
                {{ wished ? '♥ محفوظ' : '♡ مفضلة' }}
              </button>
              <button type="button" class="icon-act" @click="shareItem">مشاركة</button>
            </div>

            <p class="store-link">
              <a v-if="expressStore" :href="`/express/stores/${expressStore.id}`">{{ item.store }}</a>
              <span v-else>{{ item.store }}</span>
              <em v-if="item.store_eta"> · {{ item.store_eta }}</em>
            </p>
            <h1>{{ item.name }}</h1>
            <p v-if="item.unit_label" class="unit-chip">{{ item.unit_label }}</p>
            <p class="meta">
              <span class="stars">{{ starsText(Math.round(reviewSummary.average || item.rating || 0)) }}</span>
              {{ Number(reviewSummary.average || item.rating || 0).toFixed(1) }}
              · {{ reviewSummary.count || item.total_reviews || 0 }} تقييم
              · يصل خلال {{ item.eta }}
            </p>
            <p v-if="item.description" class="desc">{{ item.description }}</p>
            <p v-if="item.serving_note" class="serving"><strong>يُقدَّم مع:</strong> {{ item.serving_note }}</p>
            <p v-if="item.ingredients" class="serving"><strong>المكوّنات:</strong> {{ item.ingredients }}</p>

            <div v-if="variants.length" class="opt-block">
              <h3>الحصة / الحجم</h3>
              <p class="opt-hint">اختاري زي ما بتختاري المقاس بالملابس</p>
              <div class="opt-row">
                <button
                  v-for="v in variants"
                  :key="v.id"
                  type="button"
                  class="opt-chip"
                  :class="{ on: selectedVariantId === v.id }"
                  @click="selectedVariantId = v.id"
                >
                  <strong>{{ v.label }}</strong>
                  <span>{{ v.price }}</span>
                </button>
              </div>
            </div>

            <div v-for="group in extraGroups" :key="group.name" class="opt-block">
              <h3>{{ group.name }}</h3>
              <div class="opt-row">
                <button
                  v-for="c in group.choices"
                  :key="c.id"
                  type="button"
                  class="opt-chip"
                  :class="{ on: selectedExtras[group.name] === c.id }"
                  @click="selectedExtras[group.name] = c.id"
                >
                  <strong>{{ c.label }}</strong>
                  <span v-if="c.price_delta_label">{{ c.price_delta_label }}</span>
                </button>
              </div>
            </div>

            <div class="price-row">
              <strong>{{ displayPrice }}</strong>
              <span v-if="displayOldPrice" class="old">{{ displayOldPrice }}</span>
            </div>

            <div class="qty">
              <button type="button" @click="qty = Math.max(1, qty - 1)">−</button>
              <span>{{ qty }}</span>
              <button type="button" @click="qty += 1">+</button>
            </div>

            <div class="cta-stack">
              <button type="button" class="btn primary" :disabled="adding" @click="addToCart">
                {{ adding ? '...' : (added ? 'تمت الإضافة ✓' : 'أضيفي للسلة') }}
              </button>
              <a href="/cart" class="btn ghost">عرض السلة</a>
            </div>
          </div>
        </div>

        <section class="panel">
          <h2>تفاصيل الطبق</h2>
          <ul class="info-list">
            <li v-if="selectedVariant">الحصة المختارة: {{ selectedVariant.label }}{{ selectedVariant.unit_label ? ` (${selectedVariant.unit_label})` : '' }}</li>
            <li v-for="line in selectedExtraLabels" :key="line">{{ line }}</li>
            <li v-if="item.serving_note">التقديم: {{ item.serving_note }}</li>
            <li v-if="item.ingredients">المكوّنات: {{ item.ingredients }}</li>
            <li v-if="!item.description && !item.serving_note && !variants.length">التفاصيل تُضاف من لوحة التحكم لكل طبق.</li>
          </ul>
        </section>

        <section v-if="expressStore" class="panel">
          <h2>المتجر</h2>
          <div class="vendor-row">
            <div>
              <strong>{{ expressStore.name }}</strong>
              <p>{{ expressStore.cuisine }} · {{ expressStore.eta }} · ★ {{ expressStore.rating }}</p>
              <a :href="`/express/stores/${expressStore.id}`">عرض القائمة ←</a>
            </div>
            <button type="button" class="btn follow" :class="{ on: following }" @click="toggleFollow">
              {{ following ? 'أتابع' : 'متابعة المتجر' }}
            </button>
          </div>
        </section>

        <section class="panel">
          <h2>التوصيل</h2>
          <ul class="info-list">
            <li>التوصيل المتوقع: {{ item.store_eta || item.eta }}</li>
            <li v-if="item.delivery_fee_syp != null">رسوم التوصيل: {{ Number(item.delivery_fee_syp).toLocaleString('ar') }} ل.س</li>
            <li>أطعمة طازجة من متاجر قريبة عبر لقمة .</li>
          </ul>
        </section>

        <section class="panel" id="qa">
          <h2>أسئلة وأجوبة</h2>
          <div v-if="!questions.length" class="empty">ما في أسئلة مجابة بعد.</div>
          <ul v-else class="qa-list">
            <li v-for="q in questions" :key="q.id">
              <strong>س: {{ q.question }}</strong>
              <p>ج: {{ q.answer }}</p>
            </li>
          </ul>
          <div v-if="isLoggedIn" class="compose">
            <textarea v-model="questionText" rows="2" placeholder="اكتبي سؤالك عن الطبق..." maxlength="1000" />
            <button type="button" class="btn primary sm" :disabled="asking" @click="askQuestion">
              {{ asking ? '...' : 'إرسال السؤال' }}
            </button>
            <p v-if="askFeedback" class="feedback">{{ askFeedback }}</p>
          </div>
          <p v-else class="empty"><a :href="loginUrl">سجّلي دخولك</a> لتطرحي سؤالاً.</p>
        </section>

        <section class="panel reviews" id="reviews">
          <div class="reviews-head">
            <h2>آراء الواصلين</h2>
            <p class="reviews-avg">
              <strong>{{ Number(reviewSummary.average || 0).toFixed(1) }}</strong>
              تقييم لقمة 
              <span class="stars gold">{{ starsText(Math.round(reviewSummary.average || 0)) }}</span>
            </p>
          </div>

          <div class="review-filters">
            <button type="button" :class="{ active: reviewSort === 'newest' }" @click="setReviewSort('newest')">الأحدث</button>
            <button type="button" :class="{ active: reviewSort === 'highest' }" @click="setReviewSort('highest')">الأعلى تقييماً</button>
            <button type="button" :class="{ active: reviewSort === 'all' }" @click="setReviewSort('all')">الكل</button>
          </div>

          <div v-if="reviewsLoading" class="empty">جاري تحميل التقييمات...</div>
          <div v-else-if="!reviews.length" class="empty">لا توجد تقييمات بعد — كوني أول واصِلة تقيّم!</div>
          <ul v-else class="review-list">
            <li v-for="r in reviews" :key="r.id" class="review-card">
              <div class="review-avatar">{{ r.avatar_letter }}</div>
              <div>
                <div class="review-meta">
                  <span class="stars gold">{{ starsText(r.rating) }}</span>
                  <span class="who">{{ r.masked_identity }}</span>
                </div>
                <p v-if="r.body" class="review-text">«{{ r.body }}»</p>
                <a v-if="r.image_url" :href="r.image_url" target="_blank" class="review-photo">
                  <img :src="r.image_url" alt="صورة التقييم" loading="lazy" decoding="async" />
                </a>
              </div>
            </li>
          </ul>

          <div v-if="showReviewForm" class="compose review-compose">
            <h3>أضيفي تقييمك</h3>
            <p class="hint">
              مكافأة: {{ reviewRewards.text_syp }} ل.س للنص ·
              {{ reviewRewards.with_photo_syp }} ل.س مع صورة
            </p>
            <div class="star-pick">
              <button
                v-for="n in 5"
                :key="n"
                type="button"
                :class="{ on: reviewForm.rating >= n }"
                @click="reviewForm.rating = n"
              >★</button>
            </div>
            <textarea v-model="reviewForm.body" rows="3" placeholder="اكتبي رأيك بالطبق..." maxlength="2000" />
            <input type="file" accept="image/*" @change="onReviewImage" />
            <p v-if="reviewForm.imageName" class="file-name">{{ reviewForm.imageName }}</p>
            <button type="button" class="btn primary sm" :disabled="submittingReview" @click="submitReview">
              {{ submittingReview ? 'جاري الإرسال...' : 'نشر التقييم' }}
            </button>
            <p v-if="reviewFeedback" class="feedback" :class="{ error: reviewFeedbackError }">{{ reviewFeedback }}</p>
          </div>
          <button
            v-else-if="isLoggedIn && canReview"
            type="button"
            class="btn ghost"
            @click="showReviewForm = true"
          >أضف تقييمك</button>
          <p v-else-if="!isLoggedIn" class="empty"><a :href="loginUrl">سجّلي دخولك</a> لإضافة تقييم.</p>
          <p v-else-if="!canReview" class="empty">قيّمتِ هذا الطبق مسبقاً — شكراً!</p>
        </section>

        <section v-if="similar.length" class="panel">
          <h2>اقتراحات لكِ</h2>
          <div class="grid">
            <a v-for="r in similar" :key="r.id" class="card" :href="`/express/items/${r.id}`">
              <img :src="r.image" :alt="r.name" loading="lazy" decoding="async" width="280" height="200" />
              <strong>{{ r.name }}</strong>
              <span>{{ r.price }}</span>
            </a>
          </div>
        </section>
      </template>
    </div>

    <StorefrontFooter theme="express" />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../../storefront/api';
import { rememberChannel } from '../../storefront/channel';
import { addExpressGuestItem } from '../../storefront/guestCart';
import { isLoggedIn as checkAuth, refreshCartCount, store as authStore } from '../../storefront/store';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const props = defineProps({
  itemId: { type: [String, Number], required: true },
});

rememberChannel('express');

const loading = ref(true);
const item = ref(null);
const expressStore = ref(null);
const similar = ref([]);
const qty = ref(1);
const adding = ref(false);
const added = ref(false);
const wished = ref(false);
const following = ref(false);
const selectedVariantId = ref(null);
const selectedExtras = reactive({});

const questions = ref([]);
const questionText = ref('');
const asking = ref(false);
const askFeedback = ref('');

const reviews = ref([]);
const reviewsLoading = ref(true);
const reviewSort = ref('newest');
const reviewSummary = ref({ average: 0, count: 0 });
const reviewRewards = ref({ text_syp: 500, with_photo_syp: 1000 });
const canReview = ref(false);
const showReviewForm = ref(false);
const submittingReview = ref(false);
const reviewFeedback = ref('');
const reviewFeedbackError = ref(false);
const reviewForm = reactive({ rating: 5, body: '', image: null, imageName: '' });

const isLoggedIn = computed(() => checkAuth() || !!authStore.authToken);
const loginUrl = computed(() => `/login?redirect=${encodeURIComponent(`/express/items/${props.itemId}`)}`);

const variants = computed(() => item.value?.variants || []);
const extraGroups = computed(() => item.value?.extra_groups || []);
const selectedVariant = computed(() => variants.value.find((v) => v.id === selectedVariantId.value) || null);

const unitPriceNum = computed(() => {
  let base = selectedVariant.value?.priceNum ?? Number(item.value?.priceNum || 0);
  for (const group of extraGroups.value) {
    const choiceId = selectedExtras[group.name];
    const choice = group.choices.find((c) => c.id === choiceId);
    if (choice) base += Number(choice.price_delta || 0);
  }
  return Math.max(0, base);
});

const displayPrice = computed(() => `${Number(unitPriceNum.value).toLocaleString('en-US')} ل.س`);
const displayOldPrice = computed(() => selectedVariant.value?.oldPrice || item.value?.oldPrice || null);

const selectedExtraLabels = computed(() => {
  const lines = [];
  for (const group of extraGroups.value) {
    const choiceId = selectedExtras[group.name];
    const choice = group.choices.find((c) => c.id === choiceId);
    if (choice) lines.push(`${group.name}: ${choice.label}`);
  }
  return lines;
});

function starsText(n) {
  const v = Math.max(0, Math.min(5, Number(n) || 0));
  return '★'.repeat(v) + '☆'.repeat(5 - v);
}

function initOptions(payload) {
  const vars = payload.variants || [];
  const def = vars.find((v) => v.is_default) || vars[0];
  selectedVariantId.value = def?.id || null;
  Object.keys(selectedExtras).forEach((k) => delete selectedExtras[k]);
  for (const group of payload.extra_groups || []) {
    const defChoice = group.choices.find((c) => c.is_default) || group.choices[0];
    if (defChoice) selectedExtras[group.name] = defChoice.id;
  }
}

async function loadItem() {
  const { data } = await api.get(`/v1/express/items/${props.itemId}`);
  item.value = data.item;
  expressStore.value = data.store;
  initOptions(data.item || {});
}

async function loadSimilar() {
  try {
    const { data } = await api.get(`/v1/express/items/${props.itemId}/similar`);
    similar.value = data.items || [];
  } catch (_) {
    similar.value = [];
  }
}

async function loadQuestions() {
  try {
    const { data } = await api.get(`/v1/express/items/${props.itemId}/questions`);
    questions.value = data.questions || [];
  } catch (_) {
    questions.value = [];
  }
}

async function loadReviews() {
  reviewsLoading.value = true;
  try {
    const sort = reviewSort.value === 'all' ? 'newest' : reviewSort.value;
    const { data } = await api.get(`/v1/express/items/${props.itemId}/reviews`, { params: { sort } });
    reviewSummary.value = data.summary || reviewSummary.value;
    reviewRewards.value = data.rewards || reviewRewards.value;
    canReview.value = !!data.can_review;
    const page = data.reviews;
    reviews.value = page?.data || page || [];
  } catch (_) {
    reviews.value = [];
  } finally {
    reviewsLoading.value = false;
  }
}

async function loadSocialStatus() {
  try {
    const fav = await api.get(`/v1/express/items/${props.itemId}/favorite-status`);
    wished.value = !!fav.data.wished;
  } catch (_) { /* guest */ }
  if (expressStore.value?.id) {
    try {
      const fol = await api.get(`/v1/express/stores/${expressStore.value.id}/follow-status`);
      following.value = !!fol.data.following;
    } catch (_) { /* guest */ }
  }
}

function setReviewSort(sort) {
  reviewSort.value = sort;
  loadReviews();
}

function onReviewImage(e) {
  const file = e.target.files?.[0] || null;
  reviewForm.image = file;
  reviewForm.imageName = file?.name || '';
}

async function submitReview() {
  reviewFeedback.value = '';
  reviewFeedbackError.value = false;
  if (!reviewForm.body.trim() && !reviewForm.image) {
    reviewFeedbackError.value = true;
    reviewFeedback.value = 'اكتبي تعليقاً أو ارفعي صورة.';
    return;
  }
  submittingReview.value = true;
  try {
    const fd = new FormData();
    fd.append('rating', String(reviewForm.rating));
    if (reviewForm.body.trim()) fd.append('body', reviewForm.body.trim());
    if (reviewForm.image) fd.append('image', reviewForm.image);
    const { data } = await api.post(`/v1/express/items/${props.itemId}/reviews`, fd);
    reviewFeedback.value = data.message || 'تم نشر التقييم.';
    showReviewForm.value = false;
    reviewForm.rating = 5;
    reviewForm.body = '';
    reviewForm.image = null;
    reviewForm.imageName = '';
    if (data.summary) reviewSummary.value = data.summary;
    canReview.value = false;
    await loadReviews();
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

async function askQuestion() {
  if (!questionText.value.trim()) return;
  asking.value = true;
  askFeedback.value = '';
  try {
    const { data } = await api.post(`/v1/express/items/${props.itemId}/questions`, {
      question: questionText.value.trim(),
    });
    askFeedback.value = data.message || 'تم إرسال سؤالك.';
    questionText.value = '';
  } catch (e) {
    askFeedback.value = e?.response?.data?.message || 'تعذّر إرسال السؤال.';
  } finally {
    asking.value = false;
  }
}

async function toggleWish() {
  if (!isLoggedIn.value) {
    window.location.href = loginUrl.value;
    return;
  }
  try {
    if (wished.value) {
      await api.delete(`/v1/express/items/${props.itemId}/favorite`);
      wished.value = false;
    } else {
      await api.post(`/v1/express/items/${props.itemId}/favorite`);
      wished.value = true;
    }
  } catch (_) { /* ignore */ }
}

async function toggleFollow() {
  if (!expressStore.value?.id) return;
  if (!isLoggedIn.value) {
    window.location.href = loginUrl.value;
    return;
  }
  try {
    if (following.value) {
      await api.delete(`/v1/express/stores/${expressStore.value.id}/follow`);
      following.value = false;
    } else {
      await api.post(`/v1/express/stores/${expressStore.value.id}/follow`);
      following.value = true;
    }
  } catch (_) { /* ignore */ }
}

async function shareItem() {
  const url = window.location.href;
  const title = item.value?.name || 'لقمة ';
  try {
    if (navigator.share) {
      await navigator.share({ title, url });
    } else {
      await navigator.clipboard.writeText(url);
      alert('تم نسخ الرابط');
    }
  } catch (_) { /* cancelled */ }
}

function addToCart() {
  if (!item.value) return;
  adding.value = true;
  const extras = [];
  for (const group of extraGroups.value) {
    const choiceId = selectedExtras[group.name];
    const choice = group.choices.find((c) => c.id === choiceId);
    if (choice) {
      extras.push({
        group: group.name,
        id: choice.id,
        label: choice.label,
        price_delta: choice.price_delta,
      });
    }
  }
  addExpressGuestItem({
    item: item.value,
    store: expressStore.value,
    quantity: qty.value,
    variant: selectedVariant.value,
    extras,
    unitPrice: unitPriceNum.value,
  });
  refreshCartCount();
  added.value = true;
  adding.value = false;
  setTimeout(() => { added.value = false; }, 2200);
}

onMounted(async () => {
  try {
    await loadItem();
    await Promise.all([loadSimilar(), loadQuestions(), loadReviews(), loadSocialStatus()]);
  } catch (_) {
    item.value = null;
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.ex-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f3e6d6 0%, #faf6f1 30%, #fff 100%);
  color: #3d2817;
}
.shell {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1rem 1rem 3rem;
  box-sizing: border-box;
  width: 100%;
  overflow-x: clip;
}
.state { font-weight: 800; color: #8a4b12; }
.state.err { text-align: center; padding: 3rem 1rem; }
.crumbs { display: flex; flex-wrap: wrap; gap: 0.35rem; font-size: 0.82rem; margin-bottom: 1rem; color: #9a7a5c; }
.crumbs a { color: #8a4b12; font-weight: 800; text-decoration: none; }
.stage {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
  gap: 1.25rem;
  align-items: start;
  margin-bottom: 1.25rem;
  min-width: 0;
}
.media {
  position: relative;
  border-radius: 1.25rem;
  overflow: hidden;
  background: #efe2d2;
  aspect-ratio: 4/3;
  min-width: 0;
}
.media img { width: 100%; height: 100%; object-fit: cover; display: block; }
.ph { display: grid; place-items: center; height: 100%; font-size: 3rem; }
.badge {
  position: absolute; top: 0.75rem; right: 0.75rem;
  background: #8a4b12; color: #fff8ef; font-weight: 900;
  font-size: 0.75rem; padding: 0.25rem 0.55rem; border-radius: 999px;
}
.buy {
  background: #fff;
  border: 1.5px solid rgba(138,75,18,.16);
  border-radius: 1.25rem;
  padding: 1.25rem;
  box-sizing: border-box;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}
.top-actions { display: flex; gap: 0.45rem; justify-content: flex-end; margin-bottom: 0.5rem; flex-wrap: wrap; }
.icon-act {
  border: 1.5px solid rgba(138,75,18,.22); background: #fff; color: #8a4b12;
  border-radius: 999px; padding: 0.35rem 0.75rem; font-weight: 800; cursor: pointer;
  box-sizing: border-box;
}
.icon-act.on { background: #8a4b12; color: #fff8ef; }
.store-link { margin: 0 0 0.35rem; color: #9a7a5c; font-size: 0.88rem; }
.store-link a { color: #8a4b12; font-weight: 900; text-decoration: none; }
h1 { margin: 0 0 0.25rem; font-size: 1.55rem; color: #5c3210; word-break: break-word; }
.unit-chip {
  display: inline-block;
  margin: 0 0 0.55rem;
  background: #f3e6d6;
  color: #8a4b12;
  font-weight: 800;
  font-size: 0.78rem;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
}
.meta { margin: 0 0 0.75rem; color: #9a7a5c; font-weight: 700; }
.desc { margin: 0 0 0.65rem; line-height: 1.6; color: #5c4633; }
.serving { margin: 0 0 0.45rem; color: #6b4e34; font-size: 0.9rem; line-height: 1.45; }
.opt-block { margin: 0.85rem 0 1rem; }
.opt-block h3 { margin: 0 0 0.25rem; font-size: 0.95rem; color: #5c3210; }
.opt-hint { margin: 0 0 0.55rem; font-size: 0.78rem; color: #9a7a5c; font-weight: 700; }
.opt-row { display: flex; flex-wrap: wrap; gap: 0.45rem; }
.opt-chip {
  display: grid;
  gap: 0.1rem;
  text-align: right;
  min-width: 6.5rem;
  padding: 0.55rem 0.75rem;
  border-radius: 0.85rem;
  border: 1.5px solid rgba(138,75,18,.22);
  background: #fff;
  color: #5c3210;
  cursor: pointer;
  font: inherit;
  box-sizing: border-box;
}
.opt-chip strong { font-size: 0.88rem; }
.opt-chip span { font-size: 0.75rem; color: #8a4b12; font-weight: 800; }
.opt-chip.on {
  border-color: #8a4b12;
  background: #faf3e8;
  box-shadow: 0 0 0 1px #8a4b12 inset;
}
.price-row { display: flex; align-items: baseline; gap: 0.55rem; margin-bottom: 1rem; flex-wrap: wrap; }
.price-row strong { font-size: 1.35rem; color: #8a4b12; }
.old { text-decoration: line-through; color: #b59a80; font-size: 0.95rem; }
.qty {
  display: inline-flex; align-items: center; gap: 0.65rem;
  border: 1.5px solid rgba(138,75,18,.25); border-radius: 999px;
  padding: 0.25rem 0.5rem; margin-bottom: 0.85rem;
  box-sizing: border-box;
}
.qty button {
  width: 2rem; height: 2rem; border: 0; border-radius: 999px;
  background: #f3e6d6; color: #5c3210; font-weight: 900; cursor: pointer;
}
.cta-stack {
  display: grid;
  gap: 0.5rem;
  width: 100%;
  min-width: 0;
}
.btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-width: 100%;
  min-height: 2.75rem;
  padding: 0.55rem 1rem;
  border-radius: 999px;
  font-weight: 900;
  text-decoration: none;
  border: 1.5px solid transparent;
  cursor: pointer;
  box-sizing: border-box;
}
.btn.primary { background: #8a4b12; color: #fff8ef; border-color: #8a4b12; }
.btn.primary.sm { width: auto; max-width: 100%; min-height: 2.35rem; display: inline-flex; }
.btn.ghost {
  background: #fff;
  color: #8a4b12;
  border-color: rgba(138,75,18,.35);
}
.btn.follow {
  width: auto;
  max-width: 100%;
  display: inline-flex;
  background: #fff;
  color: #8a4b12;
  border-color: rgba(138,75,18,.3);
}
.btn.follow.on { background: #8a4b12; color: #fff8ef; border-color: #8a4b12; }
.panel {
  background: #fff;
  border: 1.5px solid rgba(138,75,18,.14);
  border-radius: 1.15rem;
  padding: 1.1rem 1.15rem;
  margin-bottom: 1rem;
  box-sizing: border-box;
  max-width: 100%;
  min-width: 0;
}
.panel h2 { margin: 0 0 0.75rem; font-size: 1.1rem; color: #5c3210; }
.vendor-row { display: flex; justify-content: space-between; gap: 1rem; align-items: center; flex-wrap: wrap; }
.vendor-row p { margin: 0.2rem 0 0.35rem; color: #9a7a5c; font-size: 0.88rem; }
.vendor-row a { color: #8a4b12; font-weight: 800; text-decoration: none; }
.info-list { margin: 0; padding-inline-start: 1.1rem; color: #5c4633; line-height: 1.7; }
.empty { color: #9a7a5c; font-weight: 700; }
.empty a { color: #8a4b12; }
.qa-list { list-style: none; padding: 0; margin: 0 0 0.85rem; display: grid; gap: 0.65rem; }
.qa-list li { background: #faf3e8; border-radius: 0.85rem; padding: 0.75rem 0.85rem; }
.qa-list p { margin: 0.35rem 0 0; color: #5c4633; }
.compose { display: grid; gap: 0.55rem; margin-top: 0.75rem; }
.compose textarea, .review-compose textarea {
  width: 100%; box-sizing: border-box; border: 1.5px solid rgba(138,75,18,.22);
  border-radius: 0.85rem; padding: 0.7rem 0.85rem; font: inherit;
}
.feedback { margin: 0; font-weight: 700; color: #8a4b12; }
.feedback.error { color: #a33; }
.reviews-head { margin-bottom: 0.75rem; }
.reviews-avg { margin: 0.25rem 0 0; color: #9a7a5c; font-weight: 700; }
.reviews-avg strong { color: #5c3210; font-size: 1.15rem; }
.review-filters { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 0.85rem; }
.review-filters button {
  border: 1.5px solid rgba(138,75,18,.2); background: #fff; color: #8a4b12;
  border-radius: 999px; padding: 0.3rem 0.75rem; font-weight: 800; cursor: pointer;
}
.review-filters button.active { background: #8a4b12; color: #fff; }
.review-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.85rem; }
.review-card {
  display: grid; grid-template-columns: 42px 1fr; gap: 0.75rem;
  background: #faf3e8; border-radius: 1rem; padding: 0.85rem;
}
.review-avatar {
  width: 42px; height: 42px; border-radius: 999px; background: #8a4b12; color: #fff8ef;
  display: grid; place-items: center; font-weight: 900;
}
.review-meta { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; margin-bottom: 0.25rem; }
.who { color: #9a7a5c; font-size: 0.8rem; font-weight: 700; direction: ltr; }
.review-text { margin: 0; line-height: 1.55; }
.review-photo { display: block; width: 88px; margin-top: 0.45rem; border-radius: 0.65rem; overflow: hidden; }
.review-photo img { width: 100%; display: block; aspect-ratio: 1; object-fit: cover; }
.hint { margin: 0; color: #9a7a5c; font-size: 0.82rem; font-weight: 700; }
.star-pick { display: flex; gap: 0.25rem; }
.star-pick button {
  border: 0; background: transparent; color: #d6c0a8; font-size: 1.4rem; cursor: pointer;
}
.star-pick button.on { color: #c47a1a; }
.stars { letter-spacing: 0.05em; color: #c47a1a; }
.stars.gold { color: #c47a1a; }
.file-name { margin: 0; font-size: 0.8rem; color: #9a7a5c; }
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 0.75rem;
}
.card {
  background: #faf3e8; border-radius: 1rem; overflow: hidden; text-decoration: none; color: inherit;
  border: 1.5px solid rgba(138,75,18,.12);
}
.card img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; background: #efe2d2; }
.card strong, .card span { display: block; padding: 0.35rem 0.65rem; }
.card strong { font-size: 0.88rem; color: #5c3210; }
.card span { padding-top: 0; padding-bottom: 0.65rem; color: #8a4b12; font-weight: 800; font-size: 0.82rem; }
@media (max-width: 800px) {
  .stage { grid-template-columns: 1fr; }
  .buy { padding: 1rem; }
  .shell { padding: 0.85rem 0.85rem 2.5rem; }
  h1 { font-size: 1.3rem; }
}
</style>
