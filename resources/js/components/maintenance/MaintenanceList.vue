<!-- Maintenance Aktif (full copy-paste) -->
<template>
  <div>
    <!-- Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Maintenance Aktif</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link to="/dashboard">Home</router-link>
              </li>
              <li class="breadcrumb-item active">Maintenance</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Daftar Maintenance</h3>
            <router-link
              to="/maintenance/create"
              class="btn btn-primary btn-sm"
              v-if="counter.isAdmin || counter.isHead"
            >
              <i class="fas fa-plus"></i> Tambah Maintenance
            </router-link>
          </div>

          <div class="card-body">
            <div v-if="counter.maintenanceLoading" class="text-center p-4">
              <i class="fas fa-spinner fa-spin fa-2x"></i>
              <p class="mt-2">Memuat data maintenance...</p>
            </div>

            <div v-else-if="counter.maintenanceError" class="alert alert-danger m-3">
              Terjadi kesalahan: {{ counter.maintenanceError }}
            </div>

            <div v-else-if="filteredList.length === 0" class="alert alert-info m-3">
              Belum ada maintenance aktif.
            </div>

            <table v-else class="table table-striped table-valign-middle">
              <thead>
                <tr>
                  <th>Nama Barang</th>
                  <th>Nomor Inventaris</th>
                  <th>Penanggung Jawab</th>
                  <th>Tanggal</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in filteredList" :key="item.id">
                  <td>{{ item.inventory?.item?.name || '-' }}</td>
                  <td>{{ item.inventory?.inventory_number || '-' }}</td>
                  <td>{{ item.responsible_person?.name ?? '-' }}</td>
                  <td>{{ formatDate(item.inspection_date) }}</td>
                  <td>
                    <span :class="badgeClass(item.status)">
                      {{ item.status_label || item.status }}
                    </span>
                  </td>
                  <td>
                    <router-link :to="`/maintenance/${item.id}`" class="text-muted mr-2">
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <router-link
                      v-if="canEdit(item)"
                      :to="`/maintenance/edit/${item.id}`"
                      class="text-muted"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue';
import { useCounterStore } from '@/stores/counter';
const counter = useCounterStore();

const userId   = computed(() => counter.userId);
const userRole = computed(() => counter.userRole);
const userDiv  = computed(() => counter.userDivisi);

/* 1. Filter status aktif (on_progress / handled) + hak akses */
const filteredList = computed(() => {
  let list = counter.maintenanceHistory.filter(
    m => !['reported', 'done', 'cancelled'].includes(m.status)
  );

  /* 2. Hak akses per role (urutan penting!) */
  if (userRole.value === 'karyawan' && userDiv.value === 'Umum') {
    // a) Karyawan Umum → cuma yang dia kerjakan
    list = list.filter(m => m.user_id === userId.value);
  } else if (userRole.value === 'karyawan' && userDiv.value !== 'Umum') {
    // b) Karyawan biasa & pengawas ruangan
    list = list.filter(
      m =>
        m.creator_id === userId.value ||                 // dia yang lapor
        m.inventory?.room?.pj_lokasi_id === userId.value // atau dia pengawas ruangannya
    );
  }
  // c) admin & head langsung lewat (tidak ada filter)

  return list;
});

const badgeClass = (status) => ({
  reported   : 'badge-danger',
  on_progress: 'badge-warning',
  handled    : 'badge-info',
  done       : 'badge-success',
  cancelled  : 'badge-secondary',
}[status] || 'badge-light');

const formatDate = (date) =>
  !date
    ? '-'
    : new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });

/* 3. Hak edit sesuai rule final */
const canEdit = (item) => {
  const role   = userRole.value;
  const divisi = userDiv.value;
  const isPj   = item.user_id === userId.value;
  const isSuper = item.inventory?.room?.pj_lokasi_id === userId.value;

  switch (item.status) {
    case 'reported':
      return ['admin', 'head'].includes(role);

    case 'on_progress':
      return (
        (role === 'karyawan' && divisi === 'Umum' && isPj) || isSuper
      );
    case 'handled':
      return ['admin', 'head'].includes(role) || isSuper;
    default:
      return false;
  }
};

onMounted(() => {
  counter.fetchMaintenanceHistory();
});
</script>