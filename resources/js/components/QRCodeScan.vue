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
import * as Html5Qrcode from 'html5-qrcode';

const qrCode = ref(null);

let html5QrcodeScanner;

const onScanSuccess = (decodedText, decodedResult) => {
  qrCode.value = decodedText;
  html5QrcodeScanner.stop().then(() => {
    alert('QR Code scanned successfully.');
    console.log('Scan result:', decodedText);
  }).catch((err) => {
    console.error('Failed to stop QR Code scanner:', err);
  });
};

onMounted(() => {
  nextTick(() => {
    html5QrcodeScanner = new Html5Qrcode.Html5Qrcode('qr-reader');
    html5QrcodeScanner.start({
      facingMode: "environment" // Menggunakan kamera belakang
    }, (decodedText, decodedResult) => {
      onScanSuccess(decodedText, decodedResult);
    }, (err) => {
      console.error('Failed to start QR Code scanner:', err);
    });
  });
});

onUnmounted(() => {
  if (html5QrcodeScanner) {
    html5QrcodeScanner.stop().catch((err) => {
      console.error('Failed to stop QR Code scanner:', err);
    });
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