<template>
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Daftar Divisi</h3>
      <router-link class="btn btn-primary" :to="{ name: 'DivisionCreate' }">
        Tambah Divisi
      </router-link>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead class="thead-light">
          <tr>
            <th style="width: 60px;">#</th>
            <th>Nama Divisi</th>
            <th style="width: 120px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(division, index) in divisions" :key="division.id">
            <td>{{ index + 1 }}</td>
            <td>{{ division.name }}</td>
            <td>
              <router-link
                class="btn btn-sm btn-info mr-1"
                :to="{ name: 'DivisionEdit', params: { id: division.id } }"
              >
                Edit
              </router-link>
              <button
                class="btn btn-sm btn-danger"
                @click="deleteDivision(division.id)"
              >
                Hapus
              </button>
            </td>
          </tr>
          <tr v-if="divisions.length === 0">
            <td colspan="3" class="text-center">Belum ada data divisi.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useCounterStore } from '../../stores/counter';
import Swal from 'sweetalert2';

const counterStore = useCounterStore();
const divisions = ref([]);

onMounted(async () => {
  divisions.value = await counterStore.fetchDivisions();
});

async function deleteDivision(id) {
  const result = await Swal.fire({
    title: 'Yakin hapus divisi ini?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus!',
  });
  if (!result.isConfirmed) return;

  try {
    await counterStore.deleteDivision(id);
    divisions.value = divisions.value.filter(d => d.id !== id);
    Swal.fire('Terhapus!', 'Divisi berhasil dihapus.', 'success');
  } catch (e) {
    Swal.fire('Gagal', e.response?.data?.message || 'Terjadi kesalahan', 'error');
  }
}
</script>