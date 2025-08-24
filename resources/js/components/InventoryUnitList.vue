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
      <!-- Filter -->
      <!-- GANTI SELURUH CARD FILTER dengan ini -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Filter & Pencarian</h5>
        </div>
        <div class="card-body">
          <!-- Row 1: field filter -->
          <div class="row">
            <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-3">
              <label class="mb-1">Cari</label>
              <input
                v-model="counterStore.inventoryTable.params.search"
                class="form-control form-control-sm"
                placeholder="nomor / nama barang"
                @input="debounceApply"
              />
            </div>

            <!-- GANTI bagian Status -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-3">
              <label class="mb-1">Status</label>
              <v-select
                :options="statusOptions"
                v-model="counterStore.inventoryTable.params.status"
                :reduce="s => s"
                placeholder="Semua"
                class="small-vselect"
                :clearable="true"
              />
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-3">
              <label class="mb-1">Unit</label>
              <v-select
                :options="counterStore.locationUnits"
                v-model="selectedUnits"
                label="name"
                :reduce="u => u.id"
                multiple
                placeholder="Semua"
                class="small-vselect"
              />
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-3">
              <label class="mb-1">Lantai</label>
              <v-select
                :options="availableFloors"
                v-model="selectedFloors"
                label="number"
                :reduce="f => f.id"
                multiple
                placeholder="Semua"
                :disabled="!selectedUnits.length"
                class="small-vselect"
              />
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-3">
              <label class="mb-1">Ruangan</label>
              <v-select
                :options="availableRooms"
                v-model="selectedRooms"
                label="name"
                :reduce="r => r.id"
                multiple
                placeholder="Semua"
                :disabled="!selectedFloors.length"
                class="small-vselect"
              />
            </div>

            <!-- Row 2: tombol (auto wrap ke bawah di mobile) -->
            <div class="col-12 col-lg-2 mb-3 d-flex flex-column justify-content-end gap-2">
              <button class="btn btn-primary btn-sm w-100" @click="applyFilter">
                <i class="fas fa-filter"></i> Terapkan
              </button>
              <button class="btn btn-outline-secondary btn-sm w-100" @click="resetFilter">
                <i class="fas fa-undo"></i> Reset
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Grand Total -->
      <div v-if="counterStore.isAdmin && counterStore.grandTotal" class="row mb-3">
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

      <!-- Tabel -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Daftar Unit Inventaris</h3>
        </div>
        <div class="card-body">
          <div v-if="counterStore.inventoryTable.loading" class="text-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data inventaris...</p>
          </div>

          <div v-else-if="counterStore.inventoryError" class="alert alert-danger m-3">
            {{ counterStore.inventoryError }}
          </div>

          <div v-else-if="!counterStore.inventoryTable.items.length" class="alert alert-info m-3">
            Belum ada data unit inventaris.
          </div>

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
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination -->
            <nav>
              <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ disabled: currentPage <= 1 }">
                  <a class="page-link" href="#" @click.prevent="prevPage">Previous</a>
                </li>

                <template v-if="windowStart > 1">
                  <li class="page-item"><a class="page-link" href="#" @click.prevent="goPage(1)">1</a></li>
                  <li v-if="windowStart > 2" class="page-item disabled"><span class="page-link">...</span></li>
                </template>

                <li
                  v-for="p in visiblePages"
                  :key="p"
                  class="page-item"
                  :class="{ active: p === currentPage }"
                >
                  <a class="page-link" href="#" @click.prevent="goPage(p)">{{ p }}</a>
                </li>

                <template v-if="windowEnd < lastPage">
                  <li v-if="windowEnd < lastPage - 1" class="page-item disabled"><span class="page-link">...</span></li>
                  <li class="page-item"><a class="page-link" href="#" @click.prevent="goPage(lastPage)">{{ lastPage }}</a></li>
                </template>

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
  <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
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
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useCounterStore } from '@/stores/counter';
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import axios from 'axios';

const counterStore = useCounterStore();
const itemIdToDelete = ref(null);
const itemToDeleteName = ref('');
const itemToDeleteType = ref('inventory');

// ---- multi-select state ----
const selectedUnits = ref([]);
const selectedFloors = ref([]);
const selectedRooms = ref([]);

// ---- dynamic options ----
const availableFloors = ref([]);
const availableRooms = ref([]);

// ---- data pendukung ----
const statusOptions = ['Ada', 'Rusak', 'Perbaikan', 'Hilang', 'Dipinjam', '-'];

// ---- computed pagination ----
const currentPage = computed(() => counterStore.inventoryTable.params.page);
const lastPage = computed(() =>
  Math.max(
    1,
    Math.ceil(counterStore.inventoryTable.totalRows / counterStore.inventoryTable.params.per_page)
  )
);
const windowSize = 2;
const windowStart = computed(() => Math.max(1, currentPage.value - windowSize));
const windowEnd = computed(() => Math.min(lastPage.value, currentPage.value + windowSize));
const visiblePages = computed(() => {
  const pages = [];
  for (let i = windowStart.value; i <= windowEnd.value; i++) pages.push(i);
  return pages.length ? pages : [1];
});

// ---- watchers ----
watch(selectedUnits, async (newUnits) => {
  selectedFloors.value = [];
  selectedRooms.value = [];
  if (!newUnits.length) {
    availableFloors.value = [];
    return;
  }
  const params = new URLSearchParams();
  newUnits.forEach(u => params.append('unit_id[]', u));
const { data } = await axios.get('/floors/by-units', {
  params: { unit_id: newUnits },   // axios otomatis bikin ?unit_id[]=1&unit_id[]=2
  headers: { Authorization: `Bearer ${counterStore.token}` }
});
  availableFloors.value = data;
});

watch([selectedUnits, selectedFloors], async ([units, floors]) => {
  selectedRooms.value = [];
  const params = new URLSearchParams();

  if (units.length) units.forEach(u => params.append('unit_id[]', u));
  if (floors.length) floors.forEach(f => params.append('floor_id[]', f));

const { data } = await axios.get('/rooms/by-units-floors', {
  params: { unit_id: units, floor_id: floors },
  headers: { Authorization: `Bearer ${counterStore.token}` }
});
  availableRooms.value = data;
});

// ---- methods ----
const applyFilter = () => {
  counterStore.inventoryTable.params.unit_id = selectedUnits.value;
  counterStore.inventoryTable.params.floor_id = selectedFloors.value;
  counterStore.inventoryTable.params.room_id = selectedRooms.value;
  counterStore.inventoryTable.params.page = 1;
  counterStore.fetchInventoryTable();
};

const resetFilter = () => {
  selectedUnits.value = [];
  selectedFloors.value = [];
  selectedRooms.value = [];
  counterStore.resetInventoryTable();
  counterStore.fetchInventoryTable();
};

const debounceApply = (() => {
  let t;
  return () => {
    clearTimeout(t);
    t = setTimeout(applyFilter, 400);
  };
})();

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

<style>
/* Ukuran lebih ringkas untuk v-select */
.small-vselect .vs__dropdown-toggle,
.small-vselect .vs__selected,
.small-vselect .vs__search {
  font-size: 0.875rem;
  min-height: calc(1.5em + 0.5rem + 2px);
}
.small-vselect .vs__actions {
  padding: 2px 6px;
}
</style>