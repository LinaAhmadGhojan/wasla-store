<template>
  <div class="checkout-page" dir="rtl">
    <StorefrontNav />

    <div class="checkout-shell">
      <h1>إتمام الطلب</h1>

      <div v-if="!store.authToken" class="empty-state">
        <p>لتأكيد الطلب لازم تكوني مسجّلة دخول.</p>
        <a :href="loginHref" class="btn btn-primary">دخول / تسجيل</a>
      </div>
      <div v-else-if="loading" class="loading-row">جاري تحميل الدفع…</div>
      <div v-else-if="!cartItems.length && !guestExternalItems.length" class="empty-state">
        <p>سلتك فارغة.</p>
        <a href="/" class="btn btn-primary">تصفّح المنتجات</a>
      </div>
      <div v-else class="checkout-grid">
        <div class="checkout-main">
          <nav class="steps" aria-label="خطوات الدفع">
            <button type="button" class="step" :class="{ active: step === 'choose', done: stepIndex > 0 }" @click="goStep('choose')">
              <span class="n">1</span> اختيار العنوان
            </button>
            <span class="sep" />
            <button type="button" class="step" :class="{ active: step === 'map', done: stepIndex > 1 }" @click="tryGoMap">
              <span class="n">2</span> الخريطة
            </button>
            <span class="sep" />
            <button type="button" class="step" :class="{ active: step === 'payment', done: stepIndex > 2 }" @click="tryGoPayment">
              <span class="n">3</span> الدفع
            </button>
          </nav>

          <!-- 1. Choose address -->
          <section v-if="step === 'choose'" class="checkout-section">
            <h2>اختاري عنوان التوصيل</h2>
            <p class="muted">تقدري تحفظي عدة عناوين (بيت / عمل / آخر) وتختاري واحداً للطلب.</p>

            <div v-if="addresses.length" class="address-list">
              <label
                v-for="addr in addresses"
                :key="addr.id"
                class="address-option"
                :class="{ selected: selectedAddressId === addr.id }"
              >
                <input type="radio" :value="addr.id" v-model="selectedAddressId" />
                <div class="addr-body">
                  <div class="addr-top">
                    <strong>{{ addr.label || addr.label_type_ar || addr.recipient_name }}</strong>
                    <span v-if="addr.is_default" class="badge">افتراضي</span>
                  </div>
                  <p>{{ addr.recipient_name }} · {{ addr.phone }}</p>
                  <p>{{ addr.street_address }} — {{ [addr.state, addr.city].filter(Boolean).join(' · ') }}</p>
                  <p v-if="addr.courier_notes" class="notes-line">ملاحظات المندوب: {{ addr.courier_notes }}</p>
                  <p class="pin-line" :class="{ ok: addr.has_coordinates }">
                    {{ addr.has_coordinates ? 'الموقع محدد على الخريطة' : 'يحتاج تحديد موقع على الخريطة' }}
                  </p>
                </div>
              </label>
            </div>
            <p v-else class="muted">ما في عناوين محفوظة بعد.</p>

            <div class="row-actions">
              <button type="button" class="btn btn-secondary" @click="startNewAddress">+ عنوان جديد</button>
              <button
                v-if="selectedAddress"
                type="button"
                class="btn btn-ghost"
                @click="startEditSelected"
              >
                تعديل العنوان المختار
              </button>
            </div>

            <button
              type="button"
              class="btn btn-primary next-btn"
              :disabled="!selectedAddressId && !creatingNew"
              @click="goToMapStep"
            >
              التالي: تحديد الموقع على الخريطة
            </button>
            <p v-if="stepError" class="feedback error">{{ stepError }}</p>
          </section>

          <!-- 2. Map + address details -->
          <section v-else-if="step === 'map'" class="checkout-section">
            <h2>{{ mapMode === 'new' ? 'عنوان جديد على الخريطة' : 'أكّدي الموقع على الخريطة' }}</h2>
            <p class="muted">حرّكي الدبوس أو استخدمي GPS، وأضيفي ملاحظات للمندوب إن لزم.</p>

            <AddressForm
              :key="formKey"
              :initial="mapFormInitial"
              :saving="savingAddress"
              :error="addressError"
              :show-cancel="true"
              :submit-label="mapMode === 'new' ? 'حفظ ومتابعة للدفع' : 'تحديث ومتابعة للدفع'"
              title=""
              @submit="saveAddressFromMap"
              @cancel="step = 'choose'"
            />
          </section>

          <!-- 3. Payment + extras -->
          <section v-else class="checkout-section">
            <div class="selected-addr-summary" v-if="selectedAddress">
              <strong>التوصيل إلى:</strong>
              <span>{{ selectedAddress.label || selectedAddress.label_type_ar }} — {{ selectedAddress.street_address }}, {{ selectedAddress.city }}</span>
              <button type="button" class="linkish" @click="step = 'choose'">تغيير</button>
            </div>

            <h2>طريقة الشحن</h2>
            <div class="payment-list">
              <label
                v-for="(m, key) in shippingMethods"
                :key="key"
                class="payment-option"
                :class="{ selected: shippingMethod === key }"
              >
                <input type="radio" :value="key" v-model="shippingMethod" />
                <div class="payment-option-body">
                  <strong>{{ m.label }}</strong>
                  <p>{{ m.eta }} · {{ m.fee > 0 ? formatSyp(aedToSypAmount(m.fee, 'product')) : 'مجاني' }}</p>
                </div>
              </label>
            </div>

            <h2>كوبون / محفظة / نقاط</h2>
            <div class="extras-box">
              <div class="coupon-row">
                <input v-model="couponCode" type="text" placeholder="كود الخصم" dir="ltr" :disabled="!!appliedCoupon" />
                <button v-if="!appliedCoupon" type="button" class="btn btn-secondary sm" @click="applyCoupon">تطبيق</button>
                <button v-else type="button" class="btn btn-ghost sm" @click="clearCoupon">إزالة</button>
              </div>
              <p v-if="couponMsg" class="hint" :class="{ err: couponError }">{{ couponMsg }}</p>
              <label class="wallet-row">
                <span>استخدام رصيد المحفظة (متاح {{ formatSyp(walletAvailable) }})</span>
                <input v-model.number="walletAmountSyp" type="number" min="0" :max="walletAvailable" step="1" />
              </label>
              <label class="wallet-row">
                <span>استخدام نقاط (1 نقطة = 1 ل.س)</span>
                <input v-model.number="pointsUsed" type="number" min="0" step="1" />
              </label>
            </div>

            <h2>ملاحظات الطلب</h2>
            <textarea v-model="customerNotes" class="notes-area" rows="2" placeholder="ملاحظات للتوصيل أو الطلب…"></textarea>

            <h2>هدية؟</h2>
            <label class="gift-toggle">
              <input type="checkbox" v-model="isGift" />
              <span>هذا الطلب هدية</span>
            </label>
            <div v-if="isGift" class="gift-box">
              <label class="gift-toggle">
                <input type="checkbox" v-model="giftWrapping" />
                <span>تغليف هدية (+{{ formatSyp(aedToSypAmount(giftWrapFee, 'product')) }})</span>
              </label>
              <label>اسم المستلم<input v-model="giftRecipientName" type="text" /></label>
              <label>هاتف المستلم<input v-model="giftRecipientPhone" type="text" dir="ltr" /></label>
              <label>عنوان المستلم<textarea v-model="giftRecipientAddress" rows="2"></textarea></label>
              <label>رسالة الهدية<textarea v-model="giftMessage" rows="2" placeholder="كل عام وأنتِ بخير…"></textarea></label>
            </div>

            <h2>طريقة الدفع</h2>
            <div class="payment-list">
              <label
                v-for="(label, key) in filteredPaymentMethods"
                :key="key"
                class="payment-option"
                :class="{ selected: paymentMethod === key }"
              >
                <input type="radio" :value="key" v-model="paymentMethod" />
                <img
                  v-if="paymentIcons[key]"
                  :src="paymentIcons[key]"
                  :alt="label"
                  class="pay-icon"
                />
                <div class="payment-option-body">
                  <strong>{{ label }}</strong>
                  <p v-if="paymentAccounts[key]" class="account-hint">{{ paymentAccounts[key] }}</p>
                  <p v-if="paymentInstructions[key]">{{ fillAmount(paymentInstructions[key]) }}</p>
                </div>
              </label>
            </div>

            <div class="proof-box" :class="{ 'has-error': !!proofError }" v-if="needsProofBox">
              <h3>إثبات الدفع</h3>
              <p class="proof-rule">{{ proofHint }}</p>

              <label v-if="allowsCode" :class="{ 'label-error': !!proofError && proofErrorField === 'code' }">
                {{ paymentMethod === 'sham_cash' ? 'كود شام كاش (إلزامي)' : 'كود التحويل / كود العملية' }}
              </label>
              <input
                v-if="allowsCode"
                ref="transferCodeInput"
                v-model="transferCode"
                class="proof-input"
                :class="{ 'is-invalid': !!proofError && proofErrorField === 'code' }"
                :placeholder="paymentMethod === 'sham_cash' ? 'أدخلي كود شام كاش' : 'كود التحويل (اختياري إذا رفعِت الوصل)'"
                dir="ltr"
                autocomplete="off"
                @input="clearProofError"
              />

              <label v-if="allowsReceipt" :class="{ 'label-error': !!proofError && proofErrorField === 'receipt' }">صورة الوصل</label>
              <input
                v-if="allowsReceipt"
                ref="receiptInput"
                type="file"
                accept="image/*"
                class="proof-input"
                :class="{ 'is-invalid': !!proofError && proofErrorField === 'receipt' }"
                @change="onReceiptPicked"
              />
              <p v-if="receiptFile" class="file-name">تم اختيار: {{ receiptFile.name }}</p>
              <p v-if="proofError" class="field-error" role="alert">{{ proofError }}</p>
            </div>
            <div class="proof-box soft" v-else-if="paymentMethod">
              <p class="proof-rule">{{ proofHint }}</p>
            </div>

            <div class="row-actions">
              <button type="button" class="btn btn-ghost" @click="step = 'map'">رجوع للخريطة</button>
            </div>
          </section>
        </div>

        <aside class="order-summary">
          <h2>ملخص الطلب</h2>
          <div v-for="(group, vendorName) in groupedItems" :key="vendorName" class="summary-vendor">
            <strong>وصلة — {{ vendorName }}</strong>
            <div v-for="item in group" :key="item.id" class="summary-item">
              <span>{{ item.product?.name }} &times; {{ item.quantity }}</span>
              <span>{{ moneyLine(unitPrice(item), item.quantity, item.product?.pricing_kind || 'product') }}</span>
            </div>
          </div>
          <div v-if="guestExternalItems.length" class="summary-vendor">
            <strong>مواقع خارجية (بنفس الطلبية)</strong>
            <div v-for="item in guestExternalItems" :key="item.id" class="summary-item">
              <span>
                <em class="src-tag">{{ item.platform?.name || 'خارجي' }}</em>
                {{ item.product_name }} &times; {{ item.quantity }}
              </span>
              <span>{{ money(item.unit_price * item.quantity) }}</span>
            </div>
          </div>
          <div class="summary-row">
            <span>المجموع الفرعي</span>
            <span>{{ formatSyp(subtotalSyp) }}</span>
          </div>
          <div class="summary-row">
            <span>الشحن</span>
            <span>{{ shippingFeeSyp > 0 ? formatSyp(shippingFeeSyp) : 'مجاني' }}</span>
          </div>
          <div v-if="isGift && giftWrapping" class="summary-row">
            <span>تغليف هدية</span>
            <span>{{ formatSyp(giftWrapFeeSyp) }}</span>
          </div>
          <div v-if="appliedCoupon" class="summary-row discount">
            <span>خصم ({{ appliedCoupon.code }})</span>
            <span>−{{ formatSyp(appliedCoupon.discount_syp) }}</span>
          </div>
          <div v-if="walletAmountSyp > 0 || pointsUsed > 0" class="summary-row discount">
            <span>محفظة / نقاط</span>
            <span>−{{ formatSyp(Number(walletAmountSyp || 0) + Number(pointsUsed || 0)) }}</span>
          </div>
          <div class="summary-row total-row">
            <span>الإجمالي</span>
            <span>{{ checkoutTotalSyp }}</span>
          </div>
          <button
            v-if="step === 'payment'"
            type="button"
            class="btn btn-primary place-order-btn"
            :disabled="!selectedAddressId || placingOrder || !selectedAddress?.has_coordinates"
            @click="placeOrder"
          >
            {{ placingOrder ? 'جاري تأكيد الطلب…' : 'تأكيد الطلب' }}
          </button>
          <button
            v-else-if="step === 'choose'"
            type="button"
            class="btn btn-primary place-order-btn"
            @click="goToMapStep"
          >
            متابعة للخريطة
          </button>
          <p v-else class="map-hint">احفظي العنوان من النموذج بالأسفل للمتابعة للدفع.</p>
          <p v-if="orderError" class="feedback error">{{ orderError }}</p>
        </aside>
      </div>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import api from '../../storefront/api';
