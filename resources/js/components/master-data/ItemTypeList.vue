<!-- resources/js/components/master-data/ItemTypeList.vue -->
<template>
  <div class="p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Daftar Jenis Barang</h3>
      <button class="btn btn-primary" @click="showForm = true">+ Tambah Jenis</button>
    </div>

    <div v-if="counterStore.itemTypes.length === 0">
      <p>Belum ada data jenis barang.</p>
    </div>

    <table v-else class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Jenis</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(itemType, index) in counterStore.itemTypes" :key="itemType.id">
          <td>{{ index + 1 }}</td>
          <td>{{ itemType.name }}</td>
          <td>
            <button class="btn btn-sm btn-warning mr-2" @click="editItem(itemType)">Edit</button>
            <button class="btn btn-sm btn-danger" @click="deleteItem(itemType.id)">Hapus</button>
          </td>
        </tr>
      </tbody>
    </table>

    <ItemTypeForm
      v-if="showForm"
      :editData="editData"
      @close="resetForm"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';
import ItemTypeForm from './ItemTypeForm.vue';

const counterStore = useCounterStore();
const showForm = ref(false);
const editData = ref(null);

onMounted(() => {
  counterStore.fetchItemTypes();
});

const editItem = (itemType) => {
  editData.value = { ...itemType };
  showForm.value = true;
};

const deleteItem = async (id) => {
  if (confirm('Yakin ingin menghapus jenis ini?')) {
    await counterStore.deleteItemType(id);
  }
};

const handleSaved = () => {
  resetForm();
  counterStore.fetchItemTypes();
};

const resetForm = () => {
  showForm.value = false;
  editData.value = null;
};
</script>
