<template>
  <div class="container">
    <h1 class="text-center">Scan QR Code</h1>
    <div class="row">
      <div class="col-md-6 offset-md-3">
        <div class="card">
          <div class="card-body">
            <div id="qr-reader" style="width: 100%"></div>
            <div v-if="qrCode" class="mt-3">
              <p>Scanned QR Code:</p>
              <p>{{ qrCode }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { Html5Qrcode } from 'html5-qrcode';

const router   = useRouter();
const qrCode   = ref(null);
let scanner    = null;
let isRunning  = false;

const onScanSuccess = (fullUrl) => {
  if (!isRunning) return;
  isRunning = false;

  // ambil path saja: http://localhost:8000/inventories/1 → /inventories/1
  const path = new URL(fullUrl, window.location.origin).pathname;
  qrCode.value = path;
  router.push(path);
};

onMounted(() => {
  nextTick(() => {
    scanner = new Html5Qrcode('qr-reader');
    isRunning = true;
    scanner.start(
      { facingMode: 'environment' },
      {},
      onScanSuccess,
      () => {}
    ).catch(console.error);
  });
});

onUnmounted(() => {
  if (isRunning && scanner) {
    scanner.stop().catch(() => {}); // ignore “not running”
    isRunning = false;
  }
});
</script>

<style scoped>
#qr-reader {
  border: 1px solid #ccc;
  padding: 10px;
  text-align: center;
}
</style>