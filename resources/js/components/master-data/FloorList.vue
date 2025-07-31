<!-- resources/js/components/master-data/FloorList.vue -->
<template>
  <div class="container mt-4">
    <h3>Data Lantai</h3>
    <button class="btn btn-primary mb-3" @click="openForm(null)">
      + Tambah Lantai
    </button>

    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Nama Lantai</th>
          <th>Unit</th>
          <th style="width: 150px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="floor in counterStore.floors" :key="floor.id">
          <td>{{ floor.number }}</td>
          <td>{{ floor.unit?.name || '—' }}</td>
          <td>
            <button class="btn btn-sm btn-warning mr-1" @click="openForm(floor)">Edit</button>
            <button class="btn btn-sm btn-danger" @click="remove(floor.id)">Hapus</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal form -->
    <div v-if="showForm" class="modal-backdrop">
      <div class="modal-content-wrapper">
        <FloorForm
          :editData="selectedFloor"
          @close="showForm = false"
          @saved="handleSaved"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';
import FloorForm from './FloorForm.vue';

const counterStore = useCounterStore();
const showForm = ref(false);
const selectedFloor = ref(null);

const openForm = (floor) => {
  selectedFloor.value = floor ? { ...floor } : null;
  showForm.value = true;
};

const remove = async (id) => {
  if (confirm('Yakin ingin menghapus lantai ini?')) {
    await counterStore.deleteFloor(id);
    await counterStore.fetchFloors();
  }
};

const handleSaved = async () => {
  await counterStore.fetchFloors(); // Refresh data
  showForm.value = false;
};

onMounted(() => {
  counterStore.fetchFloors();
});
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  background: rgba(0, 0, 0, 0.4);
  width: 100%;
  height: 100%;
  z-index: 1050;
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-content-wrapper {
  background: white;
  border-radius: 8px;
  max-width: 500px;
  width: 100%;
  padding: 20px;
}
</style>
