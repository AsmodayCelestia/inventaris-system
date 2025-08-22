<template>

    <div class="container-fluid">
      <!-- 📊 Ringkasan Statistik -->
      <div class="row mb-4">
        <SummaryCard icon="fas fa-box" color="info" title="Total Inventaris" :value="stats.total" />
        <SummaryCard icon="fas fa-exclamation-triangle" color="warning" title="Barang Rusak" :value="stats.rusak" />
        <SummaryCard icon="fas fa-tools" color="danger" title="Maintenance Aktif" :value="stats.maintenanceAktif" />
        <SummaryCard icon="fas fa-users" color="success" title="User Aktif Hari Ini" :value="stats.userAktif" />
      </div>

      <!-- 🆕 Filter Cepat -->
      <div class="card mb-4">
        <div class="card-header">
          <strong>🔎 Filter Cepat</strong>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <input v-model="filters.nama" class="form-control" placeholder="Cari nama barang" />
            </div>
            <div class="col-md-2">
              <input v-model="filters.lokasi" class="form-control" placeholder="Lokasi/Ruangan" />
            </div>
            <div class="col-md-2">
              <input v-model="filters.kategori" class="form-control" placeholder="Kategori" />
            </div>
            <div class="col-md-2">
              <select v-model="filters.status" class="form-select">
                <option value="">Status</option>
                <option>baik</option>
                <option>rusak</option>
                <option>maintenance</option>
              </select>
            </div>
            <div class="col-md-3">
              <input v-model="filters.petugas" class="form-control" placeholder="Petugas maintenance" />
            </div>
          </div>
        </div>
      </div>

      <!-- 📦 Inventaris Terbaru -->
      <div class="card mb-4">
        <div class="card-header"><strong>📦 Inventaris Terbaru</strong></div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Nama</th><th>Lokasi</th><th>Kategori</th><th>Tanggal Masuk</th><th>Status</th><th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in inventarisTerbaru" :key="item.id">
                <td>{{ item.nama }}</td>
                <td>{{ item.lokasi }}</td>
                <td>{{ item.kategori }}</td>
                <td>{{ item.tanggal_masuk }}</td>
                <td><span class="badge bg-secondary">{{ item.status }}</span></td>
                <td>
                  <button class="btn btn-sm btn-info me-1">🔍</button>
                  <button class="btn btn-sm btn-warning">✏️</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 🔧 Maintenance Terakhir -->
      <div class="card mb-4">
        <div class="card-header"><strong>🔧 Laporan Maintenance Terakhir</strong></div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Nama Barang</th><th>Tgl Inspeksi</th><th>Status</th><th>Petugas</th><th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in maintenanceTerbaru" :key="m.id" @click="showDetail(m)">
                <td>{{ m.nama_barang }}</td>
                <td>{{ m.tanggal }}</td>
                <td><span class="badge bg-info">{{ m.status }}</span></td>
                <td>{{ m.petugas }}</td>
                <td>{{ m.notes }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 📥 Permintaan & Approval -->
      <div class="card mb-4">
        <div class="card-header"><strong>📥 Permintaan & Approval</strong></div>
        <div class="card-body">
          <ul>
            <li>📝 <strong>{{ approval.pending }}</strong> permintaan pending approval</li>
            <li>🔧 <strong>{{ approval.permintaanMaintenance }}</strong> request maintenance baru</li>
            <li>🗑️ <strong>{{ approval.penghapusan }}</strong> barang dalam proses penghapusan</li>
          </ul>
        </div>
      </div>

      <!-- 📈 Grafik -->
      <div class="row">
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header">📊 Status Barang</div>
            <div class="card-body">
              <canvas id="statusChart" height="200"></canvas>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header">📆 Maintenance per Bulan</div>
            <div class="card-body">
              <canvas id="maintChart" height="200"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- ⚡ Akses Cepat -->
      <div class="card mb-5">
        <div class="card-header"><strong>⚡ Akses Cepat</strong></div>
        <div class="card-body d-flex flex-wrap gap-3">
          <button class="btn btn-primary">➕ Tambah Inventaris</button>
          <button class="btn btn-warning">🔄 Jadwalkan Maintenance</button>
          <button class="btn btn-success">📤 Ekspor Laporan</button>
          <button class="btn btn-secondary">🔐 Kelola Pengguna</button>
        </div>
      </div>
    </div>
</template>

<script setup>
import Layout from '@/components/Layout.vue';
import SummaryCard from '@/components/SummaryCard.vue';
import { onMounted, ref } from 'vue';
import axios from 'axios';
import Chart from 'chart.js/auto';

const stats = ref({ total: 0, rusak: 0, maintenanceAktif: 0, userAktif: 0 });
const inventarisTerbaru = ref([]);
const maintenanceTerbaru = ref([]);
const approval = ref({ pending: 0, permintaanMaintenance: 0, penghapusan: 0 });

const filters = ref({
  nama: '', lokasi: '', kategori: '', status: '', petugas: ''
});

const fetchDashboard = async () => {
  const res = await axios.get('/dashboard');
  const data = res.data;

  stats.value = data.stats;
  inventarisTerbaru.value = data.inventarisTerbaru;
  maintenanceTerbaru.value = data.maintenanceTerbaru;
  approval.value = data.approval;

  renderCharts(data.chart);
};

const renderCharts = (chartData) => {
  new Chart(document.getElementById('statusChart'), {
    type: 'pie',
    data: {
      labels: ['Baik', 'Rusak', 'Maintenance', 'Hilang'],
      datasets: [{ data: chartData.status, backgroundColor: ['#28a745', '#dc3545', '#ffc107', '#6c757d'] }]
    }
  });

  new Chart(document.getElementById('maintChart'), {
    type: 'bar',
    data: {
      labels: chartData.maintenance.labels,
      datasets: [{ label: 'Maintenance', data: chartData.maintenance.data, backgroundColor: '#007bff' }]
    }
  });
};

onMounted(fetchDashboard);

const showDetail = (item) => {
  alert(`Detail Maintenance:\nBarang: ${item.nama_barang}\nStatus: ${item.status}\nPetugas: ${item.petugas}`);
};
</script>
