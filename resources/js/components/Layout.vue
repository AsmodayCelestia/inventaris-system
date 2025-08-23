<!-- resources/js/components/Layout.vue -->

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
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link text-dark">
                        Halo, <b>{{ counterStore.userName }}</b> ({{ counterStore.userRole }})
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" @click.prevent="counterStore.logout()">
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
                        <a href="#" class="d-block">{{ counterStore.userName }} ({{ counterStore.userRole }})</a>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <router-link to="/dashboard" class="nav-link" :class="{ active: $route.path === '/dashboard' }">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </router-link>
                        </li>

                        <!-- Master Data (Hanya Admin) -->
                        <!-- Menu ini hanya akan aktif jika path dimulai dengan /master-data TAPI BUKAN /inventories/master-barang -->
                        <li class="nav-item has-treeview" :class="{ 'menu-open': isMasterDataOpen }" v-if="counterStore.isAdmin">
                            <a href="#" class="nav-link" @click.prevent="toggleMasterData" :class="{ active: $route.path.startsWith('/master-data') }">
                                <i class="nav-icon fas fa-database"></i>
                                <p>
                                    Master Data
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <router-link to="/master-data/brands" class="nav-link" :class="{ active: $route.path === '/master-data/brands' }">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Merk</p>
                                    </router-link>
                                </li>
                                <li class="nav-item">
                                    <router-link to="/master-data/categories" class="nav-link" :class="{ active: $route.path === '/master-data/categories' }">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Kategori</p>
                                    </router-link>
                                </li>
                                <li class="nav-item">
                                    <router-link to="/master-data/item-types" class="nav-link" :class="{ active: $route.path === '/master-data/item-types' }">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Jenis</p>
                                    </router-link>
                                </li>
                                <li class="nav-item">
                                    <router-link to="/master-data/floors" class="nav-link" :class="{ active: $route.path === '/master-data/floors' }">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Lantai</p>
                                    </router-link>
                                </li>                                                           <li class="nav-item">
                                    <router-link to="/master-data/rooms" class="nav-link" :class="{ active: $route.path === '/master-data/rooms' }">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ruang</p>
                                    </router-link>
                                </li>
                                <li class="nav-item">
                                    <router-link to="/master-data/units" class="nav-link" :class="{ active: $route.path === '/master-data/units' }">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Unit Lokasi</p>
                                    </router-link>
                                </li>
                                <li class="nav-item">
                                    <router-link to="/master-data/divisions" class="nav-link" :class="{ active: $route.path === '/master-data/divisions' }">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Divisi</p>
                                    </router-link>
                                </li>
                                
                                <li class="nav-item">
                                    <router-link to="/master-data/users" class="nav-link" :class="{ active: $route.path === '/master-data/users' }">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>User</p>
                                    </router-link>
                                </li>
                            </ul>
                        </li>



                        <!-- Data Inventaris (Admin & Head & Karyawan) -->
                        <!-- Link ini akan aktif jika path dimulai dengan /inventories ATAU /inventories/master-barang -->
                        <li class="nav-item has-treeview"
                            :class="{ 'menu-open': isInventarisOpen }"
                            v-if="counterStore.isAdmin || counterStore.isHead || counterStore.isKaryawan">
                            <a href="#" class="nav-link"
                                @click.prevent="toggleInventaris"
                                :class="{ active: $route.path.startsWith('/inventories') || $route.path.startsWith('/inventories/master-barang') }">
                                <i class="nav-icon fas fa-box"></i>
                                <p>
                                    Data Inventaris
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <router-link to="/inventories" class="nav-link"
                                        :class="{ active: $route.path === '/inventories' }">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Unit Inventaris</p>
                                    </router-link>
                                </li>
                                <li class="nav-item" v-if="counterStore.isAdmin || counterStore.isHead">
                                    <router-link to="/inventories/master-barang" class="nav-link"
                                        :class="{ active: $route.path === '/inventories/master-barang' }">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Master Barang</p>
                                    </router-link>
                                </li>
                            </ul>
                        </li>
                        <!-- Maintenance (Admin, Head, Maintenance Role) -->
                        <!-- (1) Menu Maintenance yang terbatas role -->
                        <li class="nav-item has-treeview"
                            :class="{ 'menu-open': isMaintenanceOpen }"
                            v-if="counterStore.isAdmin || counterStore.isHead || counterStore.isKeuangan || counterStore.isPjMaintenance || counterStore.isRoomSupervisor">
                            <a href="#" class="nav-link" @click.prevent="toggleMaintenance">
                                <i class="nav-icon fas fa-tools"></i>
                                <p>Maintenance <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <!-- sub-item yang tetap terbatas -->
                                <li v-if="!counterStore.isKeuangan && !counterStore.isPjMaintenance" class="nav-item">
                                    <router-link to="/maintenance/create" class="nav-link">
                                        Tambah Maintenance Baru
                                    </router-link>
                                </li>
                                <li class="nav-item">
                                    <router-link to="/maintenance/done" class="nav-link">
                                        Maintenance Selesai
                                    </router-link>
                                </li>
                                <li v-if="!counterStore.isKeuangan" class="nav-item">
                                    <router-link to="/maintenance/list" class="nav-link">
                                        Maintenance List
                                    </router-link>
                                </li>
                            </ul>
                        </li>

                        <!-- (2) Menu "Maintenance Diperlukan" untuk SEMUA user yang login -->
                        <li class="nav-item">
                            <router-link to="/maintenance/needed" class="nav-link">
                                <i class="nav-icon fas fa-wrench"></i>
                                <p>Maintenance Diperlukan</p>
                            </router-link>
                        </li>

                        <!-- QR Code (Admin & Head) -->
    <li class="nav-item has-treeview"
        :class="{ 'menu-open': isQrCodeOpen }"
        v-if="counterStore.isAdmin || counterStore.isHead || counterStore.isKaryawan">
        <a href="#" class="nav-link" @click.prevent="toggleQrCode" :class="{ active: $route.path.startsWith('/qr-code') }">
            <i class="nav-icon fas fa-qrcode"></i>
            <p>
                QR Code
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item" v-if="counterStore.isAdmin">
                <router-link to="/manage-qrcodes" class="nav-link" :class="{ active: $route.path === '/qr-code/generate' }">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Generate & Cetak</p>
                </router-link>
            </li>
            <li class="nav-item">
                <router-link to="/scan-qrcode" class="nav-link" :class="{ active: $route.path === '/qr-code/scan' }">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Scan QR</p>
                </router-link>
            </li>
        </ul>
    </li>

                        <!-- Manajemen User (Hanya Admin) -->
                        <li class="nav-item" v-if="counterStore.isAdmin">
                            <router-link to="/users" class="nav-link" :class="{ active: $route.path === '/users' }">
                                <i class="nav-icon fas fa-users-cog"></i>
                                <p>Manajemen User</p>
                            </router-link>
                        </li>

                        <!-- Laporan (Hanya Admin) -->
                        <li class="nav-item" v-if="counterStore.isAdmin">
                            <router-link to="/laporan" class="nav-link" :class="{ active: $route.path === '/laporan' }">
                                <i class="nav-icon fas fa-file-alt"></i>
                                <p>Laporan</p>
                            </router-link>
                        </li>

                        <!-- Activity Log (Admin only) -->
                        <li class="nav-item" v-if="counterStore.isAdmin">
                        <router-link
                            to="/activity-log"
                            class="nav-link"
                            :class="{ active: $route.path === '/activity-log' }"
                        >
                            <i class="nav-icon fas fa-history"></i>
                            <p>Activity Log</p>
                        </router-link>
                        </li>

                        <!-- Pengaturan (Hanya Admin) -->
                        <li class="nav-item" v-if="counterStore.isAdmin">
                            <router-link to="/pengaturan" class="nav-link" :class="{ active: $route.path === '/pengaturan' }">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Pengaturan</p>
                            </router-link>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <div class="sidebar-overlay" @click="document.body.classList.remove('sidebar-open')"></div>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <router-view />
        </div>
        <!-- /.content-wrapper -->

        <!-- Main Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-inline">
                Versi 1.0
            </div>
            <strong>Hak Cipta &copy; {{ new Date().getFullYear() }} <a href="#">Inventaris APP</a>.</strong> Semua hak dilindungi undang-undang.
        </footer>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import { useCounterStore } from '../stores/counter';
