<template>
  <div>
    <form @submit.prevent="submitForm">
      <div class="form-group">
        <label for="inventory_number">Nomor Inventaris</label>
        <input
          type="text"
          v-model="form.inventory_number"
          class="form-control"
          :class="{ 'is-invalid': errors.inventory_number }"
          id="inventory_number"
          placeholder="Masukkan nomor inventaris"
        />
        <div v-if="errors.inventory_number" class="invalid-feedback">
          {{ errors.inventory_number[0] }}
        </div>
      </div>

      <div class="form-group">
        <label for="inventory_item_id">Nama Barang</label>
        <select
          v-model="form.inventory_item_id"
          class="form-control"
          :class="{ 'is-invalid': errors.inventory_item_id }"
        >
          <option value="">Pilih Barang</option>
          <option
            v-for="item in counterStore.inventoryItems"
            :key="item.id"
            :value="item.id"
          >
            {{ item.name }} ({{ item.item_code }})
          </option>
        </select>
        <div v-if="errors.inventory_item_id" class="invalid-feedback">
          {{ errors.inventory_item_id[0] }}
        </div>
      </div>

      <div class="form-group">
        <label for="acquisition_source">Sumber Akuisisi</label>
        <select
          v-model="form.acquisition_source"
          class="form-control"
          :class="{ 'is-invalid': errors.acquisition_source }"
        >
          <option value="">Pilih Sumber</option>
          <option value="Beli">Beli</option>
          <option value="Hibah">Hibah</option>
          <option value="Bantuan">Bantuan</option>
          <option value="-">-</option>
        </select>
        <div v-if="errors.acquisition_source" class="invalid-feedback">
          {{ errors.acquisition_source[0] }}
        </div>
      </div>

      <div class="form-group">
        <label for="procurement_date">Tanggal Pengadaan</label>
        <input
          type="date"
          v-model="form.procurement_date"
          class="form-control"
          :class="{ 'is-invalid': errors.procurement_date }"
        />
        <div v-if="errors.procurement_date" class="invalid-feedback">
          {{ errors.procurement_date[0] }}
        </div>
      </div>

      <!-- Harga Pembelian (hanya Admin & Keuangan) -->
      <div v-if="canEditPrice" class="form-group">
        <label for="purchase_price">Harga Pembelian</label>
        <input
          type="number"
          v-model="form.purchase_price"
          class="form-control"
          id="purchase_price"
          step="0.01"
          placeholder="Masukkan harga pembelian"
        />
      </div>

      <!-- Estimasi Depresiasi (hanya Admin & Keuangan) -->
      <div v-if="canEditPrice" class="form-group">
        <label for="estimated_depreciation">Estimasi Depresiasi</label>
        <input
          type="number"
          v-model="form.estimated_depreciation"
          class="form-control"
          id="estimated_depreciation"
          step="0.01"
          placeholder="Masukkan estimasi depresiasi (opsional)"
        />
      </div>

      <div class="form-group">
        <label for="status">Status</label>
        <select
          v-model="form.status"
          class="form-control"
          :class="{ 'is-invalid': errors.status }"
        >
          <option value="">Pilih Status</option>
          <option value="Ada">Ada</option>
          <option value="Rusak">Rusak</option>
          <option value="Perbaikan">Perbaikan</option>
          <option value="Hilang">Hilang</option>
          <option value="Dipinjam">Dipinjam</option>
          <option value="-">-</option>
        </select>
        <div v-if="errors.status" class="invalid-feedback">
          {{ errors.status[0] }}
        </div>
      </div>

      <div class="form-group">
        <label for="unit_id">Unit Lokasi</label>
        <select
          v-model="form.unit_id"
          class="form-control"
          :class="{ 'is-invalid': errors.unit_id }"
        >
          <option value="">Pilih Unit</option>
          <option
            v-for="unit in counterStore.locationUnits"
            :key="unit.id"
            :value="unit.id"
          >
            {{ unit.name }}
          </option>
        </select>
        <div v-if="errors.unit_id" class="invalid-feedback">
          {{ errors.unit_id[0] }}
        </div>
      </div>

      <div class="form-group">
        <label for="room_id">Ruangan</label>
        <select
          v-model="form.room_id"
          class="form-control"
          :class="{ 'is-invalid': errors.room_id }"
        >
          <option value="">Pilih Ruangan</option>
          <option
            v-for="room in counterStore.rooms"
            :key="room.id"
            :value="room.id"
          >
            {{ room.name }}
            ({{ room.floor ? room.floor.name : 'N/A' }})
          </option>
        </select>
        <div v-if="errors.room_id" class="invalid-feedback">
          {{ errors.room_id[0] }}
        </div>
      </div>

      <div class="form-group">
        <label for="description">Keterangan</label>
        <textarea
          v-model="form.description"
          class="form-control"
          :class="{ 'is-invalid': errors.description }"
          id="description"
          rows="3"
        ></textarea>
        <div v-if="errors.description" class="invalid-feedback">
          {{ errors.description[0] }}
        </div>
      </div>

      <!-- Gambar Inventaris -->
      <div class="form-group">
        <label for="image">Gambar Inventaris</label>
        <div class="custom-file">
          <input
            type="file"
            @change="handleImageUpload"
            class="custom-file-input"
            id="image"
            accept="image/*"
          />
          <label class="custom-file-label" for="image">
            {{ imageName || 'Pilih gambar...' }}
          </label>
        </div>
        <div v-if="imageUrlPreview || form.current_image_url" class="mt-2">
          <img
            :src="imageUrlPreview || form.current_image_url"
            alt="Preview Gambar"
            class="img-thumbnail"
            style="max-width: 200px; height: auto"
          />
          <div
            class="form-check mt-2"
            v-if="form.current_image_url && !imageUrlPreview"
          >
            <input
              type="checkbox"
              class="form-check-input"
              id="removeImage"
              v-model="form.remove_image"
            />
            <label class="form-check-label" for="removeImage">
              Hapus gambar yang ada
            </label>
          </div>
        </div>
        <small class="form-text text-muted">
          Ukuran maksimal 2MB. Format: JPG, PNG, GIF.
        </small>
      </div>

      <div class="card-footer">
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <router-link to="/inventories" class="btn btn-secondary ml-2">
          Batal
        </router-link>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useCounterStore } from '../stores/counter';

