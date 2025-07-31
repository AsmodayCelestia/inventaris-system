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

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
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

            // INVENTARIS
            {
                path: 'inventories',
                component: InventoryList,
                children: [
                    {
                        path: '',
                        name: 'InventoryUnitList',
                        component: InventoryUnitList,
                        meta: { requiresAuth: true, roles: ['admin', 'head', 'karyawan'] }
                    },
                    {
                        path: 'master-barang',
                        name: 'InventoryMasterList',
                        component: InventoryMasterList,
                        meta: { requiresAuth: true, roles: ['admin'] }
                    },
                    {
                        path: 'create',
                        name: 'inventories.create',
                        component: InventoryCreate,
                        meta: { requiresAuth: true, adminOrHead: true }
                    },
                    {
                        path: 'edit/:id',
                        name: 'inventories.edit',
                        component: InventoryEdit,
                        props: true,
                        meta: { requiresAuth: true, adminOrHead: true }
                    },
                    {
                        path: ':id',
                        name: 'inventory-detail',
                        component: InventoryDetail,
                        props: true
                    }
                ]
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

    // Inisialisasi autentikasi jika belum login tapi token ada
    if (!counterStore.isLoggedIn && localStorage.getItem('Authorization')) {
        try {
            await counterStore.initializeAuth();
        } catch (e) {
            console.error('Gagal inisialisasi auth:', e);
            return next({ name: 'Login' });
        }
    }

    const { requiresAuth, roles: requiredRoles } = to.meta;
    const isLoggedIn = counterStore.isLoggedIn;
    const userRole = counterStore.userRole;

    if (requiresAuth && !isLoggedIn) {
        return next({ name: 'Login' });
    }

    if (to.name === 'Login' && isLoggedIn) {
        return next({ name: 'Dashboard' });
    }

    if (requiredRoles && (!userRole || !requiredRoles.includes(userRole))) {
        console.warn(
            `Akses ditolak: User dengan role '${userRole}' mencoba mengakses '${to.fullPath}' yang memerlukan role [${requiredRoles.join(', ')}]`
        );
        return next({ name: 'Dashboard' });
    }

    return next();
});


export default router;

