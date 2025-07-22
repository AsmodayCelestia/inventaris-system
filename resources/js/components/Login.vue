<!-- resources/js/components/Login.vue -->

<template>
    <div class="login-wrapper">
        <!-- KIRI: Background image (hanya di desktop) -->
        <div class="left-bg-image" />

        <!-- KANAN: Login Form -->
        <div class="right-login-form">
            <div class="login-box">
                <div class="card card-outline card-primary shadow-lg">
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
                                        class="btn btn-primary btn-block"
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
/* Layout wrapper */
.login-wrapper {
    position: relative;
    min-height: 100vh;
    background: url('/images/login-bg.jpg') no-repeat center center;
    background-size: cover;
    display: flex;
    justify-content: flex-end; /* 🢂 ini yang penting */
    align-items: center;
    padding: 2rem;
}

.right-login-form {
    background: rgba(255, 255, 255, 0.85); /* semi transparan biar tetap kelihatan gambar */
    border-radius: 12px;
    max-width: 400px;
    width: 100%;
    padding: 1rem;
    margin-right: 12vw; /* ⬅️ tambah ini untuk dorong ke kiri dikit */
}

/* Login box tetap sama */
.login-box {
    width: 100%;
    max-width: 400px; /* Batasi lebar login box */
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
