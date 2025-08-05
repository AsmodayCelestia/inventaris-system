<!-- resources/js/components/maintenance/MaintenanceHistory.vue -->
<template>
  <div>
    <h1 class="text-xl font-semibold mb-4">Riwayat Maintenance</h1>

    <div class="mb-4">
      <label class="block mb-1 font-medium">Filter Inventaris</label>
      <select v-model="selectedInventoryId" @change="loadMaintenance" class="border p-2 rounded w-full">
        <option value="">-- Semua Inventaris --</option>
        <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
          {{ item.name }} ({{ item.inventory_number }})
        </option>
      </select>
    </div>

    <div v-if="loading" class="text-gray-500">Memuat data...</div>

    <div v-else-if="maintenanceHistory.length === 0" class="text-gray-500">Tidak ada data maintenance.</div>

    <table v-else class="min-w-full bg-white border">
      <thead>
        <tr class="bg-gray-100">
          <th class="p-2 border">Tanggal</th>
          <th class="p-2 border">Inventaris</th>
          <th class="p-2 border">Deskripsi</th>
          <th class="p-2 border">Status</th>
          <th class="p-2 border">PJ</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="m in maintenanceHistory" :key="m.id">
          <td class="p-2 border">{{ formatDate(m.date) }}</td>
          <td class="p-2 border">{{ m.inventory?.name }} ({{ m.inventory?.inventory_number }})</td>
          <td class="p-2 border">{{ m.description }}</td>
          <td class="p-2 border">
            <span :class="statusClass(m.status)">{{ m.status }}</span>
          </td>
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
const maintenanceHistory = ref([]);
const inventoryItems = ref([]);
const selectedInventoryId = ref('');
const loading = ref(false);

const loadMaintenance = async () => {
  loading.value = true;
  try {
    await store.fetchMaintenanceHistory(selectedInventoryId.value);
    maintenanceHistory.value = store.maintenanceHistory;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const loadInventoryItems = async () => {
  await store.fetchInventoryItems();
  inventoryItems.value = store.inventoryItems;
};

const formatDate = (dateStr) => {
  return dayjs(dateStr).format('DD/MM/YYYY');
};

const statusClass = (status) => {
  if (status === 'selesai') return 'text-green-600 font-semibold';
  if (status === 'direncanakan') return 'text-yellow-600 font-semibold';
  return 'text-gray-700';
};

onMounted(async () => {
  await loadInventoryItems();
  await loadMaintenance();
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
