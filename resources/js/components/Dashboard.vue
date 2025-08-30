<template>
  <!-- <Layout> -->
    <div class="container-fluid">
      <!-- 📊 Ringkasan Statistik (hitung langsung) -->
      <div class="row mb-4">
                <SummaryCard icon="fas fa-box" color="info" title="Total Inventaris" :value="stats.total" />
<SummaryCard icon="fas fa-check-circle" color="success" title="Maintenance Done Bulan Ini" :value="stats.maintenanceDoneThisMonth" />
        <SummaryCard icon="fas fa-tools" color="danger" title="Maintenance Aktif" :value="stats.maintenanceAktif" />
        <SummaryCard icon="fas fa-users" color="success" title="Perlu Tindakan" :value="stats.needAction" />
      </div>

      <!-- 📦 Inventaris Terbaru (5 baris) -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>📦 Inventaris Terbaru</strong>
          <router-link v-if="counterStore.isAdmin || counterStore.isHead" to="/inventories/create" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Tambah
          </router-link>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Nomor Inventaris</th>
                <th>Nama Barang</th>
                <th>Lokasi</th>
                <th>Status</th>
                <th>Tgl Pengadaan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in inventarisTerbaru" :key="item.id">
                <td>{{ item.inventory_number }}</td>
                <td>{{ item.item?.name || '-' }}</td>
                <td>{{ item.room?.name || '-' }} ({{ item.unit?.name || '-' }})</td>
                <td><span :class="['badge', getStatusBadge(item.status)]">{{ item.status }}</span></td>
                <td>{{ formatDate(item.procurement_date) }}</td>
                <td>
                  <router-link :to="`/inventories/${item.id}`" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-eye"></i>
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 🔧 Maintenance Aktif (5 baris) -->
      <div class="card mb-4">
        <div class="card-header"><strong>🔧 Maintenance Aktif</strong></div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Nama Barang</th>
                <th>Tgl Inspeksi</th>
                <th>Status</th>
                <th>Petugas</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in maintenanceAktif" :key="m.id">
                <td>{{ m.inventory?.item?.name ?? '-' }}</td>
                <td>{{ formatDate(m.inspection_date) }}</td>
                <td><span :class="badgeClass(m.status)">{{ statusLabel[m.status] || m.status }}</span></td>
                <td>{{ m.responsible_person?.name ?? '-' }}</td>
                <td>
                  <router-link :to="`/maintenance/${m.id}`" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-eye"></i>
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 📥 Permintaan Maintenance Baru -->
      <div class="card mb-4">
        <div class="card-header">
          <strong>📥 Permintaan Maintenance Baru</strong>
          <span v-if="stats.needAction" class="badge badge-danger ml-2">{{ stats.needAction }}</span>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Barang</th>
                <th>No Inv</th>
                <th>Ruangan</th>
                <th>Tgl Lapor</th>
                <th>Masalah</th>
                <th v-if="counterStore.isAdmin || counterStore.isHead">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in needMaintenance" :key="m.id">
                <td>{{ m.inventory?.item?.name ?? '-' }}</td>
                <td>{{ m.inventory?.inventory_number ?? '-' }}</td>
                <td>{{ m.inventory?.room?.name ?? '-' }}</td>
                <td>{{ formatDate(m.created_at) }}</td>
                <td>{{ m.issue_found ?? '-' }}</td>
                <td v-if="counterStore.isAdmin || counterStore.isHead">
                  <router-link :to="`/maintenance/edit/${m.id}`" class="btn btn-sm btn-warning">
                    Assign
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <!-- ✅ Maintenance Done Terbaru -->
      <div class="card mb-4">
        <div class="card-header">
          <strong>✅ Maintenance Selesai Terbaru</strong>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Barang</th>
                <th>Tgl Selesai</th>
                <th>Petugas</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in maintenanceDoneLatest" :key="m.id">
                <td>{{ m.inventory?.item?.name ?? '-' }}</td>
                <td>{{ formatDate(m.inspection_date) }}</td>
                <td>{{ m.responsible_person?.name ?? '-' }}</td>
                <td>
                  <router-link :to="`/maintenance/${m.id}`" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-eye"></i>
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <!-- ⚡ Akses Cepat Sesuai Role -->
      <!-- <div class="card">
        <div class="card-header">⚡ Akses Cepat</div>
        <div class="card-body d-flex flex-wrap gap-2">
          <router-link v-if="counterStore.isAdmin || counterStore.isHead" to="/inventories/create" class="btn btn-primary">
            ➕ Tambah Inventaris
          </router-link>
          <router-link v-if="counterStore.isAdmin || counterStore.isHead" to="/maintenance/create" class="btn btn-warning">
            🔄 Jadwalkan Maintenance
          </router-link>
          <router-link v-if="counterStore.isAdmin" to="/users" class="btn btn-secondary">
            🔐 Kelola Pengguna
          </router-link>
        </div>
      </div> -->
    </div>
  <!-- </Layout> -->
