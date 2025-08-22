<template>
  <div>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">{{ pageTitle }}</h1>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">{{ pageTitle }}</h3>
          </div>

          <form @submit.prevent="submitForm">
            <div class="card-body">
              <!-- SELECT MODE (hanya admin/head) -->
              <div v-if="isAdminOrHead" class="form-group">
                <label>Jenis Form</label>
                <select v-model="mode" class="form-control">
                  <option value="laporan">Laporkan Kerusakan</option>
                  <option value="planning">Buat Jadwal Maintenance</option>
                </select>
              </div>

              <!-- INVENTARIS -->
              <div class="form-group">
                <label for="inventory_id">Inventaris</label>
                <select v-model="form.inventory_id" class="form-control" required>
<option v-for="item in inventoryItems" :key="item.id" :value="item.id">
  {{ item.item?.name || '-' }} - {{ item.inventory_number }}
</option>
                </select>
              </div>

              <!-- PJ (hanya planning & admin/head) -->
              <div class="form-group" v-if="mode === 'planning'">
                <label for="pj_id">Penanggung Jawab (PJ)</label>
                <select v-model="form.pj_id" class="form-control" required>
                  <option value="">-- Pilih PJ --</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }} ({{ user.divisi }})
                  </option>
                </select>
              </div>

              <!-- TANGGAL LAPORAN / INSPEKSI -->
              <div class="form-group">
                <label for="inspection_date">Tanggal</label>
                <input type="date" v-model="form.inspection_date" class="form-control" required />
              </div>

              <!-- FIELD PLANNING (hanya planning) -->
              <template v-if="mode === 'planning'">
                <div class="form-group">
                  <label for="maintenance_frequency_type">Frekuensi</label>
                  <select v-model="form.maintenance_frequency_type" class="form-control">
                    <option value="">-- Pilih --</option>
                    <option value="bulan">Bulan</option>
                    <option value="minggu">Minggu</option>
                    <option value="semester">Semester</option>
                    <option value="km">KM</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="maintenance_frequency_value">Nilai Frekuensi</label>
                  <input type="number" v-model="form.maintenance_frequency_value" class="form-control" />
                </div>

                <div class="form-group" v-if="form.maintenance_frequency_type !== 'km'">
                  <label for="next_due_date">Jatuh Tempo Berikutnya</label>
                  <input type="date" v-model="form.next_due_date" class="form-control" />
                </div>

                <div class="form-group" v-if="form.maintenance_frequency_type === 'km'">
                  <label for="next_due_km">Jatuh Tempo (KM)</label>
                  <input type="number" v-model="form.next_due_km" class="form-control" />
                </div>

                <div class="form-group">
                  <label for="last_odometer_reading">Odometer Terakhir</label>
                  <input type="number" v-model="form.last_odometer_reading" class="form-control" />
                </div>
              </template>

              <!-- DESKRIPSI -->
              <div class="form-group">
                <label for="issue_found">Permasalahan</label>
                <textarea v-model="form.issue_found" class="form-control" rows="3"></textarea>
              </div>

              <div class="form-group">
                <label for="solution_taken">Solusi / Langkah</label>
                <textarea v-model="form.solution_taken" class="form-control" rows="3"></textarea>
              </div>

              <div class="form-group">
                <label for="notes">Catatan Tambahan</label>
                <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
              </div>

              <div class="form-group">
                <label for="cost">Estimasi Biaya (Rp)</label>
                <input type="number" v-model.number="form.cost" class="form-control" min="0" />
              </div>
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
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCounterStore } from '@/stores/counter';
import dayjs from 'dayjs';
import { useRoute } from 'vue-router'
const route = useRoute()


const router = useRouter();
const counterStore = useCounterStore();

const mode = ref('laporan');            // default laporan
const inventoryItems = ref([]);
const users = ref([]);
const isSubmitting = ref(false);

const isAdminOrHead = computed(() => counterStore.isAdmin || counterStore.isHead);
const pageTitle = computed(() =>
  isAdminOrHead.value
    ? mode.value === 'planning'
      ? 'Buat Jadwal Maintenance'
      : 'Laporkan Kerusakan'
    : 'Laporkan Kerusakan'
);

const form = ref({
  inventory_id: '',
  pj_id: '',
  inspection_date: '',
  maintenance_frequency_type: '',
  maintenance_frequency_value: '',
  next_due_date: '',
  next_due_km: '',
  last_odometer_reading: '',
  issue_found: '',
  solution_taken: '',
  notes: '',
  cost: null,
});

/* auto next due date (planning only) */
import { watch } from 'vue';
watch(
  () => [form.value.maintenance_frequency_type, form.value.maintenance_frequency_value, form.value.inspection_date],
  () => {
    if (mode.value !== 'planning') return;
    const { maintenance_frequency_type, maintenance_frequency_value, inspection_date } = form.value;
    if (!maintenance_frequency_type || !maintenance_frequency_value || !inspection_date) return;
    const last = dayjs(inspection_date);
    let next;
    if (maintenance_frequency_type === 'bulan') next = last.add(maintenance_frequency_value, 'month');
    else if (maintenance_frequency_type === 'minggu') next = last.add(maintenance_frequency_value, 'week');
    else if (maintenance_frequency_type === 'semester') next = last.add(maintenance_frequency_value * 6, 'month');
    if (next) form.value.next_due_date = next.format('YYYY-MM-DD');
  }
);

const submitForm = async () => {
  const { inventory_id, inspection_date } = form.value;
  if (!inventory_id || !inspection_date) {
    alert('Inventaris dan tanggal wajib diisi.');
    return;
  }

  isSubmitting.value = true;
  try {
    const payload = {
      inspection_date,
      issue_found: form.value.issue_found,
      solution_taken: form.value.solution_taken,
      notes: form.value.notes,
      status: isAdminOrHead.value && mode.value === 'planning' ? 'on_progress' : 'reported',
      pj_id: mode.value === 'planning' ? form.value.pj_id : null,
      cost: form.value.cost,
    };

    await counterStore.addMaintenanceRecord(inventory_id, payload);

    /* update schedule (planning only) */
    if (isAdminOrHead.value && mode.value === 'planning') {
      await counterStore.scheduleInventory(inventory_id, {
        maintenance_frequency_type: form.value.maintenance_frequency_type,
        maintenance_frequency_value: form.value.maintenance_frequency_value,
        last_maintenance_at: inspection_date,
        next_due_date: form.value.next_due_date,
        next_due_km: form.value.next_due_km,
        last_odometer_reading: form.value.last_odometer_reading,
        pj_id: form.value.pj_id,
      });
    }

    alert('Data berhasil disimpan.');
    router.push('/maintenance/needed');
  } catch (e) {
    alert('Gagal menyimpan.');
    console.error(e);
  } finally {
    isSubmitting.value = false;
  }
};


onMounted(async () => {
  const idFromQuery = Number(route.query.inventory);

  if (idFromQuery) {
    // Flow 1 : akses dari QR / link langsung → cukup satu barang
    const { data } = await axios.get(`/inventories/${idFromQuery}?with=item`);
    inventoryItems.value = [data];            // array 1 elemen
    form.value.inventory_id = idFromQuery;    // auto-select
  } else {
    // Flow 2 : akses biasa → ambil semua untuk dropdown
    await counterStore.fetchInventories({ with: 'item' });
    inventoryItems.value = counterStore.inventories;
  }

  // load users untuk PJ (keduanya butuh)
  await counterStore.fetchUsers();
  users.value = counterStore.users;
});

</script>