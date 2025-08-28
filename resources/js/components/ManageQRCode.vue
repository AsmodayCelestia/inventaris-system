<template>
  <div class="container-fluid p-4">
    <h1 class="mb-4">Manage QR Codes</h1>
Copy

<!-- tombol aksi (bulk otomatis = 1..n item) -->
<div class="d-flex mb-3 gap-2 flex-wrap">
  <button class="btn btn-primary" :disabled="!selectedIds.length"
          @click="generateBulk">
    Generate ({{ selectedIds.length }})
  </button>
  <button class="btn btn-warning" :disabled="!selectedIds.length"
          @click="regenerateBulk">
    Update ({{ selectedIds.length }})
  </button>
  <button class="btn btn-danger" :disabled="!selectedIds.length"
          @click="deleteBulk">
    Delete ({{ selectedIds.length }})
  </button>
  <button class="btn btn-success" :disabled="!selectedIds.length"
          @click="downloadBulk">
    <i class="fas fa-download"></i> Download ({{ selectedIds.length }})
  </button>
  <button class="btn btn-info" :disabled="!selectedIds.length"
        @click="printMultiQr">
  <i class="fas fa-print"></i> Print ({{ selectedIds.length }})
``</button>
</div>

<!-- tabel inventaris -->
<div class="table-responsive">
  <table class="table table-striped table-hover align-middle">
    <thead>
      <tr>
        <th style="width: 40px">
          <input type="checkbox" v-model="selectAll" @change="toggleSelectAll">
        </th>
        <th>Nomor Inventaris</th>
        <th>Nama Barang</th>
        <th>Ruangan</th>
        <th>QR Code</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="inv in inventories" :key="inv.id">
        <td>
          <input type="checkbox" :value="inv.id" v-model="selectedIds">
        </td>
        <td>{{ inv.inventory_number }}</td>
        <td>{{ inv.item?.name }}</td>
        <td>{{ inv.room?.name }}</td>
        <td>
          <img v-if="inv.qr_code_path" :src="inv.qr_code_path"
               class="img-thumbnail" style="height: 40px">
          <span v-else class="badge bg-secondary">Belum ada</span>
        </td>
      </tr>
    </tbody>
  </table>
</div>

<!-- loading overlay -->
<div v-if="loading" class="text-center my-4">
  <i class="fas fa-spinner fa-spin fa-2x"></i>
</div>

  </div>
</template>
<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useCounterStore } from '../stores/counter';
// import printJS from 'print-js';
import { jsPDF } from 'jspdf';

const counterStore = useCounterStore();
const inventories = ref([]);
const loading     = ref(false);
const selectedIds = ref([]);

/* utilities */
const api = (path) => `${counterStore.API_BASE_URL}${path}`;

const fetchData = async () => {
  loading.value = true;
  try {
    await counterStore.fetchInventories();
    inventories.value = counterStore.inventories;
  } finally {
    loading.value = false;
  }
};

const selectAll = computed({
  get: () =>
    selectedIds.value.length === inventories.value.length && inventories.value.length > 0,
  set: (val) =>
    (selectedIds.value = val ? inventories.value.map(i => i.id) : []),
});
function toggleSelectAll () {
  selectAll.value = !selectAll.value;
}

/* progress helper */
const callApiWithProgress = async (method, url, ids, payloadFn) => {
  const total = ids.length;
  if (!total) return;

  Swal.fire({
    title: 'Processing...',
    html: `<b>0</b> / ${total}`,
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading()
  });

  const chunks = [];
  for (let i = 0; i < ids.length; i += 5) chunks.push(ids.slice(i, i + 5));

  let processed = 0;
  for (const chunk of chunks) {
    try {
      await axios({
        method,
        url: api(url),
        data: payloadFn(chunk),
        headers: counterStore.authHeader?.headers || {},
      });
      processed += chunk.length;
      Swal.getHtmlContainer().querySelector('b').textContent = processed;
    } catch (e) {
      Swal.fire('Error', e.response?.data?.message || 'Gagal', 'error');
      return;
    }
    await new Promise(r => setTimeout(r, 200)); // rate-limit safety
  }

  Swal.fire('Success!', `${total} item(s) processed`, 'success');
  selectedIds.value = [];
  fetchData();
};

/* actions */
const generateBulk = () =>
  callApiWithProgress('POST', '/qrcodes/bulk', selectedIds.value,
                      (c) => ({ inventory_ids: c }));

const regenerateBulk = () =>
  callApiWithProgress('PUT', '/qrcodes/bulk', selectedIds.value,
                      (c) => ({ inventory_ids: c }));

