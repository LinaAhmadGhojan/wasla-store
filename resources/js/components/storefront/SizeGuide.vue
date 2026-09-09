<template>
  <section class="size-guide" dir="rtl" id="size-guide">
    <div class="sg-head">
      <h2>دليل المقاسات</h2>
      <div class="unit-toggle">
        <button type="button" :class="{ on: unit === 'cm' }" @click="unit = 'cm'">CM</button>
        <button type="button" :class="{ on: unit === 'in' }" @click="unit = 'in'">IN</button>
      </div>
    </div>

    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th>المقاس</th>
            <th v-for="col in measureCols" :key="col.key">{{ col.label }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in displayRows" :key="row.size" :class="{ suggest: suggestedSize === row.size }">
            <th>{{ row.size }}</th>
            <td v-for="col in measureCols" :key="col.key">{{ formatCell(row[col.key]) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-if="note" class="note">{{ note }}</p>

    <div class="find-size">
      <h3>Find My Size — اقترحي مقاسي</h3>
      <p class="hint">أدخلي قياساتك بالـ{{ unit === 'cm' ? 'سنتيمتر' : 'إنش' }} وسنقترح المقاس الأقرب.</p>
      <div class="find-grid">
        <label v-for="col in measureCols" :key="'f-'+col.key">
          {{ col.label }}
          <input v-model.number="inputs[col.key]" type="number" min="1" step="0.5" />
        </label>
      </div>
      <button type="button" class="btn" @click="suggest">اقترحي المقاس</button>
      <p v-if="suggestedSize" class="result">المقاس المقترح: <strong>{{ suggestedSize }}</strong></p>
    </div>
  </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
  chart: { type: Object, default: null },
});

const emit = defineEmits(['suggested']);

const defaultCols = [
  { key: 'chest', label: 'الصدر Chest' },
  { key: 'waist', label: 'الخصر Waist' },
  { key: 'hip', label: 'الورك Hip' },
  { key: 'length', label: 'الطول Length' },
];

const defaultRows = [
  { size: 'XS', chest: 82, waist: 64, hip: 88, length: 60 },
  { size: 'S', chest: 86, waist: 68, hip: 92, length: 62 },
  { size: 'M', chest: 90, waist: 72, hip: 96, length: 64 },
  { size: 'L', chest: 96, waist: 78, hip: 102, length: 66 },
  { size: 'XL', chest: 102, waist: 84, hip: 108, length: 68 },
  { size: 'XXL', chest: 108, waist: 90, hip: 114, length: 70 },
];

const unit = ref(props.chart?.unit === 'in' ? 'in' : 'cm');
const suggestedSize = ref('');
const inputs = reactive({ chest: null, waist: null, hip: null, length: null });

const note = computed(() => props.chart?.note || 'القياسات تقريبية وقد تختلف حسب القصة (Fit).');

const measureCols = computed(() => {
  // Prefer structured fashion chart; fallback to product chart columns if present as labels
  if (props.chart?.measurements?.length) {
    return props.chart.measurements;
  }
  return defaultCols;
});

const baseRows = computed(() => {
  if (props.chart?.fashion_rows?.length) {
    return props.chart.fashion_rows;
  }
  // Try map generic size_chart rows if first col is size labels XS..XXL
  const cols = props.chart?.columns || [];
  const rows = props.chart?.rows || [];
  if (cols.length >= 2 && rows.length) {
    const mapped = rows.map((r) => {
      const obj = { size: r[0] };
      measureCols.value.forEach((c, i) => {
        obj[c.key] = Number(r[i + 1]) || r[i + 1];
      });
      return obj;
    });
    if (mapped.some((m) => ['XS', 'S', 'M', 'L', 'XL', 'XXL'].includes(String(m.size).toUpperCase()))) {
      return mapped;
    }
  }
  return defaultRows;
});

const displayRows = computed(() => {
  const factor = unit.value === 'in' ? (1 / 2.54) : 1;
  const chartUnit = props.chart?.unit === 'in' ? 'in' : 'cm';
  const fromChartFactor = chartUnit === 'in' ? 2.54 : 1;
  return baseRows.value.map((row) => {
    const out = { size: row.size };
    measureCols.value.forEach((c) => {
      const cm = Number(row[c.key]) * fromChartFactor;
      out[c.key] = unit.value === 'cm' ? cm : cm * factor;
    });
    return out;
  });
});

function formatCell(v) {
  if (v == null || v === '') return '—';
  const n = Number(v);
  if (Number.isNaN(n)) return v;
  return unit.value === 'cm' ? Math.round(n) : n.toFixed(1);
}

function suggest() {
  const filled = measureCols.value
    .map((c) => ({ key: c.key, val: Number(inputs[c.key]) }))
    .filter((x) => x.val > 0);
  if (!filled.length) {
    suggestedSize.value = '';
    return;
  }
  // Convert inputs to cm for comparison
  const toCm = (v) => (unit.value === 'in' ? v * 2.54 : v);
  let best = null;
  let bestScore = Infinity;
  for (const row of displayRows.value) {
    let score = 0;
    for (const f of filled) {
      const rowCm = toCm(Number(row[f.key]));
      const inputCm = toCm(f.val);
      score += Math.abs(rowCm - inputCm);
    }
    if (score < bestScore) {
      bestScore = score;
      best = row.size;
    }
  }
  suggestedSize.value = best || '';
  if (best) emit('suggested', best);
}

watch(() => props.chart, () => {
  if (props.chart?.unit) unit.value = props.chart.unit === 'in' ? 'in' : 'cm';
}, { deep: true });
</script>

<style scoped>
.size-guide {
  background: #fff; border: 1px solid rgba(28,114,130,.12); border-radius: 1.1rem;
  padding: 1.15rem 1.25rem; margin: 1.25rem 0;
}
.sg-head { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; }
.sg-head h2 { margin: 0; color: #0b3d44; font-size: 1.1rem; }
.unit-toggle { display: flex; gap: .3rem; }
.unit-toggle button {
  border: 1.5px solid rgba(28,114,130,.22); background: #fff; border-radius: 999px;
  padding: .3rem .7rem; font-weight: 800; cursor: pointer;
}
.unit-toggle button.on { background: #1c7282; color: #fff; border-color: #1c7282; }
.table-scroll { overflow: auto; margin-top: .85rem; }
table { width: 100%; border-collapse: collapse; min-width: 480px; }
th, td { padding: .65rem .75rem; border-bottom: 1px solid #eef4f5; text-align: center; }
thead th { background: #f5fbfc; color: #0b3d44; }
tbody th { text-align: right; color: #1c7282; }
tr.suggest { background: #e8f6f8; font-weight: 800; }
.note { margin: .65rem 0 0; color: #4d6b72; font-size: .88rem; }
.find-size { margin-top: 1.1rem; padding-top: 1rem; border-top: 1px dashed rgba(28,114,130,.2); }
.find-size h3 { margin: 0 0 .35rem; font-size: 1rem; color: #0b3d44; }
.hint { margin: 0 0 .75rem; color: #4d6b72; font-size: .88rem; }
.find-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: .55rem; }
.find-grid label { display: flex; flex-direction: column; gap: .25rem; font-weight: 700; font-size: .85rem; }
.find-grid input {
  border: 1.5px solid rgba(28,114,130,.22); border-radius: .65rem; padding: .5rem .65rem;
}
.btn {
  margin-top: .85rem; border: 0; border-radius: 999px; background: #1c7282; color: #fff;
  font-weight: 800; padding: .55rem 1.1rem; cursor: pointer;
}
.result { margin: .75rem 0 0; color: #1c7282; font-weight: 800; }
</style>
