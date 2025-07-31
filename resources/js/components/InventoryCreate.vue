<template>
        <div class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Form Inventaris Baru</h3>
                    </div>
                    <form @submit.prevent="submitForm"> <div class="card-body">
                            <div class="form-group">
                                <label for="description">Keterangan</label>
                                <textarea v-model="form.description" class="form-control" id="description" rows="3"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="image">Gambar Inventaris</label>
                                <div class="custom-file">
                                    <input type="file" @change="handleImageUpload" class="custom-file-input" id="image">
                                    <label class="custom-file-label" for="image">{{ imageName || 'Pilih gambar...' }}</label>
                                </div>
                                <div v-if="imageUrlPreview" class="mt-2">
                                    <img :src="imageUrlPreview" alt="Preview Gambar" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            </div>

                            </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <router-link to="/inventories" class="btn btn-secondary ml-2">Batal</router-link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</template>

<script setup>
import { ref } from 'vue';
// ... import lainnya seperti Layout, useCounterStore, useRouter

// Pastikan di `form` ada properti untuk gambar
const form = ref({
    // ... properti form lainnya
    description: '',
    image: null, // Ini untuk menyimpan file gambar yang dipilih
});

const imageName = ref(''); // Untuk menampilkan nama file yang dipilih
const imageUrlPreview = ref(null); // Untuk preview gambar

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.image = file; // Simpan objek file ke form
        imageName.value = file.name; // Tampilkan nama file
        imageUrlPreview.value = URL.createObjectURL(file); // Buat URL preview
    } else {
        form.value.image = null;
        imageName.value = '';
        imageUrlPreview.value = null;
    }
};

const submitForm = async () => {
    try {
        // PERHATIAN PENTING: Untuk mengirim file, gunakan FormData!
        const formData = new FormData();
        for (const key in form.value) {
            if (form.value[key] !== null) { // Pastikan tidak ada nilai null yang dikirim
                formData.append(key, form.value[key]);
            }
        }

        // Contoh pengiriman ke API Laravel (sesuaikan dengan method store/update kamu)
        // Jika ini untuk create:
        await axios.post('/api/inventories', formData, {
            headers: {
                'Content-Type': 'multipart/form-data' // Penting untuk kirim file
            }
        });

        // Jika ini untuk edit, pastikan method-nya PATCH/POST dan sertakan ID:
        // await axios.post(`/api/inventories/${itemId}`, formData, { // Laravel butuh POST untuk PATCH/PUT dengan file upload
        //     headers: {
        //         'Content-Type': 'multipart/form-data',
        //         'X-HTTP-Method-Override': 'PATCH' // Opsional jika API kamu tidak menerima PATCH langsung dengan FormData
        //     }
        // });

        alert('Inventaris berhasil disimpan!');
        // Redirect atau lakukan sesuatu setelah berhasil
        // router.push('/inventories');

    } catch (error) {
        console.error('Gagal menyimpan inventaris:', error);
        alert('Gagal menyimpan inventaris. Cek konsol untuk detail.');
    }
};

// ... fungsi lainnya ...
</script>

<style scoped>
/* Pastikan ada gaya untuk custom-file jika menggunakan Bootstrap/AdminLTE */
</style>