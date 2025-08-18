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
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              Laporan #{{ record.id || '-' }}
              <span class="ml-2 badge" :class="badgeClass(record.status)">
                {{ record.status_label || record.status }}
              </span>
            </h3>
            <div class="card-tools">
              <!-- tombol Edit hanya muncul jika boleh edit -->
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
            <!-- Loading -->
            <div v-if="loading" class="text-center p-4">
              <i class="fas fa-spinner fa-spin fa-2x"></i>
              <p class="mt-2">Memuat data...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="alert alert-danger">
              {{ error }}
            </div>

            <!-- Content -->
            <div v-else>
              <table class="table table-bordered">
                <tr><th style="width: 220px">Inventaris</th><td>{{ record.inventory?.item?.name || '-' }} ({{ record.inventory?.inventory_number }})</td></tr>
                <tr><th>Tanggal Pemeriksaan</th><td>{{ formatDateTime(record.inspection_date) }}</td></tr>
                <tr><th>Status</th><td><span :class="badgeClass(record.status)">{{ record.status_label || record.status }}</span></td></tr>
                <tr><th>Permasalahan</th><td><pre class="mb-0">{{ record.issue_found || '-' }}</pre></td></tr>
                <tr><th>Solusi / Tindakan</th><td><pre class="mb-0">{{ record.solution_taken || '-' }}</pre></td></tr>
                <tr><th>Catatan</th><td><pre class="mb-0">{{ record.notes || '-' }}</pre></td></tr>
                <tr><th>Biaya</th><td>{{ record.cost ? rupiah(record.cost) : '-' }}</td></tr>
                <tr><th>Teknisi / PJ</th><td>{{ record.responsible_person?.name || '-' }}</td></tr>
                <tr><th>Odometer Terakhir</th><td>{{ record.odometer_reading ?? '-' }}</td></tr>
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
          </div>

          <div class="card-footer">
            <router-link to="/maintenance/list" class="btn btn-secondary">
              <i class="fas fa-arrow-left"></i> Kembali
            </router-link>
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

// Setup dayjs
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

const showEditButton = computed(() => {
  // kalau belum ada data, sembunyikan dulu
  if (!record.value.id) return false;

  const isDone = record.value.status === 'done';
  const role   = counter.userRole;

  // planning → boleh edit semua role (sesuai gate backend)
  if (!isDone) return true;

  // done → hanya admin & head
  return ['admin', 'head'].includes(role);
});

/* helpers */
const rupiah = (val) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

const badgeClass = (status) => ({
  'badge-warning': status === 'planning',
  'badge-success': status === 'done',
});

const photos = computed(() =>
  ['photo_1', 'photo_2', 'photo_3']
    .map(k => record.value[k])
    .filter(Boolean)
);

// Date formatting helpers
const formatDate = (dateString, format = 'DD MMMM YYYY') => {
  if (!dateString) return '-';
  try {
    return dayjs(dateString).format(format);
  } catch {
    return '-';
  }
};

const formatDateTime = (dateString) => {
  if (!dateString) return '-';
  try {
    return dayjs(dateString).format('DD MMMM YYYY');
  } catch {
    return '-';
  }
};

const formatRelativeTime = (dateString) => {
  if (!dateString) return '-';
  try {
    return dayjs(dateString).fromNow();
  } catch {
    return '-';
  }
};

/* lightbox */
const openLightbox = (url) => (lightbox.value = url);
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