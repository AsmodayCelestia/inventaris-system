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
use App\Http\Controllers\MaintenanceController;
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
Route::middleware('auth:sanctum')->group(function () {

    // Autentikasi
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Illuminate\Http\Request $request) {
        return $request->user();
    });

    // --- AKSES BACA (GET) UNTUK SEMUA USER YANG TERAUTENTIKASI ---
    Route::apiResource('brands', BrandController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('item-types', ItemTypeController::class)->only(['index', 'show']);
    Route::apiResource('units', LocationUnitController::class)->only(['index', 'show']);
    Route::apiResource('floors', FloorController::class)->only(['index', 'show']);
    Route::apiResource('rooms', RoomController::class)->only(['index', 'show']);
    Route::apiResource('inventory-items', InventoryItemController::class)->only(['index', 'show']);

    // Data Inventaris - GET oleh semua yang login
    // Ini mendaftarkan 'index' (GET /inventories) dan 'show' (GET /inventories/{id})
    Route::apiResource('inventories', InventoryController::class)->only(['index', 'show']);
    // Akses detail inventaris berdasarkan QR Code - Bisa diakses Petugas/Admin/Head
    Route::get('/inventories/qr/{inventoryNumber}', [InventoryController::class, 'showByQrCode']);

    // Maintenance - GET Riwayat oleh semua yang login, POST oleh Petugas/Admin/Head
    Route::get('/maintenance/history', [MaintenanceController::class, 'index']);
    Route::post('/inventories/{inventoryId}/maintenance', [MaintenanceController::class, 'store']);
 
    // Dashboard - GET Statistik Dashboard oleh semua yang login
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

    Route::get('/me/supervised-inventories', [InventoryController::class, 'supervisedByMe']);

    // Di dalam Route::middleware('auth:sanctum')->group(...)
    Route::get('/me/assigned-inventories', function () {
        $user = auth()->user();

        // Misal relasinya: User hasMany Inventories as 'maintenancePj'
        return $user->assignedMaintenanceInventories()->pluck('id');
    });

    // --- ROUTES KHUSUS ADMIN ATAU HEAD (Memerlukan token Sanctum DAN role 'admin' atau 'head') ---
    Route::middleware('role:admin,head')->group(function () {
        // Data Inventaris (CUD oleh Admin atau Head)
        // Ini mendaftarkan 'store' (POST /inventories), 'update' (PUT/PATCH /inventories/{id}), 'destroy' (DELETE /inventories/{id})
        Route::apiResource('inventories', InventoryController::class)->except(['index', 'show']);

        // Inventory Items (CUD oleh Admin atau Head)
        Route::apiResource('inventory-items', InventoryItemController::class)->except(['index', 'show']);
        Route::put('/inventories/{inventory}/schedule', [InventoryController::class, 'updateSchedule']);

        // Maintenance (Update/Delete oleh Admin atau Head)
        Route::put('/maintenance/{id}', [MaintenanceController::class, 'update']);
        Route::delete('/maintenance/{id}', [MaintenanceController::class, 'destroy']);
    });

    // --- ROUTES KHUSUS ADMIN SAJA (Memerlukan token Sanctum DAN role 'admin') ---
    Route::middleware('role:admin')->group(function () {
        // Autentikasi Admin (jika register user hanya untuk admin)
        Route::post('/register', [AuthController::class, 'register']);

        // Master Data (CUD hanya Admin)
        Route::apiResource('brands', BrandController::class)->except(['index', 'show']);
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::apiResource('item-types', ItemTypeController::class)->except(['index', 'show']);
        Route::apiResource('units', LocationUnitController::class)->except(['index', 'show']);
        Route::apiResource('floors', FloorController::class)->except(['index', 'show']);
        Route::apiResource('rooms', RoomController::class)->except(['index', 'show']);

        // Manajemen User (CRUD hanya Admin)
        Route::apiResource('users', UserController::class);

        // Laporan (Export hanya Admin)
        Route::get('/reports/inventories/pdf', [ReportController::class, 'exportInventoriesPdf']);
        Route::get('/reports/inventories/excel', [ReportController::class, 'exportInventoriesExcel']);
    });
});
