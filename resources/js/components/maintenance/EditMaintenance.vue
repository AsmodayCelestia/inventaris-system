<template>
  <div>
    <!-- Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Edit Laporan Maintenance</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link to="/dashboard">Home</router-link>
              </li>
              <li class="breadcrumb-item">
                <router-link to="/maintenance/list">Maintenance</router-link>
              </li>
              <li class="breadcrumb-item active">Edit</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Form Pelaporan Maintenance</h3>
          </div>

          <form @submit.prevent="submitForm" enctype="multipart/form-data">
            <div class="card-body">
              <div v-if="loading" class="text-center p-4">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2">Memuat data...</p>
              </div>
              <div v-else-if="fetchError" class="alert alert-danger m-3">
                {{ fetchError }}
              </div>

              <!-- Tanggal -->
              <div class="form-group">
                <label>Tanggal Pemeriksaan</label>
                <input
                  type="date"
                  v-model="form.inspection_date"
                  class="form-control"
                  required
                />
              </div>

              <div class="form-group">
                <label>Masalah yang Ditemukan</label>
                <textarea
                  v-model="form.issue_found"
                  class="form-control"
                  rows="3"
                  placeholder="Deskripsikan masalah..."
                ></textarea>
              </div>

              <div class="form-group">
                <label>Tindakan Perbaikan</label>
                <textarea
                  v-model="form.solution_taken"
                  class="form-control"
                  rows="3"
                  placeholder="Langkah yang dilakukan..."
                ></textarea>
              </div>

              <div class="form-group">
                <label>Catatan Tambahan</label>
                <textarea
                  v-model="form.notes"
                  class="form-control"
                  rows="2"
                  placeholder="Catatan opsional..."
                ></textarea>
              </div>

              <!-- Status -->
              <div class="form-group">
                <label>Status</label>
                <select v-model="form.status" class="form-control" required>
                  <option value="planning">Direncanakan</option>
                  <option value="done">Selesai</option>
                </select>
              </div>

              <!-- Foto 1 -->
              <div class="form-group">
                <label>Foto 1</label>
                <div class="custom-file">
                  <input
                    type="file"
                    class="custom-file-input"
                    accept="image/*"
                    @change="e => handlePhoto(e, 'photo_1')"
                  />
                  <label class="custom-file-label">
                    {{ imageName1 || "Pilih gambar..." }}
                  </label>
                </div>
                <div class="mt-2" v-if="preview1 || existingPhoto1">
                  <img
                    :src="preview1 || existingPhoto1"
                    alt="Preview"
                    class="img-thumbnail"
                    style="max-width: 200px"
                  />
                  <div
                    class="form-check mt-2"
                    v-if="existingPhoto1 && !preview1"
                  >
                    <input
                      type="checkbox"
                      class="form-check-input"
                      v-model="removePhoto1"
                    />
                    <label class="form-check-label">
                      Hapus gambar yang ada
                    </label>
                  </div>
                </div>
              </div>

              <!-- Foto 2 -->
              <div class="form-group">
                <label>Foto 2</label>
                <div class="custom-file">
                  <input
                    type="file"
                    class="custom-file-input"
                    accept="image/*"
                    @change="e => handlePhoto(e, 'photo_2')"
                  />
                  <label class="custom-file-label">
                    {{ imageName2 || "Pilih gambar..." }}
                  </label>
                </div>
                <div class="mt-2" v-if="preview2 || existingPhoto2">
                  <img
                    :src="preview2 || existingPhoto2"
                    alt="Preview"
                    class="img-thumbnail"
                    style="max-width: 200px"
                  />
                  <div
                    class="form-check mt-2"
                    v-if="existingPhoto2 && !preview2"
                  >
                    <input
                      type="checkbox"
                      class="form-check-input"
                      v-model="removePhoto2"
                    />
                    <label class="form-check-label">
                      Hapus gambar yang ada
                    </label>
                  </div>
                </div>
              </div>

              <!-- Foto 3 -->
              <div class="form-group">
                <label>Foto 3</label>
                <div class="custom-file">
                  <input
                    type="file"
                    class="custom-file-input"
                    accept="image/*"
                    @change="e => handlePhoto(e, 'photo_3')"
                  />
                  <label class="custom-file-label">
                    {{ imageName3 || "Pilih gambar..." }}
                  </label>
                </div>
                <div class="mt-2" v-if="preview3 || existingPhoto3">
                  <img
                    :src="preview3 || existingPhoto3"
                    alt="Preview"
                    class="img-thumbnail"
                    style="max-width: 200px"
                  />
                  <div
                    class="form-check mt-2"
                    v-if="existingPhoto3 && !preview3"
                  >
                    <input
                      type="checkbox"
                      class="form-check-input"
                      v-model="removePhoto3"
                    />
                    <label class="form-check-label">
                      Hapus gambar yang ada
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-footer">
              <button type="submit" class="btn btn-primary" :disabled="loading">
                {{ loading ? "Menyimpan..." : "Simpan Perubahan" }}
              </button>
              <router-link to="/maintenance/list" class="btn btn-secondary ml-2">
                Batal
              </router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useCounterStore } from "@/stores/counter";

