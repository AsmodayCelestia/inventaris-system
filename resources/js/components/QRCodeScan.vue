<!-- ScanQRCode.vue – tinggal copy-paste -->
<template>
  <div class="container">
    <h1 class="text-center">Scan QR Code Inventaris</h1>

    <div class="row">
      <div class="col-md-6 offset-md-3">
        <div class="card">
          <div class="card-body">
            <!-- area kamera -->
            <div id="qr-reader" style="width: 100%"></div>

            <!-- hasil scan -->
            <div v-if="qrCode" class="mt-3">
              <p>Scanned QR Code:</p>
              <p class="text-break">{{ qrCode }}</p>
            </div>

            <!-- error -->
            <div v-if="error" class="alert alert-danger mt-3">
              {{ error }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { Html5Qrcode } from 'html5-qrcode'

const router   = useRouter()
const qrCode   = ref(null)
const error    = ref(null)

let scanner    = null
let isRunning  = false

/**
 * Cek login – silent fail, tidak throw
 */
const checkLogin = async () => {
  try {
    await axios.get('/user', { withCredentials: true })
    return true
  } catch {
    return false
  }
}

/**
 * Handler ketika QR berhasil discan
 */
const onScanSuccess = async (decodedText) => {
  if (!isRunning) return
  isRunning = false
  await scanner.stop() // langsung stop kamera

  const rawPath = new URL(decodedText, window.location.origin).pathname
  console.log('📦 rawPath dari QR :', rawPath)

  // ekstrak id inventaris dari pola /123 atau /inventories/123
  const match = rawPath.match(/\/(?:inventories\/)?(\d+)$/)
  if (!match) {
    error.value = 'QR tidak valid'
    return
  }

  const id        = match[1]
  const loggedIn  = await checkLogin()
  const target    = loggedIn
    ? `/inventories/${id}`
    : `/inventories/scan/${id}`

  console.log(loggedIn ? '✅ sudah login' : '❌ belum login', '→', target)
  qrCode.value = target
  router.push(target)
}

/**
 * Mulai kamera saat komponen mounted
 */
onMounted(async () => {
  await nextTick()
  try {
    scanner   = new Html5Qrcode('qr-reader')
    isRunning = true
    await scanner.start(
      { facingMode: 'environment' }, // kamera belakang
      { fps: 10, qrbox: 250 },       // opsi scanner
      onScanSuccess,
      () => {} // onScanFailure (kosongkan)
    )
  } catch (err) {
    console.error(err)
    error.value = 'Tidak dapat mengakses kamera: ' + err.message
  }
})

/**
 * Pastikan kamera dimatikan saat keluar halaman
 */
onUnmounted(async () => {
  if (isRunning && scanner) {
    try {
      await scanner.stop()
    } catch {
      /* ignore */
    }
    isRunning = false
  }
})
</script>

<style scoped>
#qr-reader {
  border: 1px solid #ccc;
  padding: 10px;
  text-align: center;
}
</style>