<template>
  <div class="track-page" dir="rtl">
    <StorefrontNav />

    <div class="track-shell">
      <div v-if="!store.currentUser" class="empty-state">
        <p>سجّلي الدخول لتتبع طلبك.</p>
        <a :href="loginHref" class="btn btn-primary">دخول</a>
      </div>
      <div v-else-if="loading" class="empty-state">جاري تحميل التتبع...</div>
      <div v-else-if="error" class="empty-state error">{{ error }}</div>
      <div v-else class="track-card">
        <img :src="markUrl" alt="وصلة" class="track-logo" />
        <h1>تتبّع الوصلة</h1>
        <p class="where-now">وين صار؟ <strong>{{ tracking.where_now || tracking.status_label }}</strong></p>
        <div class="meta">
          <span>طلب #{{ tracking.order_id }}</span>
          <span v-if="tracking.tracking_number">تتبع: {{ tracking.tracking_number }}</span>
          <span v-if="tracking.sources">{{ tracking.sources }}</span>
        </div>

        <div class="action-bar">
          <a :href="tracking.invoice_url || `/orders/${orderId}/invoice`" class="btn btn-ghost btn-sm">الفاتورة</a>
          <button
            v-if="tracking.can_reorder"
            type="button"
            class="btn btn-ghost btn-sm"
            :disabled="actionBusy"
            @click="reorder"
          >Buy Again</button>
          <button
            v-if="tracking.can_cancel"
            type="button"
            class="btn btn-danger btn-sm"
            :disabled="actionBusy"
            @click="showCancel = true"
          >إلغاء الطلب</button>
        </div>
        <p v-if="actionMsg" class="action-msg" :class="{ err: actionErr }">{{ actionMsg }}</p>

        <div class="eta-box" v-if="tracking.estimated_delivery">
          <div v-if="tracking.estimated_delivery.eta_label">
            التوصيل المتوقع: <strong>{{ tracking.estimated_delivery.eta_label }}</strong>
          </div>
          <div v-if="tracking.estimated_delivery.shipping_method_label">
            الشحن: {{ tracking.estimated_delivery.shipping_method_label }}
          </div>
          <div class="live-soon">Live Location — قريباً</div>
        </div>

        <ol class="steps" aria-label="مراحل الطلب">
          <li
            v-for="step in statusSteps"
            :key="step.key"
            :class="{ done: step.done, current: step.current }"
          >
            <span class="step-dot"></span>
            <span class="step-label">{{ step.label }}</span>
          </li>
        </ol>

        <div class="logistics" v-if="logisticsSteps.length">
          <h2>مسار الشحن</h2>
          <ul class="check-list">
            <li
              v-for="step in logisticsSteps"
              :key="step.key"
              :class="{ done: step.done, current: step.current }"
            >
              <span class="check">{{ step.done ? '✓' : '' }}</span>
              <div>
                <strong>{{ step.label }}</strong>
                <div class="when" v-if="step.done && step.at">{{ formatAt(step.at) }}</div>
                <div class="when" v-else-if="step.current">قيد التنفيذ</div>
              </div>
            </li>
          </ul>
        </div>

        <div class="receipt-box" v-if="tracking.payment && tracking.payment.status !== 'paid'">
          <div class="pay-head">
            <img v-if="tracking.payment.icon" :src="tracking.payment.icon" class="pay-icon-sm" :alt="tracking.payment.method_label" />
            <h2>الدفع · {{ tracking.payment.method_label }}</h2>
          </div>
          <p v-if="tracking.payment.account_hint" class="receipt-hint"><strong>{{ tracking.payment.account_hint }}</strong></p>
          <p v-if="tracking.payment.instructions" class="receipt-hint">{{ tracking.payment.instructions }}</p>
          <p v-if="tracking.payment.transfer_code" class="receipt-ok">الكود المرسل: <code dir="ltr">{{ tracking.payment.transfer_code }}</code></p>
          <p v-if="tracking.payment.has_receipt" class="receipt-ok">
            تم رفع الوصل
            <a v-if="tracking.payment.receipt_url" :href="tracking.payment.receipt_url" target="_blank">عرض</a>
          </p>
          <form class="receipt-form" @submit.prevent="uploadReceipt">
            <input
              v-if="tracking.payment.allows_receipt || tracking.payment.needs_receipt"
              type="file"
              accept="image/*"
              @change="onReceiptFile"
            />
            <input
              v-model="extraTransferCode"
              placeholder="كود التحويل / العملية"
              dir="ltr"
            />
            <button type="submit" :disabled="uploadingReceipt">
              {{ uploadingReceipt ? '...' : 'حفظ الإثبات' }}
            </button>
          </form>
          <p v-if="receiptMsg" class="receipt-msg">{{ receiptMsg }}</p>
        </div>
        <div class="receipt-box paid" v-else-if="tracking.payment?.status === 'paid'">
          <h2>تم تأكيد الدفع ✓ · {{ tracking.payment.method_label }}</h2>
        </div>
        <div class="otp-box" v-if="tracking.delivery_otp && tracking.status !== 'delivered'">
          <div class="otp-label">رمز الاستلام (OTP)</div>
          <div class="otp-code">{{ tracking.delivery_otp }}</div>
          <p class="otp-hint">أعطي هذا الرمز للمندوب عند الاستلام · جهّزي التوقيع</p>
          <form class="otp-form" @submit.prevent="verifyOtp">
            <input v-model="otpInput" placeholder="أو أكّدي الرمز هنا" />
            <button type="submit" :disabled="verifying">{{ verifying ? '...' : 'تأكيد' }}</button>
          </form>
          <p v-if="otpMsg" class="otp-msg">{{ otpMsg }}</p>
        </div>

        <div class="driver" v-if="tracking.driver">
          المندوب: <strong>{{ tracking.driver.name }}</strong>
          <span v-if="tracking.driver.phone"> · {{ tracking.driver.phone }}</span>
        </div>

        <div class="fail" v-if="tracking.failure_label">
          سبب آخر محاولة فاشلة: {{ tracking.failure_label }}
        </div>
        <div class="fail" v-if="tracking.cancellation_reason">
          سبب الإلغاء: {{ tracking.cancellation_reason }}
        </div>

        <div v-if="showCancel" class="cancel-box">
          <h2>إلغاء الطلب</h2>
          <p v-if="tracking.cancel_policy === 'maybe'" class="cancel-hint">الطلب قيد التجهيز — الإلغاء ممكن مع ذكر السبب.</p>
          <textarea v-model="cancelReason" rows="3" placeholder="سبب الإلغاء…"></textarea>
          <div class="cancel-actions">
            <button type="button" class="btn btn-ghost btn-sm" @click="showCancel = false">رجوع</button>
            <button type="button" class="btn btn-danger btn-sm" :disabled="actionBusy" @click="confirmCancel">تأكيد الإلغاء</button>
          </div>
        </div>

        <ol class="timeline">
          <li v-for="(item, idx) in tracking.timeline" :key="idx" class="timeline-item">
            <div class="dot"></div>
            <div>
              <strong>{{ item.title }}</strong>
              <div class="time">{{ formatAt(item.at) }}</div>
              <div class="note" v-if="item.note">{{ item.note }}</div>
            </div>
          </li>
        </ol>

        <div class="proofs" v-if="tracking.signature_url || tracking.proof_photo_url">
          <a v-if="tracking.signature_url" :href="tracking.signature_url" target="_blank">عرض التوقيع</a>
          <a v-if="tracking.proof_photo_url" :href="tracking.proof_photo_url" target="_blank">عرض إثبات الاستلام</a>
        </div>

        <div
          class="review-panel"
          v-if="isDelivered"
        >
          <button
            type="button"
            class="review-icon-btn"
            @click="toggleReviewPanel"
            :aria-expanded="showReviewPanel"
          >
            <span class="review-icon" aria-hidden="true">💬</span>
            <span>
              <strong>قيّمي منتجاتك</strong>
              <small v-if="reviewableItems.length">{{ reviewableItems.length }} منتج بانتظار تقييمك · رصيد مكافأة</small>
              <small v-else-if="reviewablesLoaded">تم تقييم كل المنتجات — شكراً!</small>
              <small v-else>بعد الاستلام يمكنك التقييم وكسب رصيد</small>
            </span>
          </button>

          <div v-if="showReviewPanel && reviewableItems.length" class="review-forms">
            <p class="reward-line">
              مكافأة: {{ rewards.text_syp }} ل.س للنص ·
              {{ rewards.with_photo_syp }} ل.س مع صورة/فيديو
            </p>
            <div
              v-for="item in reviewableItems"
              :key="item.order_item_id"
              class="review-item-card"
            >
              <div class="ri-head">
                <img v-if="item.image" :src="item.image" alt="" class="ri-img" />
                <div>
                  <strong>{{ item.product_name }}</strong>
                  <a v-if="item.product_url" :href="item.product_url" class="ri-link">عرض المنتج</a>
                </div>
              </div>
              <div class="star-pick">
                <button
                  v-for="n in 5"
                  :key="n"
                  type="button"
                  class="star-btn"
                  :class="{ on: (forms[item.order_item_id]?.rating || 5) >= n }"
                  @click="ensureForm(item.order_item_id).rating = n"
                >★</button>
              </div>
              <p class="fit-q">How was the fit? · كيف كان القياس؟</p>
              <div class="fit-pick">
                <button
                  v-for="(label, key) in fitOptions"
                  :key="key"
                  type="button"
                  class="fit-btn"
                  :class="{ on: forms[item.order_item_id]?.fit_feedback === key }"
                  @click="ensureForm(item.order_item_id).fit_feedback = key"
                >{{ shortFit(key) }}</button>
              </div>
              <textarea
                :value="forms[item.order_item_id]?.body || ''"
                @input="ensureForm(item.order_item_id).body = $event.target.value"
                rows="2"
                placeholder="اكتبي رأيك..."
              ></textarea>
              <label class="file-label">
                صور (حتى 5)
                <input type="file" accept="image/*" multiple @change="onItemImages(item.order_item_id, $event)" />
              </label>
              <label class="file-label">
                فيديو (اختياري)
                <input type="file" accept="video/mp4,video/webm,video/quicktime" @change="onItemVideo(item.order_item_id, $event)" />
              </label>
              <button
                type="button"
                class="btn-submit-review"
                :disabled="submittingId === item.order_item_id"
                @click="submitItemReview(item)"
              >
                {{ submittingId === item.order_item_id ? 'جاري الإرسال...' : 'نشر التقييم + رصيد' }}
              </button>
              <p v-if="formMsgs[item.order_item_id]" class="form-msg" :class="{ err: formErrors[item.order_item_id] }">
                {{ formMsgs[item.order_item_id] }}
              </p>
            </div>
          </div>
        </div>

        <div class="review-panel" id="returns" v-if="isDelivered || myReturns.length">
          <button type="button" class="review-icon-btn" @click="toggleReturnPanel">
            <span class="review-icon" aria-hidden="true">↩️</span>
            <span>
              <strong>إرجاع / استبدال / استرداد</strong>
              <small v-if="returnItems.length">خلال {{ returnDefaults.return_days || 7 }} أيام من الاستلام</small>
              <small v-else-if="myReturns.length">تتبّعي طلبات الإرجاع أدناه</small>
              <small v-else-if="returnLoaded">ما في أصناف قابلة حالياً أو انتهت المدة</small>
              <small v-else>حسب سياسة المنتج من الاستلام</small>
            </span>
          </button>

          <div v-if="showReturnPanel" class="review-forms">
            <div v-for="item in returnItems" :key="'re-'+item.order_item_id" class="review-item-card re-form">
              <div class="ri-head">
                <img v-if="item.image" :src="item.image" alt="" class="ri-img" />
                <div>
                  <strong>{{ item.product_name }}</strong>
                  <div class="ri-link" v-if="item.from_size_label">المقاس الحالي: {{ item.from_size_label }}</div>
                  <div class="ri-link">{{ item.return?.policy?.label || item.exchange?.policy?.label }}</div>
                </div>
              </div>

              <div class="type-pick">
                <button
                  v-if="item.return?.eligible"
                  type="button"
                  class="fit-btn"
                  :class="{ on: reForms[item.order_item_id]?.type === 'return' }"
                  @click="ensureReForm(item).type = 'return'"
                >Return · إرجاع</button>
                <button
                  v-if="item.exchange?.eligible"
                  type="button"
                  class="fit-btn"
                  :class="{ on: reForms[item.order_item_id]?.type === 'exchange' }"
                  @click="ensureReForm(item).type = 'exchange'"
                >Exchange · استبدال</button>
              </div>

              <label class="field">
                الكمية
                <input
                  type="number"
                  min="1"
                  :max="item.quantity || 1"
                  :value="reForms[item.order_item_id]?.quantity || 1"
                  @input="ensureReForm(item).quantity = Number($event.target.value) || 1"
                />
              </label>

              <label class="field">
                السبب
                <select
                  :value="reForms[item.order_item_id]?.reason || ''"
                  @change="ensureReForm(item).reason = $event.target.value"
                >
                  <option value="">اختاري السبب</option>
                  <option v-for="(label, key) in (returnDefaults.reasons || reasonFallback)" :key="key" :value="key">{{ label }}</option>
                </select>
              </label>

              <label class="field">
                الوصف
                <textarea
                  rows="2"
                  placeholder="تفاصيل إضافية..."
                  :value="reForms[item.order_item_id]?.notes || ''"
                  @input="ensureReForm(item).notes = $event.target.value"
                ></textarea>
              </label>

              <label class="file-label">
                صور المنتج
                <input type="file" accept="image/*" multiple @change="onReturnImages(item.order_item_id, $event)" />
              </label>

              <template v-if="reForms[item.order_item_id]?.type === 'exchange'">
                <p class="fit-q">الاستبدال: {{ item.from_size_label || 'المقاس الحالي' }} → اختاري المقاس الجديد</p>
                <div class="fit-pick wrap">
                  <button
                    v-for="sz in (item.exchange_sizes || []).filter((s) => s.available && !s.is_current)"
                    :key="sz.id"
                    type="button"
                    class="fit-btn"
                    :class="{ on: Number(reForms[item.order_item_id]?.exchange_variant_id) === Number(sz.id) }"
                    @click="pickExchangeSize(item, sz)"
                  >{{ sz.label }}</button>
                </div>
              </template>

              <template v-if="reForms[item.order_item_id]?.type === 'return'">
                <label class="field">
                  طريقة الاسترداد
                  <select
                    :value="reForms[item.order_item_id]?.refund_method || 'store_credit'"
                    @change="ensureReForm(item).refund_method = $event.target.value"
                  >
                    <option value="store_credit">رصيد المحفظة</option>
                    <option value="original">نفس طريقة الدفع</option>
                    <option value="bank">تحويل بنكي</option>
                  </select>
                </label>
              </template>

              <label class="field">
                موعد الاستلام (Schedule Pickup)
                <input
                  type="datetime-local"
                  :value="reForms[item.order_item_id]?.pickup_at || ''"
                  @input="ensureReForm(item).pickup_at = $event.target.value"
                />
              </label>
              <label class="field">
                عنوان الاستلام
                <textarea
                  rows="2"
                  placeholder="عنوان استلام الطرد..."
                  :value="reForms[item.order_item_id]?.pickup_address || ''"
                  @input="ensureReForm(item).pickup_address = $event.target.value"
                ></textarea>
              </label>

              <button
                type="button"
                class="btn-submit-review"
                :disabled="!reForms[item.order_item_id]?.type || reSubmitting === item.order_item_id"
                @click="submitReturnExchange(item)"
              >
                {{ reSubmitting === item.order_item_id ? 'جاري الإرسال...' : 'إرسال الطلب' }}
              </button>
              <p v-if="reMsgs[item.order_item_id]" class="form-msg" :class="{ err: reErrors[item.order_item_id] }">
                {{ reMsgs[item.order_item_id] }}
              </p>
            </div>

            <div v-if="myReturns.length" class="my-returns">
              <h3>تتبع الإرجاع / الاسترداد</h3>
              <div v-for="req in myReturns" :key="req.id" class="return-track-card">
                <div class="rt-head">
                  <strong>{{ req.type_label }} · {{ req.product_name }}</strong>
                  <span>{{ req.track_status_label }}</span>
                </div>
                <p v-if="req.from_size_label || req.exchange_size_label" class="rt-exchange">
                  <span v-if="req.from_size_label">{{ req.from_size_label }}</span>
                  <span v-if="req.exchange_size_label"> → {{ req.exchange_size_label }}</span>
                </p>
                <ol class="mini-steps">
                  <li
                    v-for="step in req.track_timeline || []"
                    :key="step.key"
                    :class="{ done: step.done, current: step.current }"
                  >{{ step.label }}</li>
                </ol>
                <div v-if="req.refund" class="refund-box">
                  <h4>Refund</h4>
                  <p>
                    المبلغ: <strong>{{ req.refund.amount }}</strong>
                    · الطريقة: {{ req.refund.method_label }}
                    <span v-if="req.refund.date"> · {{ formatAt(req.refund.date) }}</span>
                  </p>
                  <ol class="mini-steps">
                    <li
                      v-for="step in req.refund.steps || []"
                      :key="step.key"
                      :class="{ done: step.done, current: step.current }"
                    >{{ step.label }}</li>
                  </ol>
                </div>
              </div>
            </div>
          </div>
        </div>

        <a href="/my-requests" class="btn btn-outline">طلباتي</a>
      </div>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../../storefront/api';
