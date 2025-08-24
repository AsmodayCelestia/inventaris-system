// resources/js/router/index.js

import { createRouter, createWebHistory } from 'vue-router';
import { useCounterStore } from '../stores/counter';

// Import komponen-komponen halaman (views)
import Layout from '../components/Layout.vue';
import Login from '../components/Login.vue';
import Dashboard from '../components/Dashboard.vue';
import InventoryList from '../components/InventoryList.vue'; // Ini sekarang wrapper Layout + <router-view>
import InventoryUnitList from '../components/InventoryUnitList.vue'; // 🆕 tab: unit inventaris
import InventoryMasterList from '../components//InventoryMasterList.vue'; // 🆕 tab: master barang
import InventoryMasterEdit from '../components/InventoryItemEdit.vue'; // 🆕 tab: master barang
import InventoryItemForm from '../components/InventoryItemForm.vue';
import InventoryDetail from '../components/InventoryDetail.vue';
import InventoryCreate from '../components/InventoryCreate.vue';
import InventoryEdit from '../components/InventoryEdit.vue';

// Import Master Data Components
import BrandList from '../components/master-data/BrandList.vue';
import BrandForm from '../components/master-data/BrandForm.vue';
import CategoryList from '../components/master-data/CategoryList.vue';
import CategoryForm from '../components/master-data/CategoryForm.vue';
import ItemTypeList from '../components/master-data/ItemTypeList.vue';
import ItemTypeForm from '../components/master-data/ItemTypeForm.vue';
import FloorList from '../components/master-data/FloorList.vue';
import FloorForm from '../components/master-data/FloorForm.vue';
import LocationUnitList from '../components/master-data/LocationUnitList.vue';
import LocationUnitForm from '../components/master-data/LocationUnitForm.vue';
import RoomList from '../components/master-data/RoomList.vue';
import RoomForm from '../components/master-data/RoomForm.vue';
import UserList from '../components/master-data/UserList.vue';
import UserForm from '../components/master-data/UserForm.vue';
import QrPreview from '../components/QrPreview.vue';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { requiresAuth: false }
    },
        {
        path: '/qrpreview',
        name: 'qrpreview',
        component: QrPreview,
        meta: { requiresAuth: false }
    },
    {
        path: '/',
        component: Layout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'dashboard',
                name: 'Dashboard',
                component: Dashboard,
                meta: { requiresAuth: true }
            },

            /* ---------- ACTIVITY LOG ---------- */
            {
            path: 'activity-log',
            name: 'ActivityLog',
            component: () => import('../components/ActivityLog.vue'),
            meta: { requiresAuth: true, roles: ['admin'] }
            },

            // INVENTARIS
            {
                path: 'inventories',
                component: InventoryList,
                children: [
                    {
                        path: '',
                        name: 'InventoryUnitList',
                        component: InventoryUnitList,
                        meta: { requiresAuth: true }
                    },
                    {
                        path: 'master-barang',
                        name: 'InventoryMasterList',
                        component: InventoryMasterList,
                        meta: { requiresAuth: true, roles: ['admin', 'head'] }
                    },
                                        {
                        path: 'master-barang/create',
                        name: 'InventoryMasterCreate',
                        component: InventoryItemForm,
                        meta: { requiresAuth: true, roles: ['admin'] }
                    },
                    // di dalam array children yang sama dengan inventory routes
                    {
                        path: 'master-data/barang/:id/edit',
                        name: 'inventory-items.edit',
                        component: InventoryMasterEdit,
                        props: true,
                        meta: { requiresAuth: true, roles: ['admin', 'head'] }
                    },
                    {
                        path: 'create',
                        name: 'inventories.create',
                        component: InventoryCreate,
                        meta: { requiresAuth: true, roles: ['admin', 'head'] }
                    },
                    {
                        path: 'edit/:id',
                        name: 'inventories.edit',
                        component: InventoryEdit,
                        props: true,
                        meta: { requiresAuth: true, roles: ['admin', 'head', 'karyawan'] }
                    },
                    {
                        path: ':id',
                        name: 'inventory-detail',
                        component: InventoryDetail,
                        props: true,
                        meta: { requiresAuth: true } // semua role login boleh akses
                    },
                    {
                        path: '/inventories/scan/:id',
                        name: 'inventory-detail-public',
                        component: InventoryDetail,   // reuse komponen yang sama
                        props: true,
                        meta: { requiresAuth: false }  // tidak paksa login
                    }
                ]
            },