const route   = useRoute();
const router  = useRouter();
const counter = useCounterStore();

const loading      = ref(false);
const fetchError   = ref(null);
const form = ref({
  inspection_date: "",
  issue_found: "",
  solution_taken: "",
  notes: "",
  status: "done",
});

// Foto
const preview1        = ref(null);
const preview2        = ref(null);
const preview3        = ref(null);
const existingPhoto1  = ref("");
const existingPhoto2  = ref("");
const existingPhoto3  = ref("");
const imageName1      = ref("");
const imageName2      = ref("");
const imageName3      = ref("");
const removePhoto1    = ref(false);
const removePhoto2    = ref(false);
const removePhoto3    = ref(false);

/* -------------------------------------------------
   Handler foto
-------------------------------------------------- */
function handlePhoto(event, key) {
  const file = event.target.files[0];
  let previewRef, nameRef, removeRef;

  switch (key) {
    case "photo_1":
      previewRef = preview1;
      nameRef    = imageName1;
      removeRef  = removePhoto1;
      break;
    case "photo_2":
      previewRef = preview2;
      nameRef    = imageName2;
      removeRef  = removePhoto2;
      break;
    case "photo_3":
      previewRef = preview3;
      nameRef    = imageName3;
      removeRef  = removePhoto3;
      break;
  }

  if (!file) {
    form.value[key]   = null;
    previewRef.value  = null;
    nameRef.value     = "";
    removeRef.value   = false;
    return;
  }

  form.value[key]   = file;
  previewRef.value  = URL.createObjectURL(file);
  nameRef.value     = file.name;
  removeRef.value   = false;
}

/* -------------------------------------------------
   Ambil data saat mounted
-------------------------------------------------- */
onMounted(async () => {
  const id = route.params.id;
  loading.value = true;
  try {
    const record = await counter.fetchMaintenanceDetail(id);
    form.value = {
      inspection_date: record.inspection_date,
      issue_found: record.issue_found || "",
      solution_taken: record.solution_taken || "",
      notes: record.notes || "",
      status: record.status,
    };
    existingPhoto1.value = record.photo_1 || "";
    existingPhoto2.value = record.photo_2 || "";
    existingPhoto3.value = record.photo_3 || "";
  } catch (e) {
    fetchError.value = "Data tidak ditemukan";
    alert("Data tidak ditemukan");
    router.push("/maintenance/list");
  } finally {
    loading.value = false;
  }
});

/* -------------------------------------------------
   Submit
-------------------------------------------------- */
async function submitForm() {
  loading.value = true;
  try {
    const fd = new FormData();

    Object.entries(form.value).forEach(([k, v]) => {
      if (v !== null && v !== "") fd.append(k, v);
    });

    fd.append("_method", "PUT");
    fd.append("remove_photo_1", removePhoto1.value ? "1" : "0");
    fd.append("remove_photo_2", removePhoto2.value ? "1" : "0");
    fd.append("remove_photo_3", removePhoto3.value ? "1" : "0");

    ["photo_1", "photo_2", "photo_3"].forEach((k) => {
      if (form.value[k]) fd.append(k, form.value[k]);
    });

    await counter.updateMaintenanceRecord(route.params.id, fd);
    alert("Laporan berhasil diperbarui!");
    router.push("/maintenance/list");
  } catch (e) {
    alert("Gagal menyimpan: " + e.message);
  } finally {
    loading.value = false;
  }
}
</script>