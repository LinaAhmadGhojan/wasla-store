<template>
  <div class="account-page" dir="rtl">
    <StorefrontNav />

    <div class="shell">
      <div v-if="!store.authToken" class="empty">
        <p>سجّلي دخولك لإدارة حسابك.</p>
        <a :href="loginHref" class="btn btn-primary">دخول</a>
      </div>

      <template v-else>
        <h1>حسابي</h1>
        <p class="sub">الملف الشخصي، الأمان، اللغة، العملة، والخصوصية.</p>

        <div class="grid">
          <div class="card">
            <h2>الصورة الشخصية</h2>
            <div class="avatar-row">
              <img v-if="avatarUrl" :src="avatarUrl" alt="" class="avatar" />
              <div v-else class="avatar placeholder">{{ initials }}</div>
              <div>
                <input type="file" accept="image/*" @change="onAvatar" />
                <button type="button" class="btn btn-primary btn-sm" :disabled="uploadingAvatar" @click="uploadAvatar">
                  {{ uploadingAvatar ? 'جاري الرفع…' : 'رفع الصورة' }}
                </button>
              </div>
            </div>
          </div>

          <form class="card" @submit.prevent="saveProfile">
            <h2>البيانات الشخصية</h2>
            <label>الاسم</label>
            <input v-model="form.name" required />
            <label>البريد</label>
            <input v-model="form.email" type="email" required />
            <div class="inline">
              <button type="button" class="linkish" :disabled="otpBusy" @click="sendVerify('email')">تحقق من البريد (OTP)</button>
              <span v-if="user?.email_verified" class="badge ok">موثّق</span>
              <span v-else class="badge">غير موثّق</span>
            </div>
            <label>الهاتف</label>
            <input v-model="form.phone" type="tel" />
            <div class="inline">
              <button type="button" class="linkish" :disabled="otpBusy || !form.phone" @click="sendVerify('phone')">تحقق من الهاتف (OTP)</button>
              <span v-if="user?.phone_verified" class="badge ok">موثّق</span>
              <span v-else class="badge">غير موثّق</span>
            </div>
            <div v-if="verifyOpen" class="otp-box">
              <label>رمز التحقق لـ {{ verifyTarget }}</label>
              <input v-model="verifyCode" maxlength="6" />
              <p v-if="devCode" class="ok">رمز التطوير: {{ devCode }}</p>
              <button type="button" class="btn btn-primary btn-sm" @click="confirmVerify">تأكيد</button>
            </div>
            <button class="btn btn-primary" :disabled="saving">{{ saving ? 'جاري الحفظ…' : 'حفظ البيانات' }}</button>
            <p v-if="msg" class="ok">{{ msg }}</p>
            <p v-if="error" class="err">{{ error }}</p>
          </form>

          <form class="card" @submit.prevent="changePassword">
            <h2>تغيير كلمة المرور</h2>
            <label>كلمة المرور الحالية</label>
            <input v-model="pw.current_password" type="password" required autocomplete="current-password" />
            <label>كلمة المرور الجديدة</label>
            <input v-model="pw.password" type="password" required minlength="8" autocomplete="new-password" />
            <label>تأكيد كلمة المرور</label>
            <input v-model="pw.password_confirmation" type="password" required minlength="8" autocomplete="new-password" />
            <button class="btn btn-primary" :disabled="pwSaving">{{ pwSaving ? '...' : 'تغيير كلمة المرور' }}</button>
            <p v-if="pwMsg" class="ok">{{ pwMsg }}</p>
            <p v-if="pwErr" class="err">{{ pwErr }}</p>
          </form>

          <form class="card" @submit.prevent="savePrefs">
            <h2>اللغة والعملة</h2>
            <label>اللغة</label>
            <select v-model="prefs.locale">
              <option value="ar">العربية</option>
              <option value="en">English</option>
            </select>
            <label>عملة العرض</label>
            <select v-model="prefs.preferred_currency">
              <option value="SYP">ليرة سورية (ل.س)</option>
              <option value="AED">درهم (د.إ)</option>
              <option value="USD">دولار (USD)</option>
            </select>
            <button class="btn btn-primary" :disabled="prefSaving">حفظ التفضيلات</button>
            <p v-if="prefMsg" class="ok">{{ prefMsg }}</p>
          </form>

          <form class="card" @submit.prevent="savePrivacy">
            <h2>إعدادات الخصوصية</h2>
            <label class="check"><input type="checkbox" v-model="privacy.order_notifications" /> إشعارات الطلبات</label>
            <label class="check"><input type="checkbox" v-model="privacy.promo_notifications" /> العروض والتنبيهات</label>
            <label class="check"><input type="checkbox" v-model="privacy.marketing_emails" /> رسائل تسويقية بالبريد</label>
            <label class="check"><input type="checkbox" v-model="privacy.marketing_sms" /> رسائل تسويقية SMS</label>
            <label class="check"><input type="checkbox" v-model="privacy.show_reviews_public" /> إظهار تقييماتي للعامة (مشفّرة)</label>
            <label class="check"><input type="checkbox" v-model="privacy.share_activity" /> مشاركة نشاط التصفح للتوصيات</label>
            <button class="btn btn-primary" :disabled="privSaving">حفظ الخصوصية</button>
            <p v-if="privMsg" class="ok">{{ privMsg }}</p>
          </form>

          <div class="card links">
            <h2>روابط سريعة</h2>
            <a href="/addresses">📍 عناويني</a>
            <a href="/favorites">♡ المفضلة</a>
            <a href="/my-requests">📦 طلباتي</a>
            <a href="/cart">🛒 السلة</a>
            <div class="credit" v-if="creditLabel">
              رصيد المتجر: <strong>{{ creditLabel }}</strong>
            </div>
            <button type="button" class="btn btn-outline" @click="doLogout">تسجيل الخروج</button>
            <p class="hint">حذف الحساب غير متاح — يمكن تعطيل الحساب مؤقتاً عبر دعم وصلة.</p>
          </div>
        </div>
      </template>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../../storefront/api';
