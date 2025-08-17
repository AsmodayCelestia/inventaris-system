<template>
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1>Edit Master Barang</h1></div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <!-- Form Edit -->
      <div class="card">
        <div class="card-body">
          <form @submit.prevent="saveItem">
            <div class="row">
              <div class="col-md-6"><label>Kode Barang</label><input v-model="form.item_code" class="form-control" /></div>
              <div class="col-md-6"><label>Nama</label><input v-model="form.name" class="form-control" /></div>

              <div class="col-md-6"><label>Merk</label>
                <select v-model="form.brand_id" class="form-control">
                  <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>
              <div class="col-md-6"><label>Kategori</label>
                <select v-model="form.category_id" class="form-control">
                  <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="col-md-6"><label>Jenis</label>
                <select v-model="form.type_id" class="form-control">
                  <option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
              </div>
              <div class="col-md-6"><label>Tahun Produksi</label>
                <input v-model.number="form.manufacture_year" type="number" class="form-control" />
              </div>
              <div class="col-md-6"><label>Stock (manual)</label>
                <input v-model.number="form.quantity" type="number" min="0" class="form-control" />
              </div>
            </div>
            <button type="submit" class="btn btn-success mt-3">Simpan</button>
          </form>
        </div>
      </div>

      <!-- Tabel Unit Inventaris -->
      <div class="card mt-4">
        <div class="card-header"><h5>Daftar Unit Inventaris ({{ invCount }})</h5></div>
        <div class="card-body">
          <table class="table table-sm">
            <thead>
              <tr><th>No. Inventaris</th><th>Lokasi</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="inv in inventories" :key="inv.id">
                <td>{{ inv.inventory_number }}</td>
                <td>{{ inv.unit?.name }} - {{ inv.room?.name }}</td>
                <td>{{ inv.status }}</td>
                <td>
                  <router-link :to="`/inventories/${inv.id}`" class="text-muted mr-2">
                    <i class="fas fa-eye"></i>
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Slot kosong & tombol Buat Inventaris -->
          <div v-if="emptySlots>0" class="mt-3">
            <h6>Slot kosong: {{ emptySlots }}</h6>
            <button
              v-for="slot in emptySlots"
              :key="slot"
              class="btn btn-sm btn-primary mb-1"
              @click="createInventory(slot)"
            >
                Buat Inventaris 
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCounterStore } from '../stores/counter';
import axios from 'axios';

const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  }
});

const route  = useRoute();
const router = useRouter();
const store  = useCounterStore();

const brands     = ref([]);
const categories = ref([]);
const types      = ref([]);
const inventories= ref([]);
const form       = ref({
  item_code: '',
  name: '',
  brand_id: null,
  category_id: null,
  type_id: null,
  manufacture_year: null,
  quantity: 0,
});

onMounted(async () => {
      console.log('Store methods:', {
    fetchBrands: typeof store.fetchBrands,
    fetchCategories: typeof store.fetchCategories,
    fetchItemTypes: typeof store.fetchItemTypes
  });
  await Promise.all([
    store.fetchBrands?.(),
    store.fetchCategories?.(),
    store.fetchItemTypes?.(),
    fetchItem(),
    fetchInventories(),
  ]);
    // console.log('Types from store:', store.types);
  brands.value     = store.brands ?? [];
  categories.value = store.categories ?? [];
  types.value      = store.itemTypes ?? [];
});

const fetchItem = async () => {
  form.value = await store.fetchInventoryItemById(route.params.id) ?? {};
};

const fetchInventories = async () => {
  try {
    const { data } = await axios.get('/inventories', {
      params: { inventory_item_id: route.params.id },
    });
    
    inventories.value = data;
    console.log(inventories.value);
  } catch {
    inventories.value = [];
  }
};

const invCount   = computed(() => inventories.value.length);
const emptySlots = computed(() => Math.max(0, form.value.quantity - invCount.value));

const saveItem = async () => {
  await store.updateInventoryItem(route.params.id, form.value);
  await fetchInventories();
};

const createInventory = slot =>
  router.push({
    path: '/inventories/create',
    query: { item_id: route.params.id  },
  });
</script>