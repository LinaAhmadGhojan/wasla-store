<template>
  <div class="home-channel-row" aria-label="اختر نوع التسوق">
    <a
      :href="storeHref"
      class="channel-card channel-store"
      :class="{ 'is-active': active === 'store' }"
      @click="rememberChannel('store')"
    >
      <span class="channel-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M6 7h15l-1.5 9h-12L6 7Z"/><path d="M6 7 5 4H2"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
      </span>
      <span class="channel-text">
        <strong>تسوق وصلة</strong>
        <em>ملابس · إكسسوارات · محلي</em>
      </span>
    </a>
    <a
      href="/express"
      class="channel-card channel-express"
      :class="{ 'is-active': active === 'express' }"
      @click="rememberChannel('express')"
    >
      <span v-if="showNewBadge" class="channel-badge">جديد</span>
      <span class="channel-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24">
          <path d="M3.5 17h17" />
          <path d="M6 17V11.5c0-3.3 2.7-6 6-6s6 2.7 6 6V17" />
          <path d="M12 5.5V3.5" />
          <path d="M10.5 3.5h3" />
          <path d="M8.5 10.5h7" />
        </svg>
      </span>
      <span class="channel-text">
        <strong>طلباتي</strong>
        <em>توصيل أكل · مطاعم قريبة</em>
      </span>
    </a>
  </div>
</template>

<script setup>
import { rememberChannel } from '../../storefront/channel';

defineProps({
  active: {
    type: String,
    default: 'store',
    validator: (v) => v === 'store' || v === 'express',
  },
  storeHref: { type: String, default: '/' },
  showNewBadge: { type: Boolean, default: true },
});
</script>

<style scoped>
.home-channel-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.55rem;
  margin-bottom: 0.75rem;
}
.channel-card {
  position: relative;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: flex-start;
  gap: 0.45rem;
  min-height: 0;
  padding: 0.5rem 0.55rem;
  border-radius: 0.85rem;
  text-decoration: none;
  color: #fff;
  text-align: start;
  box-shadow: 0 6px 16px rgba(19, 47, 55, 0.1);
  border: 2px solid transparent;
  transition: box-shadow 0.15s ease, border-color 0.15s ease;
}
.channel-store {
  background: linear-gradient(145deg, #0a8a9e, #087b8d);
  border-color: rgba(255, 255, 255, 0.12);
}
.channel-express {
  background: linear-gradient(145deg, #f0923a, #e07a2f);
  border-color: rgba(255, 255, 255, 0.15);
}
.channel-card.is-active {
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.55), 0 8px 20px rgba(19, 47, 55, 0.14);
  border-color: rgba(255, 255, 255, 0.85);
}
.channel-badge {
  position: absolute;
  top: 0.28rem;
  inset-inline-end: 0.35rem;
  background: #fff;
  color: #c45a12;
  font-size: 0.52rem;
  font-weight: 900;
  padding: 0.1rem 0.32rem;
  border-radius: 999px;
  line-height: 1.2;
}
.channel-icon {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.15rem;
  height: 2.15rem;
  border-radius: 0.55rem;
  background: rgba(255, 255, 255, 0.16);
}
.channel-icon svg {
  width: 1.35rem;
  height: 1.35rem;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.75;
  stroke-linecap: round;
  stroke-linejoin: round;
}
.channel-text {
  display: flex;
  flex-direction: column;
  gap: 0.06rem;
  line-height: 1.2;
  min-width: 0;
  flex: 1;
}
.channel-text strong {
  font-size: 0.82rem;
  font-weight: 900;
}
.channel-text em {
  font-style: normal;
  font-size: 0.58rem;
  font-weight: 700;
  opacity: 0.92;
  line-height: 1.3;
}
</style>
