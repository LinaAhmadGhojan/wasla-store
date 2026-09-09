<template>
  <div class="map-picker" dir="rtl">
    <div class="map-toolbar">
      <button type="button" class="btn gps" :disabled="locating" @click="useGps">
        {{ locating ? 'جاري تحديد موقعك…' : 'استخدم موقعي (GPS)' }}
      </button>
      <p class="hint">حرّكي الخريطة أو اضغطي لتحديد نقطة التوصيل بدقة.</p>
    </div>
    <div ref="mapEl" class="map-el"></div>
    <p v-if="gpsError" class="err">{{ gpsError }}</p>
    <p v-else-if="lat != null && lng != null" class="coords" dir="ltr">
      {{ Number(lat).toFixed(5) }}, {{ Number(lng).toFixed(5) }}
    </p>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Fix default marker icons under Vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
});

const props = defineProps({
  latitude: { type: [Number, String], default: null },
  longitude: { type: [Number, String], default: null },
  /** Damascus center as Syria default */
  fallbackLat: { type: Number, default: 33.5138 },
  fallbackLng: { type: Number, default: 36.2765 },
  zoom: { type: Number, default: 14 },
});

const emit = defineEmits(['update:latitude', 'update:longitude', 'picked']);

const mapEl = ref(null);
const locating = ref(false);
const gpsError = ref('');
const lat = ref(props.latitude != null && props.latitude !== '' ? Number(props.latitude) : null);
const lng = ref(props.longitude != null && props.longitude !== '' ? Number(props.longitude) : null);

let map = null;
let marker = null;

function emitCoords(nextLat, nextLng) {
  lat.value = nextLat;
  lng.value = nextLng;
  emit('update:latitude', nextLat);
  emit('update:longitude', nextLng);
  emit('picked', { latitude: nextLat, longitude: nextLng });
}

function setMarker(nextLat, nextLng, pan = true) {
  if (!map) return;
  if (!marker) {
    marker = L.marker([nextLat, nextLng], { draggable: true }).addTo(map);
    marker.on('dragend', () => {
      const p = marker.getLatLng();
      emitCoords(p.lat, p.lng);
    });
  } else {
    marker.setLatLng([nextLat, nextLng]);
  }
  if (pan) map.setView([nextLat, nextLng], Math.max(map.getZoom(), props.zoom));
  emitCoords(nextLat, nextLng);
}

function initMap() {
  if (!mapEl.value || map) return;
  const startLat = lat.value ?? props.fallbackLat;
  const startLng = lng.value ?? props.fallbackLng;

  map = L.map(mapEl.value, {
    zoomControl: true,
    attributionControl: true,
  }).setView([startLat, startLng], props.zoom);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap',
  }).addTo(map);

  map.on('click', (e) => {
    setMarker(e.latlng.lat, e.latlng.lng, false);
  });

  if (lat.value != null && lng.value != null) {
    setMarker(lat.value, lng.value, false);
  } else {
    // Place a default pin so user always has coordinates
    setMarker(startLat, startLng, false);
  }

  setTimeout(() => map?.invalidateSize(), 80);
}

function useGps() {
  gpsError.value = '';
  if (!navigator.geolocation) {
    gpsError.value = 'المتصفح لا يدعم GPS.';
    return;
  }
  locating.value = true;
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      locating.value = false;
      setMarker(pos.coords.latitude, pos.coords.longitude, true);
    },
    (err) => {
      locating.value = false;
      gpsError.value = err?.code === 1
        ? 'اسمحي بالوصول للموقع من إعدادات المتصفح.'
        : 'تعذّر الحصول على موقعك. حدّدي النقطة يدوياً على الخريطة.';
    },
    { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
  );
}

watch(
  () => [props.latitude, props.longitude],
  ([a, b]) => {
    if (a == null || b == null || a === '' || b === '') return;
    const nextLat = Number(a);
    const nextLng = Number(b);
    if (Number.isNaN(nextLat) || Number.isNaN(nextLng)) return;
    if (Math.abs((lat.value ?? 0) - nextLat) < 1e-7 && Math.abs((lng.value ?? 0) - nextLng) < 1e-7) return;
    lat.value = nextLat;
    lng.value = nextLng;
    if (map) setMarker(nextLat, nextLng, true);
  }
);

onMounted(() => {
  initMap();
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
    marker = null;
  }
});

defineExpose({ useGps, invalidate: () => map?.invalidateSize() });
</script>

<style scoped>
.map-picker { width: 100%; }
.map-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: .65rem;
  align-items: center;
  margin-bottom: .65rem;
}
.btn.gps {
  border: 0;
  border-radius: 999px;
  background: #1c7282;
  color: #fff;
  font-weight: 800;
  padding: .55rem 1rem;
  cursor: pointer;
}
.btn.gps:disabled { opacity: .7; }
.hint { margin: 0; color: #4d6b72; font-size: .88rem; flex: 1; }
.map-el {
  height: 280px;
  width: 100%;
  border-radius: 1rem;
  overflow: hidden;
  border: 1.5px solid rgba(28, 114, 130, .18);
  background: #e8f2f4;
  z-index: 1;
}
.coords {
  margin: .45rem 0 0;
  font-size: .8rem;
  color: #1c7282;
  font-weight: 700;
  text-align: left;
}
.err { margin: .45rem 0 0; color: #a82626; font-weight: 700; font-size: .88rem; }
@media (min-width: 760px) {
  .map-el { height: 340px; }
}
</style>
