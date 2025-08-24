<template>
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Activity Log</h1></div>
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
            <!-- Search -->
            <div class="col-md-3 mb-2">
              <label>Cari</label>
              <input v-model="filters.search" @input="debounceSearch" class="form-control" placeholder="teks apa pun"/>
            </div>

            <!-- Date range -->
            <div class="col-md-4 mb-2">
              <label>Rentang Tanggal</label>
              <div class="input-group">
                <input type="date" v-model="filters.dateFrom" class="form-control" @change="reloadTable">
                <div class="input-group-prepend input-group-append"><span class="input-group-text">s/d</span></div>
                <input type="date" v-model="filters.dateTo"   class="form-control" @change="reloadTable">
              </div>
            </div>

            <!-- Model -->
            <div class="col-md-3 mb-2">
              <label>Model</label>
              <v-select
                :options="modelTypeOptions"
                v-model="filters.modelType"
                :reduce="o => o.value"
                label="label"
                multiple
                placeholder="Semua"
                @input="reloadTable"
              />
            </div>

            <!-- Event -->
            <div class="col-md-2 mb-2">
              <label>Event</label>
              <v-select
                :options="eventOptions"
                v-model="filters.event"
                multiple
                placeholder="Semua"
                @input="reloadTable"
              />
            </div>

            <!-- User -->
            <div class="col-md-2 mb-2">
              <label>User</label>
              <v-select
                :options="userOptions"
                v-model="filters.userId"
                label="name"
                :reduce="u => u.id"
                multiple
                placeholder="Semua"
                @input="reloadTable"
              />
            </div>

            <!-- Buttons -->
            <div class="col-md-2 mb-2 align-self-end">
              <button class="btn btn-primary w-100" @click="reloadTable">Terapkan</button>
            </div>
            <div class="col-md-2 mb-2 align-self-end">
              <button class="btn btn-secondary w-100" @click="resetFilter">Reset</button>
            </div>
          </div>
        </div>
      </div>

      <!-- TABLE -->
      <div class="card">
        <div class="card-header"><h3 class="card-title">Daftar Aktivitas</h3></div>
        <div class="card-body">
          <div v-if="loading" class="text-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data aktivitas...</p>
          </div>

          <div v-else-if="!logs.length" class="alert alert-info m-3">Tidak ada aktivitas yang tercatat.</div>

          <div v-else>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>User</th><th>Deskripsi</th><th>Model</th><th>Field Diubah</th><th>Waktu</th><th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in logs" :key="log.id">
                  <td>{{ log.user }}</td>
                  <td>{{ log.description }}</td>
                  <td>{{ log.model }}</td>
                  <td>{{ log.changed_fields }}</td>
                  <td>{{ log.created_at }}</td>
                  <td>
                    <button class="btn btn-sm btn-info" @click="openDetail(log.id)">Detail</button>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination -->
            <nav>
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

  <!-- Modal Detail -->
  <div class="modal fade" id="detailModal" tabindex="-1" ref="detailModal">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detail Perubahan</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div v-html="prettyDetail" class="bg-light p-3 rounded"></div>
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

/* state */
const logs   = ref([]);
const loading = ref(false);
const detailModal  = ref(null);
const prettyDetail = ref('');

const filters = reactive({
  search    : '',
  modelType : [],
  event     : [],
  userId    : [],
  dateFrom  : '',
  dateTo    : '',
});
const pagination = reactive({ currentPage: 1, perPage: 10, total: 0 });

const modelTypeOptions = ref([]);
const userOptions      = ref([]);
const eventOptions     = ['created', 'updated', 'deleted'];

/* computed */
const currentPage  = computed(() => pagination.currentPage);
const lastPage     = computed(() => Math.max(1, Math.ceil(pagination.total / pagination.perPage)));
const windowSize   = 2;
const windowStart  = computed(() => Math.max(1, currentPage.value - windowSize));
const windowEnd    = computed(() => Math.min(lastPage.value, currentPage.value + windowSize));
const visiblePages = computed(() => {
  const pages = [];
  for (let i = windowStart.value; i <= windowEnd.value; i++) pages.push(i);
  return pages.length ? pages : [1];
});

