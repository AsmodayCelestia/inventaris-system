<!-- Daftar Barang Butuh Maintenance – final-polish, siap copy-paste -->
<template>
  <div class="content">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Daftar Barang Butuh Maintenance</h1>
        </div>
      </div>

      <!-- loading -->
      <div v-if="loading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
      </div>

      <!-- empty -->
      <div v-else-if="!list.length" class="text-center py-5 text-muted">
        <i class="fas fa-check-circle fa-2x mb-2"></i>
        <p>Tidak ada barang yang perlu maintenance.</p>
      </div>

      <!-- table -->
      <div v-else class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body table-responsive p-0">
              <table class="table table-hover text-nowrap">
                <thead>
                  <tr>
                    <th>Barang</th>
                    <th>No Inv</th>
                    <th>Ruangan</th>
                    <th>Tgl Lapor</th>
                    <th>Masalah</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="m in list" :key="m.id">
                    <td>{{ m.inventory?.item?.name ?? '-' }}</td>
                    <td>{{ m.inventory?.inventory_number ?? '-' }}</td>
                    <td>{{ m.inventory?.room?.name ?? '-' }}</td>
                    <td>{{ formatDate(m.created_at) }}</td>
                    <td>{{ m.issue_found ?? '-' }}</td>
                    <td>
                      <span :class="badgeClass(m.status)">
                        {{ m.status_label }}
                      </span>
                    </td>
                    <td class="text-nowrap">
                      <!-- Admin / Head -->
                      <router-link
                        v-if="counterStore.isAdmin || counterStore.isHead"
                        :to="`/maintenance/edit/${m.id}`"
                        class="btn btn-sm btn-warning mr-1"
                      >
                        Assign to Staff
                      </router-link>

                    <!-- Karyawan Umum + status reported -->
                    <button
                    v-if="counterStore.userDivisi === 'Umum' && m.status === 'reported'"
                    class="btn btn-sm btn-primary mr-1"
                    @click="take(m.id)"
                    >
                    Ambil
                    </button>

                      <!-- Semua role – lihat detail -->
                      <router-link
                        :to="`/maintenance/${m.id}`"
                        class="btn btn-sm btn-outline-info"
                        title="Lihat detail"
                      >
                        <i class="fas fa-eye"></i>
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useCounterStore } from '@/stores/counter';

const counterStore = useCounterStore();
const list   = ref([]);
const loading = ref(true);

const badgeClass = (status) => ({
  reported   : 'badge-danger',
  on_progress: 'badge-warning',
  handled    : 'badge-info',
  done       : 'badge-success',
  cancelled  : 'badge-secondary',
}[status] || 'badge-light');

const formatDate = (date) =>
  !date ? '-' : new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric', month: 'long', day: 'numeric'
  });

const load = async () => {
  loading.value = true;
  try {
    list.value = await counterStore.fetchMaintenanceNeeded();
  } finally {
    loading.value = false;
  }
};

const take = async (id) => {
  try {
    await counterStore.assignMaintenance(id, { user_id: counterStore.userId });
    load();          // reload list
  } catch (e) {
    alert('Gagal ambil: ' + e.response?.data?.message);
  }
};

onMounted(load);
</script>

<style scoped>
.badge   { font-size: 0.75rem; }
.btn-sm  { font-size: 0.75rem; }
</style>