import { store, refreshCartCount, getGuestCart, hydrateUser } from '../../storefront/store';
import { clearGuestExternalItems, clearGuestLocalItems } from '../../storefront/guestCart';
import { money, moneyLine, formatSyp, aedToSypAmount, loginUrl } from '../../storefront/format';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';
import AddressForm from './AddressForm.vue';

const cartItems = ref([]);
const guestExternalItems = ref([]);
const addresses = ref([]);
const loading = ref(true);
const loginHref = loginUrl('/checkout');

const step = ref('choose'); // choose | map | payment
const stepError = ref('');
const creatingNew = ref(false);
const mapMode = ref('edit'); // new | edit
const mapFormInitial = ref(null);
const formKey = ref(0);

const shippingMethods = ref({});
const shippingMethod = ref('standard');
const giftWrapFee = ref(15);
const couponCode = ref(sessionStorage.getItem('wasla_coupon') || '');
const appliedCoupon = ref(null);
const couponMsg = ref('');
const couponError = ref(false);
const walletAmountSyp = ref(0);
const pointsUsed = ref(0);
const customerNotes = ref('');
const isGift = ref(false);
const giftWrapping = ref(false);
const giftMessage = ref('');
const giftRecipientName = ref('');
const giftRecipientPhone = ref('');
const giftRecipientAddress = ref('');

