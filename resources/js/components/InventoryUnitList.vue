<template>
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Data Inventaris</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item">
              <router-link to="/dashboard">Home</router-link>
            </li>
            <li class="breadcrumb-item active">Inventaris</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Daftar Unit Inventaris</h3>
          <router-link
            to="/inventories/create"
            class="btn btn-primary btn-sm"
            v-if="counterStore.isAdmin || counterStore.isHead"
          >
            <i class="fas fa-plus"></i> Tambah Unit Inventaris
          </router-link>
        </div>

        <div class="card-body">
          <div v-if="counterStore.inventoryLoading" class="text-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data inventaris...</p>
          </div>

          <div v-else-if="counterStore.inventoryError" class="alert alert-danger m-3">
            Terjadi kesalahan: {{ counterStore.inventoryError }}
          </div>

          <div v-else-if="counterStore.inventories.length === 0" class="alert alert-info m-3">
            Belum ada data unit inventaris. Silakan tambahkan.
          </div>

          <table v-else class="table table-striped table-valign-middle">
            <thead>
              <tr>
                <th>Nomor Inventaris</th>
                <th>Nama Barang</th>
                <th>Lokasi</th>
                <th>Status</th>
                <th>Tanggal Pengadaan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in counterStore.inventories" :key="item.id">
                <td>{{ item.inventory_number }}</td>
                <td>{{ item.item?.name || '-' }}</td>
                <td>{{ item.room?.name || '-' }} ({{ item.unit?.name || '-' }})</td>
                <td>
                  <span :class="['badge', getStatusBadge(item.status)]">{{ item.status }}</span>
                </td>
                <td>{{ formatDate(item.procurement_date) }}</td>
                <td>
                  <router-link :to="`/inventories/${item.id}`" class="text-muted mr-2">
                    <i class="fas fa-eye"></i>
                  </router-link>
                  <router-link
                    :to="`/inventories/edit/${item.id}`"
                    class="text-muted mr-2"
                    v-if="counterStore.isAdmin || counterStore.isHead"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <a
                    href="#"
                    class="text-muted"
                    @click.prevent="confirmDelete(item.id, item.inventory_number, 'inventory')"
                    v-if="counterStore.isAdmin || counterStore.isHead"
                  >
                    <i class="fas fa-trash"></i>
                  </a>
                  <span v-else class="text-muted">Tidak ada aksi</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Konfirmasi Hapus -->
  <div
    class="modal fade"
    id="deleteConfirmationModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="deleteConfirmationModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Konfirmasi Hapus</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          Apakah Anda yakin ingin menghapus
          <strong>{{ itemToDeleteName }}</strong>?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger" @click="deleteConfirmedItem">Hapus</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useCounterStore } from '../stores/counter';

const counterStore = useCounterStore();
const itemIdToDelete = ref(null);
const itemToDeleteName = ref('');
const itemToDeleteType = ref('inventory'); // default: 'inventory'

const getStatusBadge = (status) => {
  switch (status.toLowerCase()) {
    case 'tersedia':
      return 'badge-success';
    case 'rusak':
      return 'badge-danger';
    case 'dalam perbaikan':
      return 'badge-warning';
    case 'hilang':
      return 'badge-dark';
    case 'dipinjam':
      return 'badge-info';
    case 'tidak tersedia':
      return 'badge-secondary';
    default:
      return 'badge-primary';
  }
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const confirmDelete = (id, name, type = 'inventory') => {
  itemIdToDelete.value = id;
  itemToDeleteName.value = name;
  itemToDeleteType.value = type;
  window.$('#deleteConfirmationModal').modal('show');
};

const deleteConfirmedItem = async () => {
  try {
    await counterStore.deleteInventory(itemIdToDelete.value);
    counterStore.fetchInventories();
    window.$('#deleteConfirmationModal').modal('hide');
  } catch (err) {
    console.error('Gagal hapus:', err);
  }
};

onMounted(() => {
  counterStore.fetchInventories();
});
</script>
