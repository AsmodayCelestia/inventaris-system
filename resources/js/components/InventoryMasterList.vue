<template>
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Master Barang</h1></div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <!-- FILTER -->
      <div class="card">
        <div class="card-header"><h5>Filter & Pencarian</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3 mb-2">
              <label>Cari</label>
              <input v-model="filters.search" @input="debounceSearch" class="form-control" placeholder="Kode / Nama"/>
            </div>

            <div class="col-md-3 mb-2">
              <label>Merk</label>
              <v-select :options="brandOptions" v-model="filters.brandId"
                        label="name" :reduce="b => b.id" multiple placeholder="Semua"
                        @input="reloadTable" />
            </div>

            <div class="col-md-3 mb-2">
              <label>Kategori</label>
              <v-select :options="categoryOptions" v-model="filters.categoryId"
                        label="name" :reduce="c => c.id" multiple placeholder="Semua"
                        @input="reloadTable" />
            </div>

            <div class="col-md-3 mb-2">
              <label>Jenis</label>
              <v-select :options="typeOptions" v-model="filters.typeId"
                        label="name" :reduce="t => t.id" multiple placeholder="Semua"
                        @input="reloadTable" />
            </div>

            <div class="col-md-2 mb-2">
              <label>Tahun Produksi</label>
              <input type="number" min="1900" :max="new Date().getFullYear()"
                     v-model.number="filters.manufactureYear" class="form-control"
                     placeholder="Semua" @change="reloadTable"/>
            </div>

            <div class="col-md-2 mb-2 align-self-end">
              <button class="btn btn-primary w-100" @click="reloadTable">Terapkan</button>
            </div>
            <div class="col-md-2 mb-2 align-self-end">
              <button class="btn btn-secondary w-100" @click="resetFilter">Reset</button>
            </div>
          </div>
        </div>
      </div>

      <!-- TABEL -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Daftar Master Barang</h3>
          <router-link :to="{ name: 'InventoryMasterCreate' }"
                       class="btn btn-primary btn-sm"
                       v-if="store.isAdmin || store.isHead">
            <i class="fas fa-plus"></i> Tambah
          </router-link>
        </div>

        <div class="card-body">
          <div v-if="loading" class="text-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data...</p>
          </div>

          <div v-else-if="!items.length" class="alert alert-info m-3">
            Tidak ada data master barang.
          </div>

          <div v-else>
            <table class="table table-striped table-valign-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Kode</th>
                  <th>Nama</th>
                  <th>Merk</th>
                  <th>Kategori</th>
                  <th>Jenis</th>
                  <th>Qty</th>
                  <th>Tahun</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in items" :key="item.id">
                  <td>{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                  <td>{{ item.item_code }}</td>
                  <td>{{ item.name }}</td>
                  <td>{{ item.brand || '-' }}</td>
                  <td>{{ item.category || '-' }}</td>
                  <td>{{ item.type || '-' }}</td>
                  <td>{{ item.quantity }}</td>
                  <td>{{ item.manufacture_year || '-' }}</td>
                  <td>
                    <router-link :to="{ name: 'inventory-items.edit', params: { id: item.id } }"
                                 class="text-muted mr-2">
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <a href="#" class="text-muted" @click.prevent="confirmDelete(item.id, item.name)"
                       v-if="store.isAdmin || store.isHead">
                      <i class="fas fa-trash"></i>
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- PAGINATION -->
            <nav v-if="lastPage > 1">
              <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                  <a class="page-link" href="#" @click.prevent="prevPage">Previous</a>
                </li>

                <template v-if="windowStart > 1">
                  <li class="page-item"><a class="page-link" href="#" @click.prevent="goPage(1)">1</a></li>
                  <li v-if="windowStart > 2" class="page-item disabled"><span class="page-link">...</span></li>
                </template>

                <li v-for="p in visiblePages" :key="p"
                    class="page-item" :class="{ active: p === currentPage }">
                  <a class="page-link" href="#" @click.prevent="goPage(p)">{{ p }}</a>
                </li>

                <template v-if="windowEnd < lastPage">
                  <li v-if="windowEnd < lastPage - 1" class="page-item disabled"><span class="page-link">...</span></li>
                  <li class="page-item"><a class="page-link" href="#" @click.prevent="goPage(lastPage)">{{ lastPage }}</a></li>
                </template>

                <li class="page-item" :class="{ disabled: currentPage === lastPage }">
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
  <div class="modal fade" id="deleteModal" tabindex="-1" ref="deleteModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Hapus Master Barang</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          Yakin hapus <strong>{{ itemToDeleteName }}</strong> ?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger" @click="deleteConfirmed">Hapus</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import { useCounterStore } from '@/stores/counter';

const store = useCounterStore();

/* ---------- state ---------- */
const items   = ref([]);
const loading = ref(false);
const deleteModal = ref(null);
let deleteModalInst = null;

const filters = reactive({
  search         : '',
  brandId        : [],
  categoryId     : [],
  typeId         : [],
  manufactureYear: '',
});

const pagination = reactive({
  currentPage: 1,
  perPage    : 10,
  total      : 0,
});

/* ---------- computed ---------- */
const currentPage  = computed(() => pagination.currentPage);
const perPage      = computed(() => pagination.perPage);
const lastPage = computed(() => {
  const total = Number(pagination.total);
  const per = Number(pagination.perPage);
  console.log('total', total, 'per', per);
  return Math.max(1, Math.ceil(total / per));
});
const windowSize   = 2;
const windowStart  = computed(() => Math.max(1, currentPage.value - windowSize));
const windowEnd    = computed(() => Math.min(lastPage.value, currentPage.value + windowSize));
const visiblePages = computed(() => {
  const pages = [];
  for (let i = windowStart.value; i <= windowEnd.value; i++) pages.push(i);
  return pages;
});

/* ---------- refs options ---------- */
const brandOptions    = ref([]);
const categoryOptions = ref([]);
const typeOptions     = ref([]);

/* ---------- refs delete ---------- */
const itemIdToDelete   = ref(null);
const itemToDeleteName = ref('');

/* ---------- methods ---------- */
const fetchItems = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    params.set('per_page', pagination.perPage);
    params.set('page', pagination.currentPage);

    if (filters.search) params.set('search[value]', filters.search);
    if (filters.brandId.length) params.set('brand_id', filters.brandId.join(','));
    if (filters.categoryId.length) params.set('category_id', filters.categoryId.join(','));
    if (filters.typeId.length) params.set('type_id', filters.typeId.join(','));
    if (filters.manufactureYear) params.set('manufacture_year', filters.manufactureYear);

    const { data } = await axios.get(`${store.API_BASE_URL}/inventory-items-datatable?${params}`, {
      headers: { Authorization: `Bearer ${store.token}` },
    });
    items.value = data.data;
pagination.total = Number(data.recordsFiltered);
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const loadFilterOptions = async () => {
  try {
    const [brands, categories, types] = await Promise.all([
      axios.get(`${store.API_BASE_URL}/brands`, { headers: { Authorization: `Bearer ${store.token}` } }),
      axios.get(`${store.API_BASE_URL}/categories`, { headers: { Authorization: `Bearer ${store.token}` } }),
      axios.get(`${store.API_BASE_URL}/item-types`, { headers: { Authorization: `Bearer ${store.token}` } }),
    ]);
    brandOptions.value = brands.data;
    categoryOptions.value = categories.data;
    typeOptions.value = types.data;
  } catch (e) {
    console.error(e);
  }
};

const debounceSearch = (() => {
  let t;
  return () => {
    clearTimeout(t);
    t = setTimeout(reloadTable, 400);
  };
})();

const reloadTable = () => {
  pagination.currentPage = 1;
  fetchItems();
};

const resetFilter = () => {
  Object.assign(filters, {
    search: '',
    brandId: [],
    categoryId: [],
    typeId: [],
    manufactureYear: '',
  });
  reloadTable();
};

const prevPage = () => {
  if (pagination.currentPage > 1) {
    pagination.currentPage--;
    fetchItems();
  }
};

const nextPage = () => {
  if (pagination.currentPage < lastPage.value) {
    pagination.currentPage++;
    fetchItems();
  }
};

const goPage = (p) => {
  pagination.currentPage = p;
  fetchItems();
};

const confirmDelete = (id, name) => {
  itemIdToDelete.value = id;
  itemToDeleteName.value = name;
  deleteModalInst = new Modal(deleteModal.value);
  deleteModalInst.show();
};

const deleteConfirmed = async () => {
  try {
    await axios.delete(`${store.API_BASE_URL}/inventory-items/${itemIdToDelete.value}`, {
      headers: { Authorization: `Bearer ${store.token}` },
    });
    fetchItems();
    deleteModalInst.hide();
  } catch (e) {
    console.error(e);
  }
};

/* ---------- lifecycle ---------- */
onMounted(() => {
  loadFilterOptions();
  fetchItems();
});
</script>