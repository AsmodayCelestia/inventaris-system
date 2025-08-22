<template>
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Data Inventaris</h1></div>
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
      <!-- Filter Row -->
      <div class="card">
        <div class="card-header"><h5>Filter & Pencarian</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3 mb-2">
              <label>Cari</label>
              <input
                v-model="counterStore.inventoryTable.params.search"
                class="form-control"
                placeholder="nomor / nama barang"
              />
            </div>
            <div class="col-md-2 mb-2">
              <label>Status</label>
              <select v-model="counterStore.inventoryTable.params.status" class="form-control">
                <option :value="null">Semua</option>
                <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>
            <!-- <div class="col-md-2 mb-2">
              <label>Lantai</label>
              <select v-model="counterStore.inventoryTable.params.floor_id" class="form-control">
                <option :value="null">Semua</option>
                <option v-for="f in counterStore.floors" :key="f.id" :value="f.id">{{ f.name }}</option>
              </select>
            </div> -->
            <div class="col-md-2 mb-2">
              <label>Unit</label>
              <select v-model="counterStore.inventoryTable.params.unit_id" class="form-control">
                <option :value="null">Semua</option>
                <option v-for="u in counterStore.locationUnits" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <label>Ruangan</label>
              <select v-model="counterStore.inventoryTable.params.room_id" class="form-control">
                <option :value="null">Semua</option>
                <option v-for="r in counterStore.rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
            </div>
            <div class="col-md-1 mb-2 align-self-end">
              <button class="btn btn-primary w-100" @click="applyFilter">Terapkan</button>
            </div>
            <div class="col-md-1 mb-2 align-self-end">
              <button class="btn btn-secondary w-100" @click="resetFilter">Reset</button>
            </div>
          </div>
        </div>
      </div>
<!-- di atas tabel -->
<div v-if="counterStore.isAdmin && counterStore.grandTotal"
     class="row mb-3">
  <div class="col-md-6">
    <div class="small-box bg-info">
      <div class="inner">
<h4>{{ currency(counterStore.grandTotal?.purchase ?? 0) }}</h4>

        <p>Total Nilai Beli (semua data sesuai filter)</p>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="small-box bg-warning">
      <div class="inner">
<h4>{{ currency(counterStore.grandTotal?.depreciation ?? 0) }}</h4>

        <p>Total Depresiasi (semua data sesuai filter)</p>
      </div>
    </div>
  </div>
</div>
      <!-- Data Table -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Daftar Unit Inventaris</h3>
        </div>
        <div class="card-body">
          <!-- Loading -->
          <div v-if="counterStore.inventoryTable.loading" class="text-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data inventaris...</p>
          </div>

          <!-- Error -->
          <div v-else-if="counterStore.inventoryError" class="alert alert-danger m-3">
            {{ counterStore.inventoryError }}
          </div>

          <!-- Empty -->
          <div v-else-if="!counterStore.inventoryTable.items.length" class="alert alert-info m-3">
            Belum ada data unit inventaris.
          </div>

          <!-- Tabel -->
          <div v-else>
            <table class="table table-striped table-valign-middle">
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
                <tr v-for="item in counterStore.inventoryTable.items" :key="item.id">
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
                      v-if="counterStore.isAdmin || counterStore.isHead || counterStore.canUpdatePrice"
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
                    <span v-else class="text-muted"></span>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- ✅ Pagination baru -->
            <nav>
              <ul class="pagination justify-content-center">
                <!-- Previous -->
                <li
                  class="page-item"
                  :class="{ disabled: currentPage <= 1 }"
                >
                  <a class="page-link" href="#" @click.prevent="prevPage">Previous</a>
                </li>

                <!-- First + separator -->
                <template v-if="windowStart > 1">
                  <li class="page-item" :class="{ active: 1 === currentPage }">
                    <a class="page-link" href="#" @click.prevent="goPage(1)">1</a>
                  </li>
                  <li v-if="windowStart > 2" class="page-item disabled">
                    <span class="page-link">...</span>
                  </li>
                </template>

                <!-- Visible window -->
                <li
                  v-for="p in visiblePages"
                  :key="p"
                  class="page-item"
                  :class="{ active: p === currentPage }"
                >
                  <a class="page-link" href="#" @click.prevent="goPage(p)">{{ p }}</a>
                </li>

                <!-- Last + separator -->
                <template v-if="windowEnd < lastPage">
                  <li v-if="windowEnd < lastPage - 1" class="page-item disabled">
                    <span class="page-link">...</span>
                  </li>
                  <li class="page-item" :class="{ active: lastPage === currentPage }">
                    <a class="page-link" href="#" @click.prevent="goPage(lastPage)">{{ lastPage }}</a>
                  </li>
                </template>

                <!-- Next -->
                <li
                  class="page-item"
                  :class="{ disabled: currentPage >= lastPage }"
                >
                  <a class="page-link" href="#" @click.prevent="nextPage">Next</a>
                </li>
              </ul>
            </nav>
          </div>
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
          Apakah Anda yakin ingin menghapus <strong>{{ itemToDeleteName }}</strong>?
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
import { ref, computed, onMounted } from 'vue';
import { useCounterStore } from '@/stores/counter';

