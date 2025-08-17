<template>
  <!-- (content-header sama, tidak berubah) -->

  <!-- Main content -->
  <div class="content">
    <div class="container-fluid">
      <div class="card card-primary card-outline">
        <div class="card-header">
          <h3 class="card-title">Form Master Barang</h3>
        </div>

        <form @submit.prevent="handleSubmit">
          <div class="card-body">
            <div v-if="counterStore.inventoryItemError" class="alert alert-danger">
              {{ counterStore.inventoryItemError }}
            </div>

            <!-- Nama Barang -->
            <div class="form-group">
              <label for="name">Nama Barang <span class="text-danger">*</span></label>
              <input type="text" id="name" class="form-control" v-model="form.name" required>
              <small v-if="errors.name" class="text-danger">{{ errors.name[0] }}</small>
            </div>

            <!-- Jumlah -->
            <div class="form-group">
              <label for="quantity">Jumlah <span class="text-danger">*</span></label>
              <input type="number" id="quantity" class="form-control" v-model.number="form.quantity" required min="0">
              <small v-if="errors.quantity" class="text-danger">{{ errors.quantity[0] }}</small>
            </div>

            <!-- Merk -->
            <div class="form-group">
              <label for="brand_id">Merk <span class="text-danger">*</span></label>
              <select id="brand_id" class="form-control" v-model="form.brand_id" required>
                <option value="">-- Pilih Merk --</option>
                <option v-for="b in counterStore.brands" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
              <small v-if="errors.brand_id" class="text-danger">{{ errors.brand_id[0] }}</small>
            </div>

            <!-- Kategori -->
            <div class="form-group">
              <label for="category_id">Kategori <span class="text-danger">*</span></label>
              <select id="category_id" class="form-control" v-model="form.category_id" required>
                <option value="">-- Pilih Kategori --</option>
                <option v-for="c in counterStore.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <small v-if="errors.category_id" class="text-danger">{{ errors.category_id[0] }}</small>
            </div>

            <!-- Jenis -->
            <div class="form-group">
              <label for="type_id">Jenis <span class="text-danger">*</span></label>
              <select id="type_id" class="form-control" v-model="form.type_id" required>
                <option value="">-- Pilih Jenis --</option>
                <option v-for="t in counterStore.itemTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
              <small v-if="errors.type_id" class="text-danger">{{ errors.type_id[0] }}</small>
            </div>

            <!-- Tahun Produksi -->
            <div class="form-group">
              <label for="manufacture_year">Tahun Produksi</label>
              <input type="number" id="manufacture_year" class="form-control"
                     v-model.number="form.manufacture_year" min="1900" :max="currentYear">
              <small v-if="errors.manufacture_year" class="text-danger">{{ errors.manufacture_year[0] }}</small>
            </div>

            <!-- ISBN -->
            <div class="form-group">
              <label for="isbn">ISBN</label>
              <input type="text" id="isbn" class="form-control" v-model="form.isbn">
              <small v-if="errors.isbn" class="text-danger">{{ errors.isbn[0] }}</small>
            </div>

            <!-- Gambar -->
            <div class="form-group">
              <label for="image">Gambar Barang</label>
              <input type="file" id="image" class="form-control-file" @change="handleImageUpload">
              <small v-if="errors.image" class="text-danger">{{ errors.image[0] }}</small>
              <div v-if="form.current_image_url" class="mt-2">
                <p>Gambar saat ini:</p>
                <img :src="form.current_image_url" alt="Gambar Barang" style="max-width: 200px">
                <button type="button" class="btn btn-sm btn-danger ml-2" @click="removeImage">Hapus</button>
              </div>
              <small class="form-text text-muted">Maks 2MB, JPG/PNG/GIF.</small>
            </div>
          </div>

          <div class="card-footer">
            <button type="submit" class="btn btn-primary" :disabled="counterStore.inventoryItemLoading">
              <span v-if="counterStore.inventoryItemLoading" class="spinner-border spinner-border-sm"></span>
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
import { useCounterStore } from '../stores/counter';

const counterStore = useCounterStore();
const route = useRoute();
const router = useRouter();

const isEditMode = computed(() => route.params.id !== undefined);
const currentYear = new Date().getFullYear();

// form ringkas: tanpa item_code & manufacturer
const form = ref({
  name: '',
  quantity: 0,
  brand_id: '',
  category_id: '',
  type_id: '',
  manufacture_year: null,
  isbn: '',
  image: null,
  current_image_url: null,
  remove_image: false,
});

const errors = ref({});

const loadItemData = async () => {
  if (!isEditMode.value) return;
  try {
    const item = await counterStore.fetchInventoryItemById(route.params.id);
    form.value.name           = item.name;
    form.value.quantity       = item.quantity;
    form.value.brand_id       = item.brand_id;
    form.value.category_id    = item.category_id;
    form.value.type_id        = item.type_id;
    form.value.manufacture_year = item.manufacture_year;
    form.value.isbn           = item.isbn;
    form.value.current_image_url = item.image_path ? `/storage/${item.image_path}` : null;
  } catch (e) {
    console.error('Gagal load item:', e);
    router.push('/inventories');
  }
};

const handleImageUpload = (e) => {
  form.value.image = e.target.files[0];
  form.value.remove_image = false;
  if (form.value.image) {
    const reader = new FileReader();
    reader.onload = ev => (form.value.current_image_url = ev.target.result);
    reader.readAsDataURL(form.value.image);
  } else {
    form.value.current_image_url = null;
  }
};

const removeImage = () => {
  form.value.image = null;
  form.value.current_image_url = null;
  form.value.remove_image = true;
  document.getElementById('image').value = '';
};

const handleSubmit = async () => {
  errors.value = {};
  try {
    const payload = { ...form.value };
    if (payload.remove_image) payload.image_path = null;
    delete payload.current_image_url;
    delete payload.remove_image;
    if (!payload.image) delete payload.image;

    isEditMode.value
      ? await counterStore.updateInventoryItem(route.params.id, payload)
      : await counterStore.createInventoryItem(payload);

    router.push('/inventories');
  } catch (err) {
    if (err.response?.status === 422) errors.value = err.response.data.errors;
    else console.error(err);
  }
};

onMounted(async () => {
  await counterStore.fetchBrands();
  await counterStore.fetchCategories();
  await counterStore.fetchItemTypes();
  if (isEditMode.value) await loadItemData();
});

watch(route, () => {
  if (route.path.endsWith('/master-data/barang/create')) {
    Object.assign(form.value, {
      name: '',
      quantity: 0,
      brand_id: '',
      category_id: '',
      type_id: '',
      manufacture_year: null,
      isbn: '',
      image: null,
      current_image_url: null,
      remove_image: false,
    });
    errors.value = {};
  } else if (route.params.id) loadItemData();
});
</script>