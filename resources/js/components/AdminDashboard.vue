<template>
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <div class="brand-section">
        <img :src="logo" alt="Wasla" class="brand-logo" />
        <div>
          <h2>Wasla ERP</h2>
          <span>Marketplace Admin</span>
        </div>
      </div>

      <nav class="admin-nav">
        <template v-for="item in navItems" :key="item.key">
          <div class="nav-group" :class="{ 'last-group': item.key === 'settings' }">
            <h4>{{ item.label }}</h4>
            <button
              class="nav-main"
              :class="{ active: activeSection === item.key }
              "
              @click="onMainClick(item)">
              {{ item.label }}
            </button>

            <div v-if="openMenu === item.key" class="subnav" :aria-expanded="openMenu === item.key">
              <button
                v-for="child in item.children"
                :key="child.key"
                :class="{ active: activeSection === child.key }"
                @click="setSection(child.key)">
                {{ child.label }}
              </button>
            </div>
          </div>
        </template>

        
      </nav>
    </aside>

    <main class="admin-content">
      <header class="admin-header">
        <div>
          <h1>{{ sectionTitle }}</h1>
          <p>{{ sectionDescription }}</p>
        </div>
        <div class="header-actions">
          <template v-if="activeSection === 'users'">
            <input type="search" placeholder="Search users..." v-model="usersQuery" @input="onSearchInput" style="padding:0.5rem 0.8rem; border-radius:999px; border:1px solid #e6f7fb;" />
            <select v-model.number="usersMeta.per_page" @change="() => loadUsers(1)" style="margin-left:0.5rem;padding:0.45rem; border-radius:0.6rem;">
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="25">25</option>
            </select>
            <button type="button" class="btn btn-primary" @click="openCreate">Create New</button>
            <button type="button" class="btn btn-secondary" @click="openFilter">Filter</button>
          </template>
          <template v-else>
            <button type="button" class="btn btn-primary" @click="openCreate">Create New</button>
            <button type="button" class="btn btn-secondary" @click="openFilter">Filter</button>
          </template>
        </div>
      </header>

      <section class="admin-kpis" v-if="activeSection === 'dashboard'">
        <div class="kpi-card">
          <span>Total Orders</span>
          <strong>1,842</strong>
        </div>
        <div class="kpi-card">
          <span>Total Sales</span>
          <strong>$189,240</strong>
        </div>
        <div class="kpi-card">
          <span>Total Customers</span>
          <strong>9,120</strong>
        </div>
        <div class="kpi-card">
          <span>Active Sellers</span>
          <strong>348</strong>
        </div>
      </section>

      <section class="admin-panel">
        <template v-if="activeSection === 'users'">
          <h2>Customer Management</h2>
          <p>View and manage customer accounts, addresses, and activity.</p>
          <div class="table-card">
            <table>
              <thead>
                <tr>
                  <th style="width:40px"><input type="checkbox" @change="toggleSelectAll" :checked="allSelected" /></th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="usersLoading">
                  <td colspan="6">Loading...</td>
                </tr>
                <tr v-else v-for="u in users" :key="u.id">
                  <td><input type="checkbox" :value="u.id" @change="() => toggleSelect(u.id)" :checked="selectedUsers.has(u.id)" /></td>
                  <td>{{ u.name }}</td>
                  <td>{{ u.email }}</td>
                  <td>{{ u.phone ?? '-' }}</td>
                  <td>{{ u.created_at ? 'Active' : 'Unknown' }}</td>
                  <td>
                    <button type="button" class="small-btn" @click.stop="openView(u)"><span class="icon-eye">edit</span></button>
                    <button type="button" class="small-btn outline" @click.stop>Block</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div class="pagination" style="margin-top:0.75rem; display:flex; align-items:center; gap:0.75rem;">
              <button class="small-btn" @click="goToPage(usersMeta.current_page - 1)" :disabled="usersMeta.current_page <= 1">Prev</button>
              <span>Page {{ usersMeta.current_page }} / {{ usersMeta.last_page }}</span>
              <button class="small-btn" @click="goToPage(usersMeta.current_page + 1)" :disabled="usersMeta.current_page >= usersMeta.last_page">Next</button>
            </div>
          </div>
        </template>

        <template v-else-if="activeSection === 'sellers'">
          <h2>Seller Management</h2>
          <p>Approve new sellers, manage stores and monitor performance.</p>
          <div class="panel-grid">
            <div class="panel-card">
              <h3>Pending Sellers</h3>
              <p>12 waiting for approval</p>
            </div>
            <div class="panel-card">
              <h3>Active Stores</h3>
              <p>318 stores live</p>
            </div>
          </div>
        </template>

        <template v-else-if="activeSection === 'drivers'">
          <h2>Drivers</h2>
          <p>Track delivery drivers, assignments and status.</p>
          <div class="panel-grid">
            <div class="panel-card">
              <h3>Available Drivers</h3>
              <p>24</p>
            </div>
            <div class="panel-card">
              <h3>Assigned Orders</h3>
              <p>54</p>
            </div>
          </div>
        </template>

        <template v-else-if="activeSection === 'admins'">
          <h2>Admins</h2>
          <p>Manage admin accounts and roles.</p>
          <p>Admin users can be added, edited or removed from this section.</p>
        </template>

        <template v-else-if="activeSection === 'stores'">
          <h2>All Stores</h2>
          <p>Manage marketplace stores and approve new storefronts.</p>
        </template>

        <template v-else-if="activeSection === 'pending-stores'">
          <h2>Store Approvals</h2>
          <p>Review pending store verification requests.</p>
        </template>

        <template v-else-if="activeSection === 'store-categories'">
          <h2>Store Categories</h2>
          <p>Manage store category labels and assignment.</p>
        </template>

        <template v-else-if="activeSection === 'products'">
          <h2>Products</h2>
          <p>Manage products, images, variants, pricing and stock.</p>
        </template>

        <template v-else-if="activeSection === 'product-categories'">
          <h2>Product Categories</h2>
          <p>Manage product categories for vendors and storefronts.</p>
        </template>

        <template v-else-if="activeSection === 'attributes'">
          <h2>Attributes</h2>
          <p>Configure colors, sizes, materials and product variants.</p>
        </template>

        <template v-else-if="activeSection === 'inventory'">
          <h2>Inventory</h2>
          <p>Manage stock levels, low inventory alerts and SKU control.</p>
        </template>

        <template v-else-if="activeSection === 'new-orders'">
          <h2>New Orders</h2>
          <p>Review recent incoming orders and confirm processing.</p>
        </template>

        <template v-else-if="activeSection === 'processing'">
          <h2>Processing Orders</h2>
          <p>Orders currently being prepared and packed.</p>
        </template>

        <template v-else-if="activeSection === 'shipping'">
          <h2>Shipping</h2>
          <p>Orders in transit and assigned drivers.</p>
        </template>

        <template v-else-if="activeSection === 'delivered'">
          <h2>Delivered Orders</h2>
          <p>Completed deliveries and order history.</p>
        </template>

        <template v-else-if="activeSection === 'cancelled'">
          <h2>Cancelled Orders</h2>
          <p>Orders that were cancelled or refunded.</p>
        </template>

        <template v-else-if="activeSection === 'settings'">
          <h2>Settings</h2>
          <p>General configuration, shipping, payment and localization.</p>
        </template>
      </section>
    </main>

    <!-- Create Modal (moved out of sidebar to avoid color inheritance) -->
    <div v-if="showCreateModal" class="modal-backdrop">
      <div class="modal">
        <h3>Create User</h3>
        <label>Name</label>
        <input v-model="newUser.name" />
        <label>Email</label>
        <input v-model="newUser.email" />
        <label>Phone</label>
        <input v-model="newUser.phone" />
        <label>Password</label>
        <input type="password" v-model="newUser.password" />
        <div style="margin-top:0.75rem; display:flex; gap:0.5rem; justify-content:flex-end;">
          <button class="small-btn outline" @click="showCreateModal = false">Cancel</button>
          <button class="small-btn" @click="createUser">Create</button>
        </div>
      </div>
    </div>

    <!-- View / Edit Modal (moved out of sidebar) -->
    <div v-if="showUserModal" class="modal-backdrop">
      <div class="modal">
        <h3>Edit User</h3>
        <label>Name</label>
        <input v-model="editingUser.name" />
        <label>Email</label>
        <input v-model="editingUser.email" />
        <label>Phone</label>
        <input v-model="editingUser.phone" />
        <label>Password (leave blank to keep)</label>
        <input type="password" v-model="editingUser.password" />
        <div style="margin-top:0.75rem; display:flex; gap:0.5rem; justify-content:flex-end;">
          <button class="small-btn outline" @click="showUserModal = false">Cancel</button>
          <button class="small-btn" @click="saveUser">Save</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';

