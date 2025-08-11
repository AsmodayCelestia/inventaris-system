<!-- resources/js/components/master-data/UserForm.vue -->
<template>
  <div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ editData ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h5>
      <button class="btn btn-sm btn-secondary" @click="$emit('close')">Tutup</button>
    </div>

    <div class="card-body">
      <form @submit.prevent="submitForm">
        <!-- Nama -->
        <div class="form-group">
          <label>Nama <span class="text-danger">*</span></label>
          <input v-model="form.name" type="text" class="form-control" required />
        </div>

        <!-- Email -->
        <div class="form-group">
          <label>Email <span class="text-danger">*</span></label>
          <input v-model="form.email" type="email" class="form-control" required />
        </div>

        <!-- Password (hanya saat tambah) -->
        <div class="form-group" v-if="!editData">
          <label>Password <span class="text-danger">*</span></label>
          <input v-model="form.password" type="password" class="form-control" required minlength="6" />
        </div>

        <!-- Konfirmasi Password (hanya saat tambah) -->
        <div class="form-group" v-if="!editData">
          <label>Konfirmasi Password <span class="text-danger">*</span></label>
          <input v-model="form.password_confirmation" type="password" class="form-control" required minlength="6" />
        </div>

        <!-- Role -->
        <div class="form-group">
          <label>Role <span class="text-danger">*</span></label>
          <select v-model="form.role" class="form-control" required>
            <option value="">-- Pilih Role --</option>
            <option value="admin">Admin</option>
            <option value="head">Kepala Unit</option>
            <option value="karyawan">Karyawan</option>
          </select>
        </div>

        <!-- Divisi -->
        <div class="form-group">
          <label>Divisi</label>
          <input v-model="form.divisi" type="text" class="form-control" />
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-success">
          {{ editData ? 'Update' : 'Simpan' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { useCounterStore } from '../../stores/counter';

const emit = defineEmits(['saved', 'close']);
const counterStore = useCounterStore();

const props = defineProps({
  editData: {
    type: Object,
    default: null
  }
});

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '', // wajib saat tambah
  role: '',
  divisi: ''
});

watch(
  () => props.editData,
  (val) => {
    if (val) {
      form.name     = val.name;
      form.email    = val.email;
      form.role     = val.role;
      form.divisi   = val.divisi;
      form.password = '';
      form.password_confirmation = '';
    } else {
      Object.assign(form, {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: '',
        divisi: ''
      });
    }
  },
  { immediate: true }
);

const submitForm = async () => {
  try {
    const payload = { ...form };
    // saat edit, hapus password jika kosong
    if (props.editData && !payload.password) {
      delete payload.password;
      delete payload.password_confirmation;
    }

    if (props.editData) {
      await counterStore.updateUser(props.editData.id, payload);
    } else {
      await counterStore.createUser(payload);
    }
    emit('saved');
  } catch (e) {
    // error sudah ditangkap di store, bisa ditampilkan di sini jika mau
    console.error(e);
  }
};
</script>