const deleteBulk = async () => {
  const ok = await Swal.fire({
    title: 'Yakin?',
    text: `Hapus ${selectedIds.value.length} QR Code?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Ya, hapus!'
  });
  if (!ok.isConfirmed) return;
  callApiWithProgress('DELETE', '/qrcodes/bulk', selectedIds.value,
                      (c) => ({ inventory_ids: c }));
};

const downloadBulk = () => {
  const ids = selectedIds.value.join(',');
  const url = api(`/qrcodes/download?ids=${ids}`);
  const a = document.createElement('a');
  a.href = url;
  a.target = '_self';
  a.download = 'qr-codes.zip';
  a.style.display = 'none';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
};


// // ini yang A4
// function printMultiQr() {
//   const list = inventories.value.filter(i => selectedIds.value.includes(i.id));
//   if (!list.length) return;

//   const STRIP_W = 189;  // 50 mm
//   const STRIP_H = 378;  // 100 mm
//   const QR_W    = 170;  // 45 mm

//   let html = `
//     <html>
//       <head>
//         <title>Label Sheet</title>
//         <style>
//           *{margin:0;padding:0}
//           body{background:#fff;font-family:Arial,sans-serif}
//           .sheet{
//             width:210mm;height:297mm;
//             display:flex;flex-wrap:wrap;align-content:flex-start;
//             gap:2mm;padding:5mm;box-sizing:border-box;
//           }
//           .label{
//             width:${STRIP_W}px;height:${STRIP_H}px;
//             border:1pt dashed #666;
//             display:flex;flex-direction:column;
//             justify-content:space-between;align-items:center;
//             padding:4mm;box-sizing:border-box;
//             page-break-inside:avoid;
//           }
//           .qr img{width:${QR_W}px;height:${QR_W}px}
//         </style>
//       </head>
//       <body>
//         <div class="sheet">`;

//   for (let i = 0; i < list.length; i += 2) {
//     html += `<div class="label">`;
//     html += `<div class="qr"><img src="${list[i].qr_code_path}" /></div>`;
//     if (list[i + 1]) {
//       html += `<div class="qr"><img src="${list[i + 1].qr_code_path}" /></div>`;
//     }
//     html += `</div>`;
//   }

//   html += `</div></body></html>`;

//   const wnd = window.open('', '_blank');
//   wnd.document.write(html);
//   wnd.document.close();
//   wnd.focus();
//   wnd.print();
// }

// // ini yang kertas 5x10
// function printMultiQr() {
//   const list = inventories.value.filter(i => selectedIds.value.includes(i.id));
//   if (!list.length) return;

//   const mm  = 3.779527559;           // 1 mm → px @ 96 dpi
//   const w   = Math.round(50 * mm);   // 189 px
//   const h   = Math.round(100 * mm);  // 378 px
//   const qr  = Math.round(45 * mm);   // 170 px

//   let html = `
//     <html>
//       <head>
//         <title>QR Labels 5×10 cm</title>
//         <style>
//           *{margin:0;padding:0}
//           body{background:#fff}
//           .page{
//             width:${w}px;height:${h}px;
//             display:flex;
//             flex-direction:column;
//             justify-content:space-between;
//             align-items:center;
//             padding:5px;
//             box-sizing:border-box;
//             page-break-after:always;
//           }
//           .qr img{width:${qr}px;height:${qr}px}
//         </style>
//       </head>
//       <body>`;

//   // looping 2 QR per halaman
//   for (let i = 0; i < list.length; i += 2) {
//     html += `<div class="page">`;
//     html += `<div class="qr"><img src="${list[i].qr_code_path}"></div>`;
//     if (list[i + 1]) {
//       html += `<div class="qr"><img src="${list[i + 1].qr_code_path}"></div>`;
//     }
//     html += `</div>`;
//   }

//   html += `</body></html>`;

//   const wnd = window.open('', '_blank');
//   wnd.document.write(html);
//   wnd.document.close();
//   wnd.focus();
//   wnd.print();
// }


async function printMultiQr() {
  const list = inventories.value.filter(i => selectedIds.value.includes(i.id));
  if (!list.length) return;

  const pdf = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: [50, 100]   // 5 cm x 10 cm
  });

  const qrSize = 45; // mm

  for (let i = 0; i < list.length; i += 2) {
    if (i > 0) pdf.addPage();

    // QR 1
    const img1 = await convertImgToDataURL(list[i].qr_code_path);
    pdf.addImage(img1, 'PNG', (50 - qrSize) / 2, 5, qrSize, qrSize);

    // QR 2 (jika ada)
    if (list[i + 1]) {
      const img2 = await convertImgToDataURL(list[i + 1].qr_code_path);
      pdf.addImage(img2, 'PNG', (50 - qrSize) / 2, 100 - qrSize - 5, qrSize, qrSize);
    }
  }

  pdf.save('QR Labels 5x10 cm.pdf');
}

// helper load image → dataURL
function convertImgToDataURL(url) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => {
      const canvas = document.createElement('canvas');
      canvas.width = img.width;
      canvas.height = img.height;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0);
      resolve(canvas.toDataURL('image/png'));
    };
    img.onerror = reject;
    img.src = url;
  });
}


onMounted(fetchData);
</script>