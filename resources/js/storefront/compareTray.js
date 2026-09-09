/** Product compare tray — up to 3–4 products */
const KEY = 'wasla_compare_ids';
const MAX = 3;

function read() {
  try {
    const raw = JSON.parse(localStorage.getItem(KEY) || '[]');
    return Array.isArray(raw) ? raw.map(Number).filter((id) => id > 0) : [];
  } catch {
    return [];
  }
}

function write(ids) {
  localStorage.setItem(KEY, JSON.stringify(ids.slice(0, MAX)));
  window.dispatchEvent(new CustomEvent('wasla:compare-changed', { detail: ids.slice(0, MAX) }));
  return ids.slice(0, MAX);
}

export function getCompareIds() {
  return read();
}

export function isInCompare(id) {
  return read().includes(Number(id));
}

export function toggleCompare(id) {
  const nid = Number(id);
  let ids = read();
  if (ids.includes(nid)) {
    ids = ids.filter((x) => x !== nid);
  } else {
    if (ids.length >= MAX) {
      ids = [...ids.slice(1), nid];
    } else {
      ids = [...ids, nid];
    }
  }
  return write(ids);
}

export function removeCompare(id) {
  return write(read().filter((x) => x !== Number(id)));
}

export function clearCompare() {
  return write([]);
}

export function compareUrl() {
  const ids = read();
  return ids.length ? `/compare?ids=${ids.join(',')}` : '/compare';
}
