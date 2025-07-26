<template>
    <Layout>
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Detail Inventaris</h1>
                    </div>
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
                        <!-- PASTIKAN PENGGUNAAN inventory? DI SINI -->
                        <h3 class="card-title">Informasi Unit Inventaris: {{ inventory ? inventory.inventory_number : 'Memuat...' }}</h3>
                    </div>
                    <div class="card-body">
                        <div v-if="loading" class="text-center p-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Memuat detail inventaris...</p>
                        </div>
                        <div v-else-if="error" class="alert alert-danger m-3">
                            Terjadi kesalahan: {{ error }}
                        </div>
                        <div v-else-if="!inventory" class="alert alert-info m-3">
                            Data inventaris tidak ditemukan.
                        </div>
                        <div v-else class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">Nomor Inventaris:</dt>
                                    <dd class="col-sm-8">{{ inventory.inventory_number }}</dd>

                                    <dt class="col-sm-4">Nama Barang:</dt>
                                    <dd class="col-sm-8">{{ inventory.item ? inventory.item.name : '-' }}</dd>

                                    <dt class="col-sm-4">Kode Barang:</dt>
                                    <dd class="col-sm-8">{{ inventory.item ? inventory.item.item_code : '-' }}</dd>

                                    <dt class="col-sm-4">Sumber Akuisisi:</dt>
                                    <dd class="col-sm-8">{{ inventory.acquisition_source }}</dd>

                                    <dt class="col-sm-4">Tanggal Pengadaan:</dt>
                                    <dd class="col-sm-8">{{ formatDate(inventory.procurement_date) }}</dd>

                                    <dt class="col-sm-4">Harga Pembelian:</dt>
                                    <dd class="col-sm-8">{{ formatCurrency(inventory.purchase_price) }}</dd>

                                    <dt class="col-sm-4">Estimasi Depresiasi:</dt>
                                    <dd class="col-sm-8">{{ inventory.estimated_depreciation ? formatCurrency(inventory.estimated_depreciation) : '-' }}</dd>

                                    <dt class="col-sm-4">Status:</dt>
                                    <dd class="col-sm-8">
                                        <span :class="['badge', getStatusBadge(inventory.status)]">{{ inventory.status }}</span>
                                    </dd>

                                    <dt class="col-sm-4">Lokasi:</dt>
                                    <dd class="col-sm-8">{{ inventory.room ? inventory.room.name : '-' }} ({{ inventory.unit ? inventory.unit.name : '-' }})</dd>

                                    <dt class="col-sm-4">Tanggal Estimasi Penggantian:</dt>
                                    <dd class="col-sm-8">{{ formatDate(inventory.expected_replacement) }}</dd>

                                    <dt class="col-sm-4">Tanggal Terakhir Dicek:</dt>
                                    <dd class="col-sm-8">{{ formatDate(inventory.last_checked_at) }}</dd>

                                    <dt class="col-sm-4">Penanggung Jawab:</dt>
                                    <dd class="col-sm-8">{{ inventory.person_in_charge ? inventory.person_in_charge.name : '-' }}</dd>

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
                                    <!-- image_path akan langsung berisi URL dari Cloudinary -->
                                    <img v-if="inventory.image_path" :src="inventory.image_path" alt="Gambar Inventaris" class="img-fluid rounded" style="max-width: 100%; max-height: 300px; object-fit: contain;">
                                    <div v-else class="text-muted p-4">Tidak ada gambar</div>
                                </div>

                                <h4 class="mt-4">QR Code Inventaris</h4>
                                <div class="mb-3 border p-2 d-inline-block" style="background-color: #f8f9fa;">
                                    <!-- qr_code_path akan langsung berisi URL dari Cloudinary -->
                                    <img v-if="inventory.qr_code_path" :src="inventory.qr_code_path" alt="QR Code Inventaris" class="img-fluid rounded" style="max-width: 100%; max-height: 150px; object-fit: contain;">
                                    <div v-else class="text-muted p-4">Tidak ada QR Code</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <router-link to="/inventories" class="btn btn-secondary">Kembali</router-link>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Layout from './Layout.vue';
import { useCounterStore } from '../stores/counter';
import axios from 'axios'; // Pastikan axios di-import

const route = useRoute();
const counterStore = useCounterStore();

const inventory = ref(null);
const loading = ref(true);
const error = ref(null);

/**
 * Fetches the detailed inventory data from the API.
 */
const fetchInventoryDetail = async () => {
    loading.value = true;
    error.value = null;
    try {
        const inventoryId = route.params.id;
        // LANGSUNG PANGGIL API, HAPUS LOGIKA SIMULASI/STORE FIND
        const response = await axios.get(`/api/inventories/${inventoryId}`);
        inventory.value = response.data;

        // HAPUS ATAU KOMENTARI BAGIAN INI KARENA SUDAH TIDAK DIBUTUHKAN
        // if (inventory.value) {
        //     inventory.value.image_url = inventory.value.image_path ? `/storage/${inventory.value.image_path}` : null;
        //     inventory.value.qr_code_url = inventory.value.qr_code_path ? `/storage/${inventory.value.qr_code_path}` : null;
        // }

    } catch (e) {
        console.error('Failed to fetch inventory detail:', e);
        error.value = e.message || 'Gagal memuat detail inventaris.';
    } finally {
        loading.value = false;
    }
};

/**
 * Returns the appropriate Bootstrap badge class based on item status.
 * @param {string} status - The status of the item.
 * @returns {string} The CSS class for the badge.
 */
const getStatusBadge = (status) => {
    switch (status.toLowerCase()) {
        case 'ada':
            return 'badge-success';
        case 'rusak':
            return 'badge-danger';
        case 'perbaikan':
            return 'badge-warning';
        case 'hilang':
            return 'badge-dark';
        case 'dipinjam':
            return 'badge-info';
        case 'tidak tersedia':
            return 'badge-secondary';
        default:
            return 'badge-primary';
    }
};

/**
 * Formats a date string into a human-readable format.
 * @param {string} dateString - The date string to format.
 * @returns {string} The formatted date or '-' if null/empty.
 */
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

/**
 * Formats a numeric value as Indonesian Rupiah currency.
 * @param {number} value - The numeric value to format.
 * @returns {string} The formatted currency string or '-' if null/undefined.
 */
const formatCurrency = (value) => {
    if (value === null || value === undefined) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};

onMounted(() => {
    fetchInventoryDetail();
});
</script>

<style scoped>
/* Specific styles if needed */
</style>
