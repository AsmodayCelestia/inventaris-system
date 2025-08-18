<template>
  <div>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Tambah Jadwal Maintenance</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link to="/dashboard">Home</router-link>
              </li>
              <li class="breadcrumb-item">
                <router-link to="/maintenance/planning">Planning</router-link>
              </li>
              <li class="breadcrumb-item active">Tambah</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Form Tambah Jadwal Maintenance</h3>
          </div>

          <form @submit.prevent="submitForm">
            <div class="card-body">
              <div class="form-group">
                <label for="inventory_id">Pilih Inventaris</label>
                <select v-model="form.inventory_id" class="form-control" required>
                  <option value="">-- Pilih Inventaris --</option>
                  <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
                    {{ item.name }} - {{ item.inventory_number }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label for="pj_id">Penanggung Jawab (PJ)</label>
                <select v-model="form.pj_id" class="form-control" required>
                  <option value="">-- Pilih PJ --</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }} ({{ user.divisi }})
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label for="maintenance_frequency_type">Frekuensi Maintenance</label>
                <select v-model="form.maintenance_frequency_type" class="form-control" required>
                  <option value="">-- Pilih Tipe --</option>
                  <option value="bulan">Bulan</option>
                  <option value="minggu">Minggu</option>
                  <option value="semester">Semester</option>
                  <option value="km">KM</option>
                </select>
              </div>

              <div class="form-group">
                <label for="maintenance_frequency_value">Nilai Frekuensi</label>
                <input type="number" v-model="form.maintenance_frequency_value" class="form-control" required />
              </div>

              <div class="form-group">
                <label for="last_maintenance_at">Tanggal Maintenance</label>
                <input type="date" v-model="form.last_maintenance_at" class="form-control" required />
              </div>

              <div class="form-group" v-if="form.maintenance_frequency_type !== 'km'">
                <label for="next_due_date">Jatuh Tempo Berikutnya (Tanggal)</label>
                <input type="date" v-model="form.next_due_date" class="form-control" />
              </div>

              <div class="form-group" v-if="form.maintenance_frequency_type === 'km'">
                <label for="next_due_km">Jatuh Tempo Berikutnya (KM)</label>
                <input type="number" v-model="form.next_due_km" class="form-control" />
              </div>

              <div class="form-group">
                <label for="last_odometer_reading">Odometer Terakhir</label>
                <input type="number" v-model="form.last_odometer_reading" class="form-control" />
              </div>

              <div class="form-group">
                <label for="issue_found">Permasalahan (opsional)</label>
                <textarea v-model="form.issue_found" class="form-control" rows="3"></textarea>
              </div>

              <div class="form-group">
                <label for="solution_taken">Solusi / Langkah yang Dilakukan (opsional)</label>
                <textarea v-model="form.solution_taken" class="form-control" rows="3"></textarea>
              </div>

              <div class="form-group">
                <label for="notes">Catatan Tambahan (opsional)</label>
                <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
              </div>
            </div>

            <div class="form-group">
              <label for="cost">Estimasi Biaya (Rp)</label>
              <input
                type="number"
                v-model.number="form.cost"
                class="form-control"
                placeholder="0"
                min="0"
              />
            </div>

            <div class="card-footer">
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                {{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}
              </button>
              <router-link to="/maintenance/list" class="btn btn-secondary ml-2">Batal</router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useCounterStore } from '@/stores/counter';
import dayjs from 'dayjs';

const router = useRouter();
const counterStore = useCounterStore();

const inventoryItems = ref([]);
const users = ref([]);
const isSubmitting = ref(false);

const form = ref({
  inventory_id: '',
  pj_id: '',
  maintenance_frequency_type: '',
  maintenance_frequency_value: '',
  last_maintenance_at: '',
  next_due_date: '',
  next_due_km: '',
  last_odometer_reading: '',
  issue_found: '',
  notes: '',
  solution_taken: '',
  cost: null,       
});

// Auto-calculate next due date
watch(
  () => [form.value.maintenance_frequency_type, form.value.maintenance_frequency_value, form.value.last_maintenance_at],
  () => {
    const { maintenance_frequency_type, maintenance_frequency_value, last_maintenance_at } = form.value;
    if (!maintenance_frequency_type || !maintenance_frequency_value || !last_maintenance_at) return;

    const last = dayjs(last_maintenance_at);
    let next = '';
    if (maintenance_frequency_type === 'bulan') {
      next = last.add(maintenance_frequency_value, 'month');
    } else if (maintenance_frequency_type === 'minggu') {
      next = last.add(maintenance_frequency_value, 'week');
    } else if (maintenance_frequency_type === 'semester') {
      next = last.add(maintenance_frequency_value * 6, 'month');
    }
    if (next) form.value.next_due_date = next.format('YYYY-MM-DD');
  }
);

const submitForm = async () => {
  const {
    inventory_id,
    pj_id,
    maintenance_frequency_type,
    maintenance_frequency_value,
    last_maintenance_at
  } = form.value;

  if (!inventory_id || !pj_id || !maintenance_frequency_type || !maintenance_frequency_value || !last_maintenance_at) {
    alert('Harap lengkapi semua field wajib.');
    return;
  }

  isSubmitting.value = true;
  try {
    await counterStore.scheduleInventory(inventory_id, {
      maintenance_frequency_type,
      maintenance_frequency_value,
      last_maintenance_at: form.value.last_maintenance_at,
      next_due_date: form.value.next_due_date,
      next_due_km: form.value.next_due_km,
      last_odometer_reading: form.value.last_odometer_reading,
      pj_id: form.value.pj_id,
    });

    await counterStore.addMaintenanceRecord(inventory_id, {
      inspection_date: form.value.last_maintenance_at,
      issue_found: form.value.issue_found,
      solution_taken:  form.value.solution_taken,
      notes: form.value.notes,
      status: 'planning',
      pj_id: form.value.pj_id,
      cost: form.value.cost,
    });

    alert('Jadwal maintenance berhasil ditambahkan.');
    Object.keys(form.value).forEach(k => form.value[k] = '');
    router.push('/maintenance/list');
  } catch (e) {
    alert('Gagal menyimpan data.');
    console.error(e);
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(async () => {
  await counterStore.fetchInventoryItems();
  inventoryItems.value = counterStore.inventoryItems;
  await counterStore.fetchUsersList();
  users.value = counterStore.usersList;
});
</script>