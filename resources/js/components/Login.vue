<!-- resources/js/components/Login.vue -->

<template>
    <div class="login-wrapper">
        <!-- Login Form akan berada di sisi kanan -->
        <div class="right-login-form">
            <div class="login-box">
                <div class="card card-outline card-warning shadow-lg">
                    <div class="card-header text-center">
                        <a href="#" class="h1"><b>Inventaris</b>APP</a>
                    </div>
                    <div class="card-body">
                        <p class="login-box-msg">Masuk untuk memulai sesi Anda</p>

                        <form @submit.prevent="handleLogin">
                            <div class="form-group mb-3">
                                <input
                                    type="email"
                                    class="form-control"
                                    placeholder="Email"
                                    v-model="email"
                                    required
                                />
                            </div>
                            <div class="form-group mb-3 position-relative">
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    class="form-control"
                                    placeholder="Kata Sandi"
                                    v-model="password"
                                    required
                                />
                                <span class="toggle-password" @click="togglePassword">
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </span>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6 d-flex align-items-center">
                                    <input type="checkbox" id="remember" />
                                    <label for="remember" class="ml-2">Ingat Saya</label>
                                </div>
                                <div class="col-6 text-right">
                                    <a href="#" class="text-sm text-primary">Lupa kata sandi?</a>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <button
                                        type="submit"
                                        class="btn btn-warning btn-block"
                                        :disabled="authStore.loading"
                                    >
                                        <span
                                            v-if="authStore.loading"
                                            class="spinner-border spinner-border-sm"
                                            role="status"
                                            aria-hidden="true"
                                        />
                                        <span v-else>Masuk</span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <p v-if="authStore.error" class="text-danger mt-3">
                            {{ authStore.error }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/counter'; // Pastikan path ke store sudah benar

const authStore = useAuthStore();
const email = ref('');
const password = ref('');
const showPassword = ref(false);

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const handleLogin = () => {
    authStore.handleLogin(email.value, password.value);
};
</script>

<style scoped>
/* Layout wrapper - Full background image */
.login-wrapper {
    position: relative; /* Penting untuk posisi absolute anak-anak jika ada */
    min-height: 100vh;
    background: url('/images/login-bg.jpg') no-repeat center center; /* Ganti dengan nama file gambar kamu */
    background-size: cover;
    display: flex; /* Menggunakan flexbox untuk positioning form */
    justify-content: flex-end; /* Mendorong konten (form) ke kanan */
    align-items: center; /* Memusatkan konten secara vertikal */
    padding: 2rem; /* Padding di sekitar wrapper */
}

/* KANAN: Login Form */
.right-login-form {
    background: rgba(255, 255, 255, 0.85); /* semi transparan biar tetap kelihatan gambar */
    border-radius: 12px;
    max-width: 400px; /* Batasi lebar form */
    width: 100%; /* Agar responsif */
    padding: 1.5rem; /* Padding di dalam form */
    margin-right: 12vw; /* Dorong form ke kiri dari tepi kanan */
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* Tambah shadow */
}

/* Sesuaikan untuk mobile: dorong form ke tengah */
@media (max-width: 767px) {
    .login-wrapper {
        justify-content: center; /* Pusatkan form di mobile */
        padding: 1rem;
    }
    .right-login-form {
        margin-right: 0; /* Hapus margin kanan di mobile */
        max-width: 360px; /* Sesuaikan lebar form di mobile */
    }
}

/* Login box tetap sama (sekarang sudah diatur oleh right-login-form) */
.login-box {
    width: 100%;
}

/* Toggle password icon */
.toggle-password {
    position: absolute;
    top: 50%;
    right: 15px;
    transform: translateY(-50%);
    cursor: pointer;
    color: #aaa;
}
</style>