import { useRoute } from 'vue-router';

const counterStore = useCounterStore();
const route = useRoute();

const isMasterDataOpen = ref(false);
const isMaintenanceOpen = ref(false);
const isQrCodeOpen = ref(false);
const isInventarisOpen = ref(false);

const toggleInventaris = () => { isInventarisOpen.value = !isInventarisOpen.value; };
const toggleMasterData = () => { isMasterDataOpen.value = !isMasterDataOpen.value; };
const toggleMaintenance = () => { isMaintenanceOpen.value = !isMaintenanceOpen.value; };
const toggleQrCode = () => { isQrCodeOpen.value = !isQrCodeOpen.value; };

watch(route, (newRoute) => {
    isMasterDataOpen.value = newRoute.path.startsWith('/master-data') && !newRoute.path.startsWith('/inventories/master-barang');
    isMaintenanceOpen.value = newRoute.path.startsWith('/maintenance');
    isQrCodeOpen.value = newRoute.path.startsWith('/qr-code');
    isInventarisOpen.value = newRoute.path.startsWith('/inventories') || newRoute.path.startsWith('/inventories/master-barang');

    // 🩹 FIX: Re-init AdminLTE JS plugins setiap ganti route
    setTimeout(() => {
        if (window.$) {
            try {
                // Sidebar toggle
                if (window.$('[data-widget="pushmenu"]').length && window.$.fn.pushMenu) {
                    window.$('[data-widget="pushmenu"]').pushMenu('collapse');
                }

                // Sidebar treeview
                if (window.$('[data-widget="treeview"]').length && window.$.fn.Treeview) {
                    window.$('[data-widget="treeview"]').Treeview('init');
                }

                // Optional: tooltip, dropdown, dll
                window.$('[data-toggle="tooltip"]').tooltip();
            } catch (err) {
                console.warn('AdminLTE plugin gagal di-reinit:', err);
            }
        }
    }, 200); // kasih delay kecil biar DOM sudah stabil
}, { immediate: true });


