// resources/js/router/index.js

import { createRouter, createWebHistory } from 'vue-router';
import { useCounterStore } from '../stores/counter'; // <-- Mengimport store terbaru (useCounterStore)

// Import komponen-komponen halaman (views)
// Kita akan membuat komponen-komponen ini di langkah selanjutnya
import Login from '../components/Login.vue'; 
import Dashboard from '../components/Dashboard.vue'; 

// Definisikan rute-rute aplikasi Vue
const routes = [
    {
        path: '/login', // URL path untuk halaman login
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false } // Tidak memerlukan autentikasi
    },
    {
        path: '/dashboard', // URL path untuk halaman dashboard
        name: 'Dashboard',
        component: Dashboard,
        meta: { requiresAuth: true } // Memerlukan autentikasi
    },
    // Catch-all route untuk mengarahkan ke halaman login jika URL tidak ditemukan dan belum login
    // Ini penting agar jika user mengetik URL yang tidak ada, dia diarahkan ke login
    {
        path: '/:catchAll(.*)', // Menangkap semua path yang tidak cocok
        redirect: '/login', // Mengarahkan ke halaman login
    }
];

// Buat instance router
const router = createRouter({
    history: createWebHistory(), // Menggunakan HTML5 History API (URL bersih tanpa hash #)
    routes, // Daftar rute yang sudah didefinisikan
});

// Navigation Guard: Melindungi rute yang memerlukan autentikasi
router.beforeEach((to, from, next) => {
    const counterStore = useCounterStore(); // <-- Menggunakan nama variabel yang sesuai

    // Inisialisasi status autentikasi dari localStorage jika belum terinisialisasi
    // Ini memastikan Pinia store selalu sinkron dengan localStorage saat navigasi
    if (!counterStore.authToken && localStorage.getItem('authToken')) {
        counterStore.initializeAuth();
    }

    // Jika rute memerlukan autentikasi (requiresAuth: true) DAN user belum login
    if (to.meta.requiresAuth && !counterStore.isLoggedIn) {
        // Redirect ke halaman login
        next({ name: 'Login' });
    }
    // Jika user sudah login dan mencoba mengakses halaman login
    else if (to.name === 'Login' && counterStore.isLoggedIn) {
        // Redirect ke dashboard
        next({ name: 'Dashboard' });
    }
    // Lanjutkan navigasi
    else {
        next();
    }
});

export default router; // Mengekspor instance router agar bisa digunakan di app.js dan stores