import { store, hydrateUser, refreshCartCount } from '../../storefront/store';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const props = defineProps({
  orderId: { type: [String, Number], required: true },
});

const markUrl = '/brand/wasla-id-mark.png?v=6';
const tracking = ref({});
const loading = ref(true);
const error = ref('');
const otpInput = ref('');
const verifying = ref(false);
const otpMsg = ref('');
const receiptFile = ref(null);
const extraTransferCode = ref('');
const uploadingReceipt = ref(false);
const receiptMsg = ref('');
const loginHref = `/login?redirect=${encodeURIComponent(`/orders/${props.orderId}/track`)}`;
const showCancel = ref(false);
const cancelReason = ref('');
const actionBusy = ref(false);
const actionMsg = ref('');
const actionErr = ref(false);

const showReviewPanel = ref(false);
const reviewablesLoaded = ref(false);
const reviewableItems = ref([]);
const rewards = ref({ text_syp: 500, with_photo_syp: 1000, fit_options: {} });
const forms = reactive({});
const formMsgs = reactive({});
const formErrors = reactive({});
const submittingId = ref(null);
const showReturnPanel = ref(false);
const returnLoaded = ref(false);
const returnItems = ref([]);
const returnDefaults = ref({ return_days: 7, exchange_days: 7, reasons: {} });
const myReturns = ref([]);
const reForms = reactive({});
const reMsgs = reactive({});
const reErrors = reactive({});
const reSubmitting = ref(null);

