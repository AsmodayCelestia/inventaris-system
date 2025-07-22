<!-- resources/js/components/Layout/MainLayout.vue -->

<template>
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <router-link to="/dashboard" class="nav-link">Home</router-link>
                </li>
                <!-- Tambahkan link navbar lain di sini -->
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#" @click.prevent="authStore.logout()">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <router-link to="/dashboard" class="brand-link">
                <img src="https://placehold.co/128x128/007bff/ffffff?text=APP" alt="App Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">Inventaris APP</span>
            </router-link>

            <!-- Sidebar -->
            <div class="sidebar">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="https://placehold.co/160x160/cccccc/ffffff?text=User" class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="#" class="d-block">{{ authStore.userEmail }} ({{ authStore.userRole }})</a>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item">
                            <router-link to="/dashboard" class="nav-link" :class="{ active: $route.path === '/dashboard' }">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/inventories" class="nav-link">
                                <i class="nav-icon fas fa-box"></i>
                                <p>Inventaris</p>
                            </router-link>
                        </li>
                        <li class="nav-item" v-if="authStore.isAdmin">
                            <router-link to="/users" class="nav-link">
                                <i class="nav-icon fas fa-users-cog"></i>
                                <p>Manajemen User</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/qr-scanner" class="nav-link">
                                <i class="nav-icon fas fa-qrcode"></i>
                                <p>Scan QR</p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Pengaturan</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Slot untuk konten halaman yang akan di-render oleh router -->
            <slot></slot>
        </div>
        <!-- /.content-wrapper -->

        <!-- Main Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-inline">
                Versi 1.0
            </div>
            <strong>Hak Cipta &copy; 2023-2024 <a href="#">Inventaris APP</a>.</strong> Semua hak dilindungi undang-undang.
        </footer>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '../stores/counter'; // Mengimport store autentikasi

const authStore = useAuthStore(); // Menggunakan store autentikasi

onMounted(() => {
    // AdminLTE JS secara otomatis menginisialisasi dirinya sendiri
    // setelah DOM siap dan script-nya dimuat.
    // Kita hanya perlu memastikan elemen-elemennya ada di DOM.
    // Jika ada masalah dengan pushmenu, pastikan jQuery dan Bootstrap dimuat dengan benar.
    if (window.$ && window.$.fn.pushMenu) {
        // console.log('AdminLTE JS components should be active.');
    } else {
        console.warn('jQuery atau Bootstrap tidak ditemukan. Beberapa fitur AdminLTE mungkin tidak berfungsi.');
    }
});
</script>

<style scoped>
/* Gaya khusus untuk komponen ini (jika diperlukan) */
.main-sidebar .nav-link.active {
    background-color: rgba(255, 255, 255, .1);
    color: #fff;
}
</style>
