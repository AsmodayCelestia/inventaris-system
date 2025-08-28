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
        <!-- FILTER -->
        <div class="card">
          <div class="card-header">
            <h5>Filter & Pencarian</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <!-- Search -->
              <div class="col-md-3 mb-2">
                <label>Cari</label>
                <input
                  v-model="filters.search"
                  @input="debounceSearch"
                  class="form-control"
                  placeholder="Nama barang / nomor inventaris"
                />
              </div>

              <!-- Rentang Tanggal -->
              <div class="col-md-4 mb-2">
                <label>Rentang Tanggal</label>
                <div class="input-group">
                  <input
                    type="date"
                    v-model="filters.dateFrom"
                    class="form-control"
                    @change="reloadTable"
                  />
                  <div class="input-group-prepend input-group-append">
                    <span class="input-group-text">s/d</span>
                  </div>
                  <input
                    type="date"
                    v-model="filters.dateTo"
                    class="form-control"
                    @change="reloadTable"
                  />
                </div>
              </div>

              <div class="col-md-2 mb-2 align-self-end">
                <button class="btn btn-primary w-100" @click="reloadTable">
                  Terapkan
                </button>
              </div>
              <div class="col-md-2 mb-2 align-self-end">
                <button class="btn btn-secondary w-100" @click="resetFilter">
                  Reset
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- TABEL -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Daftar Maintenance Selesai</h3>
          </div>

          <div class="card-body">
            <!-- Loading -->
            <div v-if="loading" class="text-center p-4">
              <i class="fas fa-spinner fa-spin fa-2x"></i>
              <p class="mt-2">Memuat data...</p>
            </div>

            <!-- Kosong -->
            <div v-else-if="!items.length" class="alert alert-info m-3">
              Belum ada maintenance yang selesai.
            </div>

            <div v-else>
              <table class="table table-striped table-valign-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Nama Barang</th>
                    <th>Nomor Inventaris</th>
                    <th>Penanggung Jawab</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in items" :key="row.id">
                    <td>{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                    <td>{{ row.inventory?.item?.name || '-' }}</td>
                    <td>{{ row.inventory?.inventory_number || '-' }}</td>
                    <td>{{ row.responsible_person?.name ?? '-' }}</td>
                    <td>{{ formatDate(row.inspection_date) }}</td>
                    <td>
                      <span class="badge badge-success">Selesai</span>
                    </td>
                    <td>
                      <router-link :to="`/maintenance/${row.id}`" class="text-muted mr-2">
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link
                        v-if="counter.isAdmin || counter.isHead"
                        :to="`/maintenance/edit/${row.id}`"
                        class="text-muted">
                        <i class="fas fa-edit"></i>
                      </router-link>
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- PAGINATION (mirip halaman Master) -->
              <nav v-if="lastPage > 1">
                <ul class="pagination justify-content-center">
                  <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <a class="page-link" href="#" @click.prevent="prevPage">Previous</a>
                  </li>

                  <template v-if="windowStart > 1">
                    <li class="page-item">
                      <a class="page-link" href="#" @click.prevent="goPage(1)">1</a>
                    </li>
                    <li v-if="windowStart > 2" class="page-item disabled">
                      <span class="page-link">...</span>
                    </li>
                  </template>

                  <li
                    v-for="p in visiblePages"
                    :key="p"
                    class="page-item"
                    :class="{ active: p === currentPage }">
                    <a class="page-link" href="#" @click.prevent="goPage(p)">{{ p }}</a>
                  </li>

                  <template v-if="windowEnd < lastPage">
                    <li v-if="windowEnd < lastPage - 1" class="page-item disabled">
                      <span class="page-link">...</span>
                    </li>
                    <li class="page-item">
                      <a class="page-link" href="#" @click.prevent="goPage(lastPage)">
                        {{ lastPage }}
                      </a>
                    </li>
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
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { useCounterStore } from '@/stores/counter';

const counter = useCounterStore();

/* ---------- state ---------- */
const items   = ref([]);
const loading = ref(false);

const filters = reactive({
  search  : '',
  dateFrom: '',
  dateTo  : '',
});

const pagination = reactive({
  currentPage: 1,
  perPage    : 10,
  total      : 0,
});

/* ---------- computed ---------- */
const currentPage  = computed(() => pagination.currentPage);
const perPage      = computed(() => pagination.perPage);
const lastPage     = computed(() => Math.max(1, Math.ceil(pagination.total / perPage)));

const windowSize   = 2;
const windowStart  = computed(() => Math.max(1, currentPage.value - windowSize));
const windowEnd    = computed(() => Math.min(lastPage.value, currentPage.value + windowSize));
const visiblePages = computed(() => {
  const pages = [];
  for (let i = windowStart.value; i <= windowEnd.value; i++) pages.push(i);
  return pages;
});

/* ---------- methods ---------- */
const fetchItems = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    params.set('per_page', pagination.perPage);
    params.set('page', pagination.currentPage);

    if (filters.search) params.set('search', filters.search);
    if (filters.dateFrom) params.set('date_from', filters.dateFrom);
    if (filters.dateTo) params.set('date_to', filters.dateTo);

    const { data } = await axios.get(
      `${counter.API_BASE_URL}/maintenance/done-datatable?${params}`,
      { headers: { Authorization: `Bearer ${counter.token}` } }
    );
    items.value      = data.data;
    pagination.total = Number(data.recordsFiltered);
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const formatDate = (date) =>
  date
    ? new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      })
    : '-';

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
  filters.search   = '';
  filters.dateFrom = '';
  filters.dateTo   = '';
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

/* ---------- lifecycle ---------- */
onMounted(fetchItems);
</script>