const router = useRouter();
const route = useRoute();
const counterStore = useCounterStore();

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
  description: '',
  image: null,
  current_image_url: null,
  remove_image: false,
});

const errors = ref({});
const loading = ref(false);
const fetchError = ref(null);
const imageName = ref('');
const imageUrlPreview = ref(null);

const canEditPrice = computed(() =>
  counterStore.userRole === 'admin' || counterStore.userDivisi === 'Keuangan'
);

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.value.image = file;
    imageName.value = file.name;
    imageUrlPreview.value = URL.createObjectURL(file);
    form.value.remove_image = false;
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
    const res = await window.axios.get(`/inventories/${inventoryId}`);
    const data = res.data;
    for (const key in form.value) {
      if (['image', 'current_image_url', 'remove_image'].includes(key)) continue;
      if (data[key] !== undefined) {
        form.value[key] = ['procurement_date', 'expected_replacement'].includes(key) && data[key]
          ? data[key].split('T')[0]
          : data[key];
      }
    }
    form.value.current_image_url = data.image_path || null;
  } catch (error) {
    fetchError.value = error.message || 'Gagal memuat data inventaris.';
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  errors.value = {};
  try {
    const formData = new FormData();

    // hapus field harga kalau user bukan admin / keuangan
    if (!canEditPrice.value) {
      formData.delete('purchase_price');
      formData.delete('estimated_depreciation');
    }

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

    await window.axios.post(`/inventories/${inventoryId}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    alert('Inventaris berhasil diperbarui!');
    router.push('/inventories');
  } catch (error) {
    errors.value = error.response?.data?.errors || {};
    alert('Terjadi kesalahan saat memperbarui inventaris.');
  }
};

onMounted(() => {
  const canAccess =
    counterStore.isAdmin ||
    counterStore.isHead ||
    (counterStore.isKaryawan && counterStore.userDivisi === 'Keuangan');

  if (!canAccess) {
    alert('Anda tidak memiliki akses ke halaman ini.');
    router.push('/inventories');
    return;
  }

  fetchInventoryData();
  counterStore.fetchInventoryItems();
  counterStore.fetchLocationUnits();
  counterStore.fetchRooms();
});
</script>

<style scoped>
.custom-file-input:lang(en)~.custom-file-label::after {
  content: "Browse";
}
.custom-file-input:lang(id)~.custom-file-label::after {
  content: "Cari";
}

.wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
.content-wrapper {
  min-height: calc(100vh - 56px); /* tinggi navbar + footer */
}
</style>