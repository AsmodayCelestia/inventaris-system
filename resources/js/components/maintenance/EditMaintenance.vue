<template>
  <div>
    <!-- Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">{{ pageTitle }}</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link to="/dashboard">Home</router-link>
              </li>
              <li class="breadcrumb-item">
                <router-link to="/maintenance/list">Maintenance</router-link>
              </li>
              <li class="breadcrumb-item active">{{ pageTitle }}</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="content">
      <div class="container-fluid">
        <!-- 403 -->
        <div v-if="context === 'forbidden'" class="card card-danger">
          <div class="card-header">Akses Ditolak</div>
          <div class="card-body">
            Kamu tidak memiliki hak untuk mengubah maintenance ini.
          </div>
        </div>

        <!-- Self-assign -->
        <div v-else-if="context === 'umum-selfassign'" class="card">
          <div class="card-header">Ambil Tugas</div>
          <div class="card-body">
            <p>Kamu akan menjadi PJ untuk maintenance ini.</p>
            <button class="btn btn-success" @click="selfAssign" :disabled="loading">
              {{ loading ? "Menyimpan..." : "Ambil Tugas" }}
            </button>
          </div>
        </div>

        <!-- Mini-cancel (on_progress) -->
        <div v-else-if="context === 'mini-cancel'" class="card">
          <div class="card-header">Batalkan Laporan</div>
          <form @submit.prevent="submitMiniCancel">
            <div class="card-body">
              <div class="form-group">
                <label>Masalah yang Ditemukan</label>
                <textarea v-model="form.issue_found" class="form-control" rows="3" :disabled="!canEditIssue"></textarea>
              </div>
              <div class="form-group">
                <label>Catatan</label>
                <textarea v-model="form.notes" class="form-control" rows="2" :disabled="!canEditNotes"></textarea>
              </div>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-warning" :disabled="loading">
                Batalkan Maintenance
              </button>
            </div>
          </form>
        </div>

        <!-- Pengawas mark done (handled) -->
        <div v-else-if="context === 'pengawas-mark-done'" class="card">
          <div class="card-header">Tandai Selesai</div>
          <form @submit.prevent="submitPengawasDone">
<!-- Ringkasan maintenance -->
<div class="card-body bg-light">
  <h5>Ringkasan Maintenance</h5>
  <table class="table table-borderless mb-0">
    <tr><td style="width: 160px"><b>Barang</b></td><td>{{ maintenance.inventory?.item?.name || '-' }}</td></tr>
    <tr><td><b>Nomor</b></td><td>{{ maintenance.inventory?.inventory_number || '-' }}</td></tr>
    <tr><td><b>Ruang</b></td><td>{{ maintenance.inventory?.room?.name ?? '-' }}</td></tr>
    <tr><td><b>Merk</b></td><td>{{ maintenance.inventory?.item?.manufacturer ?? '-' }}</td></tr>
    <tr><td><b>Tanggal</b></td><td>{{ formatDate(maintenance.inspection_date) }}</td></tr>
    <tr><td><b>PJ</b></td><td>{{ maintenance.responsible_person?.name ?? '-' }}</td></tr>
    <tr><td><b>Dibuat oleh</b></td><td>{{ maintenance.creator?.name ?? '-' }}</td></tr>
    <tr><td><b>Biaya estimasi</b></td><td>Rp {{ maintenance.cost ? Number(maintenance.cost).toLocaleString('id-ID') : '-' }}</td></tr>
    <tr><td><b>Masalah</b></td><td class="text-muted">{{ maintenance.issue_found ?? '-' }}</td></tr>

<tr v-if="photoList.length">
  <td><b>Foto</b></td>
  <td>
    <div class="d-flex gap-2">
      <img
        v-for="(url, idx) in photoList"
        :key="idx"
        :src="url"
        class="img-thumbnail"
        style="width: 120px; height: 90px; object-fit: cover;"
      />
    </div>
  </td>