import { store, hydrateUser, logout } from '../../storefront/store';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const loginHref = `/login?redirect=${encodeURIComponent('/profile')}`;
const user = ref(null);
const form = ref({ name: '', email: '', phone: '' });
const avatarFile = ref(null);
const avatarUrl = ref('');
const uploadingAvatar = ref(false);
const saving = ref(false);
const msg = ref('');
const error = ref('');

const pw = ref({ current_password: '', password: '', password_confirmation: '' });
const pwSaving = ref(false);
const pwMsg = ref('');
const pwErr = ref('');

const prefs = ref({ locale: 'ar', preferred_currency: 'SYP' });
const prefSaving = ref(false);
const prefMsg = ref('');

const privacy = ref({
  order_notifications: true,
  promo_notifications: true,
  marketing_emails: true,
  marketing_sms: false,
  show_reviews_public: true,
  share_activity: false,
});
const privSaving = ref(false);
const privMsg = ref('');

const otpBusy = ref(false);
const verifyOpen = ref(false);
const verifyChannel = ref('email');
const verifyTarget = ref('');
const verifyCode = ref('');
const devCode = ref('');

const creditLabel = computed(() => {
  const n = Number(user.value?.store_credit_syp || store.currentUser?.store_credit_syp || 0);
  if (!n) return '';
  return `${n.toLocaleString('ar')} ل.س`;
});

const initials = computed(() => (form.value.name || 'و').trim().charAt(0));

