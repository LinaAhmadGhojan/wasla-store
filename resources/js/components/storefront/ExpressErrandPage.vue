<template>
  <div class="express-errand-page" dir="rtl">
    <StorefrontNav theme="express" />

    <div class="shell">
      <nav class="crumbs">
        <a href="/express">طلباتي</a>
        <span>/</span>
        <span>مشوار وشحن</span>
      </nav>

      <header class="hero">
        <div class="hero-text">
          <p class="eyebrow">طلباتي · توصيل محلي وبين المحافظات</p>
          <h1>{{ heroTitle }}</h1>
          <p class="lead">{{ heroLead }}</p>
        </div>
        <ol class="steps" aria-label="كيف بيشتغل">
          <li><strong>1</strong><span>{{ stepOne }}</span></li>
          <li><strong>2</strong><span>تأكيد السعر</span></li>
          <li><strong>3</strong><span>تنفيذ وتوصيل</span></li>
        </ol>
      </header>

      <section v-if="done" class="success-card">
        <p class="success-title">تم إرسال الطلب</p>
        <p class="ref">رقم الطلب: <strong>{{ lastReference }}</strong></p>
        <p class="success-hint">{{ successMessage }}</p>
        <div class="success-actions">
          <a href="/express" class="btn ghost">رجوع لطلباتي</a>
          <button type="button" class="btn primary" @click="startAnother">طلب جديد</button>
        </div>
      </section>

      <form v-else class="form-stack" @submit.prevent="submit">
        <section class="card service-card">
          <h2>شو نوع الطلب؟</h2>
          <div class="service-tabs">
            <button
              v-for="m in serviceModes"
              :key="m.key"
              type="button"
              class="svc"
              :class="{ on: form.serviceType === m.key }"
              @click="form.serviceType = m.key"
            >
              <span class="svc-ico" aria-hidden="true">{{ m.emoji }}</span>
              <strong>{{ m.label }}</strong>
              <em>{{ m.hint }}</em>
            </button>
          </div>
        </section>

        <!-- مشوار محلي -->
        <template v-if="form.serviceType === 'local_errand'">
          <section class="card">
            <h2>نوع المشوار</h2>
            <div class="chip-row">
              <button
                v-for="c in categories"
                :key="c.key"
                type="button"
                class="chip"
                :class="{ on: form.category === c.key }"
                @click="form.category = c.key"
              >
                <span class="chip-ico" aria-hidden="true">{{ c.emoji }}</span>
                {{ c.label }}
              </button>
            </div>
          </section>

          <section class="card">
            <div class="card-head">
              <h2>شو بدك؟</h2>
              <button type="button" class="link-btn" @click="addLine">+ سطر</button>
            </div>
            <p class="card-hint">اقتراحات سريعة أو قائمة كاملة</p>
            <div class="quick-row">
              <button v-for="q in quickItems" :key="q" type="button" class="quick" @click="addQuick(q)">{{ q }}</button>
            </div>
            <div class="lines">
              <div v-for="(line, idx) in form.lines" :key="line._id" class="line">
                <input v-model="line.name" type="text" required placeholder="اسم الغرض" class="line-name" />
                <input v-model="line.qty" type="text" placeholder="الكمية" class="line-qty" />
                <button v-if="form.lines.length > 1" type="button" class="line-del" aria-label="حذف" @click="removeLine(idx)">×</button>
              </div>
            </div>
          </section>

          <section class="card">
            <h2>من وين نشتري؟</h2>
            <div class="radio-row">
              <label class="radio">
                <input v-model="form.storePreference" type="radio" value="any" />
                <span>أقرب محل مناسب</span>
              </label>
              <label class="radio">
                <input v-model="form.storePreference" type="radio" value="specific" />
                <span>محل معيّن</span>
              </label>
            </div>
            <input
              v-if="form.storePreference === 'specific'"
              v-model="form.storeName"
              type="text"
              required
              class="full"
              placeholder="اسم المحل أو الحي"
            />
          </section>
        </template>

        <!-- استلام طرد -->
        <template v-else-if="form.serviceType === 'parcel_receive'">
          <section class="card">
            <h2>وين الطرد هلّق؟</h2>
            <p class="card-hint">محافظة المصدر + تفاصيل (عند صديق، محل، مكتب…)</p>
            <label class="field">
              محافظة المصدر
              <select v-model="form.originGovernorate" required class="select">
                <option value="" disabled>اختاري المحافظة</option>
                <option v-for="g in governorates" :key="'o-'+g" :value="g">{{ g }}</option>
              </select>
            </label>
            <textarea
              v-model="form.originDetails"
              rows="3"
              required
              class="full"
              placeholder="مثال: عند أخي بحي الميدان، أو محل معيّن، رقم المرسل 09…"
            />
          </section>

          <section class="card">
            <h2>شو الطرد؟</h2>
            <textarea
              v-model="form.parcelDescription"
              rows="2"
              required
              class="full"
              placeholder="وصف: مغلف، صندوق صغير، مستندات…"
            />
            <label class="field">
              الحجم التقريبي
              <select v-model="form.parcelSize" class="select">
                <option v-for="s in parcelSizes" :key="s.key" :value="s.key">{{ s.label }}</option>
              </select>
            </label>
          </section>
        </template>

        <!-- إرسال طرد -->
        <template v-else>
          <section class="card">
            <h2>من وين نستلم الطرد؟</h2>
            <label class="field">
              محافظة الاستلام
              <select v-model="form.originGovernorate" required class="select">
                <option value="" disabled>اختاري المحافظة</option>
                <option v-for="g in governorates" :key="'p-'+g" :value="g">{{ g }}</option>
              </select>
            </label>
            <textarea
              v-model="form.pickupAddress"
              rows="3"
              required
              class="full"
              placeholder="عنوان كامل للاستلام من عندك"
            />
          </section>

          <section class="card">
            <h2>شو بدك تبعت؟</h2>
            <textarea
              v-model="form.parcelDescription"
              rows="2"
              required
              class="full"
              placeholder="محتوى الطرد (بدون تفاصيل حساسة)"
            />
            <label class="field">
              الحجم التقريبي
              <select v-model="form.parcelSize" class="select">
                <option v-for="s in parcelSizes" :key="s.key" :value="s.key">{{ s.label }}</option>
              </select>
            </label>
          </section>
        </template>

        <section class="card">
          <h2>{{ destinationTitle }}</h2>
          <p v-if="isParcel" class="card-hint">اختاري محافظة الوجهة + عنوان تفصيلي (حي، شارع، معلم)</p>

          <label v-if="isParcel" class="field">
            محافظة {{ form.serviceType === 'parcel_send' ? 'التوصيل' : 'الاستلام عندك' }}
            <select v-model="form.destinationGovernorate" required class="select">
              <option value="" disabled>اختاري المحافظة</option>
              <option v-for="g in governorates" :key="'d-'+g" :value="g">{{ g }}</option>
            </select>
          </label>

          <div v-if="!isParcel && addresses.length" class="addr-pick">
            <label v-for="a in addresses" :key="a.id" class="addr-opt">
              <input v-model="selectedAddressId" type="radio" :value="a.id" />
              <span>
                <strong>{{ a.label || a.label_type_ar }}</strong>
                — {{ formatAddress(a) }}
              </span>
            </label>
            <label class="addr-opt">
              <input v-model="selectedAddressId" type="radio" value="custom" />
              <span>عنوان مختلف</span>
            </label>
          </div>

          <textarea
            v-model="form.deliveryAddress"
            rows="3"
            required
            class="full"
            :placeholder="destinationPlaceholder"
          />

          <label class="field">
            رقم للتواصل
            <input v-model="form.phone" type="tel" required placeholder="09xxxxxxxx" />
          </label>
          <label class="field">
            ملاحظة (اختياري)
            <input v-model="form.customerNote" type="text" placeholder="وقت مناسب، COD…" />
          </label>
        </section>

        <section v-if="isParcel" class="card">
          <h2>شركة الشحن</h2>
          <p class="card-hint">قدموس · المفتي · وصلة (محلي) — السعر يتحدّث فوراً</p>
          <div class="carrier-grid">
            <label
              v-for="c in carriers"
              :key="c.key"
              class="carrier-opt"
              :class="{ off: !c.available, on: form.shippingCarrier === c.key }"
            >
              <input
                v-model="form.shippingCarrier"
                type="radio"
                :value="c.key"
                :disabled="!c.available"
              />
              <span>
                <strong>{{ c.label }}</strong>
                <em v-if="c.badge">{{ c.badge }}</em>
                <em v-if="!c.available" class="soon">قريباً</em>
              </span>
            </label>
          </div>
        </section>

        <section v-if="form.serviceType === 'local_errand'" class="card row-2">
          <label class="field">
            سقف ميزانية (ل.س)
            <input v-model.number="form.budgetSyp" type="number" min="0" step="1000" placeholder="اختياري" />
          </label>
          <div class="field">
            <span class="lbl">الأولوية</span>
            <div class="urgency">
              <button type="button" class="urg" :class="{ on: form.urgency === 'normal' }" @click="form.urgency = 'normal'">عادي</button>
              <button type="button" class="urg urg-fast" :class="{ on: form.urgency === 'urgent' }" @click="form.urgency = 'urgent'">مستعجل</button>
            </div>
          </div>
        </section>

        <section v-else class="card">
          <div class="field">
            <span class="lbl">الأولوية</span>
            <div class="urgency">
              <button type="button" class="urg" :class="{ on: form.urgency === 'normal' }" @click="form.urgency = 'normal'">عادي</button>
              <button type="button" class="urg urg-fast" :class="{ on: form.urgency === 'urgent' }" @click="form.urgency = 'urgent'">مستعجل</button>
            </div>
          </div>
        </section>

        <section v-if="quote" class="card quote-card">
          <h2>تكلفة الخدمة الآن</h2>
          <p v-if="quote.carrier_label" class="carrier-pill">{{ quote.carrier_label }}</p>
          <ul class="quote-lines">
            <li v-for="line in quote.lines" :key="line.key">
              <span>{{ line.label }}</span>
              <strong>{{ line.amount_label }}</strong>
            </li>
          </ul>
          <p class="quote-total">{{ quote.total_label }}</p>
          <p class="quote-disc">{{ quote.disclaimer }}</p>
          <label class="quote-agree">
            <input v-model="quoteConfirmed" type="checkbox" />
            أوافق على المبلغ وأكّد الطلب
          </label>
        </section>
        <p v-else-if="quoteLoading" class="quote-loading">عم نحسب السعر…</p>

        <p v-if="error" class="err">{{ error }}</p>

        <div class="submit-bar">
          <p class="submit-note">{{ submitNote }}</p>
          <button
            type="submit"
            class="btn primary wide"
            :disabled="submitting || !quote || !quoteConfirmed"
          >
            {{ submitting ? 'عم نرسل…' : (quote ? `تأكيد · ${quote.total_label}` : submitLabel) }}
          </button>
        </div>
      </form>

      <section v-if="recent.length && !done" class="recent">
        <h3>طلباتك الأخيرة</h3>
        <ul>
          <li v-for="r in recent" :key="r.id">
            <strong>{{ r.reference }}</strong>
            · {{ r.service_label || 'طلب' }}
            <template v-if="r.origin_governorate && r.destination_governorate">
              · {{ r.origin_governorate }} ← {{ r.destination_governorate }}
            </template>
            · {{ statusLabel(r.status) }}
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { hydrateUser, store } from '../../storefront/store';
import api from '../../storefront/api';
import { rememberChannel } from '../../storefront/channel';
import { SYRIA_GOVERNORATES } from '../../storefront/syriaGovernorates';
import StorefrontNav from './StorefrontNav.vue';

