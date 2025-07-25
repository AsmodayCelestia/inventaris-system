// resources/js/router/index.js

import { createRouter, createWebHistory } from 'vue-router';
import { useCounterStore } from '../stores/counter';

// Import komponen-komponen halaman (views)
import Login from '../components/Login.vue';
import Dashboard from '../components/Dashboard.vue';
import InventoryList from '../components/InventoryList.vue'; // Ini sekarang handle Inventaris & Master Barang
import InventoryItemForm from '../components/InventoryItemForm.vue'; // <-- KOMPONEN FORM MASTER BARANG YANG BARU

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false }
    },
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/inventories',
        name: 'InventoryList',
        component: InventoryList,
        meta: { requiresAuth: true, roles: ['admin', 'head', 'karyawan'] } // Semua bisa lihat daftar
    },
    // --- ROUTES UNTUK FORM MASTER BARANG (CRUD InventoryItem) ---
    // Link ini akan dipanggil dari dalam InventoryList.vue (tab Master Barang)
    {
        path: '/master-data/barang/create', // Tetap pakai path ini agar konsisten dengan sidebar jika ada
        name: 'InventoryItemCreate', // Nama route yang lebih spesifik
        component: InventoryItemForm,
        meta: { requiresAuth: true, roles: ['admin'] } // Hanya Admin yang bisa akses ini
    },
    {
        path: '/master-data/barang/edit/:id', // Tetap pakai path ini
        name: 'InventoryItemEdit', // Nama route yang lebih spesifik
        component: InventoryItemForm,
        props: true,
        meta: { requiresAuth: true, roles: ['admin'] } // Hanya Admin yang bisa akses ini
    },
    // --- END ROUTES UNTUK FORM MASTER BARANG ---
    {
        path: '/:catchAll(.*)',
        redirect: '/login',
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const counterStore = useCounterStore();

    // Inisialisasi autentikasi jika token ada di localStorage tapi store belum terinisialisasi
    // Ini penting agar userRole sudah terisi sebelum navigation guard berjalan
    if (!counterStore.isLoggedIn && localStorage.getItem('Authorization')) {
        counterStore.initializeAuth();
    }

    const requiredAuth = to.meta.requiresAuth;
    const requiredRoles = to.meta.roles;
    const isLoggedIn = counterStore.isLoggedIn;
    const userRole = counterStore.userRole;

    if (requiredAuth && !isLoggedIn) {
        // Jika rute memerlukan autentikasi tapi user belum login
        next({ name: 'Login' });
    } else if (to.name === 'Login' && isLoggedIn) {
        // Jika user sudah login dan mencoba mengakses halaman login, redirect ke dashboard
        next({ name: 'Dashboard' });
    } else if (requiredRoles && (!userRole || !requiredRoles.includes(userRole))) {
        // Jika rute memerlukan role tertentu dan user tidak memiliki role tersebut
        console.warn(`Akses ditolak: User dengan role '${userRole}' mencoba mengakses rute yang memerlukan role '${requiredRoles.join(', ')}'`);
        next({ name: 'Dashboard' }); // Redirect ke dashboard atau halaman unauthorized
    } else {
        next();
    }
});

export default router;
