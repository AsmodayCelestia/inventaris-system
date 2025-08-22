// resources/js/stores/counter.js


import { defineStore } from 'pinia';
import axios from 'axios';
import router from '../router';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

export const useCounterStore = defineStore('inventoryApp', {
  state: () => ({
    // --- State Autentikasi & User ---
    API_BASE_URL: API_BASE_URL,
    isLoggedIn: false,
    userRole: null, // 'admin', 'head', 'karyawan'
    userName: null,
    userId: null,
    userEmail: null,
    userDivisi: null,
    token: localStorage.getItem('Authorization') || null,
    loginError: null,

    // --- Flag Tugas ---
    isPjMaintenance: false,
    isRoomSupervisor: false,
    assignedRooms: [], // e.g. ['IGD01', 'RANAP03']

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

inventoryTable: {
  items: [],
  totalRows: 0,
  loading: false,
  params: {
    page: 1,
    per_page: 10,
    search: '',
    room_id: null,
    unit_id: null,
    floor_id: null,
    status: null,
    pj_id: null,
    start_date: null,
    end_date: null,
    sort_by: 'id',
    sort_dir: 'asc'
  }
},
    // --- State Inventaris ---
    inventories: [],
    selectedInventoryDetail: null,
    inventoryStatusStats: {},
    inventoryLoading: false,
    inventoryError: null,

    // --- State Maintenance ---
    maintenanceHistory: [],
    assignedMaintenanceInventoryIds: [],

    // --- Dashboard & Laporan ---
    totalAssetValue: 0,
    totalDepreciation: 0,
    totalMaintenanceDone: 0,
    totalMaintenancePlanning: 0,

    // --- Filter ---
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
    authReady: false,
    grandTotal: null, 
  }),

  getters: {
    isAdmin: (state) => state.userRole === 'admin',
    isHead: (state) => state.userRole === 'head',
    isKaryawan: (state) => state.userRole === 'karyawan',
    isKeuangan: (state) => state.userDivisi === 'Keuangan',
//     isSupervisorOfInventory: (state) => (inventory) =>
//   inventory?.room?.location_person_in_charge?.id === state.userId,
isSupervisorOfInventory: (state) => (inventory) =>
  state.userId === inventory?.room?.pj_lokasi_id,
// tambahkan di getters juga (opsional, kalau mau pakai di banyak tempat)
// boleh baca / create harga
canSeePrice: (state) =>
  state.userRole === 'admin' ||
  state.userDivisi === 'Keuangan' ||
  (state.userRole === 'head' && state.userDivisi === 'Umum'),

// boleh update harga
canUpdatePrice: (state) =>
  state.userRole === 'admin' || state.userDivisi === 'Keuangan',
    authHeader: (state) => ({
      headers: {
        Authorization: state.token ? `Bearer ${state.token}` : '',
      },
    }),

    // ✅ Hak akses maintenance (lihat menu & data tugas sendiri)
    canAccessMaintenance: (state) => {
        // Admin atau Head otomatis bisa
        if (state.userRole === 'admin' || state.userRole === 'head') return true;

        // Karyawan yang jadi penanggung jawab maintenance atau supervisor ruang
        return state.isPjMaintenance || state.isRoomSupervisor;
    },

    // ✅ Hak edit item berdasarkan penugasan ruang
    canEditRoom: (state) => (roomCode) =>
      state.isAdmin || state.isHead || state.assignedRooms.includes(roomCode),

    isAssignedToRoom: (state) => (roomId) =>
      state.assignedRooms.some((room) => room.id === roomId),
  },

  // Tambahkan di getters
    isSupervisorThisRoom: (state) => (roomId) =>
    state.userId === state.rooms.find(r => r.id === roomId)?.supervisor_id,

    isPjThisItem: (state) => (pjId) =>
    state.userId === pjId,
    

  actions: {
    // --- Aksi Autentikasi ---
async login(payload) {
  try {
    const res = await axios.post(`${API_BASE_URL}/login`, {
      email: payload.email,
      password: payload.password,
    });

    // Ambil data dari response
    const token = res.data.Authorization
    const userId = res.data.id;
    const userName = res.data.name;
    const userEmail = res.data.email;
    const userDivisi = res.data.divisi;
    const userRole = res.data.role;
// Tambahkan langsung setelah baris ini:
const isPjMaintenance = res.data.isPjMaintenance || false;

// Tambahkan:
const isRoomSupervisor = res.data.isRoomSupervisor || false;
const assignedRooms = (res.data.assignedRooms || []).filter(Boolean);

    

    // Step 2: Simpan ke state dan localStorage
    this.token = token;
    this.userId = userId;
    this.userName = userName;
    this.userEmail = userEmail;
    this.userDivisi = userDivisi;
    this.userRole = userRole;
    this.isLoggedIn = true;
    this.isPjMaintenance = isPjMaintenance;
    this.isRoomSupervisor = isRoomSupervisor;
this.assignedRooms = assignedRooms;


    localStorage.setItem('Authorization', token);
    localStorage.setItem('userId', userId);
    localStorage.setItem('userName', userName);
    localStorage.setItem('userEmail', userEmail);
    localStorage.setItem('userDivisi', userDivisi);
    localStorage.setItem('userRole', userRole);
    localStorage.setItem('isPjMaintenance', isPjMaintenance);
    // Simpan ke localStorage
localStorage.setItem('isRoomSupervisor', isRoomSupervisor);
localStorage.setItem('assignedRooms', JSON.stringify(assignedRooms));
    

    // Step 3: Atur Authorization header global
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    // Kalau backend belum kasih info supervisor & assigned_rooms, bisa dilewatin
    // this.isRoomSupervisor = false;
    // this.assignedRooms = [];
    // localStorage.setItem('isRoomSupervisor', false);
    // localStorage.setItem('assignedRooms', JSON.stringify([]));

  } catch (error) {
    console.error('Login gagal:', error);
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
        localStorage.removeItem('assignedRooms');
        localStorage.removeItem('isPjMaintenance');
        localStorage.removeItem('isRoomSupervisor');


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
        this.isPjMaintenance = false;
        this.isRoomSupervisor = false;
        this.assignedRooms = [];
        // HAPUS AXIOS GLOBAL DEFAULT HEADER SETELAH LOGOUT
        delete axios.defaults.headers.common['Authorization'];

        router.push('/login');
      }
    },

    // Fungsi untuk inisialisasi auth saat aplikasi dimuat (dipanggil dari app.js)
// stores/counter.js
async initializeAuth() {
  const token = localStorage.getItem('Authorization');
  
  if (!token) {
    this.resetState();
    this.authReady = true;
    return;
  }

  this.token = token;
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

  try {
    const { data } = await axios.get(`${API_BASE_URL}/user`);
    
    // Update state
    this.isLoggedIn = true;
    this.userRole = data.role;
    this.userName = data.name;
    this.userId = data.id;
    this.userEmail = data.email;
    this.userDivisi = data.divisi;
    this.isPjMaintenance = data.isPjMaintenance || false;
    // this.isRoomSupervisor = data.isRoomSupervisor || false;
    // this.assignedRooms = data.assignedRooms || [];
    // Tambahkan:
    this.isRoomSupervisor = data.isRoomSupervisor || false;
    this.assignedRooms = (data.assignedRooms || []).filter(Boolean);
    
  } catch (e) {
    console.error('Token invalid/expired - SKIP logout');
    // ❌ HAPUS BARIS INI:
    // localStorage.removeItem('Authorization');
    
    // Cuma reset state, biarkan token di storage
    this.resetState();
  } finally {
    this.authReady = true;
  }
},
// Tambahin method reset state
resetState() {
  this.token = null;
  this.isLoggedIn = false;
  this.userRole = null;
  this.userName = null;
  this.userId = null;
  this.userEmail = null;
  this.userDivisi = null;
  this.isPjMaintenance = false;
  this.isRoomSupervisor = false;
  this.assignedRooms = [];
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
    console.log('Creating floor with:', floorData); // <—— Tambahkan ini
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

    async fetchRooms() {
        try {
            const response = await axios.get(`${API_BASE_URL}/rooms`);
            
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

    async fetchDivisions() {
    try {
        const { data } = await axios.get(`${API_BASE_URL}/divisions`);
        return data; // langsung return array
    } catch (error) {
        console.error('Failed to fetch divisions:', error);
        if (error.response?.status === 401) this.logout();
        throw error;
    }
    },

    async createDivision(payload) {
    try {
        const { data } = await axios.post(`${API_BASE_URL}/divisions`, payload);
        return data;
    } catch (error) {
        console.error('Failed to create division:', error);
        if (error.response?.status === 401) this.logout();
        throw error;
    }
    },

    async updateDivision(id, payload) {
    try {
        const { data } = await axios.put(`${API_BASE_URL}/divisions/${id}`, payload);
        return data;
    } catch (error) {
        console.error('Failed to update division:', error);
        if (error.response?.status === 401) this.logout();
        throw error;
    }
    },

    async deleteDivision(id) {
    try {
        await axios.delete(`${API_BASE_URL}/divisions/${id}`);
    } catch (error) {
        console.error('Failed to delete division:', error);
        if (error.response?.status === 401) this.logout();
        throw error;
    }
    },


    async fetchUsersList() {
      try {
        const response = await axios.get(`${API_BASE_URL}/users`);
        console.log(response);
        
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
async setUserSupervisorStatus(id, status = true) {
    try {
        const response = await axios.put(`${API_BASE_URL}/users/${id}`, {
            is_room_supervisor: status
        });
        this.fetchUsersList();
        return response.data;
    } catch (error) {
        console.error('Failed to set supervisor status:', error);
        if (error.response && error.response.status === 401) this.logout();
        throw error;
    }
},
async setUserMaintenanceStatus(id, status = true) {
    try {
        const response = await axios.put(`${API_BASE_URL}/users/${id}`, {
            is_pj_maintenance: status
        });
        this.fetchUsersList();
        return response.data;
    } catch (error) {
        console.error('Failed to set maintenance status:', error);
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
    async fetchInventoryItemById(id) {
        try {
            const response = await axios.get(`${API_BASE_URL}/inventory-items/${id}`);
            return response.data;
        } catch (error) {
            console.error('Failed to fetch inventory item by ID:', error);
            throw error;
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
    // di actions store
resetInventoryTable() {
  this.inventoryTable.params = {
    page: 1,
    per_page: 10,
    search: '',
    room_id: null,
    unit_id: null,
    floor_id: null,
    status: null,
    pj_id: null,
    start_date: null,
    end_date: null,
    sort_by: 'id',
    sort_dir: 'asc'
  };
},
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
// stores/counter.js
async fetchInventoryTable(params = {}) {
  this.inventoryTable.loading = true;
  this.inventoryTable.params = { ...this.inventoryTable.params, ...params };

  // --- mapping ke format DataTables ---
  const dt = {
    draw:      this.inventoryTable.params.page,     // bebas, backend cuma echo
    start:     (this.inventoryTable.params.page - 1) * this.inventoryTable.params.per_page,
    length:    this.inventoryTable.params.per_page,
    search:    { value: this.inventoryTable.params.search || '' },
    status:    this.inventoryTable.params.status,
    room_id:   this.inventoryTable.params.room_id,
    unit_id:   this.inventoryTable.params.unit_id,
    floor_id:  this.inventoryTable.params.floor_id,
    pj_id:     this.inventoryTable.params.pj_id,
    start_date:this.inventoryTable.params.start_date,
    end_date:  this.inventoryTable.params.end_date,
    order:     [{ column: 0, dir: this.inventoryTable.params.sort_dir || 'asc' }]
  };

  try {
    const { data } = await axios.get(`${API_BASE_URL}/inventories/table`, { params: dt });
    this.inventoryTable.items    = data.data;           // array records
    this.inventoryTable.totalRows = data.recordsFiltered;
    this.grandTotal = data.grand_total ?? { purchase: 0, depreciation: 0 };
  } catch (error) {
    this.inventoryError = error.response?.data?.message || error.message;
  } finally {
    this.inventoryTable.loading = false;
  }
},
    async fetchInventoryDetail(id) {
        this.inventoryLoading = true;
        try {
            const { data } = await axios.get(`${API_BASE_URL}/inventories/${id}`);
            this.selectedInventoryDetail = data.data;

            // simpan juga flag ke state (opsional)
            this.selectedInventoryDetail.meta = data.meta;
        } catch (e) {
            console.error(e);
        } finally {
            this.inventoryLoading = false;
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

    async fetchAssignedRooms() {
        try {
            const response = await axios.get(`${API_BASE_URL}/rooms`);
            const allRooms = response.data;
            const userId = this.userId;

            this.assignedRooms = allRooms.filter(room => room.pj_lokasi_id === Number(userId));
        } catch (error) {
            console.error('Gagal ambil ruangan yang jadi tanggung jawab user:', error);
        }
    },

    // --- Aksi Maintenance ---
    async fetchMaintenanceHistory(inventoryId = null, filters = {}) {
        try {
            let url = `${API_BASE_URL}/maintenance/active`; // ✅ ganti ke /active
            if (inventoryId) {
            url = `${API_BASE_URL}/inventories/${inventoryId}/maintenance`;
            }

        const queryParams = new URLSearchParams(filters).toString();
        const response = await axios.get(`${url}?${queryParams}`)
        this.maintenanceHistory = response.data;
      } catch (error) {
        console.error('Failed to fetch maintenance history:', error);
        if (error.response && error.response.status === 401) this.logout();
      }
    },

async fetchMaintenanceDetail(id) {
    try {
        const { data } = await axios.get(
            `${API_BASE_URL}/maintenance/${id}?with=inventory`,
            { headers: { Authorization: localStorage.getItem('Authorization') } }
        );

        // ── konversi ke ISO date ──
        if (data.inspection_date) {
            const [d, m, y] = data.inspection_date.split('/');
            data.inspection_date = `${y}-${m}-${d}`;
        }

        return data;
    } catch (error) {
        console.error('Failed to fetch maintenance detail:', error);
        if (error.response?.status === 401) this.logout();
        throw error;
    }
},

    async addMaintenanceRecord(inventoryId, maintenanceData) {
        try {
            const formData = new FormData();
            for (const key in maintenanceData) {
            const value = maintenanceData[key];

            // Tetap kirim 'pj_id', walau nilainya 0 atau kosong string
            if (
                value !== null &&
                (value !== '' || ['issue_found', 'notes', 'pj_id'].includes(key))
            ) {
                formData.append(key, value);
            }
            }

            console.log('Kirim ke:', `${API_BASE_URL}/inventories/${inventoryId}/maintenance`);
            console.log('Isi FormData:', Object.fromEntries(formData.entries()));

            const response = await axios.post(`${API_BASE_URL}/inventories/${inventoryId}/maintenance`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                Authorization: `Bearer ${this.token}`, // Tambahin token kalau endpoint protected
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

async scheduleInventory(inventoryId, scheduleData) {
  try {
    const response = await axios.put(`${API_BASE_URL}/inventories/${inventoryId}/schedule`, scheduleData, {
      headers: {
        Authorization: `Bearer ${this.token}`,
      },
    });
    return response.data;
  } catch (error) {
    console.error('Failed to schedule inventory:', error);
    throw error;
  }
},

async updateMaintenanceRecord(id, formData) {
  formData.append('_method', 'PUT');   // penting
  return axios.post(
    `${API_BASE_URL}/maintenance/${id}`,
    formData,
    { headers: { Authorization: localStorage.getItem('Authorization') } }
  );
},

async fetchDoneMaintenanceByInventory(inventoryId) {
  try {
    const { data } = await axios.get(
      `${API_BASE_URL}/inventories/${inventoryId}/maintenance-done`,
      this.authHeader
    );
    return data; // array maintenance dengan status 'done'
  } catch (err) {
    console.error(err);
    throw err;
  }
},

// --- Barang yang butuh maintenance (belum di-assign) ---
async fetchMaintenanceNeeded() {
  try {
    const { data } = await axios.get(`${API_BASE_URL}/maintenance/need`, this.authHeader);
    return data; // pastikan backend return array
  } catch (error) {
    console.error('Gagal ambil daftar barang butuh maintenance:', error);
    if (error.response?.status === 401) this.logout();
    throw error;
  }
},

// --- Assign maintenance ke user yang login ---
async assignMaintenance(id, payload) {
  try {
    const response = await axios.patch(
      `${API_BASE_URL}/maintenance/${id}/assign`,
      payload,
      this.authHeader
    );
    return response.data;
  } catch (error) {
    console.error('Gagal assign maintenance:', error);
    if (error.response?.status === 401) this.logout();
    throw error;
  }
},

async updateMaintenanceRecordJson(id, payload) {
  return axios.put(
    `${API_BASE_URL}/maintenance/${id}`,
    payload,
    { headers: { Authorization: localStorage.getItem('Authorization') } }
  );
},
// resources/js/stores/counter.js
async fetchDoneMaintenance() {
  try {
    const { data } = await axios.get(`${API_BASE_URL}/maintenance/done`, this.authHeader);
    this.maintenanceHistory = data; // sekarang pasti berasal dari /done
  } catch (e) {
    console.error('Gagal ambil maintenance selesai:', e);
    if (e.response?.status === 401) this.logout();
    this.maintenanceHistory = []; // fallback
  }
},



// async updateMaintenanceRecord(id, formData) {
//   try {
//     const response = await axios.post(
//       `${API_BASE_URL}/maintenance/${id}`,
//       formData,
//       { headers: { 'Content-Type': 'multipart/form-data' } }
//     );
//     return response.data;
//   } catch (error) {
//     console.error('Failed to update maintenance record:', error);
//     if (error.response?.status === 401) this.logout();
//     throw error;
//   }
// },



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