rememberChannel('express');

const governorates = SYRIA_GOVERNORATES;

const serviceModes = [
  { key: 'local_errand', label: 'مشوار', hint: 'شراء من بقالة قريبة', emoji: '🛒' },
  { key: 'parcel_receive', label: 'استلام طرد', hint: 'يجيبلك طرد من محافظة ثانية', emoji: '📦' },
  { key: 'parcel_send', label: 'إرسال طرد', hint: 'من عندك لمحافظة أو عنوان', emoji: '🚚' },
];

const parcelSizes = [
  { key: 'small', label: 'صغير (ظرف / كيس)' },
  { key: 'medium', label: 'متوسط (صندوق)' },
  { key: 'large', label: 'كبير' },
  { key: 'unknown', label: 'مو متأكد' },
];

let lineSeq = 0;
function newLine(name = '', qty = '') {
  return { _id: ++lineSeq, name, qty };
}

const categories = [
  { key: 'grocery', label: 'بقالة', emoji: '🛒' },
  { key: 'pharmacy', label: 'صيدلية', emoji: '💊' },
  { key: 'produce', label: 'خضرة', emoji: '🥬' },
  { key: 'household', label: 'منزل', emoji: '🧴' },
  { key: 'other', label: 'أخرى', emoji: '✨' },
];