const activeSection = ref('dashboard');

const menus = {
  dashboard: { title: 'ERP Overview', description: 'Monitor orders, revenue, users, and marketplace health.' },
  users: { title: 'Customers', description: 'Manage customers and their orders.' },
  sellers: { title: 'Sellers', description: 'Manage sellers, stores and seller approvals.' },
  drivers: { title: 'Drivers', description: 'Track driver assignments and delivery progress.' },
  admins: { title: 'Admins', description: 'Manage admin users and permissions.' },
  stores: { title: 'All Stores', description: 'Manage all marketplace stores.' },
  'pending-stores': { title: 'Pending Approval', description: 'Review new store registration requests.' },
  'store-categories': { title: 'Store Categories', description: 'Manage store categories and labels.' },
  products: { title: 'Products', description: 'Manage products, variants, and stock.' },
  'product-categories': { title: 'Product Categories', description: 'Manage product categories.' },
  attributes: { title: 'Attributes', description: 'Manage product attributes and variants.' },
  inventory: { title: 'Inventory', description: 'Track inventory and SKU stock levels.' },
  'new-orders': { title: 'New Orders', description: 'New incoming orders waiting approval.' },
  processing: { title: 'Processing', description: 'Orders that are currently being prepared.' },
  shipping: { title: 'Shipping', description: 'Orders assigned to drivers for delivery.' },
  delivered: { title: 'Delivered', description: 'Completed orders and delivery history.' },
  cancelled: { title: 'Cancelled', description: 'Cancelled and refunded orders.' },
  settings: { title: 'Settings', description: 'General, shipping, payment and localization settings.' },
};

