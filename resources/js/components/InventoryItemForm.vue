<!-- resources/js/components/InventoryItemForm.vue -->

<template>
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">{{ isEditMode ? 'Edit' : 'Tambah' }} Master Barang</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><router-link to="/dashboard">Home</router-link></li>
                            <li class="breadcrumb-item"><router-link to="/inventories">Inventaris</router-link></li>
                            <li class="breadcrumb-item active">{{ isEditMode ? 'Edit' : 'Tambah' }} Master Barang</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <div class="content">
            <div class="container-fluid">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Form Master Barang</h3>
                    </div>
                    <!-- /.card-header -->
                    <form @submit.prevent="handleSubmit">
                        <div class="card-body">
                            <div v-if="counterStore.inventoryItemError" class="alert alert-danger">
                                {{ counterStore.inventoryItemError }}
                            </div>

                            <div class="form-group">
                                <label for="item_code">Kode Barang <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="item_code" v-model="form.item_code" required>
                                <small v-if="errors.item_code" class="text-danger">{{ errors.item_code[0] }}</small>
                            </div>
                            <div class="form-group">
                                <label for="name">Nama Barang <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" v-model="form.name" required>
                                <small v-if="errors.name" class="text-danger">{{ errors.name[0] }}</small>
                            </div>
                            <div class="form-group">
                                <label for="quantity">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="quantity" v-model="form.quantity" required min="0">
                                <small v-if="errors.quantity" class="text-danger">{{ errors.quantity[0] }}</small>
                            </div>
                            <div class="form-group">
                                <label for="brand_id">Merk <span class="text-danger">*</span></label>
                                <select class="form-control" id="brand_id" v-model="form.brand_id" required>
                                    <option value="">-- Pilih Merk --</option>
                                    <option v-for="brand in counterStore.brands" :key="brand.id" :value="brand.id">
                                        {{ brand.name }}
                                    </option>
                                </select>
                                <small v-if="errors.brand_id" class="text-danger">{{ errors.brand_id[0] }}</small>
                            </div>
                            <div class="form-group">
                                <label for="category_id">Kategori <span class="text-danger">*</span></label>
                                <select class="form-control" id="category_id" v-model="form.category_id" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <option v-for="category in counterStore.categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                                <small v-if="errors.category_id" class="text-danger">{{ errors.category_id[0] }}</small>
                            </div>
                            <div class="form-group">
                                <label for="type_id">Jenis <span class="text-danger">*</span></label>
                                <select class="form-control" id="type_id" v-model="form.type_id" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    <option v-for="type in counterStore.itemTypes" :key="type.id" :value="type.id">
                                        {{ type.name }}
                                    </option>
                                </select>
                                <small v-if="errors.type_id" class="text-danger">{{ errors.type_id[0] }}</small>
                            </div>
                            <div class="form-group">
                                <label for="manufacturer">Produsen</label>
                                <input type="text" class="form-control" id="manufacturer" v-model="form.manufacturer">
                                <small v-if="errors.manufacturer" class="text-danger">{{ errors.manufacturer[0] }}</small>
                            </div>
                            <div class="form-group">
                                <label for="manufacture_year">Tahun Produksi</label>
                                <input type="number" class="form-control" id="manufacture_year" v-model="form.manufacture_year" min="1900" :max="currentYear">
                                <small v-if="errors.manufacture_year" class="text-danger">{{ errors.manufacture_year[0] }}</small>
                            </div>
                            <div class="form-group">
                                <label for="isbn">ISBN</label>
                                <input type="text" class="form-control" id="isbn" v-model="form.isbn">
                                <small v-if="errors.isbn" class="text-danger">{{ errors.isbn[0] }}</small>
                            </div>
                            <div class="form-group">
                                <label for="image">Gambar Barang</label>
                                <input type="file" class="form-control-file" id="image" @change="handleImageUpload">
                                <small v-if="errors.image" class="text-danger">{{ errors.image[0] }}</small>
                                <div v-if="form.current_image_url" class="mt-2">
                                    <p>Gambar saat ini:</p>
                                    <img :src="form.current_image_url" alt="Gambar Barang" style="max-width: 200px; height: auto;">
                                    <button type="button" class="btn btn-sm btn-danger ml-2" @click="removeImage">Hapus Gambar</button>
                                </div>
                                <small class="form-text text-muted">Maksimal 2MB, format JPG, PNG, GIF.</small>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" :disabled="counterStore.inventoryItemLoading">
                                <span v-if="counterStore.inventoryItemLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <span v-else>{{ isEditMode ? 'Update' : 'Simpan' }}</span>
                            </button>
                            <router-link to="/inventories" class="btn btn-default float-right">Batal</router-link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Layout from './Layout.vue';
