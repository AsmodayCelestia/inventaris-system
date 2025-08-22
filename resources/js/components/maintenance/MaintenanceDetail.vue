<!-- Detail Laporan Maintenance – lengkap & gate disesuaikan, siap copy-paste -->
<template>
  <div>
    <!-- Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Detail Laporan Maintenance</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link to="/dashboard">Home</router-link>
              </li>
              <li class="breadcrumb-item">
                <router-link to="/maintenance/list">Maintenance</router-link>
              </li>
              <li class="breadcrumb-item active">Detail</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="content">
      <div class="container-fluid">
        <!-- Hak akses -->
        <div v-if="!canView" class="alert alert-danger m-3">
          Anda tidak memiliki hak akses ke laporan ini.
        </div>

        <!-- Loading -->
        <div v-else-if="loading" class="text-center p-4">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p class="mt-2">Memuat data...</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="alert alert-danger m-3">
          {{ error }}
        </div>

        <!-- Konten utama -->
        <div v-else>
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                Laporan #{{ record.id || '-' }}
                <span class="ml-2 badge" :class="badgeClass(record.status)">
                  {{ record.status_label || record.status }}
                </span>
              </h3>
              <div class="card-tools">
                <button
                  v-if="showTakeButton"
                  class="btn btn-sm btn-primary mr-1"
                  @click="takeMaintenance"
                >
                  <i class="fas fa-hand-paper"></i> Ambil
                </button>
                <router-link
                  v-if="showEditButton"
                  :to="`/maintenance/edit/${record.id}`"
                  class="btn btn-sm btn-warning"
                >
                  <i class="fas fa-edit"></i> Edit
                </router-link>
              </div>
            </div>

            <div class="card-body">
              <table class="table table-bordered">
                <tbody>
                  <tr><th style="width: 220px">Inventaris</th><td>{{ record.inventory?.item?.name || '-' }} ({{ record.inventory?.inventory_number }})</td></tr>
                  <tr><th>Tanggal Pemeriksaan</th><td>{{ formatDateTime(record.inspection_date) }}</td></tr>
                  <tr><th>Status</th><td><span :class="badgeClass(record.status)">{{ record.status_label || record.status }}</span></td></tr>
                  <tr><th>Permasalahan</th><td><pre class="mb-0">{{ record.issue_found || '-' }}</pre></td></tr>
                  <tr><th>Solusi / Tindakan</th><td><pre class="mb-0">{{ record.solution_taken || '-' }}</pre></td></tr>
                  <tr><th>Catatan</th><td><pre class="mb-0">{{ record.notes || '-' }}</pre></td></tr>
                  <tr><th>Biaya</th><td>{{ record.cost ? rupiah(record.cost) : '-' }}</td></tr>
                  <tr><th>Teknisi / PJ</th><td>{{ record.responsible_person?.name || '-' }}</td></tr>
                  <tr><th>Pembuat Laporan</th><td>{{ record.creator?.name || '-' }}</td></tr>
                  <tr><th>Odometer Terakhir</th><td>{{ record.odometer_reading ?? '-' }}</td></tr>
                </tbody>
              </table>

              <!-- Foto -->
              <h5 class="mt-4 mb-2">Foto-foto</h5>
              <div class="row">
                <div
                  v-for="(photo, idx) in photos"
                  :key="idx"
                  class="col-md-4 mb-3"
                >
                  <div class="border rounded overflow-hidden">
                    <img
                      :src="photo"
                      alt="Foto"
                      class="img-fluid cursor-pointer"
                      style="max-height: 200px; width: 100%; object-fit: cover"
                      @click="openLightbox(photo)"
                    />
                    <div class="text-center p-1 text-muted">Foto {{ idx + 1 }}</div>
                  </div>
                </div>
                <div v-if="!photos.length" class="text-muted">Tidak ada foto.</div>
              </div>
            </div>

            <div class="card-footer">
              <router-link :to="backRoute" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox (sederhana) -->
    <div
      v-if="lightbox"
      class="lightbox"
      @click="closeLightbox"
    >
      <img :src="lightbox" alt="Preview" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useCounterStore } from '@/stores/counter';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';

dayjs.locale('id');
dayjs.extend(utc);
dayjs.extend(timezone);

const route   = useRoute();
const counter = useCounterStore();

const loading   = ref(true);
const error     = ref(null);
const record    = ref({});
const lightbox  = ref(null);

onMounted(async () => {
  try {
    record.value = await counter.fetchMaintenanceDetail(route.params.id);
  } catch (e) {
    error.value = e?.response?.data?.message || 'Gagal memuat data.';
  } finally {
    loading.value = false;
  }
});

