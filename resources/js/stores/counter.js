// resources/js/stores/auth.js

import { defineStore } from 'pinia';
import { loginUser } from '../../utils/api'; // Mengimport fungsi loginUser dari utils/api.js
import router from '../router'; 


export const useAuthStore = defineStore('auth', {
    // State: data yang akan disimpan secara global
    state: () => ({
        authToken: localStorage.getItem('authToken') || null, // Token autentikasi
        userRole: localStorage.getItem('userRole') || null,   // Role user (admin, karyawan, head)
        userEmail: localStorage.getItem('userEmail') || null, // Email user
        loading: false, // Status loading untuk request API
        error: null,    // Pesan error jika ada
    }),

    // Getters: cara untuk membaca data dari state
    getters: {
        isLoggedIn: (state) => !!state.authToken, // Mengecek apakah user sudah login
        isAdmin: (state) => state.userRole === 'admin', // Mengecek apakah user adalah admin
        getUserToken: (state) => state.authToken, // Mendapatkan token user
        getUserRole: (state) => state.userRole,   // Mendapatkan role user
        getUserEmail: (state) => state.userEmail, // Mendapatkan email user
    },

    // Actions: fungsi untuk mengubah state atau melakukan operasi asinkron (misal panggil API)
    actions: {
        /**
         * Mengelola proses login user.
         * Memanggil API login dan menyimpan token/info user.
         * @param {string} email
         * @param {string} password
         */
        async handleLogin(email, password) {
            this.loading = true;
            this.error = null; // Reset error
            try {
                // Panggil fungsi loginUser dari utils/api.js
                const data = await loginUser(email, password);

                // Simpan data ke state Pinia
                this.authToken = data.Authorization;
                this.userRole = data.role;
                this.userEmail = data.email;

                // Simpan juga ke localStorage agar tetap login setelah browser ditutup
                localStorage.setItem('authToken', this.authToken);
                localStorage.setItem('userRole', this.userRole);
                localStorage.setItem('userEmail', this.userEmail);

                // Nanti kita akan redirect ke dashboard di sini setelah Vue Router siap
                router.push('/dashboard'); 

            } catch (error) {
                // Tangani error dari API atau jaringan
                this.error = error.message || 'Login gagal. Silakan coba lagi.';
                console.error('Login error:', error);
            } finally {
                this.loading = false; // Selesai loading
            }
        },

        /**
         * Mengelola proses logout user.
         * Menghapus token/info user dari state dan localStorage.
         */
        logout() {
            this.authToken = null;
            this.userRole = null;
            this.userEmail = null;
            localStorage.removeItem('authToken');
            localStorage.removeItem('userRole');
            localStorage.removeItem('userEmail');

            // Nanti kita akan redirect ke halaman login di sini setelah Vue Router siap
            router.push('/login');
        },

        /**
         * Aksi untuk menginisialisasi state auth dari localStorage saat aplikasi dimuat.
         */
        initializeAuth() {
            this.authToken = localStorage.getItem('authToken');
            this.userRole = localStorage.getItem('userRole');
            this.userEmail = localStorage.getItem('userEmail');
        }
    },
});
