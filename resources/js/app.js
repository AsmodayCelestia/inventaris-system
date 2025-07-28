// resources/js/app.js

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import { useCounterStore } from './stores/counter';
import axios from 'axios';

console.log('🔧 ENV:', import.meta.env);

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

// Konfigurasi Axios global
window.axios = axios;
window.axios.defaults.baseURL = apiBaseUrl; // <--- DI SINI INTINYA
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = localStorage.getItem('Authorization');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    console.log('Axios Authorization header set from localStorage in app.js.');
} else {
    console.log('No Authorization token found in localStorage in app.js.');
}

const App = {
    template: `<router-view></router-view>`,
    setup() {
        const counterStore = useCounterStore();
        counterStore.initializeAuth();
        return {};
    }
};

const pinia = createPinia();
const app = createApp(App);

app.use(pinia);
app.use(router);
app.mount('#app');