/* ------------ hak akses ------------ */
const canView = computed(() => {
    if (record.value.status === 'reported') return true;
  if (!record.value.id) return false;
  const { creator_id, inventory } = record.value;
  const { userRole, userId, userDivisi } = counter;

  // admin/head bisa apa aja
  if (['admin', 'head'].includes(userRole)) return true;

  // Keuangan hanya boleh lihat apabila status sudah Done
  if (userRole === 'karyawan' && userDivisi === 'Keuangan')
    return record.value.status === 'done';

  // Pembuat laporan
  if (creator_id === userId) return true;

  // PJ Umum
  if (userRole === 'karyawan' && userDivisi === 'Umum' && record.value.user_id === userId) return true;

  // Pengawas Ruangan
  if (inventory?.room?.pj_lokasi_id === userId) return true;

  return false;
});

/* ------------ hak edit ------------ */
const showEditButton = computed(() => {
  if (record.value.status === 'reported') return true;
  if (!record.value.id) return false;
  const status       = record.value.status;
  const role         = counter.userRole;
  const divisi       = counter.userDivisi;
  const isPj         = record.value.user_id === counter.userId;
  const isSupervisor = record.value.inventory?.room?.pj_lokasi_id === counter.userId;

  // Admin/Head bisa edit apa saja
  if (['admin', 'head'].includes(role)) return true;

  // PJ Umum bisa edit saat on_progress / handled
  if (role === 'karyawan' && divisi === 'Umum' && isPj) return ['on_progress', 'handled'].includes(status);

  // Pengawas Ruangan bisa Mark Done saat handled / on_progress
  if (isSupervisor && ['handled', 'on_progress'].includes(status)) return true;

  return false;
});

/* ------------ hak ambil ------------ */
const showTakeButton = computed(() =>
  record.value.status === 'reported' &&
  record.value.user_id === null &&
  counter.userRole === 'karyawan' &&
  counter.userDivisi === 'Umum'
);

/* ------------ helper ------------ */
const rupiah = (val) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

const badgeClass = (status) => ({
  reported   : 'badge-danger',
  on_progress: 'badge-warning',
  handled    : 'badge-info',
  done       : 'badge-success',
  cancelled  : 'badge-secondary',
}[status] || 'badge-light');

const photos = computed(() =>
  ['photo_1', 'photo_2', 'photo_3']
    .map(k => record.value[k])
    .filter(Boolean)
);

const formatDateTime = (dateString) =>
  dateString ? dayjs(dateString).format('DD MMMM YYYY') : '-';

const takeMaintenance = async () => {
  try {
    await counter.assignMaintenance(record.value.id, { user_id: counter.userId });
    record.value = await counter.fetchMaintenanceDetail(route.params.id);
  } catch (e) {
    alert(e?.response?.data?.message || 'Gagal mengambil tugas');
  }
};

// tambahin di script setup
const backRoute = computed(() => {
  const { status } = record.value;
  const { userRole, userDivisi } = counter;

  // Kalau dari Keuangan & status done → balik ke approval/keuangan
  if (userRole === 'karyawan' && userDivisi === 'Keuangan' && status === 'done') {
    return '/maintenance/done';
  }

  // Kalau dari Umum/PJ → balik ke daftar tugas mereka
  if (userRole === 'karyawan' && userDivisi === 'Umum' & status === 'reported') {
    return '/maintenance/needed'; // atau '/maintenance/my-tasks'
  }

  if (userRole === 'karyawan' && userDivisi === 'Umum' & (status === 'on_progress' || status === 'handled')) {
    return '/maintenance/list'; // atau '/maintenance/my-tasks'
  }

    if (userRole === 'karyawan' && userDivisi === 'Umum' & (status === 'on_progress' || status === 'done')) {
    return '/maintenance/done'; // atau '/maintenance/my-tasks'
  }

  // Kalau dari Pengawas Ruangan → balik ke daftar ruangan mereka
  if (record.value.inventory?.room?.pj_lokasi_id === counter.userId && (status === 'on_progress' || status === 'handled')) {
    return `/maintenance/list`;
  }

  // Default fallback
  return '/maintenance/list';
});

const openLightbox  = (url) => (lightbox.value = url);
const closeLightbox = () => (lightbox.value = null);
</script>

<style scoped>
.lightbox {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.lightbox img {
  max-width: 90vw;
  max-height: 90vh;
}
.cursor-pointer {
  cursor: pointer;
}
</style>