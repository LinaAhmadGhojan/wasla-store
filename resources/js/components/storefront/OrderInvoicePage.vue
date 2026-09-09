<template>
  <div class="invoice-page" dir="rtl">
    <StorefrontNav class="no-print" />

    <div class="invoice-shell">
      <div v-if="!store.authToken" class="empty-state">
        <p>سجّلي دخولك لعرض الفاتورة.</p>
        <a :href="loginHref" class="btn btn-primary">دخول</a>
      </div>
      <div v-else-if="loading" class="empty-state">جاري تحميل الفاتورة…</div>
      <div v-else-if="error" class="empty-state error">{{ error }}</div>
      <div v-else class="invoice-card" id="invoice-print">
        <div class="invoice-actions no-print">
          <button type="button" class="btn btn-primary" @click="printInvoice">تحميل / طباعة الفاتورة</button>
          <a :href="`/orders/${orderId}/track`" class="btn btn-ghost">تفاصيل الطلب</a>
          <a href="/my-requests" class="btn btn-ghost">طلباتي</a>
        </div>

        <header class="inv-head">
          <img :src="markUrl" alt="وصلة" class="inv-logo" />
          <div>
            <h1>فاتورة وصلة</h1>
            <p>{{ invoice.invoice_number }}</p>
          </div>
          <div class="inv-meta">
            <div>طلب #{{ invoice.order_id }}</div>
            <div>{{ formatDate(invoice.placed_at) }}</div>
            <div>{{ invoice.status_label }}</div>
          </div>
        </header>

        <div class="inv-grid">
          <section>
            <h2>العميل</h2>
            <p><strong>{{ invoice.customer?.name }}</strong></p>
            <p v-if="invoice.customer?.phone">{{ invoice.customer.phone }}</p>
            <p v-if="invoice.customer?.email">{{ invoice.customer.email }}</p>
          </section>
          <section v-if="invoice.shipping_address">
            <h2>عنوان التوصيل</h2>
            <p><strong>{{ invoice.shipping_address.recipient_name }}</strong></p>
            <p>{{ invoice.shipping_address.phone }}</p>
            <p>{{ invoice.shipping_address.street_address }}</p>
            <p>{{ [invoice.shipping_address.city, invoice.shipping_address.state].filter(Boolean).join(' · ') }}</p>
          </section>
        </div>

        <table class="inv-table">
          <thead>
            <tr>
              <th>المنتج</th>
              <th>الكمية</th>
              <th>السعر</th>
              <th>الإجمالي</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, i) in invoice.items" :key="i">
              <td>
                {{ item.name }}
                <small v-if="item.source">{{ item.source }}</small>
              </td>
              <td>{{ item.quantity }}</td>
              <td>{{ money(item.unit_price) }}</td>
              <td>{{ money(item.line_total) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="inv-totals">
          <div><span>المجموع الفرعي</span><strong>{{ money(invoice.totals?.subtotal) }}</strong></div>
          <div><span>الشحن</span><strong>{{ money(invoice.totals?.shipping_cost) }}</strong></div>
          <div v-if="invoice.totals?.discount_amount" class="disc"><span>خصم</span><strong>−{{ money(invoice.totals.discount_amount) }}</strong></div>
          <div v-if="invoice.coupon_code" class="disc"><span>كوبون</span><strong>{{ invoice.coupon_code }}</strong></div>
          <div class="grand"><span>الإجمالي</span><strong>{{ money(invoice.totals?.total) }}</strong></div>
          <div><span>تقريبي بالليرة</span><strong>{{ formatSyp(invoice.totals?.total_syp) }}</strong></div>
        </div>

        <p v-if="invoice.payment" class="pay-line">
          الدفع: {{ invoice.payment.method_label }} · {{ invoice.payment.status }}
        </p>
        <p v-if="invoice.customer_notes" class="notes">ملاحظات: {{ invoice.customer_notes }}</p>
      </div>
    </div>

    <StorefrontFooter class="no-print" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../../storefront/api';
import { store, hydrateUser } from '../../storefront/store';
import { money, formatSyp } from '../../storefront/format';
import StorefrontNav from './StorefrontNav.vue';
import StorefrontFooter from './StorefrontFooter.vue';

const props = defineProps({
  orderId: { type: [String, Number], required: true },
});

const markUrl = '/brand/wasla-id-mark.png?v=6';
const invoice = ref({});
const loading = ref(true);
const error = ref('');
const loginHref = `/login?redirect=${encodeURIComponent(`/orders/${props.orderId}/invoice`)}`;

function formatDate(iso) {
  if (!iso) return '';
  try {
    return new Date(iso).toLocaleString('ar');
  } catch (_) {
    return iso;
  }
}

function printInvoice() {
  window.print();
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    await hydrateUser();
    if (!store.authToken) {
      loading.value = false;
      return;
    }
    const { data } = await api.get(`/orders/${props.orderId}/invoice`);
    invoice.value = data;
  } catch (e) {
    error.value = e?.response?.data?.message || 'تعذّر تحميل الفاتورة.';
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

<style scoped>
.invoice-page { color: #132f37; background: linear-gradient(180deg, #eef8f9 0%, #fff 55%); min-height: 100vh; }
.invoice-shell { max-width: 820px; margin: 0 auto; padding: 1.5rem 1.25rem 3rem; }
.empty-state { text-align: center; padding: 3rem 1rem; color: #4d6b72; }
.empty-state.error { color: #a82626; }
.invoice-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
.invoice-card {
  background: #fff; border: 1px solid rgba(28,114,130,.12); border-radius: 1.1rem;
  padding: 1.5rem; box-shadow: 0 12px 30px rgba(19,47,55,.05);
}
.inv-head { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; margin-bottom: 1.25rem; }
.inv-logo { width: 52px; height: 52px; object-fit: contain; background: #1c7282; border-radius: 50%; padding: 8px; }
.inv-head h1 { margin: 0; color: #0b3d44; font-size: 1.35rem; }
.inv-head p { margin: .2rem 0 0; color: #4d6b72; }
.inv-meta { margin-inline-start: auto; text-align: left; font-size: .88rem; color: #4d6b72; }
.inv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; }
.inv-grid h2 { margin: 0 0 .4rem; font-size: .95rem; color: #1c7282; }
.inv-grid p { margin: .15rem 0; font-size: .9rem; }
.inv-table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
.inv-table th, .inv-table td { border-bottom: 1px solid #eef4f5; padding: .7rem .4rem; text-align: right; font-size: .9rem; }
.inv-table th { color: #4d6b72; background: #f5fbfc; }
.inv-table small { display: block; color: #9aabaf; font-size: .75rem; }
.inv-totals { max-width: 280px; margin-inline-start: auto; }
.inv-totals > div { display: flex; justify-content: space-between; margin-bottom: .4rem; font-size: .92rem; }
.inv-totals .grand { font-size: 1.1rem; font-weight: 800; border-top: 1px solid #eef4f5; padding-top: .55rem; margin-top: .35rem; }
.inv-totals .disc { color: #c62828; }
.pay-line, .notes { color: #4d6b72; font-size: .88rem; }
.btn { display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; padding: .55rem 1rem; font-weight: 800; text-decoration: none; border: 0; cursor: pointer; }
.btn-primary { background: #1c7282; color: #fff; }
.btn-ghost { background: #fff; color: #1c7282; border: 1.5px solid rgba(28,114,130,.3); }
@media (max-width: 700px) { .inv-grid { grid-template-columns: 1fr; } }
@media print {
  .no-print { display: none !important; }
  .invoice-page { background: #fff; }
  .invoice-card { box-shadow: none; border: 0; }
}
</style>
