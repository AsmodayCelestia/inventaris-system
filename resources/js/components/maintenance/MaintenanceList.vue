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
                    <span class="badge badge-warning">{{ item.status }}</span>
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

const userId = computed(() => counter.userId);
const userRole = computed(() => counter.userRole);

// ✅ Filter berdasarkan role dan inventory.pj_id
const filteredList = computed(() => {
  let list = counter.maintenanceHistory;

  // 1) filter status "planning"
  list = list.filter(m => m.status === 'planning');

  // 2) filter berdasarkan role
  if (userRole.value === 'karyawan') {
    list = list.filter(m => m.inventory?.pj_id === userId.value);
  }

  return list;
});

// ✅ Format tanggal
const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

// ✅ Logic akses edit
const canEdit = (item) => {
  console.log(item);
  
  if (['admin', 'head'].includes(userRole.value)) return true;
  if (userRole.value === 'karyawan' && item.inventory?.pj_id === userId.value) return true;
  return false; // owner tidak bisa edit
};

onMounted(() => {
  counter.fetchMaintenanceHistory(); // isi counter.maintenanceHistory
});
</script>