{
    path: '/manage-qrcodes',
    name: 'ManageQRCode',
    component: () => import('../components/ManageQRCode.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
},
{
    path: '/scan-qrcode',
    name: 'QRCodeScan',
    component: () => import('../components/QRCodeScan.vue'),
    meta: { requiresAuth: false }
},
            // MAINTENANCE
            {
                path: 'maintenance/create',
                name: 'MaintenanceCreate',
                component: () => import('../components/maintenance/MaintenanceCreate.vue'),
                meta: { requiresAuth: true }
            },
            {
                path: 'maintenance/done',
                name: 'MaintenanceDone',
                component: () => import('../components/maintenance/MaintenanceDone.vue'),
                meta: { requiresAuth: true }
            },
            {
                path: 'maintenance-done/:inventoryId',
                name: 'MaintenanceDoneByInventory',
                component: () => import('../components/maintenance/MaintenanceDone.vue'),
                props: true
            },
            {
                path: 'maintenance/list',
                name: 'MaintenanceHistory',
                component: () => import('../components/maintenance/MaintenanceList.vue'),
                meta: { requiresAuth: true }
            },
            {
                path: 'maintenance/:id',
                name: 'MaintenanceDetail',
                component: () => import('../components/maintenance/MaintenanceDetail.vue'),
                meta: { requiresAuth: true }
            },
            {
                path: 'maintenance/edit/:id',
                name: 'MaintenanceEdit',
                component: () => import('../components/maintenance/EditMaintenance.vue'),
                props: true,
                meta: { requiresAuth: true }
            },
            // di bawah route 'maintenance/edit/:id' (atau sesukamu)
            {
                path: 'maintenance/needed',
                name: 'MaintenanceNeeded',
                component: () => import('../components/maintenance/MaintenanceNeeded.vue'),
                meta: { requiresAuth: true }   // semua role boleh
            },
            {
                path: 'master-data/brands',
                name: 'BrandList',
                component: BrandList,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/brands/create',
                name: 'BrandCreate',
                component: BrandForm,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/brands/edit/:id',
                name: 'BrandEdit',
                component: BrandForm,
                props: true,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/categories',
                name: 'CategoryList',
                component: CategoryList,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/categories/create',
                name: 'CategoryCreate',
                component: CategoryForm,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/categories/edit/:id',
                name: 'CategoryEdit',
                component: CategoryForm,
                props: true,
                meta: { requiresAuth: true, roles: ['admin'] }
            },

            {
                path: 'master-data/item-types',
                name: 'ItemTypeList',
                component: ItemTypeList,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/item-types/create',
                name: 'ItemTypeCreate',
                component: ItemTypeForm,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/item-types/edit/:id',
                name: 'ItemTypeEdit',
                component: ItemTypeForm,
                props: true,
                meta: { requiresAuth: true, roles: ['admin'] }
            },

            {
                path: 'master-data/floors',
                name: 'FloorList',
                component: FloorList,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/floors/create',
                name: 'FloorCreate',
                component: FloorForm,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/floors/edit/:id',
                name: 'FloorEdit',
                component: FloorForm,
                props: true,
                meta: { requiresAuth: true, roles: ['admin'] }
            },

            {
                path: 'master-data/units',
                name: 'LocationUnitList',
                component: LocationUnitList,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/units/create',
                name: 'LocationUnitCreate',
                component: LocationUnitForm,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/units/edit/:id',
                name: 'LocationUnitEdit',
                component: LocationUnitForm,
                props: true,
                meta: { requiresAuth: true, roles: ['admin'] }
            },

            {
                path: 'master-data/rooms',
                name: 'RoomList',
                component: RoomList,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/rooms/create',
                name: 'RoomCreate',
                component: RoomForm,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/rooms/edit/:id',
                name: 'RoomEdit',
                component: RoomForm,
                props: true,
                meta: { requiresAuth: true, roles: ['admin'] }
            },

            {
                path: 'master-data/users',
                name: 'UserList',
                component: UserList,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/users/create',
                name: 'UserCreate',
                component: UserForm,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
                path: 'master-data/users/edit/:id',
                name: 'UserEdit',
                component: UserForm,
                props: true,
                meta: { requiresAuth: true, roles: ['admin'] }
            },
            /* ---------- DIVISION ---------- */
            {
            path: 'master-data/divisions',
            name: 'DivisionList',
            component: () => import('../components/master-data/DivisionList.vue'),
            meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
            path: 'master-data/divisions/create',
            name: 'DivisionCreate',
            component: () => import('../components/master-data/DivisionForm.vue'),
            meta: { requiresAuth: true, roles: ['admin'] }
            },
            {
            path: 'master-data/divisions/edit/:id',
            name: 'DivisionEdit',
            component: () => import('../components/master-data/DivisionForm.vue'),
            props: true,
            meta: { requiresAuth: true, roles: ['admin'] }
            },
        ]
    },
    {
        path: '/:catchAll(.*)',
        redirect: '/login',
    }
];

const masterDataRoutes = [

]

const router = createRouter({
    history: createWebHistory(),
    routes: [...routes, ...masterDataRoutes],
});


router.beforeEach(async (to, from, next) => {
  const counterStore = useCounterStore();

  // ➕ FORCE reload state jika role masih null tapi ada token
  if (!counterStore.authReady && localStorage.getItem('Authorization')) {
    try {
      await counterStore.initializeAuth();
    } catch (e) {
      console.error('Gagal inisialisasi auth:', e);
      // Jangan force logout, biarkan user di halaman login
    }
  }

  const { requiresAuth, roles: requiredRoles } = to.meta;
  const isLoggedIn = counterStore.isLoggedIn;

  if (requiresAuth && !isLoggedIn) return next({ name: 'Login' });
  if (to.name === 'Login' && isLoggedIn) return next({ name: 'Dashboard' });

  if (requiredRoles) {
    const roleMap = {
      admin: counterStore.isAdmin,
      head: counterStore.isHead,
      karyawan: counterStore.isKaryawan,
    };

    const hasAccess = requiredRoles.some(role => {
      if (role === 'karyawan') {
        return roleMap.karyawan && counterStore.userDivisi === 'Keuangan';
      }
      return roleMap[role];
    });

    if (!hasAccess) {
      console.warn(
        `Akses ditolak: User ${counterStore.userName} (role: ${counterStore.userRole}, divisi: ${counterStore.userDivisi}) mencoba mengakses '${to.fullPath}'`
      );
      return next({ name: 'Dashboard' });
    }
  }

  return next();
});

export default router;

