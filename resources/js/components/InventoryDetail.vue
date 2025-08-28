<!-- InventoryDetail.vue – versi anti-crash -->
<template>
  <div>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1 class="m-0">Detail Inventaris</h1></div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><router-link to="/dashboard">Home</router-link></li>
              <li class="breadcrumb-item"><router-link to="/inventories">Inventaris</router-link></li>
              <li class="breadcrumb-item active">Detail</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              Informasi Unit Inventaris:
              {{ inventory?.inventory_number ?? 'Memuat...' }}
            </h3>
          </div>

          <div class="card-body">
            <!-- loading -->
            <div v-if="loading" class="text-center p-4">
              <i class="fas fa-spinner fa-spin fa-2x"></i>
              <p class="mt-2">Memuat detail inventaris...</p>
            </div>

            <!-- error -->
            <div v-else-if="error" class="alert alert-danger m-3">
              Terjadi kesalahan: {{ error }}
            </div>

            <!-- data tidak ada -->
            <div v-else-if="!inventory" class="alert alert-info m-3">
              Data inventaris tidak ditemukan.
            </div>

            <!-- konten utama (hanya tampil kalau sudah ada data) -->
            <div v-else>
              <div v-if="!isPublic">
                <!-- detail lengkap -->
                <div class="row">
                  <div class="col-md-6">
                    <dl class="row">
                      <dt class="col-sm-4">Nomor Inventaris:</dt>
                      <dd class="col-sm-8">{{ inventory.inventory_number }}</dd>

                      <dt class="col-sm-4">Nama Barang:</dt>
                      <dd class="col-sm-8">{{ inventory.item?.name || '-' }}</dd>

                      <dt class="col-sm-4">Kode Barang:</dt>
                      <dd class="col-sm-8">{{ inventory.item?.item_code || '-' }}</dd>

                      <dt class="col-sm-4">Sumber Akuisisi:</dt>
                      <dd class="col-sm-8">{{ inventory.acquisition_source }}</dd>

                      <dt class="col-sm-4">Tanggal Pengadaan:</dt>
                      <dd class="col-sm-8">{{ formatDate(inventory.procurement_date) }}</dd>

                      <dt v-if="counterStore.canSeePrice" class="col-sm-4">Harga Pembelian:</dt>
                      <dd v-if="counterStore.canSeePrice" class="col-sm-8">{{ formatCurrency(inventory.purchase_price) }}</dd>

                      <dt v-if="counterStore.canSeePrice" class="col-sm-4">Estimasi Depresiasi:</dt>
                      <dd v-if="counterStore.canSeePrice" class="col-sm-8">{{ inventory.estimated_depreciation ? formatCurrency(inventory.estimated_depreciation) : '-' }}</dd>

                      <dt class="col-sm-4">Status:</dt>
                      <dd class="col-sm-8">
                        <span :class="['badge', getStatusBadge(inventory.status)]">{{ inventory.status }}</span>
                      </dd>

                      <dt class="col-sm-4">Lokasi:</dt>
                      <dd class="col-sm-8">{{ inventory.room?.name || '-' }} ({{ inventory.unit?.name || '-' }})</dd>

                      <dt class="col-sm-4">Pengawas Ruangan:</dt>
                      <dd class="col-sm-8">{{ inventory.room?.location_person_in_charge?.name || '-' }}</dd>

                      <dt class="col-sm-4">Tanggal Estimasi Penggantian:</dt>
                      <dd class="col-sm-8">{{ formatDate(inventory.expected_replacement) }}</dd>

                      <dt class="col-sm-4">Tanggal Terakhir Dicek:</dt>
                      <dd class="col-sm-8">{{ formatDate(inventory.last_checked_at) }}</dd>

                      <dt class="col-sm-4">Penanggung Jawab:</dt>
                      <dd class="col-sm-8">{{ inventory.person_in_charge?.name || '-' }}</dd>

                      <dt class="col-sm-4">Tipe Frekuensi Maintenance:</dt>
                      <dd class="col-sm-8">{{ inventory.maintenance_frequency_type || '-' }}</dd>

                      <dt class="col-sm-4">Nilai Frekuensi Maintenance:</dt>
                      <dd class="col-sm-8">{{ inventory.maintenance_frequency_value || '-' }}</dd>

                      <dt class="col-sm-4">Tanggal Terakhir Maintenance:</dt>
                      <dd class="col-sm-8">{{ formatDate(inventory.last_maintenance_at) }}</dd>

                      <dt class="col-sm-4">Tanggal Jatuh Tempo Berikutnya:</dt>
                      <dd class="col-sm-8">{{ inventory.next_due_date ? formatDate(inventory.next_due_date) : '-' }}</dd>

                      <dt class="col-sm-4">KM Jatuh Tempo Berikutnya:</dt>
                      <dd class="col-sm-8">{{ inventory.next_due_km || '-' }}</dd>

                      <dt class="col-sm-4">Pembacaan Odometer Terakhir:</dt>
                      <dd class="col-sm-8">{{ inventory.last_odometer_reading || '-' }}</dd>

                      <dt class="col-sm-4">Keterangan:</dt>
                      <dd class="col-sm-8">{{ inventory.description || '-' }}</dd>
                    </dl>
                  </div>

                  <div class="col-md-6 text-center">
                    <h4>Gambar Inventaris</h4>
                    <div class="mb-3 border p-2 d-inline-block" style="background-color: #f8f9fa;">
                      <img v-if="inventory.image_path" :src="inventory.image_path" alt="Gambar Inventaris" class="img-fluid rounded" style="max-width: 100%; max-height: 300px; object-fit: contain;">
                      <div v-else class="text-muted p-4">Tidak ada gambar</div>
                    </div>

                    <h4 class="mt-4">QR Code Inventaris</h4>
                    <div class="mb-3 border p-2 d-inline-block" style="background-color: #f8f9fa;">
                      <img v-if="inventory.qr_code_path" :src="inventory.qr_code_path" alt="QR Code Inventaris" class="img-fluid rounded" style="max-width: 100%; max-height: 150px; object-fit: contain;">
                      <div v-else class="text-muted p-4">Tidak ada QR Code</div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else>
                <!-- versi public minimal -->
                <div class="row">
                    <div class="col-md-6">
                    <h4 class="mb-3">Informasi Inventaris</h4>
                    <dl class="row">
                        <dt class="col-sm-5">Nomor Inventaris:</dt>
                        <dd class="col-sm-7">{{ inventory.inventory_number }}</dd>

                        <dt class="col-sm-5">Nama Barang:</dt>
                        <dd class="col-sm-7">{{ inventory.item?.name }}</dd>

                        <dt class="col-sm-5">Kode Barang:</dt>
                        <dd class="col-sm-7">{{ inventory.item?.item_code }}</dd>

                        <dt class="col-sm-5">Sumber Akuisisi:</dt>
                        <dd class="col-sm-7">{{ inventory.acquisition_source }}</dd>

                        <dt class="col-sm-5">Tanggal Pengadaan:</dt>
                        <dd class="col-sm-7">{{ formatDate(inventory.procurement_date) }}</dd>

                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                        <span :class="['badge', getStatusBadge(inventory.status)]">{{ inventory.status }}</span>
                        </dd>

                        <dt class="col-sm-5">Lokasi:</dt>
                        <dd class="col-sm-7">{{ inventory.room?.name }} ({{ inventory.unit?.name }})</dd>

                        <dt class="col-sm-5">Pengawas Ruangan:</dt>
                        <dd class="col-sm-7">{{ inventory.room?.location_person_in_charge?.name || '-' }}</dd>
                    </dl>
                    </div>
                    <div class="col-md-6 text-center">
                        <h4>Gambar Inventaris</h4>
                        <div class="mb-3 border p-2 d-inline-block" style="background-color: #f8f9fa;">
                        <img v-if="inventory.image_path" :src="inventory.image_path" alt="Gambar Inventaris" class="img-fluid rounded" style="max-width: 100%; max-height: 300px; object-fit: contain;">
                        <div v-else class="text-muted p-4">Tidak ada gambar</div>
                        </div>

                        <h4 class="mt-4">QR Code Inventaris</h4>
                        <div class="mb-3 border p-2 d-inline-block" style="background-color: #f8f9fa;">
                        <img v-if="inventory.qr_code_path" :src="inventory.qr_code_path" alt="QR Code Inventaris" class="img-fluid rounded" style="max-width: 100%; max-height: 150px; object-fit: contain;">
                        <div v-else class="text-muted p-4">Tidak ada QR Code</div>
                        </div>
                    </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="card-footer">
            <!-- satu tombol untuk SEMUA role -->
            <router-link
            class="btn btn-outline-danger mr-2"
            :to="`/maintenance/create?inventory=${inventory?.id ?? ''}`"
            >
            <i class="fas fa-exclamation-triangle"></i> Laporkan / Tambah Maintenance
            </router-link>