const sectionTitle = computed(() => menus[activeSection.value].title);
const sectionDescription = computed(() => menus[activeSection.value].description);

const logo = '/logo.png';

// navigation structure with children for submenus
const navItems = [
  { key: 'dashboard', label: 'Dashboard' },
  { key: 'users', label: 'Users', children: [
    { key: 'users', label: 'Customers' },
    { key: 'sellers', label: 'Sellers' },
    { key: 'drivers', label: 'Drivers' },
    { key: 'admins', label: 'Admins' },
  ]},
  { key: 'stores', label: 'Stores', children: [
    { key: 'stores', label: 'All Stores' },
    { key: 'pending-stores', label: 'Pending Approval' },
    { key: 'store-categories', label: 'Store Categories' },
  ]},
  { key: 'products', label: 'Products', children: [
    { key: 'products', label: 'Products' },
    { key: 'product-categories', label: 'Categories' },
    { key: 'attributes', label: 'Attributes' },
    { key: 'inventory', label: 'Stock' },
  ]},
  { key: 'orders', label: 'Orders', children: [
    { key: 'new-orders', label: 'New Orders' },
    { key: 'processing', label: 'Processing' },
    { key: 'shipping', label: 'Shipping' },
    { key: 'delivered', label: 'Delivered' },
    { key: 'cancelled', label: 'Cancelled' },
  ]},
  { key: 'settings', label: 'Settings' }
];

const selectedUsers = reactive(new Set());

const users = ref([]);
const usersMeta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const usersLoading = ref(false);
const usersQuery = ref('');
const searchTimer = ref(null);
const showCreateModal = ref(false);
const showFilterPanel = ref(false);
const showUserModal = ref(false);
const editingUser = ref(null);
const newUser = ref({ name: '', email: '', phone: '', password: '' });

