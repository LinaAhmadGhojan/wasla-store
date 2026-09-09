<template>
  <footer class="storefront-footer" dir="rtl">
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
        <a href="/browse">تسوق عالمي</a>
        <a href="/cart">السلة</a>
      </div>
      <p class="footer-copy">&copy; {{ year }} وصلة. جميع الحقوق محفوظة.</p>
    </div>
  </footer>
</template>

<script setup>
const markUrl = '/brand/wasla-id-mark.png?v=6';
const year = new Date().getFullYear();
</script>

<style scoped>
.storefront-footer {
  margin-top: 3rem;
  background: #1c7282;
  color: #d7f0f3;
  border-top: 3px solid #0f4f5a;
}
.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2.5rem 1.5rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
}
.footer-brand {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}
.footer-logo {
  width: 48px;
  height: 48px;
  object-fit: contain;
  background: transparent;
  padding: 0;
}
.footer-brand p {
  margin: 0.15rem 0 0;
  color: #a9d7de;
  font-size: 0.85rem;
}
.footer-links {
  display: flex;
  gap: 1.35rem;
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
@media (max-width: 700px) {
  .footer-inner {
    padding: 1.75rem 1rem 2rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  .footer-links {
    gap: 0.85rem 1.1rem;
  }
}
</style>
