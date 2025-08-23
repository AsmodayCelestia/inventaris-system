<template>
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Activity Log</h1></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item">
              <router-link to="/dashboard">Home</router-link>
            </li>
            <li class="breadcrumb-item active">Activity Log</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <!-- Filter -->
      <div class="card">
        <div class="card-header"><h5>Filter & Pencarian</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4 mb-2">
              <label>Cari</label>
              <input
                v-model="filters.search"
                @input="debounceSearch"
                class="form-control"
                placeholder="user / model / deskripsi"
              />
            </div>
            <div class="col-md-3 mb-2">
              <label>Model</label>
              <select v-model="filters.modelType" @change="reloadTable" class="form-control">
                <option value="">Semua Model</option>
                <option v-for="m in modelTypeOptions" :key="m" :value="m">{{ m }}</option>
              </select>
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

      <!-- Tabel -->
      <div class="card">
        <div class="card-header"><h3 class="card-title">Daftar Aktivitas</h3></div>
        <div class="card-body">
          <div v-if="loading" class="text-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data aktivitas...</p>
          </div>

          <div v-else-if="!logs.length" class="alert alert-info m-3">
            Tidak ada aktivitas yang tercatat.
          </div>

          <div v-else>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Deskripsi</th>
                  <th>Model</th>
                  <th>Field Diubah</th>
                  <th>Waktu</th>
                  <th>Aksi</th>
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
                    <button class="btn btn-sm btn-info" @click="openDetail(log.id)">
                      Detail
                    </button>
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
                  :class="{ active: p === currentPage }"
                >
                  <a class="page-link" href="#" @click.prevent="goPage(p)">{{ p }}</a>
                </li>

                <template v-if="windowEnd < lastPage">
                  <li v-if="windowEnd < lastPage - 1" class="page-item disabled">
                    <span class="page-link">...</span>
                  </li>
                  <li class="page-item">
                    <a class="page-link" href="#" @click.prevent="goPage(lastPage)">{{ lastPage }}</a>
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
import { useCounterStore } from '@/stores/counter';

const store = useCounterStore();

/* reaktif state */
const logs            = ref([]);
const loading         = ref(false);
const detailModal     = ref(null);
const prettyDetail    = ref('');
const filters         = reactive({ search: '', modelType: '' });
const pagination      = reactive({ currentPage: 1, perPage: 10, total: 0 });
const modelTypeOptions = ref([]);

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
    const params = {
      page: pagination.currentPage,
      per_page: pagination.perPage,
      search: filters.search,
      model_type: filters.modelType,
    };
    const { data } = await axios.get(`${store.API_BASE_URL}/activity-log/datatable`, {
      params,
      headers: { Authorization: `Bearer ${store.token}` },
    });
    logs.value   = data.data;
    pagination.total = data.recordsTotal;
    modelTypeOptions.value = [...new Set(logs.value.map(l => l.model?.split(' ')[0]).filter(Boolean))];
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const debounceSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => reloadTable(), 400);
  };
})();

const reloadTable = () => { pagination.currentPage = 1; fetchLogs(); };
const resetFilter = () => { filters.search = ''; filters.modelType = ''; reloadTable(); };
const prevPage    = () => { if (pagination.currentPage > 1) { pagination.currentPage--; fetchLogs(); } };
const nextPage    = () => { if (pagination.currentPage < lastPage.value) { pagination.currentPage++; fetchLogs(); } };
const goPage      = (p) => { pagination.currentPage = p; fetchLogs(); };

const formatDetail = (raw) => {
  const { event, causer, subject, created_at, old, new: newData } = raw;

  const labels = {
    pj_id: 'Penanggung Jawab',
    status: 'Status Barang',
    room_id: 'Ruangan',
    unit_id: 'Unit',
    image_path: 'Gambar',
    next_due_km: 'KM Service Berikutnya',
    qr_code_path: 'QR Code',
    next_due_date: 'Tanggal Service Berikutnya',
    purchase_price: 'Harga Beli',
    last_checked_at: 'Terakhir Dicek',
    inventory_number: 'Nomor Inventaris',
    procurement_date: 'Tanggal Pengadaan',
    inventory_item_id: 'Jenis Barang',
    acquisition_source: 'Sumber Perolehan',
    last_maintenance_at: 'Terakhir Maintenance',
    expected_replacement: 'Perkiraan Penggantian',
    last_odometer_reading: 'KM Terakhir',
    estimated_depreciation: 'Depresiasi',
    maintenance_frequency_type: 'Tipe Frekuensi',
    maintenance_frequency_value: 'Nilai Frekuensi'
  };

  const map = obj =>
    Object.entries(obj || {})
      .filter(([, v]) => v !== null)
      .map(([k, v]) => {
        const label = labels[k] || k;
        let prettyValue = v;
        if (k.endsWith('_at') || k === 'procurement_date' || k === 'expected_replacement')
          prettyValue = new Date(v).toLocaleDateString('id-ID');
        if (k === 'purchase_price') prettyValue = `Rp ${Number(v).toLocaleString('id-ID')}`;
        return { label, value: prettyValue };
      });

  let html = `
    <div class="mb-2">
      <strong>Aksi:</strong> <span class="badge badge-primary">${(event || 'N/A').toUpperCase()}</span><br>
      <strong>Pengguna:</strong> ${causer || 'System'}<br>
      <strong>Subject:</strong> ${subject || '-'}<br>
      <strong>Waktu:</strong> ${created_at ? new Date(created_at).toLocaleString('id-ID') : '-'}
    </div>
  `;

  const oldEntries = map(old);
  const newEntries = map(newData);

if (event === 'updated' && Object.keys(old || {}).length && Object.keys(newData || {}).length) {
  const changes = [];

  // gabungkan semua key
  const allKeys = [...new Set([...Object.keys(old), ...Object.keys(newData)])];

  for (const key of allKeys) {
    const label = labels[key] || key;
    let oldVal = old[key];
    let newVal = newData[key];

    // format value
    if (key.endsWith('_at') || key === 'procurement_date' || key === 'expected_replacement') {
      oldVal = oldVal ? new Date(oldVal).toLocaleDateString('id-ID') : '-';
      newVal = newVal ? new Date(newVal).toLocaleDateString('id-ID') : '-';
    }
    if (key === 'purchase_price') {
      oldVal = oldVal ? `Rp ${Number(oldVal).toLocaleString('id-ID')}` : '-';
      newVal = newVal ? `Rp ${Number(newVal).toLocaleString('id-ID')}` : '-';
    }

    if (oldVal !== newVal) {
      changes.push(`<tr><td>${label}</td><td>${oldVal ?? '-'}</td><td>${newVal ?? '-'}</td></tr>`);
    }
  }

  if (changes.length) {
    html += `<h6 class="mt-3">Perubahan:</h6>
             <table class="table table-sm table-bordered">
               <thead><tr><th width="30%">Field</th><th width="35%">Lama</th><th width="35%">Baru</th></tr></thead>
               <tbody>${changes.join('')}</tbody>
             </table>`;
  } else {
    html += `<div class="alert alert-info mt-3">Tidak ada perubahan.</div>`;
  }
}
  return html;
};

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
    prettyDetail.value = `<div class="text-danger">Gagal memuat detail</div>`;
    await nextTick();
    new Modal(detailModal.value).show();
  }
};

/* lifecycle */
onMounted(() => fetchLogs());
</script>