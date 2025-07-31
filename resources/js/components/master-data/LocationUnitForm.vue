<!-- resources/js/components/master-data/LocationUnitForm.vue -->
<template>
  <div class="card mb-4">
    <div class="card-header">
      <strong>{{ editData ? 'Edit' : 'Tambah' }} Unit Lokasi</strong>
    </div>
    <div class="card-body">
      <form @submit.prevent="save">
        <div class="form-group">
          <label>Nama Unit</label>
          <input v-model="form.name" type="text" class="form-control" required />
        </div>
        <div class="mt-3">
          <button type="submit" class="btn btn-success">{{ editData ? 'Update' : 'Simpan' }}</button>
          <button class="btn btn-secondary ml-2" type="button" @click="$emit('close')">Batal</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';

const props = defineProps({
  editData: Object
});
const emit = defineEmits(['close', 'saved']);

const counterStore = useCounterStore();
const form = reactive({
  name: ''
});

watch(() => props.editData, (newVal) => {
  if (newVal) {
    form.name = newVal.name || '';
  }
});

const save = async () => {
  try {
    if (props.editData) {
      await counterStore.updateLocationUnit(props.editData.id, form);
    } else {
      await counterStore.createLocationUnit(form);
    }
    emit('saved');
  } catch (error) {
    console.error('Gagal menyimpan unit lokasi:', error);
  }
};

onMounted(() => {
  if (props.editData) {
    form.name = props.editData.name || '';
  }
});
</script>