async function loadUsers(page = 1) {
  usersLoading.value = true;
  try {
    const res = await window.axios.get('/api/users', {
      params: { page, per_page: usersMeta.value.per_page, q: usersQuery.value }
    });

    // axios returns data as paginator object
    users.value = res.data.data || res.data;
    usersMeta.value.current_page = res.data.current_page ?? res.data.currentPage ?? 1;
    usersMeta.value.last_page = res.data.last_page ?? res.data.lastPage ?? 1;
    usersMeta.value.per_page = res.data.per_page ?? res.data.perPage ?? usersMeta.value.per_page;
    usersMeta.value.total = res.data.total ?? res.data.total ?? 0;
  } catch (err) {
    console.error('Failed to load users', err);
  } finally {
    usersLoading.value = false;
  }
}

function goToPage(p) {
  if (p < 1 || p > usersMeta.value.last_page) return;
  loadUsers(p);
}

function onSearchInput() {
  if (searchTimer.value) clearTimeout(searchTimer.value);
  searchTimer.value = setTimeout(() => {
    loadUsers(1);
  }, 400);
}

function openCreate() {
  newUser.value = { name: '', email: '', phone: '', password: '' };
  showCreateModal.value = true;
}

function openFilter() {
  showFilterPanel.value = !showFilterPanel.value;
}

function openView(user) {
  console.log('openView called, users count:', users.value.length);
  editingUser.value = Object.assign({}, user);
  showUserModal.value = true;
  // debug after opening
  setTimeout(() => console.log('modal opened, users count:', users.value.length), 50);
}

async function createUser() {
  try {
    const res = await window.axios.post('/api/users', newUser.value);
    showCreateModal.value = false;
    loadUsers(1);
  } catch (err) {
    console.error('Create failed', err);
    alert('Create failed');
  }
}

async function saveUser() {
  try {
    const id = editingUser.value.id;
    const payload = { name: editingUser.value.name, email: editingUser.value.email, phone: editingUser.value.phone };
    if (editingUser.value.password) payload.password = editingUser.value.password;
    const res = await window.axios.put('/api/users/' + id, payload);
    showUserModal.value = false;
    loadUsers(usersMeta.value.current_page);
  } catch (err) {
    console.error('Update failed', err);
    alert('Update failed');
  }
}
function toggleSelect(id) {
  if (selectedUsers.has(id)) selectedUsers.delete(id);
  else selectedUsers.add(id);
}

function toggleSelectAll(e) {
  if (e.target.checked) {
    users.value.forEach(u => selectedUsers.add(u.id));
  } else selectedUsers.clear();
}

function setSection(name) {
  activeSection.value = name;
  if (name === 'users') {
    loadUsers(1);
  }
}

const openMenu = ref(null);

function onMainClick(item) {
  if (item.children && item.children.length) {
    // toggle open/closed without selecting a child automatically
    openMenu.value = openMenu.value === item.key ? null : item.key;
  } else {
    setSection(item.key);
  }
}
</script>