<router-link
  v-if="!isPublic && inventory &&
        (counterStore.isSupervisorOfInventory(inventory) ||
         counterStore.isAdmin ||
         counterStore.isHead)"
  :to="`/maintenance-done/${inventory.id}`"
  class="btn btn-info ml-2">
  <i class="fas fa-history"></i> Riwayat Maintenance
</router-link>
            <router-link to="/inventories" class="btn btn-secondary">Kembali</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useCounterStore } from '../stores/counter'
import axios from 'axios'

const route = useRoute()
const counterStore = useCounterStore()

const inventory = ref(null)
const loading   = ref(true)
const error     = ref(null)

const isPublic = computed(() => route.path.startsWith('/inventories/scan'))

const endpoint = computed(() =>
  isPublic.value
    ? `${counterStore.API_BASE_URL}/inventories/scan/${route.params.id}`
    : `${counterStore.API_BASE_URL}/inventories/${route.params.id}`
)

const fetchInventoryDetail = async () => {
  loading.value = true
  error.value   = null
  try {
    const { data } = await axios.get(endpoint.value, {
      withCredentials: !isPublic.value
    })
    inventory.value = data
  } catch (e) {
    error.value = e.message || 'Gagal memuat detail inventaris.'
  } finally {
    loading.value = false
  }
}

const getStatusBadge = (status) => {
  switch (status?.toLowerCase()) {
    case 'ada': return 'badge-success'
    case 'rusak': return 'badge-danger'
    case 'perbaikan': return 'badge-warning'
    case 'hilang': return 'badge-dark'
    case 'dipinjam': return 'badge-info'
    case 'tidak tersedia': return 'badge-secondary'
    default: return 'badge-primary'
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric', month: 'long', day: 'numeric'
  })
}

const formatCurrency = (value) => {
  if (value == null) return '-'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', minimumFractionDigits: 2
  }).format(value)
}

onMounted(() => {
  fetchInventoryDetail()
})
</script>

<style scoped>
/* kosongkan atau tambahkan sesuai selera */
</style>