const quickItems = ['موز', 'خبز', 'حليب', 'بيض', 'ماء', 'صابون', 'بطاطا'];

const form = reactive({
  serviceType: 'local_errand',
  category: 'grocery',
  lines: [newLine()],
  storePreference: 'any',
  storeName: '',
  originGovernorate: '',
  originDetails: '',
  destinationGovernorate: '',
  pickupAddress: '',
  parcelDescription: '',
  parcelSize: 'unknown',
  deliveryAddress: '',
  phone: '',
  customerNote: '',
  budgetSyp: null,
  urgency: 'normal',
  shippingCarrier: 'qadmous',
});

const isParcel = computed(() => form.serviceType !== 'local_errand');

const heroTitle = computed(() => {
  if (form.serviceType === 'parcel_receive') return 'جيبلي طرد من محافظة ثانية';
  if (form.serviceType === 'parcel_send') return 'ابعت طرد لمحافظة أو عنوان';
  return 'مشوار — جيبلي من البقالة والصيدلية';
});

const heroLead = computed(() => {
  if (form.serviceType === 'parcel_receive') {
    return 'الطرد موجود عند شخص أو محل بمحافظة معيّنة — وصلة بتنسّق الشحن ليوصل لعنوانك (أي محافظة). السعر حسب المسافة والحجم.';
  }
  if (form.serviceType === 'parcel_send') {
    return 'منندوب يستلم من عندك ويوصّل للمحافظة والعنوان يلي تحدّديه. مناسب للطرود الشخصية والهدايا.';
  }
  return 'حدّدي الأغراض — السعر يظهر فوراً (أجور الخدمة فقط، مو فاتورة البقالة).';
});

