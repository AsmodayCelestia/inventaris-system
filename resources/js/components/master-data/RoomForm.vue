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

            <div class="form-group mt-3">
              <label>Lantai</label>
              <select v-model="form.floor_id" class="form-control" required>
                <option value="" disabled selected>Pilih Lantai</option>
                <option v-for="floor in counterStore.floors" :key="floor.id" :value="floor.id">
                  {{ floor.number }} ({{ floor.unit?.name || '-' }})
                </option>
              </select>
            </div>

            <!-- Tampilkan nama unit otomatis -->
            <div v-if="selectedUnitName" class="mt-2 text-muted">
              <small>Unit: {{ selectedUnitName }}</small>
            </div>

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

const props = defineProps({ editData: Object });
const emit = defineEmits(['saved', 'close']);
const counterStore = useCounterStore();

const form = reactive({
  id: null,
  name: '',
  floor_id: '',
});

// Komputasi nama unit berdasarkan floor yang dipilih
const selectedUnitName = computed(() => {
  const selectedFloor = counterStore.floors.find(f => f.id === form.floor_id);
  return selectedFloor?.unit?.name || '-';
});

// Saat data edit dikirim, isi form-nya
watch(() => props.editData, (newVal) => {
  if (newVal) {
    form.id = newVal.id || null;
    form.name = newVal.name || '';
    form.floor_id = newVal.floor_id || '';
  } else {
    form.id = null;
    form.name = '';
    form.floor_id = '';
  }
}, { immediate: true });

// Saat disubmit, kirim data ke API via store
const submitForm = async () => {
  if (!form.floor_id) return;

  if (form.id) {
    await counterStore.updateRoom(form.id, {
      name: form.name,
      floor_id: form.floor_id,
    });
  } else {
    await counterStore.createRoom({
      name: form.name,
      floor_id: form.floor_id,
    });
  }

  emit('saved');
};

// Fetch floors jika belum ada
onMounted(() => {
  if (counterStore.floors.length === 0) {
    counterStore.fetchFloors();
  }
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
