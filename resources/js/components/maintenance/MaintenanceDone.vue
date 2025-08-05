<template>
  <div>
    <h1 class="text-xl font-semibold mb-4">Maintenance Selesai</h1>

    <div v-if="loading" class="text-gray-500">Memuat data...</div>
    <div v-else-if="doneMaintenance.length === 0" class="text-gray-500">Tidak ada maintenance yang selesai.</div>

    <table v-else class="min-w-full bg-white border">
      <thead>
        <tr class="bg-gray-100">
          <th class="p-2 border">Tanggal</th>
          <th class="p-2 border">Inventaris</th>
          <th class="p-2 border">Deskripsi</th>
          <th class="p-2 border">PJ</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="m in doneMaintenance" :key="m.id">
          <td class="p-2 border">{{ formatDate(m.date) }}</td>
          <td class="p-2 border">{{ m.inventory?.name }} ({{ m.inventory?.inventory_number }})</td>
          <td class="p-2 border">{{ m.description }}</td>
          <td class="p-2 border">{{ m.pj_name || '-' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useCounterStore } from '@/stores/counter';
import dayjs from 'dayjs';

const store = useCounterStore();
const doneMaintenance = ref([]);
const loading = ref(false);

const loadMaintenance = async () => {
  loading.value = true;
  try {
    await store.fetchMaintenanceHistory();
    doneMaintenance.value = store.maintenanceHistory.filter((m) => m.status === 'selesai');
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateStr) => {
  return dayjs(dateStr).format('DD/MM/YYYY');
};

onMounted(() => {
  loadMaintenance();
});
</script>

<style scoped>
table {
  border-collapse: collapse;
}
th, td {
  text-align: left;
}
</style>
