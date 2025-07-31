<!-- resources/js/components/master-data/ItemTypeForm.vue -->
<template>
  <div class="modal-backdrop">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ editData ? 'Edit Jenis Barang' : 'Tambah Jenis Barang' }}</h5>
        <button type="button" class="close" @click="$emit('close')">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="submitForm">
          <div class="form-group">
            <label>Nama Jenis</label>
            <input
              type="text"
              class="form-control"
              v-model="form.name"
              required
              placeholder="Contoh: Elektronik, Otomotif, dll"
            />
          </div>

          <button type="submit" class="btn btn-primary">
            {{ editData ? 'Simpan Perubahan' : 'Tambah' }}
          </button>
          <button type="button" class="btn btn-secondary ml-2" @click="$emit('close')">Batal</button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';

const props = defineProps({
  editData: Object,
});
const emit = defineEmits(['close', 'saved']);

const counterStore = useCounterStore();
const form = reactive({
  name: '',
});

onMounted(() => {
  if (props.editData) {
    form.name = props.editData.name;
  }
});

watch(() => props.editData, (newVal) => {
  if (newVal) {
    form.name = newVal.name;
  } else {
    form.name = '';
  }
});

const submitForm = async () => {
  try {
    if (props.editData) {
      await counterStore.updateItemType(props.editData.id, { ...form });
    } else {
      await counterStore.createItemType({ ...form });
    }
    emit('saved');
  } catch (error) {
    console.error('Gagal menyimpan jenis barang', error);
  }
};
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.3);
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-content {
  background: white;
  padding: 20px;
  border-radius: 10px;
  width: 100%;
  max-width: 500px;
}
</style>
