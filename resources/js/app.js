// resources/js/app.js

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import axios from 'axios';

import './bootstrap';

console.log('🔧 ENV:', import.meta.env);

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

window.axios = axios;
window.axios.defaults.baseURL = apiBaseUrl;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = localStorage.getItem('Authorization');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    console.log('✅ Axios Authorization header set from localStorage.');
} else {
    console.log('ℹ️ No Authorization token found in localStorage.');
}

const App = {
    template: `<router-view></router-view>`,
};

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.mount('#app');
