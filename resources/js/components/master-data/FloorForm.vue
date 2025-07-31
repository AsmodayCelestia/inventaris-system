<!-- resources/js/components/master-data/FloorForm.vue -->
<template>
  <div class="card">
    <div class="card-header">
      <h5>{{ editData ? 'Edit Lantai' : 'Tambah Lantai' }}</h5>
    </div>
    <div class="card-body">
      <form @submit.prevent="save">
        <div class="form-group">
          <label for="name">Nama Lantai</label>
          <input
            v-model="form.number"
            type="text"
            class="form-control"
            id="name"
            required
            placeholder="Contoh: Lantai 1"
          />
        </div>

        <div class="form-group mt-3">
          <label for="unit">Unit</label>
          <select v-model="form.unit_id" class="form-control" required>
            <option value="" disabled selected>Pilih Unit</option>
            <option v-for="unit in counterStore.locationUnits" :key="unit.id" :value="unit.id">
              {{ unit.name }}
            </option>
          </select>
        </div>

        <div class="mt-4 text-right">
          <button type="submit" class="btn btn-success mr-2">
            {{ loading ? 'Menyimpan...' : 'Simpan' }}
          </button>
          <button type="button" class="btn btn-secondary" @click="$emit('close')">Batal</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';

const props = defineProps({ editData: Object });
const emit = defineEmits(['close', 'saved']);
const counterStore = useCounterStore();
const loading = ref(false);

const form = reactive({
  number: '',
  unit_id: '',
});

// Isi form saat edit
watch(() => props.editData, (newVal) => {
  if (newVal) {
    form.number = newVal.number || ''; // GANTI INI
    form.unit_id = newVal.unit_id || '';
  } else {
    form.number = ''; // GANTI INI
    form.unit_id = '';
  }
}, { immediate: true });

const save = async () => {
  loading.value = true;

  const payload = {
    number: form.number, // GANTI INI
    unit_id: form.unit_id,
  };

  if (props.editData) {
    await counterStore.updateFloor(props.editData.id, payload);
  } else {
    await counterStore.createFloor(payload);
  }

  loading.value = false;
  emit('saved');
};

// Fetch unit jika belum ada
onMounted(() => {
  if (counterStore.locationUnits.length === 0) {
    counterStore.fetchLocationUnits();
  }
});
</script>
