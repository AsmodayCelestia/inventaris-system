<!-- resources/js/components/master-data/UserList.vue -->
<template>
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Daftar Pengguna</h3>
      <button class="btn btn-primary" @click="showForm = true">Tambah</button>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead class="thead-light">
          <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th>Divisi</th>
            <th style="width: 120px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in counterStore.usersList" :key="user.id">
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>{{ user.role }}</td>
            <td>{{ user.divisi }}</td>
            <td>
              <button class="btn btn-sm btn-info mr-1" @click="editUser(user)">Edit</button>
              <button class="btn btn-sm btn-danger" @click="deleteUser(user.id)">Hapus</button>
            </td>
          </tr>
          <tr v-if="counterStore.usersList.length === 0">
            <td colspan="5" class="text-center">Belum ada data pengguna.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <UserForm v-if="showForm" :editData="editingUser" @saved="onSaved" @close="showForm = false" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';
import UserForm from './UserForm.vue';

const counterStore = useCounterStore();
const showForm = ref(false);
const editingUser = ref(null);

onMounted(() => {
counterStore.fetchUsersList();
});

const editUser = (user) => {
  editingUser.value = { ...user };
  showForm.value = true;
};

const deleteUser = async (id) => {
  if (confirm('Yakin ingin menghapus pengguna ini?')) {
    await counterStore.deleteUser(id);
  }
};

const onSaved = async () => {
  showForm.value = false;
  editingUser.value = null;
  await counterStore.fetchUsersList();
};
</script>
