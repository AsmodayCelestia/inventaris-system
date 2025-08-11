<template>
  <div class="container">
    <h1 class="text-center">Manage QR Codes</h1>
    <div class="row">
      <div class="col-md-6 offset-md-3">
        <div class="card">
          <div class="card-body">
            <form @submit.prevent="submitForm">
              <div class="form-group">
                <label for="inventory_id">Select Inventory</label>
                <select v-model="form.inventory_id" class="form-control">
                  <option v-for="inventory in inventories" :key="inventory.id" :value="inventory.id">
                    {{ inventory.inventory_number }} - {{ inventory.item.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <button type="submit" class="btn btn-primary">Generate QR Code</button>
                <button type="button" @click="updateQrCode" class="btn btn-warning">Update QR Code</button>
                <button type="button" @click="deleteQrCode" class="btn btn-danger">Delete QR Code</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useCounterStore } from '../stores/counter';

const counterStore = useCounterStore();
const inventories = ref([]);
const form = ref({
  inventory_id: null,
});

onMounted(async () => {
  await counterStore.fetchInventories();
  inventories.value = counterStore.inventories;
});

const submitForm = async () => {
  try {
    await axios.post('/qrcodes', form.value);
    alert('QR Code generated successfully.');
  } catch (error) {
    console.error('Failed to generate QR Code:', error);
    alert('Failed to generate QR Code.');
  }
};

const updateQrCode = async () => {
  try {
    await axios.put(`/qrcodes/${form.value.inventory_id}`, form.value);
    alert('QR Code updated successfully.');
  } catch (error) {
    console.error('Failed to update QR Code:', error);
    alert('Failed to update QR Code.');
  }
};

const deleteQrCode = async () => {
  try {
    await axios.delete(`/qrcodes/${form.value.inventory_id}`);
    alert('QR Code deleted successfully.');
  } catch (error) {
    console.error('Failed to delete QR Code:', error);
    alert('Failed to delete QR Code.');
  }
};
</script>