<template>
  <div class="auth-page" dir="rtl">
    <StorefrontNav />
    <div class="auth-shell">
      <div class="auth-card">
        <img :src="markUrl" alt="وصلة" class="auth-logo" />
        <h1>نسيت كلمة المرور؟</h1>
        <p class="auth-subtitle">أدخلي بريدك وسنرسل رمز/رابط إعادة التعيين.</p>

        <form @submit.prevent="onSubmit">
          <label>البريد الإلكتروني</label>
          <input v-model="email" type="email" required autocomplete="email" />
          <button type="submit" class="btn btn-primary" :disabled="submitting">
            {{ submitting ? 'جاري الإرسال...' : 'إرسال' }}
          </button>
          <p v-if="msg" class="feedback ok">{{ msg }}</p>
          <p v-if="devCode" class="feedback ok">رمز التطوير: {{ devCode }}</p>
          <p v-if="error" class="feedback error">{{ error }}</p>
        </form>

        <p class="auth-footer">
          <a href="/login">رجوع لتسجيل الدخول</a>
          ·
          <a :href="resetHref">عندي رمز بالفعل</a>
        </p>
      </div>
    </div>
    <StorefrontFooter />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import api from '../../storefront/api';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const markUrl = '/brand/wasla-id-mark.png?v=6';
const email = ref('');
const submitting = ref(false);
const msg = ref('');
const error = ref('');
const devCode = ref('');

const resetHref = computed(() => `/reset-password?email=${encodeURIComponent(email.value || '')}`);

async function onSubmit() {
  submitting.value = true;
  msg.value = '';
  error.value = '';
  devCode.value = '';
  try {
    const { data } = await api.post('/auth/forgot-password', { email: email.value });
    msg.value = data.message || 'تم الإرسال إن كان البريد مسجّلاً.';
    if (data.dev_code) devCode.value = data.dev_code;
  } catch (e) {
    error.value = e?.response?.data?.message || 'تعذّر الإرسال.';
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
.auth-subtitle { color: #4d6b72; margin: 0 0 1.5rem; }
label { display: block; text-align: right; font-weight: 700; margin: .75rem 0 .35rem; }
input {
  width: 100%; box-sizing: border-box; border: 1.5px solid rgba(28,114,130,.22);
  border-radius: .75rem; padding: .75rem .85rem;
}
.btn {
  width: 100%; margin-top: 1.15rem; border: 0; border-radius: 999px;
  padding: .9rem 1.25rem; font-weight: 900; cursor: pointer; background: #006871; color: #fff;
}
.feedback { margin-top: .85rem; font-weight: 700; }
.feedback.ok { color: #1c7282; }
.feedback.error { color: #a82626; }
.auth-footer { margin-top: 1.25rem; color: #4d6b72; }
.auth-footer a { color: #006871; font-weight: 800; }
</style>
