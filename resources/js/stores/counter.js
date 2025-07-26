// resources/js/stores/counter.js

import { defineStore } from 'pinia';
import axios from 'axios';
import router from '../router';

const API_BASE_URL = 'http://127.0.0.1:8000/api'; // Pastikan URL API kamu benar
// const API_BASE_URL = 'https://e1fb794d13df.ngrok-free.app/api'; // Ganti dengan URL ngrok kamu yang sebenarnya

export const useCounterStore = defineStore('inventoryApp', {
  state: () => ({
    // --- State Autentikasi & User ---
    isLoggedIn: false,
    userRole: null, // 'admin', 'head', 'karyawan'
    userName: null,
    userId: null,
    userEmail: null,
    userDivisi: null,
    token: localStorage.getItem('Authorization') || null, // Token awal dari localStorage
    loginError: null, // Untuk pesan error login

    // --- State Master Data ---
    brands: [],
    categories: [],
    itemTypes: [],
    locationUnits: [],
    floors: [],
    rooms: [],
    usersList: [],
    inventoryItems: [],
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
    // authHeader tidak perlu lagi di getter jika kita set global default header
    // Tapi tetap bisa dipertahankan sebagai fallback atau untuk request spesifik
    authHeader: (state) => ({
      headers: { Authorization: state.token ? `Bearer ${state.token}` : '' },
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
        localStorage.setItem('userRole', role); // Simpan role juga di localStorage
        localStorage.setItem('userName', name);
        localStorage.setItem('userId', id);
        localStorage.setItem('userEmail', email);
        localStorage.setItem('userDivisi', divisi);


        this.token = Authorization;
        this.isLoggedIn = true;
        this.userRole = role;
        this.userName = name;
        this.userId = id;
        this.userEmail = email;
        this.userDivisi = divisi;

        // SET AXIOS GLOBAL DEFAULT HEADER SETELAH LOGIN BERHASIL
        axios.defaults.headers.common['Authorization'] = `Bearer ${Authorization}`;

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
        // Gunakan authHeader karena token masih ada di state sebelum dihapus
        await axios.post(`${API_BASE_URL}/logout`, {}, this.authHeader);
      } catch (error) {
        console.error('Logout API call failed, but clearing local storage anyway:', error);
      } finally {
        localStorage.removeItem('Authorization');
        localStorage.removeItem('userRole');
        localStorage.removeItem('userName');
        localStorage.removeItem('userId');
        localStorage.removeItem('userEmail');
        localStorage.removeItem('userDivisi');

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

        // HAPUS AXIOS GLOBAL DEFAULT HEADER SETELAH LOGOUT
        delete axios.defaults.headers.common['Authorization'];

        router.push('/login');
      }
    },

    // Fungsi untuk inisialisasi auth saat aplikasi dimuat (dipanggil dari app.js)
    initializeAuth() {
      const token = localStorage.getItem('Authorization');
      const role = localStorage.getItem('userRole');
      const name = localStorage.getItem('userName');
      const id = localStorage.getItem('userId');
      const email = localStorage.getItem('userEmail');
      const divisi = localStorage.getItem('userDivisi');

      if (token && role) {
        this.isLoggedIn = true;
        this.token = token;
        this.userRole = role;
        this.userName = name;
        this.userId = id;
        this.userEmail = email;
        this.userDivisi = divisi;
        // SET AXIOS GLOBAL DEFAULT HEADER SAAT INISIALISASI
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        console.log('Auth initialized from localStorage with token and role.');
      } else {
        this.isLoggedIn = false;
        this.userRole = null;
        this.token = null;
        this.userName = null;
        this.userId = null;
        this.userEmail = null;
        this.userDivisi = null;
        // HAPUS AXIOS GLOBAL DEFAULT HEADER JIKA TIDAK ADA TOKEN
        delete axios.defaults.headers.common['Authorization'];
        console.log('No valid auth data in localStorage, clearing state.');
      }
    },

    // --- Aksi Master Data (Brands, Categories, ItemTypes, LocationUnits, Floors, Rooms, Users) ---
    async fetchBrands() {
      try {
        // Tidak perlu this.authHeader lagi jika axios.defaults.headers.common sudah diatur
        const response = await axios.get(`${API_BASE_URL}/brands`);
        this.brands = response.data;
      } catch (error) {
        console.error('Failed to fetch brands:', error);
        // Handle 401 by logging out
        if (error.response && error.response.status === 401) this.logout();
      }
    },
    async createBrand(brandData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/brands`, brandData);
            this.fetchBrands(); // Refresh list
            return response.data;
        } catch (error) {
            console.error('Failed to create brand:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async updateBrand(id, brandData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/brands/${id}`, brandData);
            this.fetchBrands(); // Refresh list
            return response.data;
        } catch (error) {
            console.error('Failed to update brand:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async deleteBrand(id) {
        try {
            await axios.delete(`${API_BASE_URL}/brands/${id}`);
            this.fetchBrands(); // Refresh list
        } catch (error) {
            console.error('Failed to delete brand:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },

    async fetchCategories() {
      try {
        const response = await axios.get(`${API_BASE_URL}/categories`);
        this.categories = response.data;
      } catch (error) {
        console.error('Failed to fetch categories:', error);
        if (error.response && error.response.status === 401) this.logout();
      }
    },
    async createCategory(categoryData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/categories`, categoryData);
            this.fetchCategories();
            return response.data;
        } catch (error) {
            console.error('Failed to create category:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async updateCategory(id, categoryData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/categories/${id}`, categoryData);
            this.fetchCategories();
            return response.data;
        } catch (error) {
            console.error('Failed to update category:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async deleteCategory(id) {
        try {
            await axios.delete(`${API_BASE_URL}/categories/${id}`);
            this.fetchCategories();
        } catch (error) {
            console.error('Failed to delete category:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },

    async fetchItemTypes() {
      try {
        const response = await axios.get(`${API_BASE_URL}/item-types`);
        this.itemTypes = response.data;
      } catch (error) {
        console.error('Failed to fetch item types:', error);
        if (error.response && error.response.status === 401) this.logout();
      }
    },
    async createItemType(itemTypeData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/item-types`, itemTypeData);
            this.fetchItemTypes();
            return response.data;
        } catch (error) {
            console.error('Failed to create item type:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async updateItemType(id, itemTypeData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/item-types/${id}`, itemTypeData);
            this.fetchItemTypes();
            return response.data;
        } catch (error) {
            console.error('Failed to update item type:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async deleteItemType(id) {
        try {
            await axios.delete(`${API_BASE_URL}/item-types/${id}`);
            this.fetchItemTypes();
        } catch (error) {
            console.error('Failed to delete item type:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },

    async fetchLocationUnits() {
      try {
        const response = await axios.get(`${API_BASE_URL}/units`);
        this.locationUnits = response.data;
      } catch (error) {
        console.error('Failed to fetch location units:', error);
        if (error.response && error.response.status === 401) this.logout();
      }
    },
    async createLocationUnit(unitData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/units`, unitData);
            this.fetchLocationUnits();
            return response.data;
        } catch (error) {
            console.error('Failed to create location unit:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async updateLocationUnit(id, unitData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/units/${id}`, unitData);
            this.fetchLocationUnits();
            return response.data;
        } catch (error) {
            console.error('Failed to update location unit:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async deleteLocationUnit(id) {
        try {
            await axios.delete(`${API_BASE_URL}/units/${id}`);
            this.fetchLocationUnits();
        } catch (error) {
            console.error('Failed to delete location unit:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },

    async fetchFloors(unitId = null) {
      try {
        const url = unitId ? `${API_BASE_URL}/units/${unitId}/floors` : `${API_BASE_URL}/floors`;
        const response = await axios.get(url);
        this.floors = response.data;
      } catch (error) {
        console.error('Failed to fetch floors:', error);
        if (error.response && error.response.status === 401) this.logout();
      }
    },
    async createFloor(floorData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/floors`, floorData);
            this.fetchFloors();
            return response.data;
        } catch (error) {
            console.error('Failed to create floor:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async updateFloor(id, floorData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/floors/${id}`, floorData);
            this.fetchFloors();
            return response.data;
        } catch (error) {
            console.error('Failed to update floor:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async deleteFloor(id) {
        try {
            await axios.delete(`${API_BASE_URL}/floors/${id}`);
            this.fetchFloors();
        } catch (error) {
            console.error('Failed to delete floor:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },

    async fetchRooms(floorId = null) {
      try {
        const url = floorId ? `${API_BASE_URL}/floors/${floorId}/rooms` : `${API_BASE_URL}/rooms`;
        const response = await axios.get(url);
        this.rooms = response.data;
      } catch (error) {
        console.error('Failed to fetch rooms:', error);
        if (error.response && error.response.status === 401) this.logout();
      }
    },
    async createRoom(roomData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/rooms`, roomData);
            this.fetchRooms();
            return response.data;
        } catch (error) {
            console.error('Failed to create room:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async updateRoom(id, roomData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/rooms/${id}`, roomData);
            this.fetchRooms();
            return response.data;
        } catch (error) {
            console.error('Failed to update room:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async deleteRoom(id) {
        try {
            await axios.delete(`${API_BASE_URL}/rooms/${id}`);
            this.fetchRooms();
        } catch (error) {
            console.error('Failed to delete room:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },

    async fetchUsersList() {
      try {
        const response = await axios.get(`${API_BASE_URL}/users`);
        this.usersList = response.data;
      } catch (error) {
        console.error('Failed to fetch users list:', error);
        if (error.response && error.response.status === 401) this.logout();
      }
    },
    async createUser(userData) {
        try {
            const response = await axios.post(`${API_BASE_URL}/register`, userData);
            this.fetchUsersList();
            return response.data;
        } catch (error) {
            console.error('Failed to create user:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async updateUser(id, userData) {
        try {
            const response = await axios.put(`${API_BASE_URL}/users/${id}`, userData);
            this.fetchUsersList();
            return response.data;
        } catch (error) {
            console.error('Failed to update user:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async deleteUser(id) {
        try {
            await axios.delete(`${API_BASE_URL}/users/${id}`);
            this.fetchUsersList();
        } catch (error) {
            console.error('Failed to delete user:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },

    // --- Aksi Inventory Items (Master Barang) ---
    async fetchInventoryItems(filters = {}) {
        this.inventoryItemLoading = true;
        this.inventoryItemError = null;
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const response = await axios.get(`${API_BASE_URL}/inventory-items?${queryParams}`);
            this.inventoryItems = response.data;
        } catch (error) {
            this.inventoryItemError = error.response?.data?.message || error.message || 'Failed to fetch inventory items.';
            console.error('Failed to fetch inventory items:', error.response?.data || error.message);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        } finally {
            this.inventoryItemLoading = false;
        }
    },
    async createInventoryItem(itemData) {
        this.inventoryItemLoading = true;
        this.inventoryItemError = null;
        try {
            const formData = new FormData();
            for (const key in itemData) {
                if (itemData[key] !== null) {
                    formData.append(key, itemData[key]);
                }
            }
            
            const response = await axios.post(`${API_BASE_URL}/inventory-items`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            this.fetchInventoryItems();
            return response.data;
        } catch (error) {
            this.inventoryItemError = error.response?.data?.message || error.message || 'Failed to create inventory item.';
            console.error('Failed to create inventory item:', error.response?.data || error.message);
            if (error.response && error.response.status === 401) this.logout();
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
            formData.append('_method', 'PUT');

            const response = await axios.post(`${API_BASE_URL}/inventory-items/${id}`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            this.fetchInventoryItems();
            return response.data;
        } catch (error) {
            this.inventoryItemError = error.response?.data?.message || error.message || 'Failed to update inventory item.';
            console.error('Failed to update inventory item:', error.response?.data || error.message);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        } finally {
            this.inventoryItemLoading = false;
        }
    },
    async deleteInventoryItem(id) {
        this.inventoryItemLoading = true;
        this.inventoryItemError = null;
        try {
            await axios.delete(`${API_BASE_URL}/inventory-items/${id}`);
            this.fetchInventoryItems();
        } catch (error) {
            this.inventoryItemError = error.response?.data?.message || error.message || 'Failed to delete inventory item.';
            console.error('Failed to delete inventory item:', error.response?.data || error.message);
            if (error.response && error.response.status === 401) this.logout();
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
        const response = await axios.get(`${API_BASE_URL}/inventories?${queryParams}`);
        this.inventories = response.data;
      } catch (error) {
        this.inventoryError = error.response?.data?.message || error.message || 'Failed to fetch inventories.';
        console.error('Failed to fetch inventories:', error);
        if (error.response && error.response.status === 401) this.logout();
      } finally {
        this.inventoryLoading = false;
      }
    },
    async fetchInventoryDetail(id) {
      try {
        const response = await axios.get(`${API_BASE_URL}/inventories/${id}`);
        this.selectedInventoryDetail = response.data;
        return response.data;
      } catch (error) {
        console.error('Failed to fetch inventory detail:', error);
        if (error.response && error.response.status === 401) this.logout();
        throw error;
      }
    },
    async createInventory(inventoryData) {
        this.inventoryLoading = true;
        this.inventoryError = null;
        try {
            const response = await axios.post(`${API_BASE_URL}/inventories`, inventoryData);
            this.fetchInventories();
            return response.data;
        } catch (error) {
            this.inventoryError = error.response?.data?.message || error.message || 'Failed to create inventory.';
            console.error('Failed to create inventory:', error.response?.data || error.message);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        } finally {
            this.inventoryLoading = false;
        }
    },
    async updateInventory(id, inventoryData) {
        this.inventoryLoading = true;
        this.inventoryError = null;
        try {
            const response = await axios.put(`${API_BASE_URL}/inventories/${id}`, inventoryData);
            this.fetchInventories();
            return response.data;
        } catch (error) {
            this.inventoryError = error.response?.data?.message || error.message || 'Failed to update inventory.';
            console.error('Failed to update inventory:', error.response?.data || error.message);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        } finally {
            this.inventoryLoading = false;
        }
    },
    async deleteInventory(id) {
        this.inventoryLoading = true;
        this.inventoryError = null;
        try {
            await axios.delete(`${API_BASE_URL}/inventories/${id}`);
            this.fetchInventories();
        } catch (error) {
            this.inventoryError = error.response?.data?.message || error.message || 'Failed to delete inventory.';
            console.error('Failed to delete inventory:', error.response?.data || error.message);
            if (error.response && error.response.status === 401) this.logout();
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
        const response = await axios.get(`${url}?${queryParams}`);
        this.maintenanceHistory = response.data;
      } catch (error) {
        console.error('Failed to fetch maintenance history:', error);
        if (error.response && error.response.status === 401) this.logout();
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
                'Content-Type': 'multipart/form-data',
            },
        });
        this.fetchInventoryDetail(inventoryId);
        this.fetchMaintenanceHistory(inventoryId);
        return response.data;
      } catch (error) {
        console.error('Failed to add maintenance record:', error);
        if (error.response && error.response.status === 401) this.logout();
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
            formData.append('_method', 'PUT');

            const response = await axios.post(`${API_BASE_URL}/maintenance/${id}`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            return response.data;
        } catch (error) {
            console.error('Failed to update maintenance record:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },
    async deleteMaintenanceRecord(id) {
        try {
            await axios.delete(`${API_BASE_URL}/maintenance/${id}`);
            // Mungkin perlu refresh data terkait
        } catch (error) {
            console.error('Failed to delete maintenance record:', error);
            if (error.response && error.response.status === 401) this.logout();
            throw error;
        }
    },

    // --- Aksi Dashboard & Laporan ---
    async fetchDashboardStats() {
      try {
        const response = await axios.get(`${API_BASE_URL}/dashboard/stats`);
        const data = response.data;
        this.totalAssetValue = data.totalAssetValue;
        this.totalDepreciation = data.totalDepreciation;
        this.inventoryStatusStats = data.inventoryStatusStats;
        this.totalMaintenanceDone = data.totalMaintenanceDone;
        this.totalMaintenancePlanning = data.totalMaintenancePlanning;
      } catch (error) {
        console.error('Failed to fetch dashboard stats:', error);
        if (error.response && error.response.status === 401) this.logout();
      }
    },

    // --- Aksi QR Code ---
    async fetchInventoryByQrCode(inventoryNumber) {
      try {
        const response = await axios.get(`${API_BASE_URL}/inventories/qr/${inventoryNumber}`);
        this.selectedInventoryDetail = response.data;
        return response.data;
      } catch (error) {
        console.error('Failed to fetch inventory by QR code:', error);
        if (error.response && error.response.status === 401) this.logout();
        throw error;
      }
    },

    // --- Aksi Global/Helper ---
    // initializeAuth() sudah dipanggil di app.js setup()
    // Tidak perlu lagi fetchCurrentUser terpisah, karena initializeAuth sudah melakukan itu
    // dan data user sudah disimpan di localStorage saat login
    // async fetchCurrentUser() {
    //     try {
    //         const response = await axios.get(`${API_BASE_URL}/user`);
    //         const user = response.data;
    //         this.userRole = user.role;
    //         this.userName = user.name;
    //         this.userId = user.id;
    //         this.userEmail = user.email;
    //         this.userDivisi = user.divisi;
    //     } catch (error) {
    //         console.error('Failed to fetch current user:', error);
    //         this.logout();
    //     }
    // }
  },
});
