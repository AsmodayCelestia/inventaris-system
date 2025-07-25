<!-- resources/js/components/InventoryList.vue -->

<template>
    <Layout> <!-- Menggunakan Layout, bukan MainLayout -->
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Manajemen Inventaris</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><router-link to="/dashboard">Home</router-link></li>
                            <li class="breadcrumb-item active">Inventaris</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <div class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: activeTab === 'inventories' }" href="#" @click.prevent="activeTab = 'inventories'">Data Inventaris</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: activeTab === 'master_barang' }" href="#" @click.prevent="activeTab = 'master_barang'">Master Barang</a>
                            </li>
                        </ul>
                    </div><!-- /.card-header -->
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Tab Data Inventaris -->
                            <div class="tab-pane" :class="{ 'active show': activeTab === 'inventories' }">
                                <div class="d-flex justify-content-between mb-3">
                                    <h3 class="card-title">Daftar Unit Inventaris</h3>
                                    <router-link to="/inventories/create" class="btn btn-primary btn-sm" v-if="counterStore.isAdmin || counterStore.isHead">
                                        <i class="fas fa-plus"></i> Tambah Unit Inventaris
                                    </router-link>
                                </div>
                                <div v-if="counterStore.inventoryLoading" class="text-center p-4">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2">Memuat data inventaris...</p>
                                </div>
                                <div v-else-if="counterStore.inventoryError" class="alert alert-danger m-3">
                                    Terjadi kesalahan: {{ counterStore.inventoryError }}
                                </div>
                                <div v-else-if="counterStore.inventories.length === 0" class="alert alert-info m-3">
                                    Belum ada data unit inventaris. Silakan tambahkan.
                                </div>
                                <table v-else class="table table-striped table-valign-middle">
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
                                        <tr v-for="item in counterStore.inventories" :key="item.id">
                                            <td>{{ item.inventory_number }}</td>
                                            <td>{{ item.item ? item.item.name : '-' }}</td>
                                            <td>{{ item.room ? item.room.name : '-' }} ({{ item.unit ? item.unit.name : '-' }})</td>
                                            <td>
                                                <span :class="['badge', getStatusBadge(item.status)]">{{ item.status }}</span>
                                            </td>
                                            <td>{{ formatDate(item.procurement_date) }}</td>
                                            <td>
                                                <router-link :to="`/inventories/${item.id}`" class="text-muted mr-2">
                                                    <i class="fas fa-eye"></i>
                                                </router-link>
                                                <router-link :to="`/inventories/edit/${item.id}`" class="text-muted mr-2" v-if="counterStore.isAdmin || counterStore.isHead">
                                                    <i class="fas fa-edit"></i>
                                                </router-link>
                                                <a href="#" class="text-muted" @click.prevent="confirmDelete(item.id, item.inventory_number, 'inventory')" v-if="counterStore.isAdmin || counterStore.isHead">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <span v-if="!(counterStore.isAdmin || counterStore.isHead)" class="text-muted">Tidak ada aksi</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Tab Master Barang -->
                            <div class="tab-pane" :class="{ 'active show': activeTab === 'master_barang' }">
                                <div class="d-flex justify-content-between mb-3">
                                    <h3 class="card-title">Daftar Master Barang</h3>
                                    <router-link to="/master-data/barang/create" class="btn btn-primary btn-sm" v-if="counterStore.isAdmin">
                                        <i class="fas fa-plus"></i> Tambah Master Barang
                                    </router-link>
                                </div>
                                <div v-if="counterStore.inventoryItemLoading" class="text-center p-4">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2">Memuat data master barang...</p>
                                </div>
                                <div v-else-if="counterStore.inventoryItemError" class="alert alert-danger m-3">
                                    Terjadi kesalahan: {{ counterStore.inventoryItemError }}
                                </div>
                                <div v-else-if="counterStore.inventoryItems.length === 0" class="alert alert-info m-3">
                                    Belum ada data master barang. Silakan tambahkan.
                                </div>
                                <table v-else class="table table-striped table-valign-middle">
                                    <thead>
                                        <tr>
                                            <th>Kode Barang</th>
                                            <th>Nama Barang</th>
                                            <th>Merk</th>
                                            <th>Kategori</th>
                                            <th>Jenis</th>
                                            <th>Tahun Produksi</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in counterStore.inventoryItems" :key="item.id">
                                            <td>{{ item.item_code }}</td>
                                            <td>{{ item.name }}</td>
                                            <td>{{ item.brand ? item.brand.name : '-' }}</td>
                                            <td>{{ item.category ? item.category.name : '-' }}</td>
                                            <td>{{ item.type ? item.type.name : '-' }}</td>
                                            <td>{{ item.manufacture_year || '-' }}</td>
                                            <td>
                                                <router-link :to="`/master-data/barang/edit/${item.id}`" class="text-muted mr-2" v-if="counterStore.isAdmin">
                                                    <i class="fas fa-edit"></i>
                                                </router-link>
                                                <a href="#" class="text-muted" @click.prevent="confirmDelete(item.id, item.name, 'inventory_item')" v-if="counterStore.isAdmin">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <span v-if="!counterStore.isAdmin" class="text-muted">Tidak ada aksi</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteConfirmationModalLabel">Konfirmasi Hapus</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus "<strong>{{ itemToDeleteName }}</strong>"?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-danger" @click="deleteConfirmedItem()">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Layout from './Layout.vue';
