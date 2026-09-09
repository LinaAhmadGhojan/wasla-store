<template>
  <div class="confirmation-page" dir="rtl">
    <StorefrontNav />

    <div class="confirmation-shell">
      <div v-if="loading" class="loading-row">جاري تحميل الطلب...</div>
      <div v-else-if="!orders.length" class="empty-state">
        <p>ما لقينا هالطلب.</p>
        <a href="/shop" class="btn btn-primary">العودة للمتجر</a>
      </div>
      <div v-else>
        <div class="success-banner">
          <img
            :src="successIlluUrl"
            alt=""
            class="success-illu"
          />
          <h1>انربط الطلب بنجاح</h1>
          <p>وصلتنا… وطلبك{{ orders.length > 1 ? 'اتك' : '' }} صار جاهز للتجهيز.</p>
        </div>

        <div v-for="order in orders" :key="order.id" class="order-card">
          <div class="order-card-header">
            <div>
              <strong>طلب #{{ order.id }}</strong>
              <span class="order-vendor" v-if="order.vendor">&middot; {{ order.vendor.store_name }}</span>
            </div>
            <span class="order-status">{{ order.status }}</span>
          </div>
          <div class="order-items">
            <div v-for="item in order.items" :key="item.id" class="order-item-row">
              <span>{{ item.product?.name }} &times; {{ item.quantity }}</span>
              <span>{{ formatPrice(item.line_total) }} ل.س</span>
            </div>
          </div>
          <div class="order-total-row">
            <span>الإجمالي</span>
            <span>{{ formatPrice(order.total) }} ل.س</span>
          </div>
          <p class="payment-note" v-if="order.payment">
            الدفع: {{ order.payment.payment_method }} &middot; الحالة: {{ order.payment.status }}
          </p>
        </div>

        <div class="confirmation-actions">
          <a
            v-for="order in orders"
            :key="'track-'+order.id"
            :href="`/orders/${order.id}/track`"
            class="btn btn-primary"
          >تتبّع الوصلة #{{ order.id }}</a>
          <a href="/shop" class="btn btn-outline">تسوّق المزيد</a>
        </div>
      </div>
    </div>

    <StorefrontFooter />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../storefront/api';
import { formatPrice } from '../../storefront/format';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const successIlluUrl = '/brand/illustrations/success-check.png';
const orders = ref([]);
const loading = ref(true);

async function loadOrders() {
  const params = new URLSearchParams(window.location.search);
  const idsParam = params.get('orders');
  if (!idsParam) {
    loading.value = false;
    return;
  }
  const ids = idsParam.split(',').filter(Boolean);
  try {
    const results = await Promise.all(ids.map((id) => api.get(`/orders/${id}`)));
    orders.value = results.map((r) => r.data);
  } catch (e) {
    orders.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(loadOrders);
</script>

<style scoped>
.confirmation-page { color: #132f37; background: #f5fbfc; min-height: 100vh; }
.confirmation-shell {
  max-width: 780px;
  margin: 0 auto;
  padding: 3rem 1.5rem;
}
.loading-row, .empty-state {
  padding: 4rem 0;
  text-align: center;
  color: #4d6b72;
}
.success-banner { text-align: center; margin-bottom: 2.5rem; }
.success-illu {
  width: min(220px, 70vw);
  height: auto;
  margin: 0 auto 1rem;
  display: block;
}
.success-banner h1 {
  margin: 0 0 0.5rem;
  font-size: 1.7rem;
  color: #006871;
}
.success-banner p { color: #4d6b72; margin: 0; }
.order-card {
  background: white;
  border: 1px solid rgba(0, 104, 113, 0.1);
  border-radius: 1.25rem;
  padding: 1.5rem;
  margin-bottom: 1.25rem;
}
.order-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #eef4f5;
}
.order-vendor { color: #4d6b72; font-weight: 500; }
.order-status {
  background: #eef9fa;
  color: #006871;
  padding: 0.3rem 0.8rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 800;
}
.order-item-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
  color: #4d6b72;
  margin-bottom: 0.4rem;
}
.order-total-row {
  display: flex;
  justify-content: space-between;
  font-weight: 800;
  border-top: 1px solid #eef4f5;
  padding-top: 0.75rem;
  margin-top: 0.5rem;
  color: #006871;
}
.payment-note {
  margin: 0.75rem 0 0;
  font-size: 0.8rem;
  color: #9aabaf;
}
.confirmation-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  align-items: stretch;
  margin-top: 2rem;
  max-width: 360px;
  margin-inline: auto;
}
.btn {
  display: block;
  text-align: center;
  text-decoration: none;
  padding: 0.95rem 1.25rem;
  border-radius: 999px;
  font-weight: 800;
}
.btn-primary { background: #006871; color: #fff; }
.btn-outline {
  background: #fff;
  color: #006871;
  border: 2px solid #006871;
}
</style>