</tr>
  </table>
</div>
            <div class="card-footer">
              <button type="submit" class="btn btn-success" :disabled="loading">
                Tandai Selesai
              </button>
            </div>
          </form>
        </div>

        <!-- Full / Umum-Update form -->
        <div v-else-if="context === 'full' || context === 'umum-update'" class="card">
          <div class="card-header">{{ context === "full" ? "Edit Laporan" : "Update Progress" }}</div>
          <form @submit.prevent="submitForm" enctype="multipart/form-data">
            <div class="card-body">
              <!-- Tanggal -->
              <div class="form-group">
                <label>Tanggal Pemeriksaan</label>
                <input type="date" v-model="form.inspection_date" class="form-control" :disabled="!canEditDate" required />
                <small v-if="errors.inspection_date" class="text-danger">{{ errors.inspection_date[0] }}</small>
              </div>

              <!-- Masalah -->
              <div class="form-group">
                <label>Masalah yang Ditemukan</label>
                <textarea v-model="form.issue_found" class="form-control" rows="3" :disabled="!canEditIssue" placeholder="Deskripsikan masalah..."></textarea>
                <small v-if="errors.issue_found" class="text-danger">{{ errors.issue_found[0] }}</small>
              </div>

              <!-- Solusi -->
              <div class="form-group">
                <label>Tindakan Perbaikan</label>
                <textarea v-model="form.solution_taken" class="form-control" rows="3" :disabled="!canEditSolution" placeholder="Langkah yang dilakukan..."></textarea>
                <small v-if="errors.solution_taken" class="text-danger">{{ errors.solution_taken[0] }}</small>
              </div>

              <!-- Catatan -->
              <div class="form-group">
                <label>Catatan Tambahan</label>
                <textarea v-model="form.notes" class="form-control" rows="2" :disabled="!canEditNotes" placeholder="Catatan opsional..."></textarea>
                <small v-if="errors.notes" class="text-danger">{{ errors.notes[0] }}</small>
              </div>

              <!-- Status -->
              <div class="form-group">
                <label>Status</label>
                <select v-model="form.status" class="form-control" :disabled="!canEditStatus" required>
                  <option v-for="s in allowedStatus" :key="s" :value="s">{{ statusLabel[s] }}</option>
                </select>
                <small v-if="errors.status" class="text-danger">{{ errors.status[0] }}</small>
              </div>

              <!-- Biaya -->
              <div class="form-group">
                <label>Biaya (Rp)</label>
                <input type="number" class="form-control" v-model.number="form.cost" min="0" :disabled="!canEditCost" />
                <small v-if="errors.cost" class="text-danger">{{ errors.cost[0] }}</small>
              </div>

              <!-- Foto -->
              <div v-for="i in [1,2,3]" :key="i" class="form-group">
                <label>Foto {{ i }}</label>
                <div class="custom-file">
                  <input type="file" class="custom-file-input" accept="image/*" :disabled="!canEditPhotos" @change="e => handlePhoto(e, `photo_${i}`)" />
                  <label class="custom-file-label">{{ imageName[i-1] || "Pilih gambar..." }}</label>
                </div>
                <div class="mt-2" v-if="preview[i-1] || existingPhoto[i-1]">
                  <img :src="preview[i-1] || existingPhoto[i-1]" class="img-thumbnail" style="max-width: 200px" />
                  <div class="form-check mt-2" v-if="existingPhoto[i-1] && !preview[i-1]">
                    <input type="checkbox" class="form-check-input" v-model="remove[i-1]" :disabled="!canEditPhotos" />
                    <label class="form-check-label">Hapus gambar yang ada</label>
                  </div>
                </div>
                <small v-if="errors[`photo_${i}`]" class="text-danger">{{ errors[`photo_${i}`][0] }}</small>
              </div>

              <!-- PJ (admin/head only) -->
              <div v-if="context === 'full'" class="form-group">
                <label>Penanggung Jawab</label>
                <select v-model="form.user_id" class="form-control" :disabled="!canEditUser">
                  <option :value="null">-- Tidak ada --</option>
                  <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
              </div>
            </div>

            <div class="card-footer">
              <button type="submit" class="btn btn-primary" :disabled="loading">
                {{ loading ? "Menyimpan..." : "Simpan Perubahan" }}
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
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useCounterStore } from "@/stores/counter";

