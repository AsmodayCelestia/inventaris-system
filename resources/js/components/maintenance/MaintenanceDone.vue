<template>
  <div>
    <!-- Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Maintenance Selesai</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link to="/dashboard">Home</router-link>
              </li>
              <li class="breadcrumb-item active">Maintenance Selesai</li>
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
            <h3 class="card-title">Daftar Maintenance Selesai</h3>
          </div>

          <div class="card-body">
            <div v-if="counter.maintenanceLoading" class="text-center p-4">
              <i class="fas fa-spinner fa-spin fa-2x"></i>
              <p class="mt-2">Memuat data...</p>
            </div>

            <div v-else-if="doneList.length === 0" class="alert alert-info m-3">
              Belum ada maintenance yang selesai.
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
                <tr v-for="item in doneList" :key="item.id">
                  <td>{{ item.inventory?.item?.name || '-' }}</td>
                  <td>{{ item.inventory?.inventory_number || '-' }}</td>
                  <td>{{ item.responsible_person?.name ?? '-' }}</td>
                  <td>{{ formatDate(item.inspection_date) }}</td>
                  <td>
                    <span class="badge badge-success">Selesai</span>
                  </td>
                  <td>
                    <router-link
                      :to="`/maintenance/${item.id}`"
                      class="text-muted mr-2">
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
</template>

<script setup>
import { computed } from 'vue';
import { useCounterStore } from '@/stores/counter';

const counter = useCounterStore();

const doneList = computed(() =>
  counter.maintenanceHistory.filter((m) => m.status === 'done')
);

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

counter.fetchMaintenanceHistory();
</script>