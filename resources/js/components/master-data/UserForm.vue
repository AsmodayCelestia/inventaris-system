<!-- resources/js/components/master-data/UserForm.vue -->
<template>
  <div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h5>
      <button class="btn btn-sm btn-secondary" @click="$emit('close')">Tutup</button>
    </div>

    <div class="card-body">
      <form @submit.prevent="handleSubmit">
        <div class="form-group">
          <label>Nama <span class="text-danger">*</span></label>
          <input v-model="form.name" type="text" class="form-control" required />
        </div>

        <div class="form-group">
          <label>Email <span class="text-danger">*</span></label>
          <input v-model="form.email" type="email" class="form-control" required />
        </div>

        <div class="form-group" v-if="!isEdit">
          <label>Password <span class="text-danger">*</span></label>
          <input v-model="form.password" type="password" class="form-control" required minlength="6" />
        </div>

        <div class="form-group" v-if="!isEdit">
          <label>Konfirmasi Password <span class="text-danger">*</span></label>
          <input v-model="form.password_confirmation" type="password" class="form-control" required />
        </div>

        <div class="form-group">
          <label>Role <span class="text-danger">*</span></label>
          <select v-model="form.role" class="form-control" required>
            <option value="">-- Pilih Role --</option>
            <option value="admin">Admin</option>
            <option value="head">Kepala Unit</option>
            <option value="karyawan">Karyawan</option>
          </select>
        </div>

        <!-- Divisi (FK) -->
        <div class="form-group">
          <label>Divisi <span class="text-danger">*</span></label>
          <select v-model="form.division_id" class="form-control">
            <option value="">-- Pilih Divisi --</option>
            <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>

        <button type="submit" class="btn btn-success">
          {{ isEdit ? 'Update' : 'Simpan' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, onMounted, computed, ref } from 'vue';
import { useCounterStore } from '../../stores/counter';

const emit = defineEmits(['saved', 'close']);
const counterStore = useCounterStore();

const props = defineProps({ modelValue: { type: Object, default: null } });

const divisions = ref([]);
const isEdit    = computed(() => !!props.modelValue);

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
  division_id: ''
});

onMounted(async () => {
  divisions.value = await counterStore.fetchDivisions();
});

watch(() => props.modelValue, (val) => {
  if (val) {
    form.name        = val.name;
    form.email       = val.email;
    form.role        = val.role;
    form.division_id = val.division_id || '';
    form.password = form.password_confirmation = '';
  } else {
    Object.assign(form, { name:'', email:'', password:'', password_confirmation:'', role:'', division_id:'' });
  }
}, { immediate: true });

const handleSubmit = async () => {
  try {
    const payload = { ...form };
    if (isEdit.value && !payload.password) {
      delete payload.password;
      delete payload.password_confirmation;
    }
    isEdit.value
      ? await counterStore.updateUser(props.modelValue.id, payload)
      : await counterStore.createUser(payload);
    emit('saved');
  } catch (e) {
    console.error(e.response?.data || e);
  }
};
</script>