const paymentMethods = window.WASLA_PAYMENT_METHODS || {};
const paymentInstructions = window.WASLA_PAYMENT_INSTRUCTIONS || {};
const paymentIcons = window.WASLA_PAYMENT_ICONS || {};
const paymentAccounts = window.WASLA_PAYMENT_ACCOUNTS || {};
const paymentRules = window.WASLA_PAYMENT_RULES || {};

const filteredPaymentMethods = { ...paymentMethods };
const paymentMethod = ref(Object.keys(filteredPaymentMethods)[0] || 'sham_cash');
const transferCode = ref('');
const receiptFile = ref(null);
const transferCodeInput = ref(null);
const receiptInput = ref(null);
const proofError = ref('');
const proofErrorField = ref('');

const selectedAddressId = ref(null);
const savingAddress = ref(false);
const addressError = ref('');
const placingOrder = ref(false);
const orderError = ref('');

const walletAvailable = computed(() => Number(store.currentUser?.store_credit_syp || 0));

const selectedAddress = computed(() =>
  addresses.value.find((a) => a.id === selectedAddressId.value) || null
);

const stepIndex = computed(() => {
  if (step.value === 'map') return 1;
  if (step.value === 'payment') return 2;
  return 0;
});

const allowsReceipt = computed(() => {
  const rules = paymentRules[paymentMethod.value] || {};
  const req = rules.require || [];
  const one = rules.require_one_of || [];
  return req.includes('receipt') || one.includes('receipt');
});
const allowsCode = computed(() => {
  const rules = paymentRules[paymentMethod.value] || {};
  const req = rules.require || [];
  const one = rules.require_one_of || [];
  return req.includes('transfer_code') || one.includes('transfer_code');
});
const proofHint = computed(() => {
  if (paymentMethod.value === 'cash_on_delivery') {
    return 'الدفع كامل عند التسليم — ما في تحويل مسبق.';
  }
  if (paymentMethod.value === 'store_credit') {
    return 'يُخصم من رصيد حسابك فوراً ويبدأ التجهيز.';
  }
  if (paymentMethod.value === 'sham_cash') {
    return 'شام كاش: كود العملية إلزامي. التأكيد النهائي من داشبورد وصلة.';
  }
  return 'الهرم / الفؤاد: ارفعي صورة الوصل أو أدخلي كود التحويل (واحد إلزامي). التأكيد من داشبورد وصلة.';
});