const route   = useRoute();
const router  = useRouter();
const counter = useCounterStore();

/* -------------------------------------------------
   Reactives
-------------------------------------------------- */
const loading    = ref(false);
const fetchError = ref(null);
const errors     = ref({});
const maintenance = ref(null);
const users       = ref([]);

const form = ref({
  inspection_date: "",
  issue_found: "",
  solution_taken: "",
  notes: "",
  status: "",
  cost: null,
  user_id: null,
});

const preview       = ref([null, null, null]);
const existingPhoto = ref(["", "", ""]);
const imageName     = ref(["", "", ""]);
const remove        = ref([false, false, false]);

const statusOptions = ["reported","on_progress","handled","done","cancelled"];
const statusLabel = {
  reported   : "Dilaporkan",
  on_progress: "Dalam Proses",
  handled    : "Ditangani",
  done       : "Selesai",
  cancelled  : "Dibatalkan",
};

/* -------------------------------------------------
   Context
-------------------------------------------------- */
const isCreator = computed(() => maintenance.value?.creator_id === counter.userId);

const photoList = computed(() =>
  [maintenance.value?.photo_1, maintenance.value?.photo_2, maintenance.value?.photo_3].filter(Boolean)
);

const context = computed(() => {
  const user   = counter.userRole;
  const divisi = counter.userDivisi;
  const status = maintenance.value?.status;

  if (["admin","head"].includes(user)) return "full";

  if (user === "karyawan" && divisi === "Umum") {
    if (status === "reported") return "umum-selfassign";
    if (status === "on_progress") return "umum-update";
  }

  if (status === "on_progress" && (counter.isRoomSupervisor || isCreator.value))
    return "mini-cancel";

  if (status === "handled" && counter.isRoomSupervisor)
    return "pengawas-mark-done";

  return "forbidden";
});

/* -------------------------------------------------
   Field-level disable maps
-------------------------------------------------- */
const canEditDate     = computed(() => ["full"].includes(context.value));
const canEditIssue    = computed(() => ["full","umum-update","mini-cancel","pengawas-mark-done"].includes(context.value));
const canEditSolution = computed(() => ["full","umum-update"].includes(context.value));
const canEditNotes    = computed(() => ["full","umum-update","mini-cancel","pengawas-mark-done"].includes(context.value));
const canEditStatus   = computed(() => ["full","umum-update","mini-cancel","pengawas-mark-done"].includes(context.value));
const canEditCost     = computed(() => ["full","umum-update"].includes(context.value));
const canEditPhotos   = computed(() => ["full","umum-update"].includes(context.value));
const canEditUser     = computed(() => context.value === "full");

/* -------------------------------------------------
   Allowed status list
-------------------------------------------------- */
const allowedStatus = computed(() => {
  switch (context.value) {
    case "umum-update":  return ["handled","cancelled"];
    case "mini-cancel":  return ["cancelled"];
    case "pengawas-mark-done": return ["done"];
    default:             return statusOptions;
  }
});

const pageTitle = computed(() => {
  switch (context.value) {
    case "umum-selfassign": return "Ambil Tugas";
    case "mini-cancel":     return "Batalkan Laporan";
    case "pengawas-mark-done": return "Tandai Selesai";
    case "full":            return "Edit Laporan";
    case "umum-update":     return "Update Progress";
    default:                return "Maintenance";
  }
});

