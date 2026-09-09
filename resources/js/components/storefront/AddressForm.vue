<template>
  <form class="addr-form" dir="rtl" @submit.prevent="onSubmit">
    <h2 v-if="title">{{ title }}</h2>

    <div class="label-types">
      <button
        v-for="opt in labelOptions"
        :key="opt.value"
        type="button"
        class="chip"
        :class="{ active: form.label_type === opt.value }"
        @click="pickType(opt)"
      >
        {{ opt.label }}
      </button>
    </div>

    <label>اسم مخصص (اختياري)</label>
    <input v-model="form.label" type="text" :placeholder="typePlaceholder" />

    <div class="row">
      <div>
        <label>اسم المستلم</label>
        <input v-model="form.recipient_name" required autocomplete="name" />
      </div>
      <div>
        <label>هاتف التواصل</label>
        <input v-model="form.phone" required autocomplete="tel" inputmode="tel" />
      </div>
    </div>

    <div class="row">
      <div>
        <label>المدينة</label>
        <input v-model="form.city" required placeholder="دمشق، حلب، اللاذقية…" />
      </div>
      <div>
        <label>المنطقة / الحي</label>
        <input v-model="form.state" placeholder="اختياري" />
      </div>
    </div>

    <label>العنوان التفصيلي</label>
    <textarea v-model="form.street_address" rows="2" required placeholder="الشارع، البناء، الطابق، الشقة…" />

    <label>ملاحظات للمندوب</label>
    <textarea
      v-model="form.courier_notes"
      rows="2"
      placeholder="مثال: الباب الأزرق، اتصل قبل الوصول، لا جرس…"
    />

    <div class="map-block">
      <h3>تحديد الموقع على الخريطة</h3>
      <AddressMapPicker
        ref="mapRef"
        v-model:latitude="form.latitude"
        v-model:longitude="form.longitude"
      />
    </div>

    <label class="check">
      <input type="checkbox" v-model="form.is_default" />
      جعله العنوان الافتراضي
    </label>

    <div class="actions">
      <button type="submit" class="btn primary" :disabled="saving">
        {{ saving ? 'جاري الحفظ…' : submitLabel }}
      </button>
      <button v-if="showCancel" type="button" class="btn ghost" @click="$emit('cancel')">إلغاء</button>
    </div>
    <p v-if="error" class="err">{{ error }}</p>
  </form>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import AddressMapPicker from './AddressMapPicker.vue';

const props = defineProps({
  initial: { type: Object, default: null },
  title: { type: String, default: '' },
  submitLabel: { type: String, default: 'حفظ العنوان' },
  saving: { type: Boolean, default: false },
  error: { type: String, default: '' },
  showCancel: { type: Boolean, default: true },
});

const emit = defineEmits(['submit', 'cancel']);

const labelOptions = [
  { value: 'home', label: 'البيت', icon: '' },
  { value: 'work', label: 'العمل', icon: '' },
  { value: 'other', label: 'عنوان آخر', icon: '' },
];

function blank() {
  return {
    id: null,
    label: 'البيت',
    label_type: 'home',
    recipient_name: '',
    phone: '',
    country: 'سوريا',
    city: '',
    state: '',
    postal_code: '-',
    street_address: '',
    courier_notes: '',
    latitude: null,
    longitude: null,
    is_default: false,
  };
}

const form = reactive(blank());
const mapRef = ref(null);

const typePlaceholder = computed(() => {
  const opt = labelOptions.find((o) => o.value === form.label_type);
  return opt?.label || 'البيت';
});

function applyInitial(src) {
  Object.assign(form, blank(), src || {});
  if (!form.label_type) form.label_type = 'home';
  if (!form.country) form.country = 'سوريا';
  if (!form.postal_code) form.postal_code = '-';
}

function pickType(opt) {
  form.label_type = opt.value;
  if (!form.label || ['البيت', 'العمل', 'عنوان آخر', 'Home', 'Work', 'Other'].includes(form.label)) {
    form.label = opt.label;
  }
}

function onSubmit() {
  if (form.latitude == null || form.longitude == null) {
    return;
  }
  emit('submit', { ...form });
}

watch(
  () => props.initial,
  (v) => applyInitial(v),
  { immediate: true, deep: true }
);

defineExpose({
  form,
  refreshMap: () => mapRef.value?.invalidate?.(),
});
</script>

<style scoped>
.addr-form { text-align: right; }
.addr-form h2 { margin: 0 0 1rem; color: #0b3d44; font-size: 1.2rem; }
.addr-form h3 { margin: 0 0 .65rem; color: #1c7282; font-size: 1rem; }
.label-types { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: .85rem; }
.chip {
  border: 1.5px solid rgba(28,114,130,.22);
  background: #fff;
  border-radius: 999px;
  padding: .45rem .9rem;
  font-weight: 800;
  color: #0b3d44;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: .35rem;
}
.chip.active {
  background: #1c7282;
  border-color: #1c7282;
  color: #fff;
}
.ico { font-size: 1rem; }
label { display: block; margin: .7rem 0 .3rem; font-weight: 700; font-size: .88rem; color: #0b3d44; }
input, textarea {
  width: 100%; box-sizing: border-box;
  border: 1.5px solid rgba(28,114,130,.22);
  border-radius: .75rem;
  padding: .7rem .85rem;
  font: inherit;
}
.row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.map-block {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 1rem;
  background: #f5fbfc;
  border: 1px solid rgba(28,114,130,.12);
}
.check {
  display: flex; align-items: center; gap: .5rem;
  margin-top: .9rem; font-weight: 700; cursor: pointer;
}
.check input { width: auto; }
.actions { display: flex; gap: .55rem; flex-wrap: wrap; margin-top: 1rem; }
.btn {
  border: 0; border-radius: 999px; padding: .7rem 1.2rem;
  font-weight: 800; cursor: pointer;
}
.btn.primary { background: #1c7282; color: #fff; }
.btn.ghost { background: #fff; color: #1c7282; border: 2px solid #1c7282; }
.btn:disabled { opacity: .65; }
.err { color: #a82626; font-weight: 700; margin-top: .75rem; }
@media (max-width: 640px) {
  .row { grid-template-columns: 1fr; }
}
</style>