const reasonFallback = {
  wrong_size: 'مقاس خاطئ',
  wrong_item: 'صنف مختلف',
  damaged: 'تالف / معيب',
  not_as_described: 'غير مطابق للوصف',
  changed_mind: 'غيّرت رأيي',
  other: 'سبب آخر',
};

const fitOptions = computed(() => rewards.value.fit_options || {
  too_small: 'Too Small',
  perfect: 'Perfect',
  too_large: 'Too Large',
});

const isDelivered = computed(() => {
  const key = tracking.value.status_key || tracking.value.status;
  return key === 'delivered';
});

function shortFit(key) {
  if (key === 'too_small') return 'Too Small';
  if (key === 'too_large') return 'Too Large';
  return 'Perfect';
}

function ensureForm(id) {
  if (!forms[id]) {
    forms[id] = { rating: 5, body: '', image: null, images: [], video: null, fit_feedback: 'perfect' };
  }
  return forms[id];
}

function onItemImage(id, e) {
  ensureForm(id).image = e.target.files?.[0] || null;
}

function onItemImages(id, e) {
  ensureForm(id).images = Array.from(e.target.files || []).slice(0, 5);
  if (ensureForm(id).images[0]) ensureForm(id).image = ensureForm(id).images[0];
}

function onItemVideo(id, e) {
  ensureForm(id).video = e.target.files?.[0] || null;
}