const stepOne = computed(() => (isParcel.value ? 'من → إلى + وصف' : 'قائمة + عنوان'));

const destinationTitle = computed(() => {
  if (form.serviceType === 'parcel_receive') return 'يوصلني وين؟';
  if (form.serviceType === 'parcel_send') return 'يوصل وين؟';
  return 'التوصيل';
});

const destinationPlaceholder = computed(() => {
  if (form.serviceType === 'parcel_receive') return 'عنوانك الكامل في محافظة الاستلام';
  if (form.serviceType === 'parcel_send') return 'اسم المستلم، الحي، الشارع، هاتف المستلم…';
  return 'الحي، الشارع، معلم، طابق…';
});

const submitNote = computed(() => 'السعر أعلاه لخدمة وصلة — أكّدي بالcheckbox ثم اضغطي تأكيد.');

const submitLabel = computed(() => {
  if (form.serviceType === 'parcel_receive') return 'طلب استلام طرد';
  if (form.serviceType === 'parcel_send') return 'طلب إرسال طرد';
  return 'إرسال طلب المشوار';
});

const addresses = ref([]);
const selectedAddressId = ref('custom');
const submitting = ref(false);
const error = ref('');
const done = ref(false);
const lastReference = ref('');
const successMessage = ref('');
const recent = ref([]);
const carriers = ref([]);
const quote = ref(null);
const quoteLoading = ref(false);
const quoteConfirmed = ref(false);
let quoteTimer = null;

function quotePayload() {
  const items = form.lines.map((l) => l.name.trim()).filter(Boolean);
  return {
    service_type: form.serviceType,
    category: form.category,
    items_count: Math.max(1, items.length || 1),
    origin_governorate: form.originGovernorate || null,
    destination_governorate: form.destinationGovernorate || null,
    parcel_size: form.parcelSize,
    shipping_carrier: isParcel.value ? form.shippingCarrier : null,
    urgency: form.urgency,
  };
}

