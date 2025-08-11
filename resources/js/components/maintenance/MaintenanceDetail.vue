<template>
  <div>
    <!-- Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Detail Laporan Maintenance</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link to="/dashboard">Home</router-link>
              </li>
              <li class="breadcrumb-item">
                <router-link to="/maintenance/list">Maintenance</router-link>
              </li>
              <li class="breadcrumb-item active">Detail</li>
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
            <h3 class="card-title">Data Laporan</h3>
            <div class="card-tools">
              <router-link
                :to="`/maintenance/edit/${record.id}`"
                class="btn btn-sm btn-warning"
              >
                <i class="fas fa-edit"></i> Edit
              </router-link>
            </div>
          </div>

          <div class="card-body">
            <div v-if="loading" class="text-center p-4">
              <i class="fas fa-spinner fa-spin fa-2x"></i>
              <p class="mt-2">Memuat data...</p>
            </div>

            <div v-else-if="error" class="alert alert-danger">
              {{ error }}
            </div>

            <div v-else>
              <!-- Tabel informasi -->
              <table class="table table-bordered">
                <tr>
                  <th style="width: 200px">Nomor Laporan</th>
                  <td>{{ record.id }}</td>
                </tr>
                <tr>
                  <th>Tanggal Pemeriksaan</th>
                  <td>{{ record.inspection_date }}</td>
                </tr>
                <tr>
                  <th>Status</th>
                  <td>
                    <span
                      :class="{
                        'badge badge-secondary': record.status === 'planning',
                        'badge badge-success': record.status === 'done',
                      }"
                      >{{ record.status_label || record.status }}</span
                    >
                  </td>
                </tr>
                <tr>
                  <th>Masalah yang Ditemukan</th>
                  <td>
                    <pre class="mb-0">{{ record.issue_found || "-" }}</pre>
                  </td>
                </tr>
                <tr>
                  <th>Tindakan Perbaikan</th>
                  <td>
                    <pre class="mb-0">{{ record.solution_taken || "-" }}</pre>
                  </td>
                </tr>
                <tr>
                  <th>Catatan Tambahan</th>
                  <td>
                    <pre class="mb-0">{{ record.notes || "-" }}</pre>
                  </td>
                </tr>
              </table>

              <!-- Foto -->
              <h5 class="mt-4 mb-2">Foto-foto</h5>
              <div class="row">
                <div
                  v-for="(photo, idx) in photos"
                  :key="idx"
                  class="col-md-4 mb-3"
                >
                  <div class="border p-2 rounded text-center">
                    <img
                      :src="photo"
                      alt="Foto"
                      class="img-fluid"
                      style="max-height: 200px"
                    />
                    <div class="mt-1 text-muted">Foto {{ idx + 1 }}</div>
                  </div>
                </div>
                <div v-if="!photos.length" class="text-muted">
                  Tidak ada foto.
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer">
            <router-link to="/maintenance/list" class="btn btn-secondary">
              Kembali
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useCounterStore } from "@/stores/counter";

const route   = useRoute();
const counter = useCounterStore();

const loading = ref(true);
const error   = ref(null);
const record  = ref({});

onMounted(async () => {
  try {
    record.value = await counter.fetchMaintenanceDetail(route.params.id);
  } catch (e) {
    error.value = e?.response?.data?.message || "Gagal memuat data.";
  } finally {
    loading.value = false;
  }
});

// foto yang ada di record (path full URL)
const photos = computed(() =>
  ["photo_1", "photo_2", "photo_3"]
    .map((key) => record.value[key])
    .filter(Boolean)
);
</script>