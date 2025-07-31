<!-- resources/js/components/Dashboard.vue -->

<template>
    <!-- Kita akan menggunakan Layout.vue di sini -->
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Dashboard</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <h3 class="card-title">Selamat Datang, {{ counterStore.userEmail }}!</h3>
                            </div>
                            <div class="card-body">
                                <p>Anda login sebagai <strong>{{ counterStore.userRole }}</strong>.</p>
                                <p>Ini adalah halaman dashboard AdminLTE 3 Anda.</p>
                                <p>Anda bisa mulai menambahkan widget, grafik, dan fitur lainnya di sini.</p>
                                <div class="d-flex justify-content-between align-items-center border-bottom mb-3">
                                    <p class="text-success text-xl">
                                        <i class="ion ion-ios-refresh-empty"></i>
                                    </p>
                                    <p class="d-flex flex-column text-right">
                                        <span class="font-weight-bold">
                                            <i class="ion ion-android-arrow-up text-success"></i> 12%
                                        </span>
                                        <span class="text-muted">Sejak Bulan Lalu</span>
                                    </p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-0">
                                    <p class="text-danger text-xl">
                                        <i class="ion ion-ios-cart-outline"></i>
                                    </p>
                                    <p class="d-flex flex-column text-right">
                                        <span class="font-weight-bold">
                                            <i class="ion ion-android-arrow-down text-danger"></i> 1%
                                        </span>
                                        <span class="text-muted">Sejak Bulan Lalu</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</template>

<script setup>
import Layout from './Layout.vue'; // Path disesuaikan ke Layout.vue
import { useCounterStore } from '../stores/counter'; // <-- Mengimport store terbaru (counter.js)

const counterStore = useCounterStore(); // <-- Menggunakan nama variabel yang sesuai
</script>

<style scoped>
/* Gaya khusus untuk komponen ini (jika diperlukan) */
</style>