function canFetchQuote() {
  if (form.serviceType === 'local_errand') {
    return form.lines.some((l) => l.name.trim());
  }
  if (form.serviceType === 'parcel_receive') {
    return form.originGovernorate && form.destinationGovernorate;
  }
  return form.originGovernorate && form.destinationGovernorate;
}

async function fetchQuote() {
  if (!canFetchQuote()) {
    quote.value = null;
    quoteConfirmed.value = false;
    return;
  }
  quoteLoading.value = true;
  try {
    const { data } = await api.post('/v1/express/errands/quote', quotePayload());
    quote.value = data;
    quoteConfirmed.value = false;
  } catch {
    quote.value = null;
  } finally {
    quoteLoading.value = false;
  }
}

function scheduleQuote() {
  if (quoteTimer) clearTimeout(quoteTimer);
  quoteTimer = setTimeout(fetchQuote, 350);
}

watch(
  () => [
    form.serviceType,
    form.category,
    form.lines.length,
    ...form.lines.map((l) => l.name),
    form.originGovernorate,
    form.destinationGovernorate,
    form.parcelSize,
    form.shippingCarrier,
    form.urgency,
  ],
  scheduleQuote,
);

function addLine() {
  form.lines.push(newLine());
}
function removeLine(idx) {
  form.lines.splice(idx, 1);
}
function addQuick(name) {
  const empty = form.lines.find((l) => !l.name.trim());
  if (empty) empty.name = name;
  else form.lines.push(newLine(name, '1'));
}

function formatAddress(a) {
  return [a.city, a.state, a.street_address].filter(Boolean).join(' · ');
}

watch(selectedAddressId, (id) => {
  if (id === 'custom' || isParcel.value) return;
  const a = addresses.value.find((x) => x.id === id);
  if (a) {
    form.deliveryAddress = formatAddress(a);
    if (a.phone && !form.phone) form.phone = a.phone;
    if (a.city && !form.destinationGovernorate) form.destinationGovernorate = a.city;
  }
});

const statusLabels = {
  pending: 'بانتظار التأكيد',
  accepted: 'مقبول',
  shopping: 'قيد التنفيذ',
  delivered: 'تم التوصيل',
  cancelled: 'ملغى',
};

function statusLabel(s) {
  return statusLabels[s] || s;
}

function resetForm() {
  form.serviceType = 'local_errand';
  form.category = 'grocery';
  form.lines = [newLine()];
  form.storePreference = 'any';
  form.storeName = '';
  form.originGovernorate = '';
  form.originDetails = '';
  form.destinationGovernorate = '';
  form.pickupAddress = '';
  form.parcelDescription = '';
  form.parcelSize = 'unknown';
  form.deliveryAddress = '';
  form.customerNote = '';
  form.budgetSyp = null;
  form.urgency = 'normal';
  error.value = '';
}

function startAnother() {
  done.value = false;
  lastReference.value = '';
  resetForm();
}

async function loadAddresses() {
  if (!store.authToken) return;
  try {
    await hydrateUser();
    const { data } = await api.get('/addresses');
    addresses.value = data;
    const def = data.find((a) => a.is_default) || data[0];
    if (def && form.serviceType === 'local_errand') {
      selectedAddressId.value = def.id;
      form.deliveryAddress = formatAddress(def);
      if (def.phone) form.phone = def.phone;
    }
    if (store.currentUser?.phone && !form.phone) {
      form.phone = store.currentUser.phone;
    }
  } catch {
    /* optional */
  }
}

async function loadRecent() {
  if (!store.authToken) return;
  try {
    const { data } = await api.get('/v1/express/errands/mine');
    recent.value = data.data || [];
  } catch {
    recent.value = [];
  }
}