import { useCounterStore } from '../stores/counter';
import { useRouter } from 'vue-router'; // Import useRouter

const counterStore = useCounterStore();
const router = useRouter(); // Inisialisasi router

const activeTab = ref('inventories'); // Default tab aktif
const itemIdToDelete = ref(null);
const itemToDeleteName = ref('');
const itemToDeleteType = ref(''); // 'inventory' atau 'inventory_item'

/**
 * Returns the appropriate Bootstrap badge class based on item status.
 * @param {string} status - The status of the item.
 * @returns {string} The CSS class for the badge.
 */
const getStatusBadge = (status) => {
    switch (status.toLowerCase()) {
        case 'tersedia':
            return 'badge-success';
        case 'rusak':
            return 'badge-danger';
        case 'dalam perbaikan':
            return 'badge-warning';
        case 'hilang':
            return 'badge-dark'; // Added for 'Hilang'
        case 'dipinjam':
            return 'badge-info'; // Added for 'Dipinjam'
        case 'tidak tersedia':
            return 'badge-secondary';
        default:
            return 'badge-primary'; // Default for unknown status
    }
};

/**
 * Formats a date string into a human-readable format (e.g., "24 Juli 2024").
 * @param {string} dateString - The date string to format.
 * @returns {string} The formatted date or '-' if null/empty.
 */
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

/**
 * Shows the delete confirmation modal.
 * @param {number} id - The ID of the item to delete.
 * @param {string} name - The name of the item to display in the modal.
 * @param {string} type - The type of item ('inventory' or 'inventory_item').
 */
const confirmDelete = (id, name, type) => {
    itemIdToDelete.value = id;
    itemToDeleteName.value = name;
    itemToDeleteType.value = type;
    // Use jQuery to show the AdminLTE modal
    window.$('#deleteConfirmationModal').modal('show');
};

/**
 * Executes the delete action after confirmation.
 */
const deleteConfirmedItem = async () => {
    try {
        if (itemToDeleteType.value === 'inventory') {
            await counterStore.deleteInventory(itemIdToDelete.value);
            // Re-fetch inventories to update the list
            counterStore.fetchInventories();
        } else if (itemToDeleteType.value === 'inventory_item') {
            await counterStore.deleteInventoryItem(itemIdToDelete.value);
            // Re-fetch inventory items to update the list
            counterStore.fetchInventoryItems();
        }
        window.$('#deleteConfirmationModal').modal('hide'); // Hide the modal
        // Optional: Show a success notification
    } catch (error) {
        console.error('Error deleting item:', error);
        // Optional: Show an error notification
    }
};

onMounted(() => {
    // Fetch data for both tabs when the component is mounted
    counterStore.fetchInventories();
    counterStore.fetchInventoryItems();
});
</script>

<style scoped>
/* Specific styles for this component (if needed) */
.card-header .nav-pills .nav-link.active {
    background-color: #007bff; /* AdminLTE primary color */
    color: #fff;
}
</style>
