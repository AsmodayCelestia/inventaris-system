<!-- resources/js/components/master-data/BrandList.vue -->
<template>
  <div class="container-fluid">
    <div class="card">
      <div class="card-header d-flex justify-between items-center">
        <h3 class="card-title">Daftar Merk</h3>
        <button class="btn btn-primary btn-sm" @click="openForm(null)">
          <i class="fas fa-plus"></i> Tambah Merk
        </button>
      </div>
      <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Merk</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(brand, index) in counterStore.brands" :key="brand.id">
              <td>{{ index + 1 }}</td>
              <td>{{ brand.name }}</td>
              <td>
                <button class="btn btn-sm btn-info" @click="openForm(brand)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" @click="deleteBrand(brand.id)">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="counterStore.brands.length === 0">
              <td colspan="3" class="text-center text-muted">Belum ada data merk.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <BrandForm :editData="selectedBrand" @saved="refreshBrands" v-if="showForm" @close="showForm = false" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';
import BrandForm from './BrandForm.vue';

const counterStore = useCounterStore();
const showForm = ref(false);
const selectedBrand = ref(null);

const openForm = (brand) => {
  selectedBrand.value = brand;
  showForm.value = true;
};

const refreshBrands = async () => {
  await counterStore.fetchBrands();
  showForm.value = false;
};

const deleteBrand = async (id) => {
  if (confirm('Yakin ingin menghapus merk ini?')) {
    await counterStore.deleteBrand(id);
    await counterStore.fetchBrands();
  }
};

onMounted(async () => {
  await counterStore.fetchBrands();
});
</script>

<style scoped>
.table th,
.table td {
  vertical-align: middle;
}
</style>