</template>

<script setup>
// import Layout from '@/components/Layout.vue';
import SummaryCard from '@/components/SummaryCard.vue';
import { ref, onMounted } from 'vue';
import { useCounterStore } from '@/stores/counter';
import axios from 'axios';

const counterStore = useCounterStore();

/* ---------- reactive data ---------- */
const stats = ref({
  total: 0,
  maintenanceDoneThisMonth: 0,
  maintenanceAktif: 0,
  needAction: 0,
});

const inventarisTerbaru = ref([]);
const maintenanceAktif = ref([]);
const needMaintenance = ref([]);
const maintenanceDoneLatest = ref([]);

/* ---------- helper ---------- */
const formatDate = (date) =>
  date
    ? new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      })
    : '-';

const badgeClass = (status) => ({
  reported: 'badge-danger',
  on_progress: 'badge-warning',
  handled: 'badge-info',
  done: 'badge-success',
  cancelled: 'badge-secondary',
}[status] || 'badge-light');

const statusLabel = {
  reported: 'Dilaporkan',
  on_progress: 'Dalam Proses',
  handled: 'Ditangani',
  done: 'Selesai',
  cancelled: 'Dibatalkan',
};

const getStatusBadge = (status) => {
  const map = {
    ada: 'badge-success',
    rusak: 'badge-danger',
    perbaikan: 'badge-warning',
    hilang: 'badge-dark',
    dipinjam: 'badge-info',
    '-': 'badge-secondary',
  };
  return map[status?.toLowerCase()] || 'badge-primary';
};

/* ---------- fetch semua data ---------- */
const fetchAll = async () => {
  const now = new Date();
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
  const lastDay  = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];

  const [inv, maint, need, doneAll, doneLatest] = await Promise.all([
    axios.get('/inventories/table?length=5'),
    axios.get('/maintenance/active-datatable', { params: { per_page: 5 } }),
    axios.get('/maintenance/need'),
    axios.get('/maintenance/done-datatable', { params: {
      date_from: firstDay,
      date_to: lastDay,
      /* tidak kirim per_page → ambil semua */
    } }),
    axios.get('/maintenance/done-datatable', { params: { per_page: 5 } }),
  ]);

  // Statistik: total done bulan ini = seluruh record tanpa paging
  stats.value.total                  = inv.data.recordsTotal || 0;
  stats.value.maintenanceDoneThisMonth = doneAll.data.recordsFiltered || 0;
  stats.value.maintenanceAktif      = maint.data.recordsFiltered || 0;
  stats.value.needAction            = Array.isArray(need.data) ? need.data.length : 0;

  // Tabel
  inventarisTerbaru.value      = inv.data.data || [];
  maintenanceAktif.value       = maint.data.data || [];
  needMaintenance.value        = Array.isArray(need.data) ? need.data : need.data.data || [];
  maintenanceDoneLatest.value  = doneLatest.data.data || [];
};

onMounted(fetchAll);
</script>