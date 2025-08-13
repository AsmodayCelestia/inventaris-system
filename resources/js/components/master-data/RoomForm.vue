<!-- resources/js/components/master-data/RoomForm.vue -->
<template>
  <div class="modal-backdrop">
    <div class="modal-content-wrapper">
      <div class="card">
        <div class="card-header">
          <h5>{{ form.id ? 'Edit Ruangan' : 'Tambah Ruangan' }}</h5>
        </div>
        <div class="card-body">
          <form @submit.prevent="submitForm">
            <!-- Nama Ruangan -->
            <div class="form-group">
              <label>Nama Ruangan</label>
              <input
                v-model="form.name"
                type="text"
                class="form-control"
                required
                placeholder="Contoh: Ruang Server"
              />
            </div>

            <!-- Lantai -->
            <div class="form-group mt-3">
              <label>Lantai</label>
              <select v-model="form.floor_id" class="form-control" required>
                <option value="" disabled>Pilih Lantai</option>
                <option v-for="floor in counterStore.floors" :key="floor.id" :value="floor.id">
                  {{ floor.number }} ({{ floor.unit?.name || '-' }})
                </option>
              </select>
            </div>

            <!-- Pengawas Ruangan -->
            <div class="form-group mt-3">
              <label>Pengawas Ruangan</label>
              <select v-model="form.pj_lokasi_id" class="form-control">
                <option :value="null">-- Tidak Ada --</option>
                <option v-for="u in counterStore.usersList" :key="u.id" :value="u.id">
                  {{ u.name }}
                </option>
              </select>
            </div>

            <!-- Nama unit otomatis -->
            <div v-if="selectedUnitName" class="mt-2 text-muted">
              <small>Unit: {{ selectedUnitName }}</small>
            </div>

            <!-- Tombol -->
            <div class="form-group text-right mt-4">
              <button type="submit" class="btn btn-success mr-2">Simpan</button>
              <button type="button" class="btn btn-secondary" @click="$emit('close')">Batal</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, watch, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';

const props   = defineProps({ editData: Object });
const emit    = defineEmits(['saved', 'close']);
const counterStore = useCounterStore();

const form = reactive({
  id: null,
  name: '',
  floor_id: '',
  pj_lokasi_id: null,   // tambahan
});

const selectedUnitName = computed(() => {
  const f = counterStore.floors.find(f => f.id === form.floor_id);
  return f?.unit?.name || '-';
});

watch(() => props.editData, (val) => {
  if (val) {
    form.id             = val.id || null;
    form.name           = val.name || '';
    form.floor_id       = val.floor_id || '';
    form.pj_lokasi_id   = val.pj_lokasi_id || null;
  } else {
    Object.assign(form, { id: null, name: '', floor_id: '', pj_lokasi_id: null });
  }
}, { immediate: true });

const submitForm = async () => {
  if (!form.floor_id) return;

  const payload = {
    name: form.name,
    floor_id: form.floor_id,
    pj_lokasi_id: form.pj_lokasi_id,
  };

  form.id
    ? await counterStore.updateRoom(form.id, payload)
    : await counterStore.createRoom(payload);

  emit('saved');
};

onMounted(() => {
  if (counterStore.floors.length === 0) counterStore.fetchFloors();
  if (counterStore.usersList.length === 0) counterStore.fetchUsersList();
});
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.4);
  z-index: 1050;
  display: flex;
  justify-content: center;
  align-items: center;
}
.modal-content-wrapper {
  width: 100%;
  max-width: 500px;
}
</style>