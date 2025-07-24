// resources/js/stores/counter.js

import { defineStore } from 'pinia';
import axios from 'axios'; // Pastikan axios sudah terinstal dan diimpor
import router from '../router'; // <-- IMPORT ROUTER DI SINI!

const API_BASE_URL = 'http://localhost:8000/api'; // Sesuaikan dengan URL API Laravel kamu

export const useCounterStore = defineStore('inventoryApp', {
  state: () => ({
    // --- State Autentikasi & User ---
    isLoggedIn: false,
    userRole: null, // 'admin', 'head', 'karyawan'
    userName: null,
    userId: null,
    userEmail: null,
    token: localStorage.getItem('Authorization') || null, // Ambil token dari localStorage saat inisialisasi

    // --- State Master Data ---
    brands: [],
    categories: [],
    itemTypes: [],
    locationUnits: [],
    floors: [],
    rooms: [],
    usersList: [], // Daftar user untuk manajemen admin

    // --- State Data Inventaris ---
    inventories: [], // Daftar semua item inventaris
    selectedInventoryDetail: null, // Detail inventaris yang sedang dilihat
    inventoryStatusStats: {}, // Statistik jumlah barang berdasarkan status (dashboard)
    inventoryLoading: false, // <-- TAMBAHKAN INI untuk status loading inventaris
    inventoryError: null,    // <-- TAMBAHKAN INI untuk error inventaris

    // --- State Maintenance ---
    maintenanceHistory: [], // Riwayat maintenance keseluruhan
    // planningMaintenance: [], // Bisa difilter dari maintenanceHistory
    // doneMaintenance: [], // Bisa difilter dari maintenanceHistory

    // --- State Dashboard & Laporan ---
    totalAssetValue: 0,
    totalDepreciation: 0,
    // Statistik jumlah barang berdasarkan status sudah di inventoryStatusStats
    totalMaintenanceDone: 0,
    totalMaintenancePlanning: 0,

    // --- State untuk Filter/Pencarian (jika ada) ---
    filters: {
      locationUnit: null,
      building: null, // Jika ada tabel building
      floor: null,
      room: null,
      status: null,
      searchQuery: '',
      startDate: null,
      endDate: null,
    },
  }),

  getters: {
    // Getter untuk mengecek apakah user adalah admin
    isAdmin: (state) => state.userRole === 'admin',
    // Getter untuk mengecek apakah user adalah petugas/karyawan
    isKaryawan: (state) => state.userRole === 'karyawan',
    // Getter untuk mendapatkan header otorisasi
    authHeader: (state) => ({
      headers: { Authorization: state.token },
    }),
  },

  actions: {
    // --- Aksi Autentikasi ---
    async login(credentials) {
      try {
        const response = await axios.post(`${API_BASE_URL}/login`, credentials);
        const { Authorization, role, email, name, id } = response.data;

        localStorage.setItem('Authorization', Authorization);
        this.token = Authorization;
        this.isLoggedIn = true;
        this.userRole = role;
        this.userName = name;
        this.userId = id;
        this.userEmail = email;

        // Redirect ke dashboard setelah login berhasil
        router.push('/dashboard'); 

        return true; // Beri sinyal sukses
      } catch (error) {
        console.error('Login failed:', error.response?.data?.message || error.message);
        this.logout(); // Pastikan state bersih jika login gagal
        throw error; // Lempar error agar bisa ditangkap di komponen
      }
    },

    logout() {
      localStorage.removeItem('Authorization');
      this.token = null;
      this.isLoggedIn = false;
      this.userRole = null;
      this.userName = null;
      this.userId = null;
      this.userEmail = null;
      // Bersihkan state lain yang tergantung pada user
      this.inventories = [];
      this.selectedInventoryDetail = null;
      this.maintenanceHistory = [];
      this.usersList = [];
      
      // Redirect ke halaman login setelah logout
      router.push('/login'); 
    },

    // --- Aksi Master Data ---
    async fetchBrands() {
      try {
        const response = await axios.get(`${API_BASE_URL}/brands`, this.authHeader);
        this.brands = response.data;
      } catch (error) {
        console.error('Failed to fetch brands:', error);
      }
    },
    async createBrand(brandData) { /* ... */ },
    async updateBrand(id, brandData) { /* ... */ },
    async deleteBrand(id) { /* ... */ },

    async fetchCategories() {
      try {
        const response = await axios.get(`${API_BASE_URL}/categories`, this.authHeader);
        this.categories = response.data;
      } catch (error) {
        console.error('Failed to fetch categories:', error);
      }
    },
    async createCategory(categoryData) { /* ... */ },
    async updateCategory(id, categoryData) { /* ... */ },
    async deleteCategory(id) { /* ... */ },

    async fetchItemTypes() {
      try {
        const response = await axios.get(`${API_BASE_URL}/item-types`, this.authHeader);
        this.itemTypes = response.data;
      } catch (error) {
        console.error('Failed to fetch item types:', error);
      }
    },
    async createItemType(itemTypeData) { /* ... */ },
    async updateItemType(id, itemTypeData) { /* ... */ },
    async deleteItemType(id) { /* ... */ },

    async fetchLocationUnits() {
      try {
        const response = await axios.get(`${API_BASE_URL}/units`, this.authHeader);
        this.locationUnits = response.data;
      } catch (error) {
        console.error('Failed to fetch location units:', error);
      }
    },
    async createLocationUnit(unitData) { /* ... */ },
    async updateLocationUnit(id, unitData) { /* ... */ },
    async deleteLocationUnit(id) { /* ... */ },

    async fetchFloors(unitId = null) { // Bisa difilter berdasarkan unit
      try {
        const url = unitId ? `${API_BASE_URL}/units/${unitId}/floors` : `${API_BASE_URL}/floors`;
        const response = await axios.get(url, this.authHeader);
        this.floors = response.data;
      } catch (error) {
        console.error('Failed to fetch floors:', error);
      }
    },
    async createFloor(floorData) { /* ... */ },
    async updateFloor(id, floorData) { /* ... */ },
    async deleteFloor(id) { /* ... */ },

    async fetchRooms(floorId = null) { // Bisa difilter berdasarkan lantai
      try {
        const url = floorId ? `${API_BASE_URL}/floors/${floorId}/rooms` : `${API_BASE_URL}/rooms`;
        const response = await axios.get(url, this.authHeader);
        this.rooms = response.data;
      } catch (error) {
        console.error('Failed to fetch rooms:', error);
      }
    },
    async createRoom(roomData) { /* ... */ },
    async updateRoom(id, roomData) { /* ... */ },
    async deleteRoom(id) { /* ... */ },

    async fetchUsersList() { // Hanya untuk admin
      try {
        const response = await axios.get(`${API_BASE_URL}/users`, this.authHeader);
        this.usersList = response.data;
      } catch (error) {
        console.error('Failed to fetch users list:', error);
      }
    },
    async createUser(userData) { /* ... */ },
    async updateUser(id, userData) { /* ... */ },
    async deleteUser(id) { /* ... */ },

    // --- Aksi Data Inventaris ---
    async fetchInventories(filters = {}) {
      this.inventoryLoading = true; // <-- Set loading true
      this.inventoryError = null;    // <-- Reset error
      try {
        // Bangun query params dari filters
        const queryParams = new URLSearchParams(filters).toString();
        const response = await axios.get(`${API_BASE_URL}/inventories?${queryParams}`, this.authHeader);
        this.inventories = response.data;
      } catch (error) {
        this.inventoryError = error.response?.data?.message || error.message || 'Failed to fetch inventories.'; // <-- Set error
        console.error('Failed to fetch inventories:', error);
      } finally {
        this.inventoryLoading = false; // <-- Set loading false
      }
    },
    async fetchInventoryDetail(id) {
      try {
        const response = await axios.get(`${API_BASE_URL}/inventories/${id}`, this.authHeader);
        this.selectedInventoryDetail = response.data;
      } catch (error) {
        console.error('Failed to fetch inventory detail:', error);
      }
    },
    async createInventory(inventoryData) { /* ... */ },
    async updateInventory(id, inventoryData) { /* ... */ },
    async deleteInventory(id) { /* ... */ },

    // --- Aksi Maintenance ---
    async fetchMaintenanceHistory(inventoryId = null, filters = {}) { // Bisa untuk semua atau spesifik inventaris
      try {
        let url = `${API_BASE_URL}/maintenance/history`;
        if (inventoryId) {
          url = `${API_BASE_URL}/inventories/${inventoryId}/maintenance`;
        }
        const queryParams = new URLSearchParams(filters).toString();
        const response = await axios.get(`${url}?${queryParams}`, this.authHeader);
        this.maintenanceHistory = response.data;
      } catch (error) {
        console.error('Failed to fetch maintenance history:', error);
      }
    },
    async addMaintenanceRecord(inventoryId, maintenanceData) {
      try {
        await axios.post(`${API_BASE_URL}/inventories/${inventoryId}/maintenance`, maintenanceData, this.authHeader);
        // Setelah berhasil, refetch detail inventaris atau riwayat maintenance
        this.fetchInventoryDetail(inventoryId);
        this.fetchMaintenanceHistory(inventoryId); // Refresh history untuk inventaris ini
      } catch (error) {
        console.error('Failed to add maintenance record:', error);
        throw error; // Lempar error agar bisa ditangkap di komponen
      }
    },
    async updateMaintenanceRecord(id, maintenanceData) { /* ... */ },
    async deleteMaintenanceRecord(id) { /* ... */ },

    // --- Aksi Dashboard & Laporan ---
    async fetchDashboardStats() {
      try {
        const response = await axios.get(`${API_BASE_URL}/dashboard/stats`, this.authHeader);
        const data = response.data;
        this.totalAssetValue = data.totalAssetValue;
        this.totalDepreciation = data.totalDepreciation;
        this.inventoryStatusStats = data.inventoryStatusStats;
        this.totalMaintenanceDone = data.totalMaintenanceDone;
        this.totalMaintenancePlanning = data.totalMaintenancePlanning;
      } catch (error) {
        console.error('Failed to fetch dashboard stats:', error);
      }
    },

    // --- Aksi QR Code ---
    // Logika scan QR code akan lebih banyak di komponen Vue yang menangani kamera
    // Store mungkin hanya menyimpan data inventaris yang di-scan atau statusnya
    async fetchInventoryByQrCode(inventoryNumber) {
      try {
        const response = await axios.get(`${API_BASE_URL}/inventories/qr/${inventoryNumber}`, this.authHeader);
        this.selectedInventoryDetail = response.data;
        return response.data; // Mengembalikan data untuk komponen yang memanggil
      } catch (error) {
        console.error('Failed to fetch inventory by QR code:', error);
        throw error;
      }
    },

    // --- Aksi Global/Helper ---
    initializeAuth() {
      // Panggil ini saat aplikasi pertama kali dimuat (misal di App.vue created/mounted)
      // Untuk memeriksa apakah ada token di localStorage dan set isLoggedIn/userRole
      const token = localStorage.getItem('Authorization');
      if (token) {
        // Kamu bisa menambahkan validasi token ke backend jika perlu
        // Untuk saat ini, kita asumsikan token valid jika ada
        this.isLoggedIn = true;
        this.token = token;
        // Jika token JWT kamu menyimpan role, kamu bisa decode di sini
        // const decoded = jwt_decode(token); // Butuh library jwt-decode
        // this.userRole = localStorage.getItem('userRole'); // Ambil dari localStorage
        // this.userName = localStorage.getItem('userName'); // Ambil dari localStorage
        // this.userId = localStorage.getItem('userId');     // Ambil dari localStorage
        // this.userEmail = localStorage.getItem('userEmail'); // Ambil dari localStorage
        // Atau, fetch profil user dari backend jika tokennya hanya ID
      }
    },
  },
});