const needsProofBox = computed(() => {
  return ['al_haram', 'fouad', 'sham_cash'].includes(paymentMethod.value);
});

function fillAmount(text) {
  if (!text) return '';
  return String(text).replace('{amount}', checkoutTotalSyp.value);
}

function onReceiptPicked(e) {
  receiptFile.value = e.target.files?.[0] || null;
  clearProofError();
}

function clearProofError() {
  proofError.value = '';
  proofErrorField.value = '';
}

async function showProofError(message, field = 'code') {
  proofError.value = message;
  proofErrorField.value = field;
  orderError.value = message;
  await nextTick();
  const el = field === 'receipt' ? receiptInput.value : transferCodeInput.value;
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.focus({ preventScroll: true });
  }
}

watch(paymentMethod, () => {
  clearProofError();
  transferCode.value = '';
  receiptFile.value = null;
});

function goStep(name) {
  if (name === 'choose') step.value = 'choose';
}

function tryGoMap() {
  if (selectedAddressId.value || creatingNew.value) goToMapStep();
}

function tryGoPayment() {
  if (selectedAddress.value?.has_coordinates) {
    step.value = 'payment';
  } else if (selectedAddressId.value) {
    goToMapStep();
  }
}

function startNewAddress() {
  creatingNew.value = true;
  mapMode.value = 'new';
  mapFormInitial.value = {
    is_default: addresses.value.length === 0,
    country: 'سوريا',
    label_type: 'home',
    label: 'البيت',
  };
  formKey.value += 1;
  step.value = 'map';
  stepError.value = '';
}

function startEditSelected() {
  if (!selectedAddress.value) return;
  creatingNew.value = false;
  mapMode.value = 'edit';
  mapFormInitial.value = { ...selectedAddress.value };
  formKey.value += 1;
  step.value = 'map';
  stepError.value = '';
}

