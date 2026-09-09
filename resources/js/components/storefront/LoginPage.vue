<template>
  <div class="auth-page" dir="rtl">
    <StorefrontNav />

    <div class="auth-shell">
      <div class="auth-card">
        <img :src="markUrl" alt="وصلة" class="auth-logo" />
        <h1>مرحباً بعودتك</h1>
        <p class="auth-subtitle">سجّلي الدخول لمتابعة التسوق والطلبات.</p>

        <div class="tabs">
          <button type="button" :class="{ active: mode === 'password' }" @click="mode = 'password'">كلمة المرور</button>
          <button type="button" :class="{ active: mode === 'otp' }" @click="mode = 'otp'">رمز OTP</button>
        </div>

        <form v-if="mode === 'password'" @submit.prevent="onPasswordLogin">
          <label>البريد الإلكتروني</label>
          <input v-model="form.email" type="email" required autocomplete="email" />
          <label>كلمة المرور</label>
          <input v-model="form.password" type="password" required autocomplete="current-password" />
          <p class="forgot"><a href="/forgot-password">نسيت كلمة المرور؟</a></p>
          <button type="submit" class="btn btn-primary" :disabled="submitting">
            {{ submitting ? 'جاري الدخول...' : 'دخول' }}
          </button>
        </form>

        <form v-else @submit.prevent="otpSent ? onOtpVerify() : onOtpSend()">
          <label>الدخول عبر</label>
          <div class="tabs mini">
            <button type="button" :class="{ active: otpChannel === 'email' }" @click="otpChannel = 'email'">البريد</button>
            <button type="button" :class="{ active: otpChannel === 'phone' }" @click="otpChannel = 'phone'">الهاتف</button>
          </div>
          <label>{{ otpChannel === 'email' ? 'البريد' : 'رقم الهاتف' }}</label>
          <input
            v-model="otpDest"
            :type="otpChannel === 'email' ? 'email' : 'tel'"
            required
            :placeholder="otpChannel === 'email' ? 'you@email.com' : '09xxxxxxxx'"
          />
          <template v-if="otpSent">
            <label>رمز التحقق</label>
            <input v-model="otpCode" inputmode="numeric" maxlength="6" required placeholder="******" />
            <p v-if="devCode" class="dev">رمز التطوير: {{ devCode }}</p>
          </template>
          <button type="submit" class="btn btn-primary" :disabled="submitting">
            {{ submitting ? '...' : (otpSent ? 'تأكيد الدخول' : 'إرسال الرمز') }}
          </button>
        </form>

        <form v-if="needsEmailVerify" @submit.prevent="onVerifyEmail" class="verify-box">
          <p class="verify-msg">لازم تأكيدي البريد أولاً. أدخل الرمز المرسل إلى {{ pendingEmail }}.</p>
          <label>رمز التأكيد</label>
          <input v-model="verifyCode" inputmode="numeric" maxlength="6" required placeholder="******" />
          <p v-if="devCode" class="dev">رمز التطوير: {{ devCode }}</p>
          <button type="submit" class="btn btn-primary" :disabled="submitting">تأكيد البريد والدخول</button>
          <button type="button" class="btn btn-link" :disabled="submitting" @click="resendVerify">إعادة إرسال</button>
        </form>

        <p v-if="error" class="feedback error">{{ error }}</p>

        <p class="auth-footer">
          ما عندك حساب؟ <a :href="registerHref">إنشاء حساب</a>
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
const registerHref = `/register?redirect=${encodeURIComponent(redirectTo)}`;

const mode = ref('password');
const form = ref({ email: '', password: '' });
const otpChannel = ref('email');
const otpDest = ref('');
const otpCode = ref('');
const otpSent = ref(false);
const needsEmailVerify = ref(false);
const pendingEmail = ref('');
const verifyCode = ref('');
const devCode = ref('');
const submitting = ref(false);
const error = ref('');

function finishAuth(data) {
  login(data.token, data.user);
  window.location.href = redirectTo;
}

async function onPasswordLogin() {
  submitting.value = true;
  error.value = '';
  needsEmailVerify.value = false;
  try {
    const { data } = await api.post('/auth/login', form.value);
    finishAuth(data);
  } catch (e) {
    const payload = e?.response?.data;
    if (payload?.code === 'email_not_verified') {
      needsEmailVerify.value = true;
      pendingEmail.value = payload.email || form.value.email;
      if (payload.otp?.dev_code) devCode.value = payload.otp.dev_code;
      error.value = payload.message || 'يجب تأكيد البريد أولاً.';
    } else {
      error.value = payload?.message || 'بيانات الدخول غير صحيحة.';
    }
  } finally {
    submitting.value = false;
  }
}

