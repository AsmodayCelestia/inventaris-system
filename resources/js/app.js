// resources/js/app.js

// Import bootstrap.js bawaan Laravel (jika ada dan diperlukan)
// import './bootstrap'; 

// Import fungsi createApp dari Vue
import { createApp } from 'vue';
// Import createPinia untuk state management
import { createPinia } from 'pinia';
// Import router yang sudah kita konfigurasi
import router from './router'; 
// Import store autentikasi
import { useAuthStore } from './stores/counter';

// Komponen Vue utama (Root Component)
const App = {
    template: `
        <router-view></router-view> <!-- Vue Router akan me-render komponen halaman di sini -->
    `,
    setup() {
        const authStore = useAuthStore();
        // Inisialisasi state autentikasi dari localStorage saat aplikasi dimuat
        authStore.initializeAuth();
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