<style scoped>
.admin-shell {
  display: grid;
  grid-template-columns: 280px 1fr;
  min-height: 100vh;
  background: #f4fbfc;
}
.admin-sidebar {
  background: #072a34;
  color: #e6f7fb;
  padding: 2rem 1.5rem;
  display: flex;
  flex-direction: column;
}
.brand-section {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-bottom: 2rem;
}
.brand-section img {
  width: 44px;
  border-radius: 0.9rem;
  border: 1px solid rgba(255,255,255,.12);
}
.brand-section h2 {
  margin: 0;
  font-size: 1.1rem;
}
.brand-section span {
  font-size: 0.85rem;
  color: #95dce8;
}
.admin-nav {
  overflow-y: auto;
  flex: 1;
}
.nav-group {
  margin-bottom: 1.75rem;
}
.nav-group h4 {
  margin-bottom: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #72d5e2;
  font-size: 0.75rem;
}
.admin-nav button {
  width: 100%;
  display: block;
  text-align: left;
  padding: 0.8rem 1rem;
  margin-bottom: 0.5rem;
  border: none;
  background: transparent;
  color: #dceff4;
  border-radius: 0.95rem;
  cursor: pointer;
  transition: background .2s ease;
}
.admin-nav button:hover,
.admin-nav button.active {
  background: rgba(51, 187, 204, 0.18);
  color: #ffffff;
}
.nav-main {
  width: 100%;
  text-align: left;
  padding: 0.6rem 1rem;
  margin-bottom: 0.25rem;
  background: transparent;
  color: #dceff4;
  border: none;
  border-radius: 0.6rem;
}
.subnav {
  display: flex;
  flex-direction: column;
  margin-top: 0.5rem;
}
.subnav button {
  background: transparent;
  color: #bfe9ee;
  padding: 0.5rem 1rem;
  text-align: left;
  border: none;
  border-radius: 0.6rem;
  margin-bottom: 0.25rem;
}
.subnav button.active {
  background: rgba(51, 187, 204, 0.22);
  color: #fff;
}
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 60;
}
.modal {
  background: white;
  padding: 1.25rem;
  border-radius: 0.6rem;
  width: 420px;
  color: #132f37;
}
.modal input {
  width: 100%;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
  border-radius: 0.5rem;
  border: 1px solid #e9f5f6;
}
.icon-eye { display:inline-block; font-size:1rem; }
.last-group {
  margin-top: 2rem;
}
.admin-content {
  padding: 2rem 2.5rem;
}
.admin-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}
.admin-header h1 {
  margin: 0;
  font-size: 2rem;
  color: #1c7282;
}
.admin-header p {
  margin: 0.6rem 0 0;
  color: #4d6b72;
  max-width: 40rem;
}
.header-actions {
  display: flex;
  gap: 0.75rem;
}
.btn {
  border: none;
  border-radius: 999px;
  padding: 0.85rem 1.2rem;
  font-weight: 700;
  cursor: pointer;
}
.btn-primary {
  background: #1c7282;
  color: white;
}
.btn-secondary {
  background: #26b4c3;
  color: white;
}
.admin-kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.kpi-card {
  background: white;
  border-radius: 1.25rem;
  padding: 1.5rem;
  border: 1px solid rgba(15, 90, 107, .08);
}
.kpi-card span {
  display: block;
  color: #4d6b72;
  margin-bottom: 0.75rem;
}
.kpi-card strong {
  display: block;
  font-size: 1.8rem;
  color: #132f37;
}
.admin-panel {
  background: white;
  border-radius: 1.5rem;
  padding: 1.75rem;
  border: 1px solid rgba(15, 90, 107, .08);
}
.admin-panel h2 {
  margin-top: 0;
  color: #1c7282;
}
.admin-panel p {
  color: #4d6b72;
  margin-bottom: 1.25rem;
}
.panel-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}
.panel-card {
  background: #f3fbfc;
  border-radius: 1.25rem;
  padding: 1.25rem;
  border: 1px solid rgba(15, 90, 107, .08);
}
.panel-card h3 {
  margin: 0 0 0.5rem;
}
.small-btn {
  border: none;
  padding: 0.55rem 0.9rem;
  border-radius: 999px;
  cursor: pointer;
  margin-right: 0.5rem;
  background: #1c7282;
  color: white;
}
.small-btn.outline {
  background: transparent;
  color: #1c7282;
  border: 1px solid #1c7282;
}
.admin-panel table {
  width: 100%;
  border-collapse: collapse;
}
.admin-panel th,
.admin-panel td {
  padding: 0.95rem 0.75rem;
  border-bottom: 1px solid #e4eef1;
}
.admin-panel th {
  color: #1c7282;
  font-weight: 700;
}
.admin-panel td {
  color: #4d6b72;
}
@media (max-width: 1100px) {
  .admin-shell {
    grid-template-columns: 1fr;
  }
  .admin-sidebar {
    flex-direction: row;
    overflow-x: auto;
    padding: 1rem;
  }
  .admin-sidebar .admin-nav {
    display: flex;
    flex-wrap: nowrap;
  }
  .nav-group {
    margin-right: 1.5rem;
  }
  .admin-content {
    padding: 1.5rem;
  }
  .admin-kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 720px) {
  .admin-header {
    flex-direction: column;
    align-items: stretch;
  }
  .admin-kpis {
    grid-template-columns: 1fr;
  }
  .panel-grid {
    grid-template-columns: 1fr;
  }
}
</style>