/* -------------------------------------------------
   Helpers
-------------------------------------------------- */
function handlePhoto(e, key) {
  const idx = +key.split("_")[1] - 1;
  const file = e.target.files[0];
  if (!file) {
    form.value[key] = null;
    preview.value[idx] = null;
    imageName.value[idx] = "";
    remove.value[idx] = false;
    return;
  }
  form.value[key] = file;
  preview.value[idx] = URL.createObjectURL(file);
  imageName.value[idx] = file.name;
  remove.value[idx] = false;
}


/* -------------------------------------------------
   Helpers
-------------------------------------------------- */
function formatDate(date) {
  return date
    ? new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    : '-';
}
/* -------------------------------------------------
   Mount
-------------------------------------------------- */
onMounted(async () => {
  const id = route.params.id;
  loading.value = true;
  try {
    maintenance.value = await counter.fetchMaintenanceDetail(id);
    const isoDate = maintenance.value.inspection_date
      ? new Date(maintenance.value.inspection_date).toISOString().slice(0, 10)
      : "";
    form.value = {
      inspection_date: isoDate,
      issue_found: maintenance.value.issue_found || "",
      solution_taken: maintenance.value.solution_taken || "",
      notes: maintenance.value.notes || "",
      status: maintenance.value.status,
      cost: maintenance.value.cost ?? null,
      user_id: maintenance.value.user_id ?? null,
    };
    existingPhoto.value = [maintenance.value.photo_1||"", maintenance.value.photo_2||"", maintenance.value.photo_3||""];
    if (context.value === "full") users.value = await counter.fetchUsersList?.() ?? [];
  } catch (e) {
    fetchError.value = e.message || "Gagal memuat data";
    router.push("/maintenance/list");
  } finally {
    loading.value = false;
  }
});

/* -------------------------------------------------
   Submit handlers
-------------------------------------------------- */
async function selfAssign() {
  loading.value = true;
  try {
    await counter.assignMaintenance(route.params.id, { user_id: counter.userId });
    alert("Tugas diambil!");
    router.push("/maintenance/list");
  } catch (e) {
    alert("Gagal: " + (e.response?.data?.message || e.message));
  } finally {
    loading.value = false;
  }
}

async function submitForm() {
  loading.value = true;
  errors.value = {};
  const fd = new FormData();
  const allowed = new Set(["inspection_date","issue_found","solution_taken","notes","status","cost","user_id","photo_1","photo_2","photo_3"]);
  Object.keys(form.value).forEach(k => {
    if (allowed.has(k) && form.value[k] != null) fd.append(k, form.value[k]);
  });
  ["photo_1","photo_2","photo_3"].forEach((k,i)=>{
    fd.append(`remove_${k}`, remove.value[i] ? "1" : "0");
    if (preview.value[i] && form.value[k]) fd.append(k, form.value[k]);
  });
  try {
    await counter.updateMaintenanceRecord(route.params.id, fd);
    alert("Berhasil disimpan!");
    router.push("/maintenance/list");
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors;
    else alert("Gagal: " + (e.response?.data?.message || e.message));
  } finally {
    loading.value = false;
  }
}

async function submitMiniCancel() {
  loading.value = true;
  try {
    await counter.updateMaintenanceRecord(route.params.id, {
      _method: "PUT",
      issue_found: form.value.issue_found,
      notes: form.value.notes,
      status: "cancelled",
    });
    alert("Maintenance dibatalkan.");
    router.push("/maintenance/list");
  } catch (e) {
    alert("Gagal: " + (e.response?.data?.message || e.message));
  } finally {
    loading.value = false;
  }
}

async function submitPengawasDone() {
  loading.value = true;
  try {
    await counter.updateMaintenanceRecordJson(route.params.id, {
      issue_found: form.value.issue_found,
      notes: form.value.notes,
      status: "done",
    });
    alert("Ditandai selesai!");
    router.push("/maintenance/list");
  } catch (e) {
    alert("Gagal: " + (e.response?.data?.message || e.message));
  } finally {
    loading.value = false;
  }
}
</script>