async function onVerifyEmail() {
  submitting.value = true;
  error.value = '';
  try {
    const { data } = await api.post('/auth/register/verify', {
      email: pendingEmail.value,
      code: verifyCode.value,
    });
    finishAuth(data);
  } catch (e) {
    error.value = e?.response?.data?.errors
      ? Object.values(e.response.data.errors).flat().join(' ')
      : (e?.response?.data?.message || 'رمز غير صحيح.');
  } finally {
    submitting.value = false;
  }
}

async function resendVerify() {
  submitting.value = true;
  error.value = '';
  try {
    const { data } = await api.post('/auth/register/resend-otp', { email: pendingEmail.value });
    if (data.otp?.dev_code) devCode.value = data.otp.dev_code;
    error.value = '';
  } catch (e) {
    error.value = e?.response?.data?.message || 'تعذّر إعادة الإرسال.';
  } finally {
    submitting.value = false;
  }
}

async function onOtpSend() {
  submitting.value = true;
  error.value = '';
  devCode.value = '';
  try {
    const { data } = await api.post('/auth/otp/send', {
      channel: otpChannel.value,
      destination: otpDest.value,
      purpose: 'login',
    });
    otpSent.value = true;
    if (data.dev_code) devCode.value = data.dev_code;
  } catch (e) {
    error.value = e?.response?.data?.message || Object.values(e?.response?.data?.errors || {}).flat().join(' ') || 'تعذّر إرسال الرمز.';
  } finally {
    submitting.value = false;
  }
}

async function onOtpVerify() {
  submitting.value = true;
  error.value = '';
  try {
    const { data } = await api.post('/auth/otp/verify', {
      channel: otpChannel.value,
      destination: otpDest.value,
      purpose: 'login',
      code: otpCode.value,
    });
    finishAuth(data);
  } catch (e) {
    error.value = e?.response?.data?.message || Object.values(e?.response?.data?.errors || {}).flat().join(' ') || 'رمز غير صحيح.';
  } finally {
    submitting.value = false;
  }
}

</script>

<style scoped>
.auth-page { color: #132f37; background: #f5fbfc; min-height: 100vh; }
.auth-shell { max-width: 460px; margin: 0 auto; padding: 3rem 1.5rem; }
.auth-card {
  background: white; border-radius: 1.5rem; border: 1px solid rgba(0, 104, 113, 0.1);
  padding: 2.25rem; text-align: center;
}
.auth-logo {
  width: 72px; height: 72px; object-fit: contain; background: #1c7282;
  border-radius: 16px; padding: 8px; margin: 0 auto 1rem; display: block;
}
h1 { margin: 0 0 .5rem; color: #006871; }
.auth-subtitle { color: #4d6b72; margin: 0 0 1.25rem; }
.tabs { display: flex; gap: .4rem; margin-bottom: 1rem; justify-content: center; }
.tabs button {
  border: 1px solid rgba(0,104,113,.2); background: #fff; color: #006871;
  border-radius: 999px; padding: .4rem .9rem; font-weight: 800; cursor: pointer;
}
.tabs button.active { background: #006871; color: #fff; }
.tabs.mini { margin: .35rem 0 .75rem; }
label { display: block; text-align: right; font-weight: 700; margin: .75rem 0 .35rem; }
input {
  width: 100%; box-sizing: border-box; border: 1.5px solid rgba(28,114,130,.22);
  border-radius: .75rem; padding: .75rem .85rem;
}
.forgot { text-align: left; margin: .45rem 0 0; }
.forgot a { color: #006871; font-weight: 700; font-size: .88rem; }
.btn {
  width: 100%; margin-top: 1rem; border: 0; border-radius: 999px;
  padding: .9rem 1.25rem; font-weight: 900; cursor: pointer;
}
.btn-primary { background: #006871; color: #fff; }
.btn-link {
  margin-top: .55rem; background: transparent; border: 0; color: #006871;
  font-weight: 800; cursor: pointer; width: 100%; padding: .5rem;
}
.verify-box { margin-top: 1rem; text-align: right; }
.verify-msg { color: #4d6b72; margin: 0 0 .5rem; text-align: center; }
.dev { color: #1c7282; font-weight: 800; margin: .5rem 0 0; }
.feedback.error { color: #a82626; font-weight: 700; margin-top: .85rem; }
.auth-footer { margin-top: 1.25rem; color: #4d6b72; }
.auth-footer a { color: #006871; font-weight: 800; }
</style>