function ensureReForm(item) {
  const id = item.order_item_id;
  if (!reForms[id]) {
    reForms[id] = {
      type: item.return?.eligible ? 'return' : 'exchange',
      quantity: 1,
      reason: '',
      notes: '',
      images: [],
      pickup_at: '',
      pickup_address: '',
      exchange_variant_id: null,
      exchange_size_label: '',
      refund_method: 'store_credit',
    };
  }
  return reForms[id];
}

function pickExchangeSize(item, sz) {
  const f = ensureReForm(item);
  f.exchange_variant_id = sz.id;
  f.exchange_size_label = sz.label;
}

function onReturnImages(id, e) {
  ensureReForm({ order_item_id: id }).images = Array.from(e.target.files || []).slice(0, 5);
}

function toggleReviewPanel() {
  showReviewPanel.value = !showReviewPanel.value;
  if (showReviewPanel.value && !reviewablesLoaded.value) {
    loadReviewables();
  }
}

function toggleReturnPanel() {
  showReturnPanel.value = !showReturnPanel.value;
  if (showReturnPanel.value && !returnLoaded.value) {
    loadReturnItems();
  }
}

async function loadReturnItems() {
  try {
    const [elig, mine] = await Promise.all([
      api.get(`/v1/orders/${props.orderId}/return-exchange-items`),
      api.get(`/v1/orders/${props.orderId}/return-exchange-requests`),
    ]);
    returnItems.value = elig.data.items || [];
    returnDefaults.value = elig.data.defaults || returnDefaults.value;
    myReturns.value = mine.data.data || [];
    returnLoaded.value = true;
    returnItems.value.forEach((item) => ensureReForm(item));
  } catch (_) {
    returnItems.value = [];
    returnLoaded.value = true;
  }
}

