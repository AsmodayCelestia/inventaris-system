<!-- QrPreview.vue -->
<template>
  <div>
    <label>URL</label>
    <input v-model="url" @input="refresh" />

    <label>Warna Foreground</label>
    <input type="color" v-model="color" @input="refresh" />

    <label>Logo</label>
    <select v-model="logo" @change="refresh">
      <option value="logo.png">Logo Kantor</option>
      <option value="logo-alt.png">Logo Alt</option>
    </select>

    <img :src="qrSrc" alt="QR Preview" class="mt-2 border" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { useCounterStore } from '@/stores/counter';

const store   = useCounterStore();
const url     = ref('https://example.com');
const color   = ref('#000000');
const logo    = ref('logo.png');

const qrSrc = computed(() =>
  `${store.API_BASE_URL}/api/qr-preview?url=${encodeURIComponent(url.value)}&color=${color.value}&logo=${logo.value}`
);
</script>