async function submit() {
  error.value = '';
  if (!quote.value || !quoteConfirmed.value) {
    error.value = 'لازم يظهر السعر وتوافقي عليه قبل الإرسال.';
    return;
  }
  submitting.value = true;
  try {
    const payload = {
      service_type: form.serviceType,
      contact_phone: form.phone.trim(),
      customer_note: form.customerNote.trim() || null,
      urgency: form.urgency,
      shipping_carrier: isParcel.value ? form.shippingCarrier : null,
      quote_confirmed: true,
    };

    if (form.serviceType === 'local_errand') {
      const items = form.lines
        .map((l) => ({ name: l.name.trim(), qty: (l.qty || '1').trim() }))
        .filter((l) => l.name);
      if (!items.length) {
        error.value = 'أضيفي على الأقل غرض واحد.';
        submitting.value = false;
        return;
      }
      Object.assign(payload, {
        category: form.category,
        items,
        store_preference: form.storePreference,
        store_name: form.storePreference === 'specific' ? form.storeName.trim() : null,
        delivery_address: form.deliveryAddress.trim(),
        budget_syp: form.budgetSyp || null,
      });
    } else if (form.serviceType === 'parcel_receive') {
      Object.assign(payload, {
        origin_governorate: form.originGovernorate,
        origin_details: form.originDetails.trim(),
        destination_governorate: form.destinationGovernorate,
        delivery_address: `[${form.destinationGovernorate}] ${form.deliveryAddress.trim()}`,
        parcel_description: form.parcelDescription.trim(),
        parcel_size: form.parcelSize,
      });
    } else {
      Object.assign(payload, {
        origin_governorate: form.originGovernorate,
        origin_details: form.pickupAddress.trim(),
        destination_governorate: form.destinationGovernorate,
        delivery_address: `[${form.destinationGovernorate}] ${form.deliveryAddress.trim()}`,
        parcel_description: form.parcelDescription.trim(),
        parcel_size: form.parcelSize,
      });
    }

    const { data } = await api.post('/v1/express/errands', payload);
    lastReference.value = data.reference;
    successMessage.value = data.message || 'تم استلام طلبك.';
    done.value = true;
    await loadRecent();
  } catch (e) {
    const errs = e?.response?.data?.errors;
    error.value = errs
      ? Object.values(errs).flat().join(' ')
      : (e?.response?.data?.message || 'ما قدرنا نرسل الطلب — جرّبي بعد شوي.');
  } finally {
    submitting.value = false;
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/v1/express/errands/pricing-config');
    carriers.value = data.carriers || [];
    const first = carriers.value.find((c) => c.available);
    if (first) form.shippingCarrier = first.key;
  } catch {
    carriers.value = [];
  }
  await loadAddresses();
  await loadRecent();
});
</script>

