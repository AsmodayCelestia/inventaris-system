<!-- resources/js/components/master-data/CategoryList.vue -->
<template>
  <div class="container mt-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Daftar Kategori</h3>
        <button class="btn btn-primary btn-sm" @click="openForm()">+ Tambah</button>
      </div>
      <div class="card-body p-0">
        <table class="table table-striped table-hover m-0">
          <thead>
            <tr>
              <th style="width: 10px">#</th>
              <th>Nama Kategori</th>
              <th style="width: 100px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(category, index) in counterStore.categories" :key="category.id">
              <td>{{ index + 1 }}</td>
              <td>{{ category.name }}</td>
              <td>
                <button class="btn btn-sm btn-info mr-1" @click="openForm(category)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" @click="deleteCategory(category.id)">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="counterStore.categories.length === 0">
              <td colspan="3" class="text-center text-muted py-3">Belum ada data.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form -->
    <CategoryForm
      v-if="showForm"
      :editData="selectedCategory"
      @close="showForm = false"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';
import CategoryForm from './CategoryForm.vue';

const counterStore = useCounterStore();

const showForm = ref(false);
const selectedCategory = ref(null);

const openForm = (category = null) => {
  selectedCategory.value = category;
  showForm.value = true;
};

const handleSaved = async () => {
  showForm.value = false;
  selectedCategory.value = null;
  await counterStore.fetchCategories();
};

const deleteCategory = async (id) => {
  if (confirm('Yakin ingin menghapus kategori ini?')) {
    await counterStore.deleteCategory(id);
    await counterStore.fetchCategories();
  }
};

onMounted(() => {
  counterStore.fetchCategories();
});
</script>
