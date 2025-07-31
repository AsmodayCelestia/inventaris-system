<!-- resources/js/components/master-data/UserForm.vue -->
<template>
  <div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ editData ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h5>
      <button class="btn btn-sm btn-secondary" @click="$emit('close')">Tutup</button>
    </div>
    <div class="card-body">
      <form @submit.prevent="submitForm">
        <div class="form-group">
          <label>Nama</label>
          <input type="text" class="form-control" v-model="form.name" required />
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" class="form-control" v-model="form.email" required />
        </div>

        <div class="form-group" v-if="!editData">
          <label>Password</label>
          <input type="password" class="form-control" v-model="form.password" required />
        </div>

        <div class="form-group">
          <label>Role</label>
          <select class="form-control" v-model="form.role" required>
            <option value="">-- Pilih Role --</option>
            <option value="admin">Admin</option>
            <option value="head">Kepala Unit</option>
            <option value="karyawan">Petugas</option>
          </select>
        </div>

        <div class="form-group">
          <label>Divisi</label>
          <input type="text" class="form-control" v-model="form.divisi" />
        </div>

        <button type="submit" class="btn btn-success">{{ editData ? 'Update' : 'Simpan' }}</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';

const emit = defineEmits(['saved', 'close']);
const counterStore = useCounterStore();
const props = defineProps({
  editData: Object
});

const form = reactive({
  name: '',
  email: '',
  password: '',
  role: '',
  divisi: ''
});

watch(
  () => props.editData,
  (newData) => {
    if (newData) {
      form.name = newData.name;
      form.email = newData.email;
      form.password = '';
      form.role = newData.role;
      form.divisi = newData.divisi;
    } else {
      form.name = '';
      form.email = '';
      form.password = '';
      form.role = '';
      form.divisi = '';
    }
  },
  { immediate: true }
);

const submitForm = async () => {
  if (props.editData) {
    await counterStore.updateUser(props.editData.id, form);
  } else {
    await counterStore.createUser(form);
  }
  emit('saved');
};
</script>
