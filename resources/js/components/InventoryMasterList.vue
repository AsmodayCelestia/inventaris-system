<template>
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Master Barang</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><router-link to="/dashboard">Home</router-link></li>
            <li class="breadcrumb-item active">Master Barang</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Daftar Master Barang</h3>
          <router-link
            :to="{ name: 'InventoryMasterCreate' }"
            class="btn btn-primary btn-sm"
            v-if="counterStore.isAdmin"
          >
            <i class="fas fa-plus"></i> Tambah Master Barang
          </router-link>
        </div>

        <div class="card-body">
          <div v-if="counterStore.inventoryItemLoading" class="text-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data master barang...</p>
          </div>

          <div v-else-if="counterStore.inventoryItemError" class="alert alert-danger m-3">
            Terjadi kesalahan: {{ counterStore.inventoryItemError }}
          </div>

          <div v-else-if="counterStore.inventoryItems.length === 0" class="alert alert-info m-3">
            Belum ada data master barang. Silakan tambahkan.
          </div>

          <table v-else class="table table-striped table-valign-middle">
            <thead>
              <tr>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Merk</th>
                <th>Kategori</th>
                <th>Jenis</th>
                <th>Tahun Produksi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in counterStore.inventoryItems" :key="item.id">
                <td>{{ item.item_code }}</td>
                <td>{{ item.name }}</td>
                <td>{{ item.brand?.name || '-' }}</td>
                <td>{{ item.category?.name || '-' }}</td>
                <td>{{ item.type?.name || '-' }}</td>
                <td>{{ item.manufacture_year || '-' }}</td>
                <td>
                <router-link
                  :to="{ name: 'inventory-items.edit', params: { id: item.id } }"
                  class="text-muted mr-2"
                >
                  <i class="fas fa-edit"></i>
                </router-link>
                  <a
                    href="#"
                    class="text-muted"
                    @click.prevent="confirmDelete(item.id, item.name)"
                    v-if="counterStore.isAdmin"
                  >
                    <i class="fas fa-trash"></i>
                  </a>
                  <span v-else class="text-muted"></span>
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
import { ref, onMounted } from 'vue';
import { useCounterStore } from '../stores/counter';

const counterStore = useCounterStore();
const itemIdToDelete = ref(null);
const itemToDeleteName = ref('');

const confirmDelete = (id, name) => {
  itemIdToDelete.value = id;
  itemToDeleteName.value = name;
  window.$('#deleteConfirmationModal').modal('show');
};

const deleteConfirmedItem = async () => {
  try {
    await counterStore.deleteInventoryItem(itemIdToDelete.value);
    counterStore.fetchInventoryItems();
    window.$('#deleteConfirmationModal').modal('hide');
  } catch (err) {
    console.error('Gagal hapus master barang:', err);
  }
};

onMounted(() => {
  counterStore.fetchInventoryItems();
});
</script>