/* methods */
const fetchLogs = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    params.set('page', pagination.currentPage);
    params.set('per_page', pagination.perPage);
    if (filters.search)    params.set('search', filters.search);
    if (filters.dateFrom)  params.set('date_from', filters.dateFrom);
    if (filters.dateTo)    params.set('date_to', filters.dateTo);
    if (filters.modelType.length) params.set('model_type', filters.modelType.join(','));
    if (filters.event.length)     params.set('event', filters.event.join(','));
    if (filters.userId.length)    params.set('user_id', filters.userId.join(','));

    const { data } = await axios.get(`${store.API_BASE_URL}/activity-log/datatable?${params}`, {
      headers: { Authorization: `Bearer ${store.token}` },
    });
    logs.value   = data.data;
    pagination.total = data.recordsTotal;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const loadOptions = async () => {
  try {
    const [models, users] = await Promise.all([
      axios.get(`${store.API_BASE_URL}/activity-log/models`, { headers: { Authorization: `Bearer ${store.token}` } }),
      axios.get(`${store.API_BASE_URL}/activity-log/users`,  { headers: { Authorization: `Bearer ${store.token}` } }),
    ]);
    // Ubah mapping agar value = FQCN, label = basename
    modelTypeOptions.value = Object.entries(models.data).map(([label, value]) => ({
      label,
      value,
    }));
    userOptions.value = Object.entries(users.data).map(([id, name]) => ({
      id: Number(id),
      name,
    }));
  } catch (e) {
    console.error(e);
  }
};

const debounceSearch = (() => {
  let t; return () => { clearTimeout(t); t = setTimeout(reloadTable, 400); };
})();

const reloadTable = () => { pagination.currentPage = 1; fetchLogs(); };
const resetFilter = () => {
  Object.assign(filters, {
    search: '', modelType: [], event: [], userId: [], dateFrom: '', dateTo: '',
  });
  reloadTable();
};
const prevPage = () => { if (pagination.currentPage > 1) { pagination.currentPage--; fetchLogs(); } };
const nextPage = () => { if (pagination.currentPage < lastPage.value) { pagination.currentPage++; fetchLogs(); } };
const goPage   = (p) => { pagination.currentPage = p; fetchLogs(); };

/* detail modal */
const openDetail = async (id) => {
  try {
    const { data } = await axios.get(`${store.API_BASE_URL}/activity-log/${id}/detail`, {
      headers: { Authorization: `Bearer ${store.token}` },
    });
    prettyDetail.value = formatDetail(data);
    await nextTick();
    new Modal(detailModal.value).show();
  } catch (e) {
    console.error(e);
    prettyDetail.value = '<div class="text-danger">Gagal memuat detail</div>';
    await nextTick();
    new Modal(detailModal.value).show();
  }
};

const formatDetail = (raw) => {
  const { event, causer, subject, created_at, old, new: newData } = raw;
  const labels = {}; // isi dengan label custom jika perlu

  const map = (obj) => Object.entries(obj || {})
    .map(([k, v]) => {
      const label = labels[k] || k;
      let val = v;
      if (k.endsWith('_at') || k === 'procurement_date') val = new Date(v).toLocaleDateString('id-ID');
      if (k === 'purchase_price') val = `Rp ${Number(v).toLocaleString('id-ID')}`;
      return `<tr><td>${label}</td><td>${val ?? '-'}</td></tr>`;
    });

  let html = `
    <div class="mb-2">
      <strong>Aksi:</strong> <span class="badge badge-primary">${event.toUpperCase()}</span><br>
      <strong>Pengguna:</strong> ${causer || 'System'}<br>
      <strong>Subject:</strong> ${subject}<br>
      <strong>Waktu:</strong> ${new Date(created_at).toLocaleString('id-ID')}
    </div>`;

  if (event === 'updated' && Object.keys(old).length && Object.keys(newData).length) {
    const keys = [...new Set([...Object.keys(old), ...Object.keys(newData)])];
    const rows = keys.map(k => {
      const label = labels[k] || k;
      let oVal = old[k];
      let nVal = newData[k];
      if (k.endsWith('_at') || k === 'procurement_date') {
        oVal = oVal ? new Date(oVal).toLocaleDateString('id-ID') : '-';
        nVal = nVal ? new Date(nVal).toLocaleDateString('id-ID') : '-';
      }
      if (k === 'purchase_price') {
        oVal = oVal ? `Rp ${Number(oVal).toLocaleString('id-ID')}` : '-';
        nVal = nVal ? `Rp ${Number(nVal).toLocaleString('id-ID')}` : '-';
      }
      return `<tr><td>${label}</td><td>${oVal ?? '-'}</td><td>${nVal ?? '-'}</td></tr>`;
    }).join('');
    html += `<h6 class="mt-3">Perubahan:</h6>
             <table class="table table-sm table-bordered">
               <thead><tr><th>Field</th><th>Lama</th><th>Baru</th></tr></thead>
               <tbody>${rows}</tbody>
             </table>`;
  } else if (event === 'created') {
    html += `<h6 class="mt-3">Data baru:</h6>
             <table class="table table-sm table-bordered"><tbody>${map(newData).join('')}</tbody></table>`;
  } else if (event === 'deleted') {
    html += `<h6 class="mt-3">Data lama:</h6>
             <table class="table table-sm table-bordered"><tbody>${map(old).join('')}</tbody></table>`;
  }
  return html;
};

/* lifecycle */
onMounted(() => { loadOptions(); fetchLogs(); });
</script>