async function submitReturnExchange(item) {
  const key = item.order_item_id;
  const form = ensureReForm(item);
  reMsgs[key] = '';
  reErrors[key] = false;
  if (!form.type) {
    reErrors[key] = true;
    reMsgs[key] = 'اختاري إرجاع أو استبدال.';
    return;
  }
  if (form.type === 'exchange' && !form.exchange_variant_id && !form.exchange_size_label) {
    reErrors[key] = true;
    reMsgs[key] = 'اختاري المقاس الجديد للاستبدال.';
    return;
  }
  reSubmitting.value = key;
  try {
    const fd = new FormData();
    fd.append('order_item_id', String(item.order_item_id));
    fd.append('type', form.type);
    fd.append('quantity', String(form.quantity || 1));
    if (form.reason) fd.append('reason', form.reason);
    if (form.notes) fd.append('notes', form.notes);
    if (form.pickup_at) fd.append('pickup_at', new Date(form.pickup_at).toISOString());
    if (form.pickup_address) fd.append('pickup_address', form.pickup_address);
    if (form.type === 'exchange' && form.exchange_variant_id) {
      fd.append('exchange_variant_id', String(form.exchange_variant_id));
    }
    if (form.exchange_size_label) fd.append('exchange_size_label', form.exchange_size_label);
    if (form.type === 'return') fd.append('refund_method', form.refund_method || 'store_credit');
    (form.images || []).forEach((file) => fd.append('images[]', file));
    const { data } = await api.post('/v1/return-exchange-requests', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    reMsgs[key] = data.message || 'تم إرسال الطلب.';
    await loadReturnItems();
  } catch (e) {
    reErrors[key] = true;
    const errs = e?.response?.data?.errors;
    reMsgs[key] = errs
      ? Object.values(errs).flat().join(' ')
      : (e?.response?.data?.message || 'تعذّر إرسال الطلب.');
  } finally {
    reSubmitting.value = null;
  }
}

async function loadReviewables() {
  try {
    const { data } = await api.get(`/v1/orders/${props.orderId}/reviewable-items`);
    reviewableItems.value = data.items || [];
    rewards.value = data.rewards || rewards.value;
    reviewablesLoaded.value = true;
    if (reviewableItems.value.length) {
      showReviewPanel.value = true;
    }
  } catch (_) {
    reviewableItems.value = [];
    reviewablesLoaded.value = true;
  }
}

async function submitItemReview(item) {
  const form = ensureForm(item.order_item_id);
  formMsgs[item.order_item_id] = '';
  formErrors[item.order_item_id] = false;
  if (!form.body?.trim() && !form.image && !(form.images || []).length && !form.video) {
    formErrors[item.order_item_id] = true;
    formMsgs[item.order_item_id] = 'اكتبي تعليقاً أو ارفعي صورة/فيديو.';
    return;
  }
  submittingId.value = item.order_item_id;
  try {
    const fd = new FormData();
    fd.append('order_item_id', String(item.order_item_id));
    fd.append('rating', String(form.rating || 5));
    if (form.body?.trim()) fd.append('body', form.body.trim());
    if (form.fit_feedback) fd.append('fit_feedback', form.fit_feedback);
    if (form.image) fd.append('image', form.image);
    (form.images || []).forEach((file) => fd.append('images[]', file));
    if (form.video) fd.append('video', form.video);
    const { data } = await api.post('/v1/reviews', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    formMsgs[item.order_item_id] = data.message || 'تم التقييم.';
    reviewableItems.value = reviewableItems.value.filter((x) => x.order_item_id !== item.order_item_id);
    if (store.currentUser && data.store_credit_syp != null) {
      store.currentUser.store_credit_syp = data.store_credit_syp;
      try {
        localStorage.setItem('wasla_user', JSON.stringify(store.currentUser));
      } catch (_) { /* ignore */ }
    }
  } catch (e) {
    formErrors[item.order_item_id] = true;
    const errs = e?.response?.data?.errors;
    formMsgs[item.order_item_id] = errs
      ? Object.values(errs).flat().join(' ')
      : (e?.response?.data?.message || 'تعذّر نشر التقييم.');
  } finally {
    submittingId.value = null;
  }
}

const STEP_ORDER = ['placed', 'confirmed', 'preparing', 'packed', 'shipped', 'out_for_delivery', 'delivered'];
const STEP_LABELS = {
  placed: 'تم استلام الطلب',
  confirmed: 'تم التأكيد',
  preparing: 'قيد التجهيز',
  packed: 'تم التغليف',
  shipped: 'تم الشحن',
  out_for_delivery: 'خرج للتسليم',
  delivered: 'تم التسليم',
};

const statusSteps = computed(() => {
  if (tracking.value.status_steps?.length) {
    return tracking.value.status_steps;
  }
  const key = tracking.value.status_key || tracking.value.status || 'pending';
  const mapped = key === 'pending' ? 'placed' : key;
  const idx = STEP_ORDER.indexOf(mapped);
  const active = idx < 0 ? 0 : idx;

  return STEP_ORDER.map((stepKey, i) => ({
    key: stepKey,
    label: STEP_LABELS[stepKey],
    done: i < active || key === 'delivered',
    current: key === 'delivered' ? stepKey === 'delivered' : i === active,
  }));
});

const logisticsSteps = computed(() => tracking.value.logistics || []);

async function reorder() {
  actionBusy.value = true;
  actionMsg.value = '';
  actionErr.value = false;
  try {
    const { data } = await api.post(`/orders/${props.orderId}/reorder`);
    actionMsg.value = data.message || 'تمت الإضافة للسلة.';
    await refreshCartCount();
    if (data.added_count > 0) {
      window.location.href = '/cart';
    }
  } catch (e) {
    actionErr.value = true;
    actionMsg.value = e?.response?.data?.message || 'تعذّرت إعادة الطلب.';
  } finally {
    actionBusy.value = false;
  }
}

async function confirmCancel() {
  if (cancelReason.value.trim().length < 3) {
    actionErr.value = true;
    actionMsg.value = 'اكتبي سبب الإلغاء (٣ أحرف على الأقل).';
    return;
  }
  actionBusy.value = true;
  actionMsg.value = '';
  actionErr.value = false;
  try {
    await api.post(`/orders/${props.orderId}/cancel`, { reason: cancelReason.value.trim() });
    showCancel.value = false;
    actionMsg.value = 'تم إلغاء الطلب.';
    await load();
  } catch (e) {
    actionErr.value = true;
    actionMsg.value = e?.response?.data?.message
      || e?.response?.data?.errors?.reason?.[0]
      || 'تعذّر إلغاء الطلب.';
  } finally {
    actionBusy.value = false;
  }
}

function formatAt(at) {
  if (!at) return '';
  try {
    return new Date(at).toLocaleString('ar');
  } catch (e) {
    return at;
  }
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    await hydrateUser();
    if (!store.currentUser) {
      loading.value = false;
      return;
    }
    const { data } = await api.get(`/orders/${props.orderId}/tracking`);
    tracking.value = data;
    const key = data.status_key || data.status;
    if (key === 'delivered') {
      await loadReviewables();
      await loadReturnItems();
      showReturnPanel.value = true;
      if (window.location.hash === '#returns') {
        showReturnPanel.value = true;
        document.getElementById('returns')?.scrollIntoView({ behavior: 'smooth' });
      }
    } else {
      try {
        const mine = await api.get(`/v1/orders/${props.orderId}/return-exchange-requests`);
        myReturns.value = mine.data.data || [];
        if (myReturns.value.length) showReturnPanel.value = true;
      } catch (_) { /* ignore */ }
    }
  } catch (e) {
    error.value = e?.response?.data?.message || 'تعذّر تحميل التتبع.';
  } finally {
    loading.value = false;
  }
}

async function verifyOtp() {
  verifying.value = true;
  otpMsg.value = '';
  try {
    await api.post(`/orders/${props.orderId}/verify-otp`, { otp: otpInput.value });
    otpMsg.value = 'تم تأكيد الرمز.';
    await load();
  } catch (e) {
    otpMsg.value = e?.response?.data?.errors?.otp?.[0] || e?.response?.data?.message || 'رمز غير صحيح.';
  } finally {
    verifying.value = false;
  }
}

function onReceiptFile(e) {
  receiptFile.value = e.target.files?.[0] || null;
}

async function uploadReceipt() {
  if (!receiptFile.value && !extraTransferCode.value.trim()) {
    receiptMsg.value = 'ارفعي وصل أو أدخلي كود.';
    return;
  }
  uploadingReceipt.value = true;
  receiptMsg.value = '';
  try {
    const fd = new FormData();
    if (receiptFile.value) fd.append('receipt', receiptFile.value);
    if (extraTransferCode.value.trim()) fd.append('transfer_code', extraTransferCode.value.trim());
    await api.post(`/orders/${props.orderId}/receipt`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    receiptMsg.value = 'تم حفظ الإثبات — بانتظار تأكيد وصلة من الداشبورد.';
    receiptFile.value = null;
    extraTransferCode.value = '';
    await load();
  } catch (e) {
    receiptMsg.value = e?.response?.data?.message || 'تعذّر حفظ الإثبات.';
  } finally {
    uploadingReceipt.value = false;
  }
}

onMounted(load);
</script>

<style scoped>
.track-page { min-height: 100vh; background: #f5fbfc; color: #132f37; }
.track-shell { max-width: 640px; margin: 0 auto; padding: 2rem 1.25rem 3rem; }
.empty-state { text-align: center; padding: 3rem 1rem; color: #4d6b72; }
.empty-state.error { color: #a82626; }
.track-card {
  background: #fff;
  border: 1px solid rgba(28, 114, 130, 0.12);
  border-radius: 1.35rem;
  padding: 1.75rem;
  text-align: center;
}
.track-logo {
  width: 72px; height: 72px; object-fit: contain;
  background: #1c7282; border-radius: 16px; padding: 8px; margin: 0 auto 1rem; display: block;
}
h1 { margin: 0 0 0.5rem; color: #1c7282; font-size: 1.55rem; }
.where-now { margin: 0 0 1rem; color: #4d6b72; }
.meta { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; color: #4d6b72; font-size: 0.9rem; margin-bottom: 1rem; }
.action-bar { display: flex; flex-wrap: wrap; gap: .4rem; justify-content: center; margin-bottom: .75rem; }
.action-msg { font-size: .85rem; font-weight: 700; color: #1c7282; margin: 0 0 1rem; }
.action-msg.err { color: #a82626; }
.eta-box {
  text-align: right; margin: 0 0 1.25rem; padding: .85rem 1rem;
  background: #f7fbfc; border-radius: .9rem; border: 1px solid rgba(28,114,130,.12);
  font-size: .9rem; color: #4d6b72;
}
.live-soon { margin-top: .35rem; font-size: .8rem; color: #9aabaf; }
.btn {
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: 999px; padding: .55rem 1rem; font-weight: 800;
  text-decoration: none; border: 0; cursor: pointer;
}
.btn-sm { padding: .4rem .75rem; font-size: .8rem; }
.btn-ghost { background: #fff; color: #1c7282; border: 1.5px solid rgba(28,114,130,.28); }
.btn-danger { background: #fff; color: #a82626; border: 1.5px solid rgba(168,38,38,.35); }
.btn:disabled { opacity: .5; cursor: not-allowed; }
.cancel-box {
  text-align: right; margin: 0 0 1.25rem; padding: 1rem;
  background: #fff5f5; border-radius: 1rem; border: 1px solid rgba(168,38,38,.2);
}
.cancel-box h2 { margin: 0 0 .5rem; font-size: 1rem; color: #a82626; }
.cancel-hint { color: #8a5a00; font-size: .85rem; }
.cancel-box textarea {
  width: 100%; box-sizing: border-box; border: 1.5px solid rgba(28,114,130,.22);
  border-radius: .75rem; padding: .65rem .75rem; margin: .5rem 0;
}
.cancel-actions { display: flex; gap: .4rem; justify-content: flex-end; }
.steps {
  list-style: none; padding: 0; margin: 0 0 1.5rem;
  display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0.25rem; text-align: center;
}
.steps li { position: relative; opacity: 0.45; }
.steps li.done, .steps li.current { opacity: 1; }
.step-dot {
  display: block; width: 12px; height: 12px; border-radius: 50%;
  background: #c5d8dc; margin: 0 auto 0.4rem;
}
.steps li.done .step-dot, .steps li.current .step-dot { background: #1c7282; }
.steps li.current .step-dot { box-shadow: 0 0 0 4px rgba(28,114,130,.18); }
.step-label { font-size: 0.68rem; color: #0b3d44; font-weight: 700; line-height: 1.25; display: block; }
.logistics {
  text-align: right; margin: 0 0 1.35rem; padding: 1rem;
  background: #f7fbfc; border-radius: 1rem; border: 1px solid rgba(28,114,130,.1);
}
.logistics h2 { margin: 0 0 .75rem; font-size: .95rem; color: #0b3d44; }
.check-list { list-style: none; padding: 0; margin: 0; }
.check-list li {
  display: flex; gap: .75rem; align-items: flex-start;
  padding: .55rem 0; border-bottom: 1px solid rgba(28,114,130,.07);
  opacity: .45;
}
.check-list li:last-child { border-bottom: 0; }
.check-list li.done, .check-list li.current { opacity: 1; }
.check {
  width: 24px; height: 24px; border-radius: 7px; flex-shrink: 0;
  border: 2px solid #c5d8dc; display: inline-flex; align-items: center; justify-content: center;
  font-weight: 900; color: transparent; background: #fff; font-size: .85rem;
}
.check-list li.done .check { background: #1c7282; border-color: #1c7282; color: #fff; }
.check-list li.current .check { border-color: #1c7282; box-shadow: 0 0 0 3px rgba(28,114,130,.15); }
.when { color: #4d6b72; font-size: .8rem; margin-top: .15rem; }
.receipt-box {
  text-align: right; margin: 0 0 1.35rem; padding: 1rem;
  background: #fff8e8; border-radius: 1rem; border: 1px dashed #c9a227;
}
.receipt-box.paid { background: #eef8f9; border-color: #1c7282; }
.pay-head { display: flex; align-items: center; gap: .65rem; margin-bottom: .5rem; }
.pay-icon-sm { width: 40px; height: 40px; border-radius: 10px; }
.receipt-box h2 { margin: 0; font-size: .95rem; color: #0b3d44; }
.receipt-form input[type="text"], .receipt-form input:not([type="file"]) {
  flex: 1; min-width: 140px; border: 1.5px solid rgba(28,114,130,.25);
  border-radius: .75rem; padding: .55rem .75rem;
}
.receipt-hint { margin: 0 0 .75rem; color: #4d6b72; font-size: .88rem; }
.receipt-ok { color: #1c7282; font-weight: 700; }
.receipt-form { display: flex; gap: .5rem; flex-wrap: wrap; }
.receipt-form button {
  border: 0; background: #1c7282; color: #fff; border-radius: .75rem;
  padding: .65rem 1rem; font-weight: 800; cursor: pointer;
}
.receipt-msg { margin: .5rem 0 0; font-weight: 700; color: #1c7282; }
.otp-box {
  background: #eef8f9; border: 1px dashed #1c7282; border-radius: 1rem; padding: 1rem; margin-bottom: 1.25rem;
}
.otp-label { font-weight: 700; color: #0b3d44; }
.otp-code { font-size: 2rem; font-weight: 800; letter-spacing: 0.25rem; color: #1c7282; margin: 0.35rem 0; }
.otp-hint { margin: 0 0 0.75rem; color: #4d6b72; font-size: 0.85rem; }
.otp-form { display: flex; gap: 0.5rem; }
.otp-form input {
  flex: 1; border: 1.5px solid rgba(28,114,130,.25); border-radius: 0.75rem; padding: 0.65rem 0.85rem;
}
.otp-form button {
  border: 0; background: #1c7282; color: #fff; border-radius: 0.75rem; padding: 0.65rem 1rem; font-weight: 800; cursor: pointer;
}
.otp-msg { margin: 0.5rem 0 0; font-weight: 700; color: #1c7282; }
.driver, .fail { margin-bottom: 1rem; color: #4d6b72; }
.fail { color: #a82626; font-weight: 700; }
.timeline { list-style: none; padding: 0; margin: 1.5rem 0; text-align: right; }
.timeline-item {
  display: grid; grid-template-columns: 18px 1fr; gap: 0.75rem; margin-bottom: 1rem; position: relative;
}
.dot {
  width: 12px; height: 12px; border-radius: 50%; background: #1c7282; margin-top: 0.35rem;
  box-shadow: 0 0 0 4px rgba(28,114,130,.15);
}
.time, .note { color: #4d6b72; font-size: 0.85rem; }
.proofs { display: flex; gap: 1rem; justify-content: center; margin-bottom: 1rem; }
.proofs a { color: #1c7282; font-weight: 700; }
.review-panel {
  text-align: right;
  margin: 0 0 1.35rem;
}
.review-icon-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0.85rem;
  text-align: right;
  border: 1px solid rgba(28,114,130,.18);
  background: #eef8f9;
  border-radius: 1.1rem;
  padding: 0.95rem 1rem;
  cursor: pointer;
}
.review-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: #1c7282;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}
.review-icon-btn strong { display: block; color: #0b3d44; }
.review-icon-btn small { display: block; color: #4d6b72; margin-top: 0.15rem; font-size: 0.82rem; }
.review-forms { margin-top: 0.85rem; display: grid; gap: 0.85rem; }
.reward-line { margin: 0; color: #1c7282; font-weight: 800; font-size: 0.88rem; }
.review-item-card {
  background: #fff;
  border: 1px solid rgba(28,114,130,.12);
  border-radius: 1rem;
  padding: 1rem;
}
.ri-head { display: flex; gap: 0.75rem; align-items: center; margin-bottom: 0.65rem; }
.ri-img { width: 52px; height: 52px; border-radius: 10px; object-fit: cover; }
.ri-link { display: block; color: #1c7282; font-size: 0.82rem; font-weight: 700; margin-top: 0.15rem; }
.star-pick { display: flex; gap: 0.2rem; margin-bottom: 0.55rem; }
.star-btn { border: 0; background: transparent; color: #c5d8dc; font-size: 1.45rem; cursor: pointer; line-height: 1; }
.star-btn.on { color: #d4a017; }
.review-item-card textarea {
  width: 100%; box-sizing: border-box; border: 1.5px solid rgba(28,114,130,.22);
  border-radius: .75rem; padding: .65rem; margin-bottom: .5rem; resize: vertical;
}
.file-label { display: block; font-weight: 700; font-size: .85rem; color: #0b3d44; margin-bottom: .55rem; }
.btn-submit-review {
  border: 0; background: #1c7282; color: #fff; border-radius: 999px;
  padding: .65rem 1.1rem; font-weight: 900; cursor: pointer;
}
.form-msg { margin: .45rem 0 0; font-weight: 700; color: #1c7282; }
.form-msg.err { color: #a82626; }
.fit-q { margin: .35rem 0 .4rem; font-weight: 800; font-size: .88rem; color: #0b3d44; text-align: right; }
.fit-pick, .type-pick { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .65rem; }
.fit-btn {
  border: 1.5px solid rgba(28,114,130,.25); background: #fff; color: #0b3d44;
  border-radius: 999px; padding: .4rem .75rem; font-weight: 800; font-size: .8rem; cursor: pointer;
}
.fit-btn.on { background: #1c7282; color: #fff; border-color: #1c7282; }
.field { display: flex; flex-direction: column; gap: .3rem; font-weight: 700; font-size: .85rem; margin-bottom: .55rem; text-align: right; }
.field input, .field select, .field textarea {
  border: 1.5px solid rgba(28,114,130,.22); border-radius: .65rem; padding: .55rem .7rem; font-weight: 600;
}
.my-returns { margin-top: 1rem; text-align: right; }
.my-returns h3 { margin: 0 0 .75rem; color: #0b3d44; font-size: 1rem; }
.return-track-card {
  background: #f7fbfc; border: 1px solid rgba(28,114,130,.12); border-radius: 1rem;
  padding: .9rem; margin-bottom: .75rem;
}
.rt-head { display: flex; justify-content: space-between; gap: .5rem; flex-wrap: wrap; margin-bottom: .35rem; }
.rt-exchange { margin: 0 0 .5rem; color: #1c7282; font-weight: 800; }
.mini-steps { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: .35rem; }
.mini-steps li {
  font-size: .72rem; font-weight: 800; padding: .25rem .55rem; border-radius: 999px;
  background: #e8f2f4; color: #9aabaf;
}
.mini-steps li.done, .mini-steps li.current { background: #1c7282; color: #fff; }
.refund-box { margin-top: .65rem; padding-top: .55rem; border-top: 1px dashed rgba(28,114,130,.2); }
.refund-box h4 { margin: 0 0 .35rem; color: #c62828; }
.refund-box p { margin: 0 0 .45rem; font-size: .85rem; color: #4d6b72; }
.btn {
  display: inline-flex; justify-content: center; text-decoration: none; padding: 0.75rem 1.25rem;
  border-radius: 999px; font-weight: 800;
}
.btn-primary { background: #1c7282; color: #fff; }
.btn-outline { border: 2px solid #1c7282; color: #1c7282; background: #fff; }
@media (max-width: 520px) {
  .step-label { font-size: 0.58rem; }
}
</style>
