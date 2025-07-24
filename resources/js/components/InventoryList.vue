<!-- resources/js/components/InventoryList.vue -->
<!-- Asumsi file ini ada di resources/js/components/InventoryList.vue -->

<template>
    <Layout> <!-- Menggunakan Layout, bukan MainLayout -->
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Daftar Inventaris</h1>
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
                    <div class="card-header">
                        <h3 class="card-title">Data Inventaris</h3>
                        <div class="card-tools">
                            <router-link to="/inventories/create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Inventaris
                            </router-link>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div v-if="counterStore.inventoryLoading" class="text-center p-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Memuat data inventaris...</p>
                        </div>
                        <div v-else-if="counterStore.inventoryError" class="alert alert-danger m-3">
                            Terjadi kesalahan: {{ counterStore.inventoryError }}
                        </div>
                        <div v-else-if="counterStore.inventories.length === 0" class="alert alert-info m-3">
                            Belum ada data inventaris. Silakan tambahkan.
                        </div>
                        <table v-else class="table table-striped table-valign-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Barang</th>
                                    <th>Kode</th>
                                    <th>Lokasi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in counterStore.inventories" :key="item.id">
                                    <td>{{ item.id }}</td>
                                    <td>{{ item.name }}</td>
                                    <td>{{ item.code }}</td>
                                    <td>{{ item.location }}</td>
                                    <td>
                                        <span :class="['badge', getStatusBadge(item.status)]">{{ item.status }}</span>
                                    </td>
                                    <td>
                                        <a href="#" class="text-muted mr-2">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="#" class="text-muted mr-2">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="text-muted">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { onMounted } from 'vue';
// Path ke Layout.vue yang benar (sekarang ada di resources/js/components/Layout.vue)
import Layout from './Layout.vue'; 
// Path ke useCounterStore (sudah benar karena InventoryList.vue ada di components/)
import { useCounterStore } from '../stores/counter';

const counterStore = useCounterStore();

const getStatusBadge = (status) => {
    switch (status.toLowerCase()) {
        case 'tersedia':
            return 'badge-success';
        case 'rusak':
            return 'badge-danger';
        case 'dalam perbaikan':
            return 'badge-warning';
        case 'tidak tersedia':
            return 'badge-secondary';
        default:
            return 'badge-info';
    }
};

onMounted(() => {
    counterStore.fetchInventories();
});
</script>

<style scoped>
/* Gaya khusus untuk komponen ini (jika diperlukan) */
</style>
