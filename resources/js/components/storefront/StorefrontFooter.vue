<template>
  <footer class="storefront-footer" :class="{ 'theme-express': theme === 'express', 'theme-home': theme === 'home' }" dir="rtl">
    <div class="footer-inner">
      <div class="footer-brand">
        <img :src="markUrl" alt="وصلة" class="footer-logo" />
        <div>
          <strong>وصلة — WASLA</strong>
          <p>متاجر كثيرة… وصلة واحدة</p>
        </div>
      </div>
      <div class="footer-links">
        <a href="/">الرئيسية</a>
        <a href="/shop">المتجر</a>
        <a href="/express">طلباتي </a>
        <a href="/browse">تسوق عالمي</a>
        <a href="/cart">السلة</a>
      </div>
      <p class="footer-copy">&copy; {{ year }} وصلة. جميع الحقوق محفوظة.</p>
    </div>
  </footer>
</template>

<script setup>
defineProps({
  theme: {
    type: String,
    default: 'store',
    validator: (v) => ['store', 'express', 'home'].includes(v),
  },
});

const markUrl = '/brand/wasla-id-mark-white.png?v=6';
const year = new Date().getFullYear();
</script>

<style scoped>
.storefront-footer {
  margin-top: 1.5rem;
  background: #1c7282;
  color: #d7f0f3;
  border-top: 3px solid #0f4f5a;
}
.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0.8rem 1.5rem 0.9rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.footer-brand {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}
.footer-logo {
  width: 40px;
  height: 40px;
  object-fit: contain;
  background: transparent;
  padding: 0;
}
.footer-brand p {
  margin: 0.1rem 0 0;
  color: #a9d7de;
  font-size: 0.78rem;
}
.footer-links {
  display: flex;
  gap: 1rem 1.2rem;
  flex-wrap: wrap;
}
.footer-links a {
  color: #d7f0f3;
  text-decoration: none;
  font-weight: 700;
}
.footer-links a:hover { color: #ffffff; }
.footer-copy {
  margin: 0;
  color: #a9d7de;
  font-size: 0.8rem;
  width: 100%;
}

.storefront-footer.theme-express {
  background: #8a4b12;
  color: #fff4e4;
  border-top-color: #5c3210;
}
.storefront-footer.theme-home {
  background: #087b8d;
  color: #e9fbfd;
  border-top-color: #075d6b;
}
.theme-home .footer-brand p,
.theme-home .footer-copy { color: #b8e4e8; }
.theme-home .footer-links a { color: #e9fbfd; }
.theme-home .footer-links a:hover { color: #ffffff; }
.theme-express .footer-brand p,
.theme-express .footer-copy {
  color: #e8c9a0;
}
.theme-express .footer-links a {
  color: #fff4e4;
}
.theme-express .footer-links a:hover {
  color: #ffffff;
}

@media (max-width: 700px) {
  .footer-inner {
    padding: 0.8rem 1rem 1rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.8rem;
  }
  .footer-links {
    gap: 0.7rem 1rem;
  }
}
</style>