<style scoped>
.express-errand-page {
  min-height: 100vh;
  color: #3d2817;
  background: linear-gradient(180deg, #f7efe4 0%, #f3e8d8 40%, #efe4d6 100%);
}
.shell {
  max-width: 720px;
  margin: 0 auto;
  padding: 0.75rem 0.85rem 5.75rem;
}
.crumbs {
  font-size: 0.78rem;
  font-weight: 700;
  color: #9a7a5c;
  margin-bottom: 0.65rem;
}
.crumbs a {
  color: #8a4b12;
  text-decoration: none;
}
.hero {
  background: #fff;
  border-radius: 1.1rem;
  padding: 1rem 1rem 0.85rem;
  border: 1px solid rgba(138, 75, 18, 0.12);
  box-shadow: 0 8px 24px rgba(92, 50, 16, 0.08);
  margin-bottom: 0.85rem;
}
.eyebrow {
  margin: 0;
  font-size: 0.72rem;
  font-weight: 800;
  color: #8a4b12;
}
.hero h1 {
  margin: 0.25rem 0 0.35rem;
  font-size: 1.2rem;
  font-weight: 900;
  color: #5c3210;
  line-height: 1.35;
}
.lead {
  margin: 0;
  font-size: 0.82rem;
  line-height: 1.55;
  color: #6b4a2e;
}
.steps {
  list-style: none;
  margin: 0.85rem 0 0;
  padding: 0.65rem 0 0;
  border-top: 1px dashed rgba(138, 75, 18, 0.2);
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.35rem;
}
.steps li {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
  font-size: 0.65rem;
  font-weight: 700;
  color: #9a7a5c;
  text-align: center;
}
.steps strong {
  width: 1.65rem;
  height: 1.65rem;
  border-radius: 999px;
  background: #8a4b12;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}
.service-tabs {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.45rem;
}
.svc {
  display: grid;
  grid-template-columns: auto 1fr;
  grid-template-rows: auto auto;
  gap: 0.1rem 0.55rem;
  text-align: start;
  padding: 0.65rem 0.75rem;
  border-radius: 0.85rem;
  border: 1.5px solid rgba(138, 75, 18, 0.18);
  background: #faf6f0;
  cursor: pointer;
  color: #5c3210;
}
.svc.on {
  border-color: #8a4b12;
  background: #fff8ef;
  box-shadow: 0 4px 12px rgba(138, 75, 18, 0.12);
}
.svc-ico {
  grid-row: 1 / span 2;
  font-size: 1.35rem;
  align-self: center;
}
.svc strong {
  font-size: 0.88rem;
}
.svc em {
  grid-column: 2;
  font-style: normal;
  font-size: 0.72rem;
  color: #9a7a5c;
  font-weight: 600;
}
.card {
  background: #fff;
  border-radius: 1rem;
  padding: 0.9rem 0.95rem;
  margin-bottom: 0.65rem;
  border: 1px solid rgba(138, 75, 18, 0.1);
  box-shadow: 0 4px 14px rgba(92, 50, 16, 0.05);
}
.card h2 {
  margin: 0 0 0.5rem;
  font-size: 0.95rem;
  font-weight: 900;
  color: #5c3210;
}
.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.card-hint {
  margin: 0 0 0.55rem;
  font-size: 0.75rem;
  color: #9a7a5c;
  font-weight: 600;
}
.link-btn {
  border: 0;
  background: none;
  color: #8a4b12;
  font-weight: 800;
  font-size: 0.8rem;
  cursor: pointer;
}
.chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}
.chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1.5px solid rgba(138, 75, 18, 0.22);
  background: #faf6f0;
  border-radius: 999px;
  padding: 0.4rem 0.7rem;
  font-size: 0.78rem;
  font-weight: 800;
  color: #5c3210;
  cursor: pointer;
}
.chip.on {
  background: #8a4b12;
  border-color: #8a4b12;
  color: #fff;
}
.quick-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-bottom: 0.65rem;
}
.quick {
  border: 1px solid rgba(138, 75, 18, 0.18);
  background: #fff8ef;
  border-radius: 999px;
  padding: 0.28rem 0.55rem;
  font-size: 0.72rem;
  font-weight: 800;
  cursor: pointer;
}
.lines {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}
.line {
  display: grid;
  grid-template-columns: 1fr 5.5rem auto;
  gap: 0.35rem;
}
.line-name,
.line-qty,
.full,
.field input,
.select {
  font: inherit;
  border: 1.5px solid rgba(138, 75, 18, 0.2);
  border-radius: 0.65rem;
  padding: 0.55rem 0.65rem;
  width: 100%;
  box-sizing: border-box;
}
.line-del {
  width: 2rem;
  height: 2rem;
  border: 0;
  border-radius: 999px;
  background: #f5e8dc;
  color: #8a4b12;
  font-size: 1.1rem;
  cursor: pointer;
}
.radio-row {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  margin-bottom: 0.55rem;
}
.radio {
  display: flex;
  gap: 0.45rem;
  font-size: 0.82rem;
  font-weight: 700;
}
.full {
  margin-bottom: 0.55rem;
  resize: vertical;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  font-size: 0.78rem;
  font-weight: 800;
  color: #5c3210;
  margin-top: 0.35rem;
}
.addr-pick {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  margin-bottom: 0.55rem;
}
.addr-opt {
  display: flex;
  gap: 0.45rem;
  font-size: 0.78rem;
  font-weight: 700;
  color: #6b4a2e;
}
.row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.65rem;
}
.lbl {
  font-size: 0.78rem;
  font-weight: 800;
}
.urgency {
  display: flex;
  gap: 0.35rem;
  margin-top: 0.35rem;
}
.urg {
  flex: 1;
  border: 1.5px solid rgba(138, 75, 18, 0.22);
  background: #faf6f0;
  border-radius: 0.65rem;
  padding: 0.5rem;
  font-weight: 800;
  font-size: 0.78rem;
  cursor: pointer;
}
.urg.on {
  background: #8a4b12;
  color: #fff;
}
.err {
  color: #b42318;
  font-weight: 700;
  font-size: 0.82rem;
}
.submit-bar {
  position: sticky;
  bottom: calc(4.25rem + env(safe-area-inset-bottom, 0px));
  background: linear-gradient(180deg, transparent, #efe4d6 28%);
  padding: 0.75rem 0 0.25rem;
  z-index: 2;
}
.submit-note {
  margin: 0 0 0.45rem;
  font-size: 0.72rem;
  font-weight: 700;
  color: #9a7a5c;
  text-align: center;
}
.btn {
  border: 0;
  border-radius: 999px;
  padding: 0.75rem 1.2rem;
  font-weight: 900;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.btn.primary {
  background: #8a4b12;
  color: #fff;
}
.btn.ghost {
  background: #fff;
  color: #8a4b12;
  border: 1.5px solid rgba(138, 75, 18, 0.25);
}
.btn.wide {
  width: 100%;
}
.btn:disabled {
  opacity: 0.65;
}
.success-card {
  background: #fff;
  border-radius: 1rem;
  padding: 1.25rem 1rem;
  text-align: center;
  border: 1px solid rgba(46, 107, 52, 0.2);
}
.success-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 900;
  color: #2e6b34;
}
.ref {
  margin: 0.5rem 0;
}
.success-hint {
  margin: 0 0 1rem;
  font-size: 0.82rem;
  line-height: 1.5;
}
.success-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  justify-content: center;
}
.recent {
  margin-top: 1rem;
  font-size: 0.78rem;
}
.recent ul {
  margin: 0;
  padding: 0;
  list-style: none;
}
.recent li {
  padding: 0.35rem 0;
  border-bottom: 1px solid rgba(138, 75, 18, 0.1);
}
.quote-card {
  border-color: rgba(138, 75, 18, 0.25);
  background: linear-gradient(180deg, #fff 0%, #fff9f0 100%);
}
.quote-card h2 {
  margin-bottom: 0.35rem;
}
.carrier-pill {
  display: inline-block;
  font-size: 0.72rem;
  font-weight: 800;
  color: #8a4b12;
  margin-bottom: 0.5rem;
}
.quote-lines {
  list-style: none;
  margin: 0 0 0.5rem;
  padding: 0;
}
.quote-lines li {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
  font-size: 0.8rem;
  padding: 0.35rem 0;
  border-bottom: 1px dashed rgba(138, 75, 18, 0.15);
}
.quote-total {
  margin: 0.5rem 0 0.35rem;
  font-size: 1.35rem;
  font-weight: 900;
  color: #5c3210;
}
.quote-disc {
  margin: 0;
  font-size: 0.72rem;
  color: #9a7a5c;
  line-height: 1.45;
}
.quote-agree {
  display: flex;
  align-items: flex-start;
  gap: 0.45rem;
  margin-top: 0.75rem;
  font-size: 0.82rem;
  font-weight: 800;
  color: #5c3210;
}
.quote-loading {
  text-align: center;
  font-size: 0.82rem;
  font-weight: 700;
  color: #8a4b12;
}
.carrier-grid {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.carrier-opt {
  display: flex;
  gap: 0.5rem;
  align-items: flex-start;
  padding: 0.55rem 0.65rem;
  border-radius: 0.75rem;
  border: 1.5px solid rgba(138, 75, 18, 0.18);
  cursor: pointer;
}
.carrier-opt.on {
  border-color: #8a4b12;
  background: #fff8ef;
}
.carrier-opt.off {
  opacity: 0.55;
  cursor: not-allowed;
}
.carrier-opt strong {
  display: block;
  font-size: 0.82rem;
}
.carrier-opt em {
  font-style: normal;
  font-size: 0.68rem;
  color: #9a7a5c;
  margin-inline-start: 0.35rem;
}
.carrier-opt em.soon {
  color: #b45309;
}
@media (min-width: 560px) {
  .service-tabs {
    grid-template-columns: repeat(3, 1fr);
  }
  .svc {
    grid-template-columns: 1fr;
    grid-template-rows: auto auto auto;
    text-align: center;
  }
  .svc-ico {
    grid-row: auto;
    justify-self: center;
  }
  .svc em {
    grid-column: 1;
  }
}
@media (max-width: 520px) {
  .row-2 {
    grid-template-columns: 1fr;
  }
  .line {
    grid-template-columns: 1fr 4.5rem auto;
  }
}
</style>
