<!-- resources/js/components/master-data/LocationUnitList.vue -->
<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4>Data Unit Lokasi</h4>
      <button class="btn btn-primary" @click="showForm = true">
        Tambah Unit
      </button>
    </div>

    <LocationUnitForm v-if="showForm" @close="showForm = false" @saved="refresh" />

    <div v-if="counterStore.locationUnits.length === 0" class="text-muted">Belum ada data unit lokasi.</div>

    <table v-if="counterStore.locationUnits.length > 0" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Unit</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(unit, index) in counterStore.locationUnits" :key="unit.id">
          <td>{{ index + 1 }}</td>
          <td>{{ unit.name }}</td>
          <td>
            <button class="btn btn-sm btn-info mr-2" @click="edit(unit)">Edit</button>
            <button class="btn btn-sm btn-danger" @click="destroy(unit.id)">Hapus</button>
          </td>
        </tr>
      </tbody>
    </table>

    <LocationUnitForm v-if="editData" :editData="editData" @close="editData = null" @saved="refresh" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useCounterStore } from '../../stores/counter';
import LocationUnitForm from './LocationUnitForm.vue';

const counterStore = useCounterStore();
const showForm = ref(false);
const editData = ref(null);

const refresh = async () => {
  showForm.value = false;
  editData.value = null;
  await counterStore.fetchLocationUnits();
};

const edit = (unit) => {
  editData.value = { ...unit };
};

const destroy = async (id) => {
  if (confirm('Hapus data ini?')) {
    await counterStore.deleteLocationUnit(id);
    await counterStore.fetchLocationUnits();
  }
};

onMounted(() => {
  counterStore.fetchLocationUnits();
});
</script>
