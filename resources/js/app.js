// resources/js/app.js

// Import bootstrap.js bawaan Laravel (jika ada dan diperlukan)
// import './bootstrap';

// Import fungsi createApp dari Vue
import { createApp } from 'vue';
// Import createPinia untuk state management
import { createPinia } from 'pinia';
// Import router yang sudah kita konfigurasi
import router from './router';
// Import store autentikasi (sekarang useCounterStore)
import { useCounterStore } from './stores/counter';
import axios from 'axios'; // <-- PASTIKAN AXIOS DI-IMPORT DI SINI

// Konfigurasi Axios global
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// --- BAGIAN PENTING UNTUK AUTENTIKASI ---
// Ini adalah tempat terbaik untuk mengatur header Authorization global Axios
// berdasarkan token yang ada di localStorage saat aplikasi dimuat.
const token = localStorage.getItem('Authorization');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    console.log('Axios Authorization header set from localStorage in app.js.');
} else {
    console.log('No Authorization token found in localStorage in app.js.');
}
// --- AKHIR BAGIAN PENTING ---


// Komponen Vue utama (Root Component)
const App = {
    template: `
        <router-view></router-view> <!-- Vue Router akan me-render komponen halaman di sini -->
    `,
    setup() {
        // Menggunakan counterStore
        const counterStore = useCounterStore();
        // initializeAuth akan memverifikasi token dan mengisi state user
        // Tidak perlu mengatur axios.defaults.headers.common di sini lagi, karena sudah di atas
        counterStore.initializeAuth();
        return {};
    }
};

// Buat instance Pinia
const pinia = createPinia();

// Buat aplikasi Vue
const app = createApp(App);

// Gunakan Pinia di aplikasi Vue
app.use(pinia);

// Gunakan Vue Router di aplikasi Vue
app.use(router);

// Mount aplikasi Vue ke elemen dengan ID 'app' di HTML
app.mount('#app');

// Catatan: AdminLTE JS secara otomatis menginisialisasi dirinya sendiri
// setelah DOM siap dan script-nya dimuat. Kita tidak perlu memanggilnya secara eksplisit di sini.