function goToMapStep() {
  stepError.value = '';
  if (creatingNew.value && mapMode.value === 'new') {
    step.value = 'map';
    return;
  }
  if (!selectedAddressId.value) {
    stepError.value = 'اختاري عنواناً أو أضيفي عنواناً جديداً.';
    return;
  }
  mapMode.value = 'edit';
  creatingNew.value = false;
  mapFormInitial.value = { ...selectedAddress.value };
  formKey.value += 1;
  step.value = 'map';
}

async function saveAddressFromMap(payload) {
  savingAddress.value = true;
  addressError.value = '';
  try {
    const body = { ...payload };
    const id = body.id;
    delete body.id;
    delete body.label_type_ar;
    delete body.has_coordinates;
    delete body.created_at;
    delete body.updated_at;
    delete body.user_id;

    let saved;
    if (id) {
      const { data } = await api.put(`/addresses/${id}`, body);
      saved = data;
      addresses.value = addresses.value.map((a) => (a.id === saved.id ? saved : a));
    } else {
      const { data } = await api.post('/addresses', body);
      saved = data;
      addresses.value = [saved, ...addresses.value.filter((a) => a.id !== saved.id)];
    }

    if (saved.is_default) {
      addresses.value = addresses.value.map((a) => ({
        ...a,
        is_default: a.id === saved.id,
      }));
    }

    selectedAddressId.value = saved.id;
    creatingNew.value = false;
    step.value = 'payment';
  } catch (e) {
    addressError.value = e?.response?.data?.errors
      ? Object.values(e.response.data.errors).flat().join(' ')
      : 'تعذّر حفظ العنوان.';
  } finally {
    savingAddress.value = false;
  }
}

function unitPrice(item) {
  const variant = item.variant;
  const product = item.product;
  if (variant) {
    return variant.sale_price ? Number(variant.sale_price) : Number(variant.price);
  }
  if (product) {
    return product.sale_price ? Number(product.sale_price) : Number(product.price);
  }
  return 0;
}

const groupedItems = computed(() => {
  const groups = {};
  for (const item of cartItems.value) {
    const vendorName = item.product?.vendor?.store_name || 'Wasla Store';
    if (!groups[vendorName]) groups[vendorName] = [];
    groups[vendorName].push(item);
  }
  return groups;
});

const subtotalAed = computed(() => {
  const local = cartItems.value.reduce((sum, item) => sum + unitPrice(item) * item.quantity, 0);
  const external = guestExternalItems.value.reduce(
    (sum, item) => sum + Number(item.unit_price || 0) * item.quantity,
    0
  );
  return local + external;
});

const subtotalSyp = computed(() => {
  const local = cartItems.value.reduce(
    (sum, item) => sum + aedToSypAmount(unitPrice(item), item.product?.pricing_kind || 'product') * item.quantity,
    0
  );
  const external = guestExternalItems.value.reduce(
    (sum, item) => sum + aedToSypAmount(item.unit_price, 'raw') * item.quantity,
    0
  );
  return local + external;
});

const shippingFeeAed = computed(() => Number(shippingMethods.value[shippingMethod.value]?.fee || 0));
const shippingFeeSyp = computed(() => aedToSypAmount(shippingFeeAed.value, 'product'));
const giftWrapFeeSyp = computed(() => aedToSypAmount(Number(giftWrapFee.value || 0), 'product'));

const checkoutTotalSyp = computed(() => {
  let total = subtotalSyp.value + shippingFeeSyp.value;
  if (isGift.value && giftWrapping.value) total += giftWrapFeeSyp.value;
  total -= appliedCoupon.value?.discount_syp || 0;
  total -= Number(walletAmountSyp.value || 0) + Number(pointsUsed.value || 0);
  return formatSyp(Math.max(0, total));
});

async function applyCoupon() {
  const code = couponCode.value.trim();
  if (!code) return;
  couponError.value = false;
  couponMsg.value = 'جاري التحقق…';
  try {
    const { data } = await api.get('/v1/coupons/preview', {
      params: { code, subtotal: subtotalAed.value || 1 },
    });
    const discountAed = Number(data.discount || 0);
    appliedCoupon.value = {
      code: data.code || code.toUpperCase(),
      discount: discountAed,
      discount_syp: aedToSypAmount(discountAed, 'product'),
    };
    sessionStorage.setItem('wasla_coupon', appliedCoupon.value.code);
    couponCode.value = appliedCoupon.value.code;
    couponMsg.value = data.name ? `تم تطبيق: ${data.name}` : 'تم تطبيق الكوبون.';
  } catch (e) {
    couponError.value = true;
    const errs = e?.response?.data?.errors;
    couponMsg.value = errs
      ? Object.values(errs).flat().join(' ')
      : 'الكوبون غير صالح.';
    appliedCoupon.value = null;
  }
}