import { useCounterStore } from '../stores/counter';

const counterStore = useCounterStore();
const route = useRoute();
const router = useRouter();

const isEditMode = computed(() => route.params.id !== undefined);
const currentYear = new Date().getFullYear();

const form = ref({
    item_code: '',
    name: '',
    quantity: 0,
    brand_id: '',
    category_id: '',
    type_id: '',
    manufacturer: '',
    manufacture_year: null,
    isbn: '',
    image: null,
    current_image_url: null,
    remove_image: false,
});

const errors = ref({});

/**
 * Load item detail from API for edit mode
 */
const loadItemData = async () => {
    if (!isEditMode.value) return;

    try {
        const item = await counterStore.fetchInventoryItemById(route.params.id);
        form.value.item_code = item.item_code;
        form.value.name = item.name;
        form.value.quantity = item.quantity;
        form.value.brand_id = item.brand_id;
        form.value.category_id = item.category_id;
        form.value.type_id = item.type_id;
        form.value.manufacturer = item.manufacturer;
        form.value.manufacture_year = item.manufacture_year;
        form.value.isbn = item.isbn;
        form.value.current_image_url = item.image_path ? `/storage/${item.image_path}` : null;
    } catch (error) {
        console.error('Gagal load item:', error);
        router.push('/inventories');
    }
};

const handleImageUpload = (event) => {
    form.value.image = event.target.files[0];
    form.value.remove_image = false;

    if (form.value.image) {
        const reader = new FileReader();
        reader.onload = (e) => {
            form.value.current_image_url = e.target.result;
        };
        reader.readAsDataURL(form.value.image);
    } else {
        form.value.current_image_url = null;
    }
};

const removeImage = () => {
    form.value.image = null;
    form.value.current_image_url = null;
    form.value.remove_image = true;
    const fileInput = document.getElementById('image');
    if (fileInput) fileInput.value = '';
};

const handleSubmit = async () => {
    errors.value = {};
    try {
        let payload = { ...form.value };

        if (payload.remove_image) {
            payload.image_path = null;
        }

        delete payload.current_image_url;
        delete payload.remove_image;

        if (!payload.image && !form.value.remove_image) {
            delete payload.image;
        }

        if (isEditMode.value) {
            await counterStore.updateInventoryItem(route.params.id, payload);
        } else {
            await counterStore.createInventoryItem(payload);
        }

        router.push('/inventories');
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Submission error:', error);
        }
    }
};

onMounted(async () => {
    await counterStore.fetchBrands();
    await counterStore.fetchCategories();
    await counterStore.fetchItemTypes();

    if (isEditMode.value) {
        await loadItemData();
    }
});

watch(route, async (newRoute) => {
    if (newRoute.path.endsWith('/master-data/barang/create')) {
        form.value = {
            item_code: '',
            name: '',
            quantity: 0,
            brand_id: '',
            category_id: '',
            type_id: '',
            manufacturer: '',
            manufacture_year: null,
            isbn: '',
            image: null,
            current_image_url: null,
            remove_image: false,
        };
        errors.value = {};
    } else if (newRoute.params.id) {
        await loadItemData();
    }
});
</script>


<style scoped>
/* Specific styles for this component (if needed) */
</style>
