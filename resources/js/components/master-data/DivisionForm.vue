<template>
  <div class="container mt-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">{{ isEdit ? 'Edit Divisi' : 'Tambah Divisi' }}</h5>
        <router-link class="btn btn-sm btn-secondary" :to="{ name: 'DivisionList' }">
          Kembali
        </router-link>
      </div>

      <div class="card-body">
        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label>Nama Divisi <span class="text-danger">*</span></label>
            <input
              v-model="form.name"
              type="text"
              class="form-control"
              required
              placeholder="Contoh: IT"
            />
          </div>

          <button type="submit" class="btn btn-success">
            {{ isEdit ? 'Update' : 'Simpan' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCounterStore } from '../../stores/counter';
import Swal from 'sweetalert2';

const route       = useRoute();
const router      = useRouter();
const counterStore = useCounterStore();

const isEdit = computed(() => !!route.params.id);

const form = reactive({
  name: ''
});

onMounted(() => {
  if (isEdit.value) {
    loadDivision();
  }
});

async function loadDivision() {
  try {
    const { data } = await axios.get(`/divisions/${route.params.id}`);
    form.name = data.name;
  } catch (e) {
    Swal.fire('Error', 'Gagal memuat data divisi', 'error');
    router.replace({ name: 'DivisionList' });
  }
}

async function handleSubmit() {
  try {
    if (isEdit.value) {
      await axios.put(`/divisions/${route.params.id}`, form);
      Swal.fire('Berhasil', 'Divisi diperbarui', 'success');
    } else {
      await axios.post('/divisions', form);
      Swal.fire('Berhasil', 'Divisi ditambahkan', 'success');
    }
    router.push({ name: 'DivisionList' });
  } catch (e) {
    Swal.fire('Gagal', e.response?.data?.message || 'Terjadi kesalahan', 'error');
  }
}
</script>