const handleClickOutsideSidebar = (event) => {
    const sidebar = document.querySelector('.main-sidebar');
    const hamburger = document.querySelector('[data-widget="pushmenu"]');

    if (
        sidebar &&
        !sidebar.contains(event.target) &&
        (!hamburger || !hamburger.contains(event.target))
    ) {
        if (window.innerWidth >= 768) {
            document.body.classList.add('sidebar-collapse');
            document.body.classList.remove('sidebar-open');
        }
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutsideSidebar);

    const checkAdminLTEReady = () => {
        if (typeof window.$ === 'function' && window.$.fn && typeof window.$.fn.pushMenu === 'function') {
            console.log('✅ AdminLTE aktif.');
        } else {
            console.warn('⚠️ jQuery atau Bootstrap tidak ditemukan. Beberapa fitur AdminLTE mungkin tidak berfungsi.');
        }
    };

    // Cek jika jQuery dan AdminLTE sudah langsung tersedia
    if (typeof window.$ !== 'undefined' && window.$.fn && typeof window.$.fn.pushMenu === 'function') {
        checkAdminLTEReady();
    } else {
        // Jika belum, tunggu sampai script AdminLTE selesai dimuat dari CDN
        const adminLteScript = document.querySelector('script[src*="adminlte.min.js"]');
        if (adminLteScript) {
            adminLteScript.addEventListener('load', () => {
                // Tunggu sedikit lagi agar jQuery plugin benar-benar siap
                setTimeout(checkAdminLTEReady, 100);
            });
        } else {
            console.warn('⚠️ Script AdminLTE belum ditemukan di DOM.');
        }
    }
});



onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutsideSidebar);
});
</script>


<style scoped>
/* Gaya khusus untuk komponen ini (jika diperlukan) */
.main-sidebar .nav-link.active {
    background-color: rgba(255, 255, 255, .1);
    color: #fff;
}
/* Gaya untuk sub-menu yang aktif */
.nav-item.has-treeview.menu-open > .nav-link {
    background-color: rgba(255, 255, 255, .1);
    color: #fff;
}
.sidebar-overlay {
    position: fixed;
    inset: 0;
    z-index: 998;
    background: rgba(0, 0, 0, 0.3);
    display: none;
}
body.sidebar-open .sidebar-overlay {
    display: block;
}

</style>