function clearCoupon() {
  appliedCoupon.value = null;
  couponCode.value = '';
  couponMsg.value = '';
  couponError.value = false;
  sessionStorage.removeItem('wasla_coupon');
}

async function loadData() {
  if (!store.authToken) {
    loading.value = false;
    return;
  }
  loading.value = true;
  try {
    await hydrateUser();
    const [cartRes, addressRes, shipRes] = await Promise.all([
      api.get('/cart'),
      api.get('/addresses'),
      api.get('/v1/shipping-methods').catch(() => ({ data: {} })),
    ]);
    const cartData = cartRes.data;
    cartItems.value = Array.isArray(cartData) ? cartData : (cartData.items || []);
    guestExternalItems.value = getGuestCart().filter((i) => i.type === 'external' && !i.saved_for_later);
    addresses.value = addressRes.data;
    shippingMethods.value = shipRes.data?.methods || {
      standard: { label: 'شحن عادي', fee: 0, eta: '2–5 أيام' },
      express: { label: 'توصيل سريع', fee: 25, eta: '24–48 ساعة' },
    };
    giftWrapFee.value = Number(shipRes.data?.gift_wrapping_fee ?? 15);
    if (!shippingMethods.value[shippingMethod.value]) {
      shippingMethod.value = Object.keys(shippingMethods.value)[0] || 'standard';
    }
    if (couponCode.value) {
      await applyCoupon();
    }
    const defaultAddress = addresses.value.find((a) => a.is_default) || addresses.value[0];
    if (defaultAddress) {
      selectedAddressId.value = defaultAddress.id;
      step.value = 'choose';
    } else {
      startNewAddress();
    }
  } catch (e) {
    cartItems.value = [];
  } finally {
    loading.value = false;
  }
}

