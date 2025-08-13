<!-- resources/js/components/master-data/RoomList.vue -->
<template>
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Daftar Ruangan</h5>
      <button class="btn btn-primary" @click="openForm()">+ Tambah Ruangan</button>
    </div>
    <div class="card-body">
      <table class="table table-bordered table-hover">
        <thead class="thead-light">
          <tr>
            <th>No</th>
            <th>Nama Ruangan</th>
            <th>Lantai</th>
            <th>Unit</th>
            <th>Pengawas Ruangan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(room, index) in counterStore.rooms" :key="room.id">
            <td>{{ index + 1 }}</td>
            <td>{{ room.name }}</td>
            <td>{{ room.floor?.number || '-' }}</td>
            <td>{{ room.floor?.unit?.name || '-' }}</td>
            <td>{{ room.location_person_in_charge?.name || '-' }}</td>
            <td>
              <button class="btn btn-sm btn-warning" @click="openForm(room)">Edit</button>
              <button class="btn btn-sm btn-danger ml-2" @click="deleteRoom(room.id)">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>

      <RoomForm v-if="showForm" :editData="selectedRoom" @saved="handleSaved" @close="showForm = false" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';
import RoomForm from './RoomForm.vue';

const counterStore = useCounterStore();
const showForm = ref(false);
const selectedRoom = ref(null);

const openForm = (room = null) => {
  selectedRoom.value = room;
  showForm.value = true;
};

const deleteRoom = async (id) => {
  if (confirm('Yakin ingin menghapus ruangan ini?')) {
    await counterStore.deleteRoom(id);
  }
};

const handleSaved = () => {
  showForm.value = false;
  counterStore.fetchRooms();
};

onMounted(() => {
  counterStore.fetchRooms();
});
</script>
