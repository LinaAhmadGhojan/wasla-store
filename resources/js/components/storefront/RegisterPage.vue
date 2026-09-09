<template>
  <div class="auth-page" dir="rtl">
    <StorefrontNav />

    <div class="auth-shell">
      <div class="auth-card">
        <img :src="markUrl" alt="وصلة" class="auth-logo" />

        <template v-if="step === 'form'">
          <h1>إنشاء حساب</h1>
          <p class="auth-subtitle">انضمّي لوصلة وابدئي التسوق.</p>

          <form @submit.prevent="onSubmit">
            <label>الاسم الكامل</label>
            <input v-model="form.name" type="text" required autocomplete="name" />

            <label>البريد الإلكتروني</label>
            <input v-model="form.email" type="email" required autocomplete="email" />

            <label>الهاتف (اختياري)</label>
            <input v-model="form.phone" type="text" autocomplete="tel" />

            <label>كلمة المرور</label>
            <input v-model="form.password" type="password" required autocomplete="new-password" minlength="8" />

            <label>تأكيد كلمة المرور</label>
            <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" minlength="8" />

            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'جاري الإنشاء...' : 'إنشاء حساب' }}
            </button>

            <p v-if="error" class="feedback error">{{ error }}</p>
          </form>
        </template>

        <template v-else>
          <h1>تأكيد البريد</h1>
          <p class="auth-subtitle">
            أرسلنا رمزاً إلى <strong>{{ pendingEmail }}</strong>. أدخليه لإثبات أن الإيميل حقيقي.
          </p>

          <form @submit.prevent="onVerify">
            <label>رمز التأكيد</label>
            <input
              v-model="otpCode"
              inputmode="numeric"
              maxlength="6"
              required
              placeholder="******"
              autocomplete="one-time-code"
            />
            <p v-if="devCode" class="dev">رمز التطوير: {{ devCode }}</p>

            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'جاري التأكيد...' : 'تأكيد وإنشاء الجلسة' }}
            </button>

            <button type="button" class="btn btn-link" :disabled="submitting || resending" @click="onResend">
              {{ resending ? 'جاري الإرسال...' : 'إعادة إرسال الرمز' }}
            </button>

            <p v-if="info" class="feedback ok">{{ info }}</p>
            <p v-if="error" class="feedback error">{{ error }}</p>
          </form>
        </template>

        <p class="auth-footer">
          عندك حساب؟ <a :href="loginHref">دخول</a>
        </p>
      </div>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '../../storefront/api';
import { login } from '../../storefront/store';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const markUrl = '/brand/wasla-id-mark.png?v=6';
const params = new URLSearchParams(window.location.search);
const redirectTo = params.get('redirect') || '/';
const loginHref = `/login?redirect=${encodeURIComponent(redirectTo)}`;

const step = ref('form');
const form = ref({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
const pendingEmail = ref('');
const otpCode = ref('');
const devCode = ref('');
const submitting = ref(false);
const resending = ref(false);
const error = ref('');
const info = ref('');

async function onSubmit() {
  submitting.value = true;
  error.value = '';
  info.value = '';
  try {
    const { data } = await api.post('/auth/register', form.value);
    pendingEmail.value = data.email || form.value.email;
    if (data.otp?.dev_code) devCode.value = data.otp.dev_code;
    step.value = 'verify';
  } catch (e) {
    error.value = e?.response?.data?.errors
      ? Object.values(e.response.data.errors).flat().join(' ')
      : (e?.response?.data?.message || 'تعذّر إنشاء الحساب.');
  } finally {
    submitting.value = false;
  }
}

async function onVerify() {
  submitting.value = true;
  error.value = '';
  info.value = '';
  try {
    const { data } = await api.post('/auth/register/verify', {
      email: pendingEmail.value,
      code: otpCode.value,
    });
    login(data.token, data.user);
    window.location.href = redirectTo;
  } catch (e) {
    error.value = e?.response?.data?.errors
      ? Object.values(e.response.data.errors).flat().join(' ')
      : (e?.response?.data?.message || 'رمز غير صحيح.');
  } finally {
    submitting.value = false;
  }
}

async function onResend() {
  resending.value = true;
  error.value = '';
  info.value = '';
  try {
    const { data } = await api.post('/auth/register/resend-otp', { email: pendingEmail.value });
    info.value = data.message || 'أرسلنا رمزاً جديداً.';
    if (data.otp?.dev_code) devCode.value = data.otp.dev_code;
  } catch (e) {
    error.value = e?.response?.data?.message || 'تعذّر إعادة الإرسال.';
  } finally {
    resending.value = false;
  }
}
</script>

<style scoped>
.auth-page { color: #132f37; background: #f5fbfc; min-height: 100vh; }
.auth-shell {
  max-width: 460px;
  margin: 0 auto;
  padding: 3rem 1.5rem;
}
.auth-card {
  background: white;
  border-radius: 1.5rem;
  border: 1px solid rgba(0, 104, 113, 0.1);
  padding: 2.25rem;
  text-align: center;
}
.auth-logo {
  width: 96px;
  height: 96px;
  margin: 0 auto 1rem;
  display: block;
  object-fit: contain;
  background: #1c7282;
  border-radius: 18px;
  padding: 10px;
}
.auth-card h1 { margin: 0 0 0.35rem; color: #006871; font-size: 1.55rem; }
.auth-subtitle { margin: 0 0 1.5rem; color: #4d6b72; }
.auth-subtitle strong { color: #006871; }
form { text-align: right; display: flex; flex-direction: column; }
label {
  font-weight: 700;
  font-size: 0.9rem;
  margin-bottom: 0.35rem;
  margin-top: 0.85rem;
  color: #0b3d44;
}
label:first-of-type { margin-top: 0; }
input {
  width: 100%;
  box-sizing: border-box;
  padding: 0.8rem 1rem;
  border-radius: 0.85rem;
  border: 1.5px solid rgba(0, 104, 113, 0.18);
  font-size: 1rem;
  outline: none;
}
input:focus { border-color: #006871; }
.btn-primary, button[type="submit"] {
  margin-top: 1.35rem;
  padding: 0.95rem;
  border: none;
  border-radius: 999px;
  background: #006871;
  color: #fff;
  font-weight: 800;
  cursor: pointer;
}
button[type="submit"]:disabled,
.btn-link:disabled { opacity: .65; }
.btn-link {
  margin-top: 0.75rem;
  background: transparent;
  border: none;
  color: #006871;
  font-weight: 800;
  cursor: pointer;
  padding: 0.5rem;
}
.dev { color: #1c7282; font-weight: 800; margin: 0.5rem 0 0; text-align: center; }
.feedback.error { color: #a82626; margin-top: 0.85rem; font-weight: 600; }
.feedback.ok { color: #006871; margin-top: 0.85rem; font-weight: 600; }
.auth-footer { margin: 1.35rem 0 0; color: #4d6b72; text-align: center; }
.auth-footer a { color: #006871; font-weight: 800; text-decoration: none; }
</style>