async function placeOrder() {
  if (!selectedAddressId.value) return;
  if (!selectedAddress.value?.has_coordinates) {
    orderError.value = 'حدّدي موقع التوصيل على الخريطة أولاً.';
    step.value = 'map';
    return;
  }
  if (!cartItems.value.length && !guestExternalItems.value.length) return;

  const code = transferCode.value.trim();
  const hasFile = !!receiptFile.value;
  if (needsProofBox.value) {
    if (paymentMethod.value === 'sham_cash' && !code) {
      await showProofError('كود شام كاش إلزامي — أدخليه من تطبيق شام كاش.', 'code');
      return;
    }
    if (['al_haram', 'fouad'].includes(paymentMethod.value) && !code && !hasFile) {
      await showProofError('ارفعي صورة الوصل أو أدخلي كود التحويل.', allowsCode.value ? 'code' : 'receipt');
      return;
    }
  }
  placingOrder.value = true;
  orderError.value = '';
  clearProofError();
  try {
    const external_items = guestExternalItems.value.map((item) => ({
      platform_id: item.platform?.id || undefined,
      product_name: item.product_name,
      external_url: item.external_url,
      external_product_id: item.external_product_id || null,
      quantity: item.quantity,
      unit_price: item.unit_price || 0,
      image: item.image || null,
      variant_data: {
        quote: item.quote || null,
        external_product_id: item.external_product_id || null,
        sku: item.sku || item.variant_data?.sku || null,
        color: item.color || item.variant_data?.color || null,
        color_name: item.color_name || item.variant_data?.color_name || null,
        size: item.size || item.variant_data?.size || null,
        size_name: item.size_name || item.variant_data?.size_name || null,
        goods_id: item.goods_id || item.variant_data?.goods_id || null,
        image: item.image || item.variant_data?.image || null,
      },
    }));

    const fd = new FormData();
    fd.append('shipping_address_id', selectedAddressId.value);
    fd.append('payment_method', paymentMethod.value);
    fd.append('shipping_method', shippingMethod.value);
    if (appliedCoupon.value?.code) fd.append('coupon_code', appliedCoupon.value.code);
    if (customerNotes.value.trim()) fd.append('customer_notes', customerNotes.value.trim());
    if (walletAmountSyp.value > 0) fd.append('wallet_amount', String(walletAmountSyp.value));
    if (pointsUsed.value > 0) fd.append('points_used', String(pointsUsed.value));
    fd.append('is_gift', isGift.value ? '1' : '0');
    if (isGift.value) {
      fd.append('gift_wrapping', giftWrapping.value ? '1' : '0');
      if (giftMessage.value) fd.append('gift_message', giftMessage.value);
      if (giftRecipientName.value) fd.append('gift_recipient_name', giftRecipientName.value);
      if (giftRecipientPhone.value) fd.append('gift_recipient_phone', giftRecipientPhone.value);
      if (giftRecipientAddress.value) fd.append('gift_recipient_address', giftRecipientAddress.value);
    }
    if (code) fd.append('transfer_code', code);
    if (receiptFile.value) fd.append('receipt', receiptFile.value);
    fd.append('external_items', JSON.stringify(external_items));

    const { data } = await api.post('/orders', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    const orderIds = (data.orders || [data.order]).filter(Boolean).map((o) => o.id);

    clearGuestExternalItems();
    clearGuestLocalItems();
    sessionStorage.removeItem('wasla_coupon');
    await refreshCartCount();

    window.location.href = `/order-confirmation?orders=${orderIds.join(',')}`;
  } catch (e) {
    orderError.value = e?.response?.data?.message
      || e?.response?.data?.errors && Object.values(e.response.data.errors).flat().join(' ')
      || 'تعذّر تأكيد الطلب. حاولي مرة ثانية.';
  } finally {
    placingOrder.value = false;
  }
}

onMounted(loadData);
</script>

<style scoped>
.checkout-page { color: #132f37; background: linear-gradient(180deg, #eaf6f8 0%, #f7fbfc 45%, #fff 100%); min-height: 100vh; }
.checkout-shell { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem; }
.checkout-shell h1 { margin: 0 0 1.25rem; color: #0b3d44; }
.loading-row, .empty-state { padding: 4rem 0; text-align: center; color: #4d6b72; }
.empty-state a { display: inline-block; margin-top: 1rem; }
.checkout-grid {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 2rem;
  align-items: start;
}
.steps {
  display: flex;
  align-items: center;
  gap: .35rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}
.step {
  border: 0;
  background: #fff;
  color: #4d6b72;
  border-radius: 999px;
  padding: .45rem .85rem;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  cursor: pointer;
  border: 1.5px solid rgba(28,114,130,.15);
}
.step .n {
  width: 1.35rem; height: 1.35rem; border-radius: 999px;
  display: inline-flex; align-items: center; justify-content: center;
  background: #e8f2f4; font-size: .8rem;
}
.step.active { border-color: #1c7282; color: #0b3d44; background: #f5fbfc; }
.step.active .n, .step.done .n { background: #1c7282; color: #fff; }
.step.done { color: #1c7282; }
.sep { width: 1.25rem; height: 2px; background: rgba(28,114,130,.2); }
.checkout-section {
  background: white;
  border-radius: 1.25rem;
  border: 1px solid rgba(15, 90, 107, 0.08);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 12px 30px rgba(19,47,55,.04);
}
.checkout-section h2 { margin-top: 0; font-size: 1.1rem; color: #0b3d44; }
.muted { color: #9aabaf; font-size: 0.9rem; }
.address-list { display: flex; flex-direction: column; gap: 0.75rem; margin: 1rem 0; }
.payment-list { display: flex; flex-direction: column; gap: 0.6rem; }
.address-option, .payment-option {
  display: flex;
  gap: 0.75rem;
  padding: 0.85rem;
  border: 1.5px solid rgba(15, 90, 107, 0.15);
  border-radius: 0.85rem;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}
.address-option.selected, .payment-option.selected {
  border-color: #1c7282;
  background: #f5fbfc;
}
.address-option p, .payment-option p {
  margin: 0.15rem 0 0;
  font-size: 0.82rem;
  color: #4d6b72;
}
.addr-top { display: flex; gap: .45rem; align-items: center; flex-wrap: wrap; }
.badge { background: #e8f6f8; color: #1c7282; border-radius: 999px; padding: .1rem .5rem; font-size: .72rem; font-weight: 800; }
.notes-line { color: #0b3d44 !important; }
.pin-line { font-weight: 700; color: #a87400 !important; }
.pin-line.ok { color: #1c7282 !important; }
.row-actions { display: flex; gap: .55rem; flex-wrap: wrap; margin: .75rem 0; }
.next-btn { margin-top: .5rem; }
.selected-addr-summary {
  display: flex; flex-wrap: wrap; gap: .45rem; align-items: center;
  background: #f5fbfc; border-radius: .85rem; padding: .75rem .9rem;
  margin-bottom: 1rem; font-size: .92rem;
}
.linkish {
  border: 0; background: transparent; color: #1c7282; font-weight: 800; cursor: pointer;
}
.payment-option-body { flex: 1; }
.pay-icon {
  width: 56px; height: 56px; border-radius: 12px; flex-shrink: 0;
  object-fit: contain; background: #fff; padding: 4px;
  box-shadow: 0 2px 8px rgba(15, 79, 90, 0.12);
}
.account-hint { color: #1c7282 !important; font-weight: 700 !important; }
.proof-box {
  margin-top: 1rem; padding: 1rem; border-radius: 1rem;
  background: #f7fbfc; border: 1px dashed rgba(28,114,130,.35);
}
.proof-box.has-error { border-color: #c62828; background: #fff5f5; }
.proof-box h3 { margin: 0 0 .35rem; font-size: 1rem; color: #0b3d44; }
.proof-rule { margin: 0 0 .85rem; color: #4d6b72; font-size: .9rem; }
.proof-box label { display: block; margin: .55rem 0 .3rem; font-weight: 700; font-size: .88rem; }
.proof-box label.label-error { color: #c62828; }
.proof-input {
  width: 100%; border: 1.5px solid rgba(28,114,130,.22); border-radius: .75rem;
  padding: .7rem .85rem; box-sizing: border-box; background: #fff;
}
.proof-input.is-invalid {
  border-color: #c62828 !important; background: #fff8f8;
  box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.18); outline: none;
}
.field-error { margin: 0.55rem 0 0; color: #c62828; font-size: 0.9rem; font-weight: 700; }
.proof-box.soft { background: #eef8f9; border-style: solid; border-color: rgba(28,114,130,.2); }
.file-name { margin: .35rem 0 0; color: #1c7282; font-size: .85rem; font-weight: 700; }
.extras-box {
  display: flex; flex-direction: column; gap: .65rem;
  margin: .75rem 0 1.25rem; padding: .9rem;
  background: #f7fbfc; border-radius: .9rem;
}
.coupon-row { display: flex; gap: .4rem; }
.coupon-row input, .wallet-row input, .gift-box input, .gift-box textarea, .notes-area {
  border: 1.5px solid rgba(28,114,130,.22); border-radius: .65rem; padding: .55rem .7rem;
  width: 100%; box-sizing: border-box;
}
.coupon-row input { flex: 1; }
.wallet-row { display: flex; flex-direction: column; gap: .3rem; font-weight: 700; font-size: .88rem; }
.notes-area { margin: .5rem 0 1.1rem; resize: vertical; }
.gift-toggle { display: flex; align-items: center; gap: .5rem; font-weight: 800; margin: .5rem 0; cursor: pointer; }
.gift-box { display: flex; flex-direction: column; gap: .55rem; margin-bottom: 1.1rem; }
.gift-box label { display: flex; flex-direction: column; gap: .25rem; font-weight: 700; font-size: .88rem; }
.hint { margin: 0; font-size: .82rem; color: #c62828; }
.hint.err { color: #a82626; }
.summary-row.discount { color: #c62828; font-weight: 700; margin-bottom: .45rem; }
.sm { font-size: .82rem; padding: .45rem .7rem !important; }
.btn {
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: 999px; padding: .65rem 1.15rem; font-weight: 800;
  cursor: pointer; border: 0; text-decoration: none;
}
.btn-primary { background: #1c7282; color: #fff; }
.btn-secondary { background: #132f37; color: #fff; }
.btn-ghost { background: #fff; color: #1c7282; border: 2px solid #1c7282; }
.order-summary {
  background: white; border-radius: 1.25rem;
  border: 1px solid rgba(15, 90, 107, 0.08);
  padding: 1.5rem; position: sticky; top: 5.5rem;
  box-shadow: 0 12px 30px rgba(19,47,55,.04);
}
.order-summary h2 { margin-top: 0; font-size: 1.1rem; }
.summary-vendor { margin-bottom: 1rem; font-size: 0.85rem; }
.summary-vendor strong { display: block; margin-bottom: 0.35rem; color: #1c7282; }
.summary-item { display: flex; justify-content: space-between; color: #4d6b72; margin-bottom: 0.25rem; gap: .5rem; }
.src-tag {
  font-style: normal; font-size: 0.72rem; font-weight: 800;
  background: #132f37; color: #fff; border-radius: 999px;
  padding: 0.05rem 0.4rem; margin-left: 0.35rem;
}
.summary-row { display: flex; justify-content: space-between; font-size: 0.9rem; color: #4d6b72; }
.total-row {
  font-weight: 800; color: #132f37; font-size: 1.1rem;
  border-top: 1px solid #eef4f5; padding-top: 0.75rem; margin-top: 0.5rem;
}
.place-order-btn { width: 100%; margin-top: 1rem; padding: 0.9rem; }
.place-order-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.map-hint { margin-top: 1rem; color: #4d6b72; font-size: .9rem; font-weight: 700; text-align: center; }
.feedback.error { color: #a82626; margin-top: 0.75rem; font-size: 0.85rem; }
@media (max-width: 860px) {
  .checkout-grid { grid-template-columns: 1fr; }
  .order-summary { position: static; }
}
</style>
