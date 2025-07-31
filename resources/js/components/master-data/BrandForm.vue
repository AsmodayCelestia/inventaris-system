<!-- resources/js/components/master-data/BrandForm.vue -->
<template>
  <div class="modal-backdrop">
    <div class="modal-dialog">
      <div class="modal-content">
        <form @submit.prevent="submitForm">
          <div class="modal-header">
            <h5 class="modal-title">{{ editData ? 'Edit Merk' : 'Tambah Merk' }}</h5>
            <button type="button" class="close" @click="$emit('close')">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="brandName">Nama Merk</label>
              <input
                id="brandName"
                type="text"
                v-model="form.name"
                class="form-control"
                required
              />
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="$emit('close')">Batal</button>
            <button type="submit" class="btn btn-primary">
              {{ editData ? 'Update' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';

const props = defineProps({
  editData: Object
});
const emit = defineEmits(['saved', 'close']);

const form = ref({
  name: ''
});

const counterStore = useCounterStore();

watch(
  () => props.editData,
  (newVal) => {
    if (newVal) {
      form.value.name = newVal.name;
    } else {
      form.value.name = '';
    }
  },
  { immediate: true }
);

const submitForm = async () => {
  try {
    if (props.editData) {
      await counterStore.updateBrand(props.editData.id, form.value);
    } else {
      await counterStore.createBrand(form.value);
    }
    emit('saved');
  } catch (error) {
    console.error('Gagal simpan merk:', error);
  }
};
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}
.modal-dialog {
  width: 100%;
  max-width: 500px;
}
</style>