function applyUser(data) {
  user.value = data;
  store.currentUser = data;
  localStorage.setItem('wasla_user', JSON.stringify(data));
  form.value.name = data.name || '';
  form.value.email = data.email || '';
  form.value.phone = data.phone || '';
  avatarUrl.value = data.avatar || '';
  prefs.value.locale = data.locale || 'ar';
  prefs.value.preferred_currency = data.preferred_currency || 'SYP';
  privacy.value = { ...privacy.value, ...(data.privacy_settings || {}) };
  try {
    localStorage.setItem('wasla_locale', prefs.value.locale);
    localStorage.setItem('wasla_currency', prefs.value.preferred_currency);
  } catch (_) { /* ignore */ }
}

onMounted(async () => {
  await hydrateUser();
  if (store.currentUser) {
    try {
      const { data } = await api.get('/auth/profile');
      applyUser(data);
    } catch (_) {
      applyUser(store.currentUser);
    }
  }
});

function onAvatar(e) {
  avatarFile.value = e.target.files?.[0] || null;
  if (avatarFile.value) {
    avatarUrl.value = URL.createObjectURL(avatarFile.value);
  }
}

async function uploadAvatar() {
  if (!avatarFile.value) return;
  uploadingAvatar.value = true;
  error.value = '';
  try {
    const fd = new FormData();
    fd.append('avatar', avatarFile.value);
    const { data } = await api.post('/auth/avatar', fd);
    applyUser(data.user);
    msg.value = 'تم تحديث الصورة.';
  } catch (e) {
    error.value = e?.response?.data?.message || 'تعذّر رفع الصورة.';
  } finally {
    uploadingAvatar.value = false;
  }
}

async function saveProfile() {
  saving.value = true;
  msg.value = '';
  error.value = '';
  try {
    const { data } = await api.put('/auth/profile', {
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone || null,
    });
    applyUser(data);
    msg.value = 'تم حفظ البيانات.';
  } catch (e) {
    const errs = e?.response?.data?.errors;
    error.value = errs ? Object.values(errs).flat().join(' ') : (e?.response?.data?.message || 'تعذّر الحفظ.');
  } finally {
    saving.value = false;
  }
}

async function changePassword() {
  pwSaving.value = true;
  pwMsg.value = '';
  pwErr.value = '';
  try {
    const { data } = await api.post('/auth/change-password', pw.value);
    pwMsg.value = data.message || 'تم التغيير.';
    pw.value = { current_password: '', password: '', password_confirmation: '' };
  } catch (e) {
    const errs = e?.response?.data?.errors;
    pwErr.value = errs ? Object.values(errs).flat().join(' ') : (e?.response?.data?.message || 'تعذّر التغيير.');
  } finally {
    pwSaving.value = false;
  }
}

async function savePrefs() {
  prefSaving.value = true;
  prefMsg.value = '';
  try {
    const { data } = await api.put('/auth/preferences', prefs.value);
    applyUser(data);
    prefMsg.value = 'تم حفظ اللغة والعملة.';
  } catch (e) {
    prefMsg.value = e?.response?.data?.message || 'تعذّر الحفظ.';
  } finally {
    prefSaving.value = false;
  }
}

async function savePrivacy() {
  privSaving.value = true;
  privMsg.value = '';
  try {
    const { data } = await api.put('/auth/preferences', { privacy_settings: privacy.value });
    applyUser(data);
    privMsg.value = 'تم حفظ الخصوصية.';
  } catch (e) {
    privMsg.value = e?.response?.data?.message || 'تعذّر الحفظ.';
  } finally {
    privSaving.value = false;
  }
}

async function sendVerify(channel) {
  otpBusy.value = true;
  devCode.value = '';
  error.value = '';
  try {
    const destination = channel === 'email' ? form.value.email : form.value.phone;
    const { data } = await api.post('/auth/otp/send-auth', {
      channel,
      destination,
      purpose: channel === 'email' ? 'verify_email' : 'verify_phone',
    });
    verifyChannel.value = channel;
    verifyTarget.value = destination;
    verifyOpen.value = true;
    if (data.dev_code) devCode.value = data.dev_code;
  } catch (e) {
    error.value = e?.response?.data?.message || Object.values(e?.response?.data?.errors || {}).flat().join(' ') || 'تعذّر إرسال الرمز.';
  } finally {
    otpBusy.value = false;
  }
}

