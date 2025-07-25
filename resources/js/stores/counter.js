// resources/js/stores/counter.js

import { defineStore } from 'pinia';
import axios from 'axios';
import router from '../router';

// const API_BASE_URL = 'http://127.0.0.1:8000/api'; // Pastikan URL API kamu benar
const API_BASE_URL = 'https://e1fb794d13df.ngrok-free.app/api'; // Ganti dengan URL ngrok kamu yang sebenarnya

export const useCounterStore = defineStore('inventoryApp', {
  state: () => ({
    // --- State Autentikasi & User ---
    isLoggedIn: false,
    userRole: null, // 'admin', 'head', 'karyawan'
    userName: null,
    userId: null,
    userEmail: null,
    userDivisi: null, // <-- Pastikan ini ada dan diinisialisasi
    token: localStorage.getItem('Authorization') || null,
    loginError: null, // Untuk pesan error login

    // --- State Master Data ---
    brands: [],
    categories: [],
    itemTypes: [],
    locationUnits: [],
    floors: [],
    rooms: [],
    usersList: [],
    inventoryItems: [], // Daftar master barang
    inventoryItemLoading: false,
    inventoryItemError: null,

    // --- State Data Inventaris ---
    inventories: [],
    selectedInventoryDetail: null,
    inventoryStatusStats: {},
    inventoryLoading: false,
    inventoryError: null,

    // --- State Maintenance ---
    maintenanceHistory: [],

    // --- State Dashboard & Laporan ---
    totalAssetValue: 0,
    totalDepreciation: 0,
    totalMaintenanceDone: 0,
    totalMaintenancePlanning: 0,

    // --- State untuk Filter/Pencarian (jika ada) ---
    filters: {
      locationUnit: null,
      building: null,
      floor: null,
      room: null,
      status: null,
      searchQuery: '',
      startDate: null,
      endDate: null,
    },
  }),

  getters: {
    isAdmin: (state) => state.userRole === 'admin',
    isKaryawan: (state) => state.userRole === 'karyawan',
    isHead: (state) => state.userRole === 'head',
    authHeader: (state) => ({
      headers: { Authorization: state.token },
    }),
  },

  actions: {
    // --- Aksi Autentikasi ---
    async login(credentials) {
      this.loginError = null; // Reset error
      try {
        const response = await axios.post(`${API_BASE_URL}/login`, credentials);
        const { Authorization, role, email, name, id, divisi } = response.data;

        localStorage.setItem('Authorization', Authorization);
        this.token = Authorization;
        this.isLoggedIn = true;
        this.userRole = role;
        this.userName = name;
        this.userId = id;
        this.userEmail = email;
        this.userDivisi = divisi;

        router.push('/dashboard');

        return true;
      } catch (error) {
        console.error('Login failed:', error.response?.data?.message || error.message);
        if (error.response && error.response.status === 401) {
            this.loginError = 'Email atau kata sandi salah.';
        } else if (error.response && error.response.status === 422) {
            this.loginError = 'Validasi gagal. Mohon periksa input Anda.';
        } else {
            this.loginError = 'Terjadi kesalahan saat login.';
        }
        this.logout(); // Pastikan state bersih jika login gagal
        throw error;
      }
    },

    async logout() {
      try {
        // Panggil API logout di backend untuk menghapus token
        await axios.post(`${API_BASE_URL}/logout`, {}, this.authHeader);
      } catch (error) {
        console.error('Logout API call failed, but clearing local storage anyway:', error);
      } finally {
        localStorage.removeItem('Authorization');
        this.token = null;
        this.isLoggedIn = false;
        this.userRole = null;
        this.userName = null;
        this.userId = null;
        this.userEmail = null;
        this.userDivisi = null;
        this.inventories = [];
        this.inventoryItems = [];
        this.selectedInventoryDetail = null;
        this.maintenanceHistory = [];
        this.usersList = [];

        router.push('/login');
      }
    },

    // --- Aksi Master Data (Brands, Categories, ItemTypes, LocationUnits, Floors, Rooms, Users) ---
    async fetchBrands() {
      try {
        const response = await axios.get(`${API_BASE_URL}/brands`, this.authHeader);
        this.brands = response.data;
      } catch (error) {
        console.error('Failed to fetch brands:', error);
      }
    },
    async createBrand(brandData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/brands`, brandData, this.authHeader);
            this.fetchBrands(); // Refresh list
            return response.data;
        } catch (error) {
            console.error('Failed to create brand:', error);
            throw error;
        }
    },
    async updateBrand(id, brandData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/brands/${id}`, brandData, this.authHeader);
            this.fetchBrands(); // Refresh list
            return response.data;
        } catch (error) {
            console.error('Failed to update brand:', error);
            throw error;
        }
    },
    async deleteBrand(id) {
        try {
            await axios.delete(`${API_BASE_URL}/brands/${id}`, this.authHeader);
            this.fetchBrands(); // Refresh list
        } catch (error) {
            console.error('Failed to delete brand:', error);
            throw error;
        }
    },

    async fetchCategories() {
      try {
        const response = await axios.get(`${API_BASE_URL}/categories`, this.authHeader);
        this.categories = response.data;
      } catch (error) {
        console.error('Failed to fetch categories:', error);
      }
    },
    async createCategory(categoryData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/categories`, categoryData, this.authHeader);
            this.fetchCategories();
            return response.data;
        } catch (error) {
            console.error('Failed to create category:', error);
            throw error;
        }
    },
    async updateCategory(id, categoryData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/categories/${id}`, categoryData, this.authHeader);
            this.fetchCategories();
            return response.data;
        } catch (error) {
            console.error('Failed to update category:', error);
            throw error;
        }
    },
    async deleteCategory(id) {
        try {
            await axios.delete(`${API_BASE_URL}/categories/${id}`, this.authHeader);
            this.fetchCategories();
        } catch (error) {
            console.error('Failed to delete category:', error);
            throw error;
        }
    },

    async fetchItemTypes() {
      try {
        const response = await axios.get(`${API_BASE_URL}/item-types`, this.authHeader);
        this.itemTypes = response.data;
      } catch (error) {
        console.error('Failed to fetch item types:', error);
      }
    },
    async createItemType(itemTypeData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/item-types`, itemTypeData, this.authHeader);
            this.fetchItemTypes();
            return response.data;
        } catch (error) {
            console.error('Failed to create item type:', error);
            throw error;
        }
    },
    async updateItemType(id, itemTypeData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/item-types/${id}`, itemTypeData, this.authHeader);
            this.fetchItemTypes();
            return response.data;
        } catch (error) {
            console.error('Failed to update item type:', error);
            throw error;
        }
    },
    async deleteItemType(id) {
        try {
            await axios.delete(`${API_BASE_URL}/item-types/${id}`, this.authHeader);
            this.fetchItemTypes();
        } catch (error) {
            console.error('Failed to delete item type:', error);
            throw error;
        }
    },

    async fetchLocationUnits() {
      try {
        const response = await axios.get(`${API_BASE_URL}/units`, this.authHeader);
        this.locationUnits = response.data;
      } catch (error) {
        console.error('Failed to fetch location units:', error);
      }
    },
    async createLocationUnit(unitData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/units`, unitData, this.authHeader);
            this.fetchLocationUnits();
            return response.data;
        } catch (error) {
            console.error('Failed to create location unit:', error);
            throw error;
        }
    },
    async updateLocationUnit(id, unitData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/units/${id}`, unitData, this.authHeader);
            this.fetchLocationUnits();
            return response.data;
        } catch (error) {
            console.error('Failed to update location unit:', error);
            throw error;
        }
    },
    async deleteLocationUnit(id) {
        try {
            await axios.delete(`${API_BASE_URL}/units/${id}`, this.authHeader);
            this.fetchLocationUnits();
        } catch (error) {
            console.error('Failed to delete location unit:', error);
            throw error;
        }
    },

    async fetchFloors(unitId = null) { // Bisa difilter berdasarkan unit
      try {
        const url = unitId ? `${API_BASE_URL}/units/${unitId}/floors` : `${API_BASE_URL}/floors`;
        const response = await axios.get(url, this.authHeader);
        this.floors = response.data;
      } catch (error) {
        console.error('Failed to fetch floors:', error);
      }
    },
    async createFloor(floorData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/floors`, floorData, this.authHeader);
            this.fetchFloors();
            return response.data;
        } catch (error) {
            console.error('Failed to create floor:', error);
            throw error;
        }
    },
    async updateFloor(id, floorData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/floors/${id}`, floorData, this.authHeader);
            this.fetchFloors();
            return response.data;
        } catch (error) {
            console.error('Failed to update floor:', error);
            throw error;
        }
    },
    async deleteFloor(id) {
        try {
            await axios.delete(`${API_BASE_URL}/floors/${id}`, this.authHeader);
            this.fetchFloors();
        } catch (error) {
            console.error('Failed to delete floor:', error);
            throw error;
        }
    },

    async fetchRooms(floorId = null) { // Bisa difilter berdasarkan lantai
      try {
        const url = floorId ? `${API_BASE_URL}/floors/${floorId}/rooms` : `${API_BASE_URL}/rooms`;
        const response = await axios.get(url, this.authHeader);
        this.rooms = response.data;
      } catch (error) {
        console.error('Failed to fetch rooms:', error);
      }
    },
    async createRoom(roomData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/rooms`, roomData, this.authHeader);
            this.fetchRooms();
            return response.data;
        } catch (error) {
            console.error('Failed to create room:', error);
            throw error;
        }
    },
    async updateRoom(id, roomData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/rooms/${id}`, roomData, this.authHeader);
            this.fetchRooms();
            return response.data;
        } catch (error) {
            console.error('Failed to update room:', error);
            throw error;
        }
    },
    async deleteRoom(id) {
        try {
            await axios.delete(`${API_BASE_URL}/rooms/${id}`, this.authHeader);
            this.fetchRooms();
        } catch (error) {
            console.error('Failed to delete room:', error);
            throw error;
        }
    },

    async fetchUsersList() { // Hanya untuk admin
      try {
        const response = await axios.get(`${API_BASE_URL}/users`, this.authHeader);
        this.usersList = response.data;
      } catch (error) {
        console.error('Failed to fetch users list:', error);
      }
    },
    async createUser(userData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/register`, userData, this.authHeader); // Menggunakan /register API
            this.fetchUsersList();
            return response.data;
        } catch (error) {
            console.error('Failed to create user:', error);
            throw error;
        }
    },
    async updateUser(id, userData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/users/${id}`, userData, this.authHeader);
            this.fetchUsersList();
            return response.data;
        } catch (error) {
            console.error('Failed to update user:', error);
            throw error;
        }
    },
    async deleteUser(id) {
        try {
            await axios.delete(`${API_BASE_URL}/users/${id}`, this.authHeader);
            this.fetchUsersList();
        } catch (error) {
            console.error('Failed to delete user:', error);
            throw error;
        }
    },

    // --- Aksi Inventory Items (Master Barang) ---
    async fetchInventoryItems(filters = {}) {
        this.inventoryItemLoading = true;
        this.inventoryItemError = null;
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const response = await axios.get(`${API_BASE_URL}/inventory-items?${queryParams}`, this.authHeader);
            this.inventoryItems = response.data;
        } catch (error) {
            this.inventoryItemError = error.response?.data?.message || error.message || 'Failed to fetch inventory items.';
            console.error('Failed to fetch inventory items:', error.response?.data || error.message);
            throw error;
        } finally {
            this.inventoryItemLoading = false;
        }
    },
    async createInventoryItem(itemData) {
        this.inventoryItemLoading = true;
        this.inventoryItemError = null;
        try {
            // Untuk upload file, gunakan FormData
            const formData = new FormData();
            for (const key in itemData) {
                if (itemData[key] !== null) { // Pastikan tidak menambahkan null ke FormData
                    formData.append(key, itemData[key]);
                }
            }
            
            const response = await axios.post(`${API_BASE_URL}/inventory-items`, formData, {
                headers: {
                    ...this.authHeader.headers,
                    'Content-Type': 'multipart/form-data', // Penting untuk upload file
                },
            });
            this.fetchInventoryItems(); // Refresh list
            return response.data;
        } catch (error) {
            this.inventoryItemError = error.response?.data?.message || error.message || 'Failed to create inventory item.';
            console.error('Failed to create inventory item:', error.response?.data || error.message);
            throw error;
        } finally {
            this.inventoryItemLoading = false;
        }
    },
    async updateInventoryItem(id, itemData) {
        this.inventoryItemLoading = true;
        this.inventoryItemError = null;
        try {
            const formData = new FormData();
            for (const key in itemData) {
                if (itemData[key] !== null) {
                    formData.append(key, itemData[key]);
                }
            }
            formData.append('_method', 'PUT'); // Laravel membutuhkan ini untuk PUT/PATCH dengan FormData

            const response = await axios.post(`${API_BASE_URL}/inventory-items/${id}`, formData, {
                headers: {
                    ...this.authHeader.headers,
                    'Content-Type': 'multipart/form-data',
                },
            });
            this.fetchInventoryItems(); // Refresh list
            return response.data;
        } catch (error) {
            this.inventoryItemError = error.response?.data?.message || error.message || 'Failed to update inventory item.';
            console.error('Failed to update inventory item:', error.response?.data || error.message);
            throw error;
        } finally {
            this.inventoryItemLoading = false;
        }
    },
    async deleteInventoryItem(id) {
        this.inventoryItemLoading = true;
        this.inventoryItemError = null;
        try {
            await axios.delete(`${API_BASE_URL}/inventory-items/${id}`, this.authHeader);
            this.fetchInventoryItems(); // Refresh list
        } catch (error) {
            this.inventoryItemError = error.response?.data?.message || error.message || 'Failed to delete inventory item.';
            console.error('Failed to delete inventory item:', error.response?.data || error.message);
            throw error;
        } finally {
            this.inventoryItemLoading = false;
        }
    },


    // --- Aksi Data Inventaris ---
    async fetchInventories(filters = {}) {
      this.inventoryLoading = true;
      this.inventoryError = null;
      try {
        const queryParams = new URLSearchParams(filters).toString();
        const response = await axios.get(`${API_BASE_URL}/inventories?${queryParams}`, this.authHeader);
        this.inventories = response.data;
      } catch (error) {
        this.inventoryError = error.response?.data?.message || error.message || 'Failed to fetch inventories.';
        console.error('Failed to fetch inventories:', error);
      } finally {
        this.inventoryLoading = false;
      }
    },
    async fetchInventoryDetail(id) {
      try {
        const response = await axios.get(`${API_BASE_URL}/inventories/${id}`, this.authHeader);
        this.selectedInventoryDetail = response.data;
        return response.data;
      } catch (error) {
        console.error('Failed to fetch inventory detail:', error);
        throw error;
      }
    },
    async createInventory(inventoryData) {
        this.inventoryLoading = true;
        this.inventoryError = null;
        try {
            const response = await axios.post(`${API_BASE_URL}/inventories`, inventoryData, this.authHeader);
            this.fetchInventories();
            return response.data;
        } catch (error) {
            this.inventoryError = error.response?.data?.message || error.message || 'Failed to create inventory.';
            console.error('Failed to create inventory:', error.response?.data || error.message);
            throw error;
        } finally {
            this.inventoryLoading = false;
        }
    },
    async updateInventory(id, inventoryData) {
        this.inventoryLoading = true;
        this.inventoryError = null;
        try {
            const response = await axios.put(`${API_BASE_URL}/inventories/${id}`, inventoryData, this.authHeader);
            this.fetchInventories();
            return response.data;
        } catch (error) {
            this.inventoryError = error.response?.data?.message || error.message || 'Failed to update inventory.';
            console.error('Failed to update inventory:', error.response?.data || error.message);
            throw error;
        } finally {
            this.inventoryLoading = false;
        }
    },
    async deleteInventory(id) {
        this.inventoryLoading = true;
        this.inventoryError = null;
        try {
            await axios.delete(`${API_BASE_URL}/inventories/${id}`, this.authHeader);
            this.fetchInventories();
        } catch (error) {
            this.inventoryError = error.response?.data?.message || error.message || 'Failed to delete inventory.';
            console.error('Failed to delete inventory:', error.response?.data || error.message);
            throw error;
        } finally {
            this.inventoryLoading = false;
        }
    },

    // --- Aksi Maintenance ---
    async fetchMaintenanceHistory(inventoryId = null, filters = {}) {
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
        const formData = new FormData();
        for (const key in maintenanceData) {
            if (maintenanceData[key] !== null) {
                formData.append(key, maintenanceData[key]);
            }
        }
        const response = await axios.post(`${API_BASE_URL}/inventories/${inventoryId}/maintenance`, formData, {
            headers: {
                ...this.authHeader.headers,
                'Content-Type': 'multipart/form-data',
            },
        });
        this.fetchInventoryDetail(inventoryId);
        this.fetchMaintenanceHistory(inventoryId);
        return response.data;
      } catch (error) {
        console.error('Failed to add maintenance record:', error);
        throw error;
      }
    },
    async updateMaintenanceRecord(id, maintenanceData) {
        try {
            const formData = new FormData();
            for (const key in maintenanceData) {
                if (maintenanceData[key] !== null) {
                    formData.append(key, maintenanceData[key]);
                }
            }
            formData.append('_method', 'PUT'); // Laravel membutuhkan ini untuk PUT/PATCH dengan FormData

            const response = await axios.post(`${API_BASE_URL}/maintenance/${id}`, formData, {
                headers: {
                    ...this.authHeader.headers,
                    'Content-Type': 'multipart/form-data',
                },
            });
            // Mungkin perlu refresh data terkait jika ada perubahan status atau tanggal
            return response.data;
        } catch (error) {
            console.error('Failed to update maintenance record:', error);
            throw error;
        }
    },
    async deleteMaintenanceRecord(id) {
        try {
            await axios.delete(`${API_BASE_URL}/maintenance/${id}`, this.authHeader);
            // Mungkin perlu refresh data terkait
        } catch (error) {
            console.error('Failed to delete maintenance record:', error);
            throw error;
        }
    },

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
    async fetchInventoryByQrCode(inventoryNumber) {
      try {
        const response = await axios.get(`${API_BASE_URL}/inventories/qr/${inventoryNumber}`, this.authHeader);
        this.selectedInventoryDetail = response.data;
        return response.data;
      } catch (error) {
        console.error('Failed to fetch inventory by QR code:', error);
        throw error;
      }
    },

    // --- Aksi Global/Helper ---
    initializeAuth() {
      const token = localStorage.getItem('Authorization');
      if (token) {
        this.isLoggedIn = true;
        this.token = token;
        this.fetchCurrentUser();
      }
    },
    async fetchCurrentUser() {
        try {
            const response = await axios.get(`${API_BASE_URL}/user`, this.authHeader);
            const user = response.data;
            this.userRole = user.role;
            this.userName = user.name;
            this.userId = user.id;
            this.userEmail = user.email;
            this.userDivisi = user.divisi;
        } catch (error) {
            console.error('Failed to fetch current user:', error);
            this.logout();
        }
    }
  },
});
