<template>
  <div class="tracking-map-wrap" dir="rtl">
    <div ref="mapEl" class="tracking-map" aria-label="خريطة التتبع" />
    <div v-if="!hasMap" class="map-fallback">الخريطة متاحة عند تفعيل عنوان GPS للتوصيل.</div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
});

const props = defineProps({
  live: { type: Object, default: null },
});

const mapEl = ref(null);
let map = null;
let layers = [];

const hasMap = computed(() => {
  const d = props.live?.destination;
  return d && d.lat != null && d.lng != null;
});

function driverIcon() {
  return L.divIcon({
    className: 'driver-marker',
    html: '<span aria-hidden="true">🏍️</span>',
    iconSize: [36, 36],
    iconAnchor: [18, 18],
  });
}

function clearLayers() {
  layers.forEach((l) => l.remove());
  layers = [];
}

function render() {
  if (!map || !hasMap.value) return;
  clearLayers();

  const { origin, destination, driver } = props.live;
  const points = [];

  if (origin?.lat != null) {
    const m = L.marker([origin.lat, origin.lng], { title: origin.label || 'المستودع' }).addTo(map);
    m.bindPopup(origin.label || 'انطلاق');
    layers.push(m);
    points.push([origin.lat, origin.lng]);
  }

  const dest = L.marker([destination.lat, destination.lng], { title: destination.label || 'عنوانك' }).addTo(map);
  dest.bindPopup(destination.label || 'التوصيل');
  layers.push(dest);
  points.push([destination.lat, destination.lng]);

  if (driver?.lat != null) {
    const d = L.marker([driver.lat, driver.lng], { icon: driverIcon(), zIndexOffset: 500 }).addTo(map);
    d.bindPopup(driver.label || 'المندوب');
    layers.push(d);
    points.push([driver.lat, driver.lng]);

    const route = L.polyline(
      [[driver.lat, driver.lng], [destination.lat, destination.lng]],
      { color: '#1c7282', weight: 4, opacity: 0.85, dashArray: '8 6' },
    ).addTo(map);
    layers.push(route);
  } else if (origin?.lat != null) {
    const route = L.polyline(
      [[origin.lat, origin.lng], [destination.lat, destination.lng]],
      { color: '#94a3b8', weight: 3, opacity: 0.5, dashArray: '4 8' },
    ).addTo(map);
    layers.push(route);
  }

  if (points.length) {
    map.fitBounds(L.latLngBounds(points), { padding: [36, 36], maxZoom: 15 });
  }
}

onMounted(() => {
  if (!mapEl.value) return;
  map = L.map(mapEl.value, { zoomControl: false, attributionControl: true });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap',
  }).addTo(map);
  L.control.zoom({ position: 'bottomleft' }).addTo(map);
  map.setView([33.5138, 36.2765], 13);
  render();
});

watch(() => props.live, () => render(), { deep: true });

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});
</script>

<style scoped>
.tracking-map-wrap {
  position: relative;
  border-radius: 1rem;
  overflow: hidden;
  border: 1px solid rgba(28, 114, 130, 0.15);
  box-shadow: 0 8px 24px rgba(19, 47, 55, 0.08);
}
.tracking-map {
  height: 220px;
  width: 100%;
  background: #e8f4f6;
}
.map-fallback {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  text-align: center;
  font-size: 0.82rem;
  font-weight: 700;
  color: #4d6b72;
  background: rgba(255, 255, 255, 0.92);
}
:deep(.driver-marker) {
  background: #fff;
  border: 2px solid #1c7282;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