async function confirmVerify() {
  try {
    const { data } = await api.post('/auth/otp/verify-auth', {
      channel: verifyChannel.value,
      destination: verifyTarget.value,
      purpose: verifyChannel.value === 'email' ? 'verify_email' : 'verify_phone',
      code: verifyCode.value,
    });
    if (data.user) applyUser(data.user);
    verifyOpen.value = false;
    verifyCode.value = '';
    msg.value = data.message || 'تم التحقق.';
  } catch (e) {
    error.value = e?.response?.data?.message || Object.values(e?.response?.data?.errors || {}).flat().join(' ') || 'رمز غير صحيح.';
  }
}

async function doLogout() {
  try {
    await api.post('/auth/logout');
  } catch (_) { /* ignore */ }
  logout();
  window.location.href = '/';
}
</script>

<style scoped>
.account-page { min-height: 100vh; background: #f5fbfc; color: #132f37; }
.shell { max-width: 960px; margin: 0 auto; padding: 2rem 1.25rem 3rem; }
h1 { margin: 0 0 .35rem; color: #1c7282; font-size: 1.6rem; }
.sub { margin: 0 0 1.5rem; color: #4d6b72; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.card {
  background: #fff; border: 1px solid rgba(28,114,130,.12);
  border-radius: 1.2rem; padding: 1.35rem;
}
.card h2 { margin: 0 0 1rem; font-size: 1.05rem; color: #0b3d44; }
label { display: block; margin: .7rem 0 .3rem; font-weight: 700; font-size: .88rem; }
label.check { display: flex; align-items: center; gap: .55rem; font-weight: 600; }
input, select {
  width: 100%; border: 1.5px solid rgba(28,114,130,.22); border-radius: .75rem;
  padding: .7rem .85rem; box-sizing: border-box;
}
.btn {
  display: inline-flex; margin-top: 1rem; border: 0; border-radius: 999px;
  padding: .75rem 1.25rem; font-weight: 800; cursor: pointer; text-decoration: none;
}
.btn-sm { padding: .45rem .9rem; font-size: .85rem; margin-top: .5rem; }
.btn-primary { background: #1c7282; color: #fff; }
.btn-outline { background: #fff; color: #1c7282; border: 1.5px solid #1c7282; width: 100%; justify-content: center; }
.avatar-row { display: flex; gap: 1rem; align-items: center; }
.avatar { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; background: #eef8f9; }
.avatar.placeholder {
  display: flex; align-items: center; justify-content: center;
  font-weight: 900; font-size: 1.5rem; color: #006871;
}
.inline { display: flex; align-items: center; gap: .65rem; margin-top: .35rem; flex-wrap: wrap; }
.linkish { border: 0; background: transparent; color: #006871; font-weight: 800; cursor: pointer; padding: 0; }
.badge { font-size: .75rem; background: #eef4f5; color: #4d6b72; border-radius: 999px; padding: .15rem .55rem; }
.badge.ok { background: #e7f6ed; color: #1b7a4e; }
.otp-box { margin-top: .75rem; padding: .85rem; background: #f5fbfc; border-radius: .85rem; }
.links a {
  display: block; padding: .75rem 0; border-bottom: 1px solid rgba(28,114,130,.08);
  color: #0b3d44; font-weight: 700; text-decoration: none;
}
.credit { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed rgba(28,114,130,.2); color: #1c7282; }
.hint { margin-top: .85rem; color: #4d6b72; font-size: .82rem; }
.empty { text-align: center; padding: 3rem 1rem; }
.ok { color: #1c7282; font-weight: 700; }
.err { color: #a82626; font-weight: 700; }
@media (max-width: 820px) { .grid { grid-template-columns: 1fr; } }
</style>
