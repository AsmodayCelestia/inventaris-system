<template>
    <Layout>
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Edit Inventaris</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><router-link to="/dashboard">Home</router-link></li>
                            <li class="breadcrumb-item"><router-link to="/inventories">Inventaris</router-link></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Form Edit Inventaris: {{ form.inventory_number }}</h3>
                    </div>
                    <form @submit.prevent="submitForm" enctype="multipart/form-data">
                        <div class="card-body">
                            <div v-if="loading" class="text-center p-4">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-2">Memuat data inventaris...</p>
                            </div>
                            <div v-else-if="fetchError" class="alert alert-danger m-3">
                                Terjadi kesalahan saat memuat data: {{ fetchError }}
                            </div>
                            <div v-else>
                                <div class="form-group">
                                    <label for="inventory_number">Nomor Inventaris</label>
                                    <input type="text" v-model="form.inventory_number" class="form-control" :class="{'is-invalid': errors.inventory_number}" id="inventory_number" placeholder="Masukkan nomor inventaris">
                                    <div v-if="errors.inventory_number" class="invalid-feedback">{{ errors.inventory_number[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="inventory_item_id">Nama Barang</label>
                                    <select v-model="form.inventory_item_id" class="form-control" :class="{'is-invalid': errors.inventory_item_id}">
                                        <option value="">Pilih Barang</option>
                                        <option v-for="item in counterStore.inventoryItems" :key="item.id" :value="item.id">
                                            {{ item.name }} ({{ item.item_code }})
                                        </option>
                                    </select>
                                    <div v-if="errors.inventory_item_id" class="invalid-feedback">{{ errors.inventory_item_id[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="acquisition_source">Sumber Akuisisi</label>
                                    <select v-model="form.acquisition_source" class="form-control" :class="{'is-invalid': errors.acquisition_source}">
                                        <option value="">Pilih Sumber</option>
                                        <option value="Beli">Beli</option>
                                        <option value="Hibah">Hibah</option>
                                        <option value="Bantuan">Bantuan</option>
                                        <option value="-">-</option>
                                    </select>
                                    <div v-if="errors.acquisition_source" class="invalid-feedback">{{ errors.acquisition_source[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="procurement_date">Tanggal Pengadaan</label>
                                    <input type="date" v-model="form.procurement_date" class="form-control" :class="{'is-invalid': errors.procurement_date}">
                                    <div v-if="errors.procurement_date" class="invalid-feedback">{{ errors.procurement_date[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="purchase_price">Harga Pembelian</label>
                                    <input type="number" v-model="form.purchase_price" class="form-control" :class="{'is-invalid': errors.purchase_price}" step="0.01" placeholder="Masukkan harga pembelian">
                                    <div v-if="errors.purchase_price" class="invalid-feedback">{{ errors.purchase_price[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="estimated_depreciation">Estimasi Depresiasi</label>
                                    <input type="number" v-model="form.estimated_depreciation" class="form-control" :class="{'is-invalid': errors.estimated_depreciation}" step="0.01" placeholder="Masukkan estimasi depresiasi (opsional)">
                                    <div v-if="errors.estimated_depreciation" class="invalid-feedback">{{ errors.estimated_depreciation[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select v-model="form.status" class="form-control" :class="{'is-invalid': errors.status}">
                                        <option value="">Pilih Status</option>
                                        <option value="Ada">Ada</option>
                                        <option value="Rusak">Rusak</option>
                                        <option value="Perbaikan">Perbaikan</option>
                                        <option value="Hilang">Hilang</option>
                                        <option value="Dipinjam">Dipinjam</option>
                                        <option value="-">-</option>
                                    </select>
                                    <div v-if="errors.status" class="invalid-feedback">{{ errors.status[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="unit_id">Unit Lokasi</label>
                                    <select v-model="form.unit_id" class="form-control" :class="{'is-invalid': errors.unit_id}">
                                        <option value="">Pilih Unit</option>
                                        <option v-for="unit in counterStore.locationUnits" :key="unit.id" :value="unit.id">
                                            {{ unit.name }}
                                        </option>
                                    </select>
                                    <div v-if="errors.unit_id" class="invalid-feedback">{{ errors.unit_id[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="room_id">Ruangan</label>
                                    <select v-model="form.room_id" class="form-control" :class="{'is-invalid': errors.room_id}">
                                        <option value="">Pilih Ruangan</option>
                                        <option v-for="room in counterStore.rooms" :key="room.id" :value="room.id">
                                            {{ room.name }} ({{ room.floor ? room.floor.name : 'N/A' }})
                                        </option>
                                    </select>
                                    <div v-if="errors.room_id" class="invalid-feedback">{{ errors.room_id[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="expected_replacement">Tanggal Estimasi Penggantian</label>
                                    <input type="date" v-model="form.expected_replacement" class="form-control" :class="{'is-invalid': errors.expected_replacement}">
                                    <div v-if="errors.expected_replacement" class="invalid-feedback">{{ errors.expected_replacement[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="last_checked_at">Tanggal Terakhir Dicek</label>
                                    <input type="date" v-model="form.last_checked_at" class="form-control" :class="{'is-invalid': errors.last_checked_at}">
                                    <div v-if="errors.last_checked_at" class="invalid-feedback">{{ errors.last_checked_at[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="pj_id">Penanggung Jawab</label>
                                    <select v-model="form.pj_id" class="form-control" :class="{'is-invalid': errors.pj_id}">
                                        <option value="">Pilih Penanggung Jawab</option>
                                        <option v-for="user in counterStore.users" :key="user.id" :value="user.id">
                                            {{ user.name }}
                                        </option>
                                    </select>
                                    <div v-if="errors.pj_id" class="invalid-feedback">{{ errors.pj_id[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="maintenance_frequency_type">Tipe Frekuensi Maintenance</label>
                                    <select v-model="form.maintenance_frequency_type" class="form-control" :class="{'is-invalid': errors.maintenance_frequency_type}">
                                        <option value="">Pilih Tipe</option>
                                        <option value="bulan">Bulan</option>
                                        <option value="km">KM</option>
                                        <option value="minggu">Minggu</option>
                                        <option value="semester">Semester</option>
                                    </select>
                                    <div v-if="errors.maintenance_frequency_type" class="invalid-feedback">{{ errors.maintenance_frequency_type[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="maintenance_frequency_value">Nilai Frekuensi Maintenance</label>
                                    <input type="number" v-model="form.maintenance_frequency_value" class="form-control" :class="{'is-invalid': errors.maintenance_frequency_value}" placeholder="Masukkan nilai frekuensi">
                                    <div v-if="errors.maintenance_frequency_value" class="invalid-feedback">{{ errors.maintenance_frequency_value[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="last_maintenance_at">Tanggal Terakhir Maintenance</label>
                                    <input type="date" v-model="form.last_maintenance_at" class="form-control" :class="{'is-invalid': errors.last_maintenance_at}">
                                    <div v-if="errors.last_maintenance_at" class="invalid-feedback">{{ errors.last_maintenance_at[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="next_due_date">Tanggal Jatuh Tempo Maintenance Berikutnya</label>
                                    <input type="date" v-model="form.next_due_date" class="form-control" :class="{'is-invalid': errors.next_due_date}">
                                    <div v-if="errors.next_due_date" class="invalid-feedback">{{ errors.next_due_date[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="next_due_km">KM Jatuh Tempo Maintenance Berikutnya</label>
                                    <input type="number" v-model="form.next_due_km" class="form-control" :class="{'is-invalid': errors.next_due_km}" placeholder="Masukkan KM jatuh tempo (jika tipe KM)">
                                    <div v-if="errors.next_due_km" class="invalid-feedback">{{ errors.next_due_km[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="last_odometer_reading">Pembacaan Odometer Terakhir</label>
                                    <input type="number" v-model="form.last_odometer_reading" class="form-control" :class="{'is-invalid': errors.last_odometer_reading}" placeholder="Masukkan pembacaan odometer terakhir">
                                    <div v-if="errors.last_odometer_reading" class="invalid-feedback">{{ errors.last_odometer_reading[0] }}</div>
                                </div>

                                <div class="form-group">
                                    <label for="description">Keterangan</label>
                                    <textarea v-model="form.description" class="form-control" :class="{'is-invalid': errors.description}" id="description" rows="3"></textarea>
                                    <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] }}</div>
                                </div>

                                <!-- INPUT UNTUK GAMBAR INVENTARIS -->
                                <div class="form-group">
                                    <label for="image">Gambar Inventaris</label>
                                    <div class="custom-file">
                                        <input type="file" @change="handleImageUpload" class="custom-file-input" id="image" accept="image/*">
                                        <label class="custom-file-label" for="image">{{ imageName || 'Pilih gambar...' }}</label>
                                    </div>
                                    <div v-if="imageUrlPreview || form.current_image_url" class="mt-2">
                                        <img :src="imageUrlPreview || form.current_image_url" alt="Preview Gambar" class="img-thumbnail" style="max-width: 200px; height: auto;">
                                        <div class="form-check mt-2" v-if="form.current_image_url && !imageUrlPreview">
                                            <input type="checkbox" class="form-check-input" id="removeImage" v-model="form.remove_image">
                                            <label class="form-check-label" for="removeImage">Hapus gambar yang ada</label>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Ukuran maksimal 2MB. Format: JPG, PNG, GIF.</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <router-link to="/inventories" class="btn btn-secondary ml-2">Batal</router-link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Layout from './Layout.vue';
import { useCounterStore } from '../stores/counter';
import { useRouter, useRoute } from 'vue-router';
// HAPUS BARIS INI: import axios from 'axios'; // <--- HAPUS INI

const counterStore = useCounterStore();
const router = useRouter();
const route = useRoute();

const inventoryId = route.params.id;

const form = ref({
    inventory_number: '',
    inventory_item_id: '',
    acquisition_source: '',
    procurement_date: '',
    purchase_price: '',
    estimated_depreciation: '',
    status: '',
    unit_id: '',
    room_id: '',
    expected_replacement: '',
    last_checked_at: '',
    pj_id: '',
    maintenance_frequency_type: '',
    maintenance_frequency_value: '',
    last_maintenance_at: '',
    next_due_date: '',
    next_due_km: '',
    last_odometer_reading: '',
    description: '',
    image: null, // Untuk file gambar baru
    current_image_url: null, // Untuk menampilkan gambar yang sudah ada
    remove_image: false, // Flag untuk menghapus gambar yang ada
});

const loading = ref(true);
const fetchError = ref(null);
const errors = ref({});

const imageName = ref('');
const imageUrlPreview = ref(null);

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.image = file;
        imageName.value = file.name;
        imageUrlPreview.value = URL.createObjectURL(file);
        form.value.remove_image = false; // Jika upload gambar baru, batalkan hapus gambar lama
    } else {
        form.value.image = null;
        imageName.value = '';
        imageUrlPreview.value = null;
    }
};

const fetchInventoryData = async () => {
    loading.value = true;
    fetchError.value = null;
    try {
        // Ganti axios.get menjadi window.axios.get
        const response = await window.axios.get(`/api/inventories/${inventoryId}`);
        const data = response.data;

        for (const key in form.value) {
            if (data[key] !== undefined && key !== 'image' && key !== 'current_image_url' && key !== 'remove_image') {
                if (['procurement_date', 'expected_replacement', 'last_checked_at', 'last_maintenance_at', 'next_due_date'].includes(key) && data[key]) {
                    form.value[key] = data[key].split('T')[0];
                } else {
                    form.value[key] = data[key];
                }
            }
        }
        form.value.current_image_url = data.image_path || null;

    } catch (error) {
        console.error('Failed to fetch inventory data:', error);
        fetchError.value = error.message || 'Gagal memuat data inventaris.';
    } finally {
        loading.value = false;
    }
};

const submitForm = async () => {
    errors.value = {}; // reset error state
    try {
        const formData = new FormData();

        Object.entries(form.value).forEach(([key, value]) => {
            if (key === 'current_image_url') return;

            if (key === 'image' && value instanceof File) {
                formData.append('image', value);
            } else if (key === 'remove_image') {
                formData.append('remove_image', value ? '1' : '0');
            } else if (value !== null && value !== '') {
                formData.append(key, value);
            }
        });

        formData.append('_method', 'PUT');

        const response = await window.axios.post(`/api/inventories/${inventoryId}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        alert('Inventaris berhasil diperbarui!');
        router.push('/inventories');

    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Gagal memperbarui inventaris:', error);
            alert('Terjadi kesalahan saat memperbarui inventaris.');
        }
    }
};


onMounted(() => {
    fetchInventoryData();
    counterStore.fetchInventoryItems();
    counterStore.fetchLocationUnits();
    counterStore.fetchRooms();
    counterStore.fetchUsersList();
});
</script>

<style scoped>
.custom-file-input:lang(en)~.custom-file-label::after {
    content: "Browse";
}
.custom-file-input:lang(id)~.custom-file-label::after {
    content: "Cari";
}
</style>
