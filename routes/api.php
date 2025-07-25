<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemTypeController;
use App\Http\Controllers\LocationUnitController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MaintenanceController; // <-- KOREKSI: ini harusnya App\Http\Controllers\MaintenanceController
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\InventoryItemController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// --- PUBLIC ROUTES (Tidak memerlukan autentikasi) ---
Route::post('/login', [AuthController::class, 'login']);

// --- AUTHENTICATED ROUTES (Memerlukan token Sanctum) ---
// Ini adalah grup utama untuk semua user yang sudah login (Admin, Head, Karyawan/Petugas).
// Route di sini akan bisa diakses oleh siapa saja yang memiliki token valid.
Route::middleware('auth:sanctum')->group(function () {

    // Autentikasi
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Illuminate\Http\Request $request) {
        return $request->user();
    });

    // --- AKSES BACA (GET) UNTUK SEMUA USER YANG TERAUTENTIKASI ---
    // Semua user yang login (Admin, Head, Karyawan/Petugas) bisa melihat daftar & detail
    // Route::apiResource(...)->only(['index', 'show']) berarti hanya operasi GET yang didaftarkan di sini.
    // Admin dan Head juga termasuk dalam "semua user yang terautentikasi", jadi mereka bisa mengakses ini.
    Route::apiResource('brands', BrandController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']); // <-- KOREKSI: Route::
    Route::apiResource('item-types', ItemTypeController::class)->only(['index', 'show']); // <-- KOREKSI: Route::
    Route::apiResource('units', LocationUnitController::class)->only(['index', 'show']);
    Route::apiResource('floors', FloorController::class)->only(['index', 'show']); // <-- KOREKSI: Route::
    Route::apiResource('rooms', RoomController::class)->only(['index', 'show']);
    Route::apiResource('inventory-items', InventoryItemController::class)->only(['index', 'show']); // <-- KOREKSI: Route::

    // Data Inventaris - GET oleh semua yang login
    Route::apiResource('inventories', InventoryController::class)->only(['index', 'show']);
    // Akses detail inventaris berdasarkan QR Code - Bisa diakses Petugas/Admin/Head
    Route::get('/inventories/qr/{inventoryNumber}', [InventoryController::class, 'showByQrCode']);

    // Maintenance - GET Riwayat oleh semua yang login, POST oleh Petugas/Admin/Head
    Route::get('/maintenance/history', [MaintenanceController::class, 'index']);
    Route::post('/inventories/{inventoryId}/maintenance', [MaintenanceController::class, 'store']);

    // Dashboard - GET Statistik Dashboard oleh semua yang login
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

    // --- ROUTES KHUSUS ADMIN ATAU HEAD (Memerlukan token Sanctum DAN role 'admin' atau 'head') ---
    // Route di dalam grup ini dapat diakses oleh user dengan role 'admin' ATAU 'head'.
    // Ini berlaku untuk operasi CUD pada Inventaris dan Inventory Items, serta Update/Delete Maintenance.
    // Admin juga memiliki akses ke route ini karena role 'admin' termasuk dalam 'admin,head'.
    Route::middleware('role:admin,head')->group(function () {
        // Data Inventaris (CUD oleh Admin atau Head)
        // Mendaftarkan 'store', 'update', 'destroy' untuk 'inventories'.
        // 'index' dan 'show' tidak didaftarkan di sini karena sudah ada di grup 'auth:sanctum' di atas.
        Route::apiResource('inventories', InventoryController::class)->except(['index', 'show']);

        // Inventory Items (CUD oleh Admin atau Head)
        // Mendaftarkan 'store', 'update', 'destroy' untuk 'inventory-items'.
        // 'index' dan 'show' tidak didaftarkan di sini karena sudah ada di grup 'auth:sanctum' di atas.
        Route::apiResource('inventory-items', InventoryItemController::class)->except(['index', 'show']);

        // Maintenance (Update/Delete oleh Admin atau Head)
        Route::put('/maintenance/{id}', [MaintenanceController::class, 'update']);
        Route::delete('/maintenance/{id}', [MaintenanceController::class, 'destroy']);
    });

    // --- ROUTES KHUSUS ADMIN SAJA (Memerlukan token Sanctum DAN role 'admin') ---
    // Route di dalam grup ini hanya dapat diakses oleh user dengan role 'admin'.
    // Admin memiliki akses penuh ke semua route di sini.
    Route::middleware('role:admin')->group(function () {
        // Autentikasi Admin (jika register user hanya untuk admin)
        Route::post('/register', [AuthController::class, 'register']);

        // Master Data (CUD hanya Admin)
        // Mendaftarkan 'store', 'update', 'destroy' untuk master data lainnya.
        // 'index' dan 'show' tidak didaftarkan di sini karena sudah ada di grup 'auth:sanctum' di atas.
        Route::apiResource('brands', BrandController::class)->except(['index', 'show']);
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::apiResource('item-types', ItemTypeController::class)->except(['index', 'show']);
        Route::apiResource('units', LocationUnitController::class)->except(['index', 'show']);
        Route::apiResource('floors', FloorController::class)->except(['index', 'show']);
        Route::apiResource('rooms', RoomController::class)->except(['index', 'show']);

        // Manajemen User (CRUD hanya Admin)
        // Ini akan mendaftarkan SEMUA operasi CRUD untuk 'users' (index, store, show, update, destroy).
        // Karena 'users' TIDAK didaftarkan di grup 'auth:sanctum' di atas, maka semua operasinya hanya bisa diakses admin.
        Route::apiResource('users', UserController::class);

        // Laporan (Export hanya Admin)
        Route::get('/reports/inventories/pdf', [ReportController::class, 'exportInventoriesPdf']);
        Route::get('/reports/inventories/excel', [ReportController::class, 'exportInventoriesExcel']);
    });
});
