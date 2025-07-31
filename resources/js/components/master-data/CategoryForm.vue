<!-- resources/js/components/master-data/CategoryForm.vue -->
<template>
  <div class="modal-backdrop show d-flex align-items-center justify-content-center">
    <div class="modal-dialog">
      <div class="modal-content">
        <form @submit.prevent="handleSubmit">
          <div class="modal-header">
            <h5 class="modal-title">{{ form.id ? 'Edit Kategori' : 'Tambah Kategori' }}</h5>
            <button type="button" class="close" @click="$emit('close')">
              <span>&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <div class="form-group">
              <label>Nama Kategori</label>
              <input v-model="form.name" type="text" class="form-control" required />
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="$emit('close')">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { useCounterStore } from '../../stores/counter';

const props = defineProps({
  editData: {
    type: Object,
    default: null,
  },
});
const emit = defineEmits(['close', 'saved']);
const counterStore = useCounterStore();

const form = reactive({
  id: null,
  name: '',
});

watch(
  () => props.editData,
  (newVal) => {
    if (newVal) {
      form.id = newVal.id;
      form.name = newVal.name;
    } else {
      form.id = null;
      form.name = '';
    }
  },
  { immediate: true }
);

const handleSubmit = async () => {
  if (form.id) {
    await counterStore.updateCategory(form.id, { name: form.name });
  } else {
    await counterStore.createCategory({ name: form.name });
  }
  emit('saved');
};
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 1050;
}
</style>
