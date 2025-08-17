<template>
  <div class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Tambah Inventaris Baru</h3>
        </div>

        <!-- Detail Master Barang -->
        <div class="card-body bg-light">
          <h5>Menambahkan Inventaris untuk:</h5>
          <div>
            <strong>{{ item.name }}</strong><br />
            <small class="text-muted">
              Merk: {{ item.brand?.name }} | Kategori: {{ item.category?.name }}<br />
              Tahun Produksi: {{ item.manufacture_year }}<br />
              ISBN: {{ item.isbn || '-' }}<br />
              Stok Master: {{ item.quantity }} unit
            </small>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm">
          <div class="card-body">

            <div class="form-group">
              <label>Tanggal Perolehan *</label>
              <input type="date" class="form-control" v-model="form.procurement_date" required />
            </div>

            <div class="form-group">
              <label>Sumber Perolehan *</label>
              <select v-model="form.acquisition_source" class="form-control" required>
                <option value="Beli">Beli</option>
                <option value="Hibah">Hibah</option>
                <option value="Bantuan">Bantuan</option>
                <option value="-">Lainnya</option>
              </select>
            </div>

            <div class="form-group">
              <label>Harga Beli *</label>
              <input type="number" class="form-control" v-model.number="form.purchase_price" required />
            </div>

            <div class="form-group">
              <label>Unit / Lokasi *</label>
              <select v-model="form.unit_id" class="form-control" required>
                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>

            <div class="form-group">
              <label>Ruang *</label>
              <select v-model="form.room_id" class="form-control" required>
                <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
            </div>

            <div class="form-group">
              <label>Status *</label>
              <select v-model="form.status" class="form-control" required>
                <option value="Ada">Ada</option>
                <option value="Rusak">Rusak</option>
                <option value="Perbaikan">Perbaikan</option>
                <option value="Hilang">Hilang</option>
                <option value="Dipinjam">Dipinjam</option>
                <option value="-">Lainnya</option>
              </select>
            </div>

            <div class="form-group">
              <label>Keterangan</label>
              <textarea v-model="form.description" class="form-control" rows="3"></textarea>
            </div>
          </div>

          <div class="card-footer">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm" role="status"></span>
              <span v-else>Simpan</span>
            </button>
            <router-link :to="`/master-data/barang/${itemId}/edit`" class="btn btn-secondary ml-2">Batal</router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route  = useRoute();
const router = useRouter();
const itemId  = route.query.item_id;
const autoNum = route.query.auto_num;

const loading = ref(false);
const item    = ref({});
const units   = ref([]);
const rooms   = ref([]);

const form = ref({
  procurement_date: new Date().toISOString().split('T')[0],
  acquisition_source: 'Beli',
  purchase_price: 0,
  unit_id: '',
  room_id: '',
  status: 'Ada',
  description: '',
});

onMounted(async () => {
  const { data } = await axios.get(`/inventory-items/${itemId}`);
  item.value = data;
  units.value  = (await axios.get('/units')).data;
  rooms.value  = (await axios.get('/rooms')).data;
});


const submitForm = async () => {
  loading.value = true;
  try {
    const formData = new FormData();
    Object.keys(form.value).forEach(k => formData.append(k, form.value[k]));

await axios.post(`/inventory-items/${itemId}/inventories`,    formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    alert('Inventaris berhasil ditambahkan!');
    router.push(`/master-data/barang/${itemId}/edit`);
  } catch (err) {
    console.error(err);
    alert('Gagal menambahkan inventaris.');
  } finally {
    loading.value = false;
  }
};
</script>