const counterStore = useCounterStore();
const itemIdToDelete = ref(null);
const itemToDeleteName = ref('');
const itemToDeleteType = ref('inventory');

// ---- data pendukung ----
const statusOptions = ['Ada', 'Rusak', 'Perbaikan', 'Hilang', 'Dipinjam', '-'];

// ganti SELURUH computed pagination di <script setup>
const currentPage   = computed(() => counterStore.inventoryTable.params.page);
const lastPage      = computed(() =>
  Math.max(
    1,
    Math.ceil(
      counterStore.inventoryTable.totalRows /
        counterStore.inventoryTable.params.per_page
    )
  )
);

const windowSize   = 2;
const windowStart  = computed(() =>
  Math.max(1, currentPage.value - windowSize)
);
const windowEnd    = computed(() =>
  Math.min(lastPage.value, currentPage.value + windowSize)
);
const visiblePages = computed(() => {
  const pages = [];
  for (let i = windowStart.value; i <= windowEnd.value; i++) {
    pages.push(i);
  }
  // jika hasil kosong (lastPage = 0) → force [1]
  return pages.length ? pages : [1];
});

// ---- methods ----
const applyFilter = () => {
  counterStore.inventoryTable.params.page = 1;
  counterStore.fetchInventoryTable();
};
const resetFilter = () => {
  counterStore.resetInventoryTable();
  counterStore.fetchInventoryTable();
};
const prevPage = () => {
  if (currentPage.value > 1) {
    counterStore.inventoryTable.params.page--;
    counterStore.fetchInventoryTable();
  }
};
const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    counterStore.inventoryTable.params.page++;
    counterStore.fetchInventoryTable();
  }
};
const goPage = (p) => {
  counterStore.inventoryTable.params.page = p;
  counterStore.fetchInventoryTable();
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
  return map[status.toLowerCase()] || 'badge-primary';
};

const formatDate = (date) =>
  date
    ? new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      })
    : '-';

const confirmDelete = (id, name, type = 'inventory') => {
  itemIdToDelete.value = id;
  itemToDeleteName.value = name;
  itemToDeleteType.value = type;
  window.$('#deleteConfirmationModal').modal('show');
};

const deleteConfirmedItem = async () => {
  try {
    await counterStore.deleteInventory(itemIdToDelete.value);
    counterStore.fetchInventoryTable();
    window.$('#deleteConfirmationModal').modal('hide');
  } catch (err) {
    console.error('Gagal hapus:', err);
  }
};

const currency = (val) =>
  new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(val);

// ---- lifecycle ----
onMounted(async () => {
  await Promise.all([
    counterStore.fetchFloors(),
    counterStore.fetchLocationUnits(),
    counterStore.fetchRooms(),
  ]);
  counterStore.resetInventoryTable();
  counterStore.fetchInventoryTable();
});
</script>