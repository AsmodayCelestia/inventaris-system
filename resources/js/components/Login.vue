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
                  <router-link to="/lupa-password" class="text-sm text-primary">Lupa kata sandi?</router-link>
                </div>
              </div>

              <div class="row">
                <div class="col-12">
                  <button
                    type="submit"
                    class="btn btn-warning btn-block"
                    :disabled="counterStore.loading"
                  >
                    <span
                      v-if="counterStore.loading"
                      class="spinner-border spinner-border-sm"
                      role="status"
                      aria-hidden="true"
                    />
                    <span v-else>Masuk</span>
                  </button>
                </div>
              </div>
            </form>

            <p class="mt-4 text-center text-sm text-muted">
              <router-link to="/scan-qrcode" class="text-primary hover:underline">
                <i class="fas fa-qrcode mr-1"></i> Scan QR Inventaris
              </router-link>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
// Mengimport useCounterStore dari file counter.js yang sudah dikonsolidasi
import { useCounterStore } from '../stores/counter'; 

const counterStore = useCounterStore();
const router = useRouter();

const email = ref('');
const password = ref('');
const showPassword = ref(false);

const togglePassword = () => {
  showPassword.value = !showPassword.value;
};

const handleLogin = async () => {
  try {
    await counterStore.login({ email: email.value, password: password.value });
        router.push('/Dashboard');
  } catch (error) {
    console.error("Login attempt failed in component:", error);
  }
};

</script>

<style scoped>
/* Layout wrapper - Full background image */
.login-wrapper {
    position: relative;
    min-height: 100vh;
    background: url('/images/login-bg.webp') no-repeat center center;
    background-size: cover;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 2rem;
}

/* KANAN: Login Form */
.right-login-form {
    background: rgba(255, 255, 255, 0.85);
    border-radius: 12px;
    max-width: 400px;
    width: 100%;
    padding: 1.5rem;
    margin-right: 12vw;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Sesuaikan untuk mobile: dorong form ke tengah */
@media (max-width: 767px) {
    .login-wrapper {
        justify-content: center;
        padding: 1rem;
    }
    .right-login-form {
        margin-right: 0;
        max-width: 360px;
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

/* Override autofill background */
input:-webkit-autofill,
input:-webkit-autofill:hover, 
input:-webkit-autofill:focus, 
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0px 1000px white inset !important;
    -webkit-text-fill-color: black !important;
}
</style>
