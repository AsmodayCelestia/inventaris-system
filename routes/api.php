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
use App\Http\Controllers\DivisionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// PUBLIC
Route::post('/login', [AuthController::class, 'login']);

// AUTHENTICATED
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [UserController::class, 'me']);

    /* ----------  READ-ONLY UNTUK SEMUA USER  ---------- */
    Route::apiResource('brands',         BrandController::class)->only(['index','show']);
    Route::apiResource('categories',     CategoryController::class)->only(['index','show']);
    Route::apiResource('item-types',     ItemTypeController::class)->only(['index','show']);
    Route::apiResource('units',          LocationUnitController::class)->only(['index','show']);
    Route::apiResource('floors',         FloorController::class)->only(['index','show']);
    Route::apiResource('rooms',          RoomController::class)->only(['index','show']);
    Route::apiResource('inventory-items',InventoryItemController::class)->only(['index','show']);
    Route::apiResource('inventories',    InventoryController::class)->only(['index','show']);
    Route::apiResource('divisions', DivisionController::class)->only(['index','show']);

    Route::get('/inventories/qr/{inventoryNumber}', [InventoryController::class,'showByQrCode']);
    Route::get('/maintenance/history', [MaintenanceController::class,'index']);
    Route::post('/inventories/{inventoryId}/maintenance', [MaintenanceController::class,'store']);
    Route::get('/maintenance/{id}', [MaintenanceController::class,'show']);
    Route::post('/maintenance/{id}', [MaintenanceController::class,'update']);

    Route::get('/dashboard/stats', [DashboardController::class,'getStats']);
    Route::get('/me/supervised-inventories', [InventoryController::class,'supervisedByMe']);
    Route::get('/me/assigned-inventories', function () {
        return auth()->user()->assignedMaintenanceInventories()->pluck('id');
    });

        Route::put('/inventories/{id}', [InventoryController::class, 'update']);


    /* ----------  ADMIN & HEAD  ---------- */
    Route::middleware('role:admin,head')->group(function () {
    Route::get('/users',        [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
        // Inventory Items (full CUD)
        Route::apiResource('inventory-items', InventoryItemController::class)->except(['index','show']);

        // Inventories (full CUD)
        Route::apiResource('inventories', InventoryController::class)->except(['index','show', 'update']);

        // Flow HEAD : quantity → empty-slot → create
        Route::post('/inventory-items/{inventoryItem}/increase-quantity', [InventoryItemController::class,'increaseQuantity']);
        Route::get('/inventory-items/{inventoryItem}/empty-slots', [InventoryItemController::class,'getEmptySlots']);
        Route::post('/inventory-items/{inventoryItem}/inventories', [InventoryController::class,'storeFromSlot']);

        // Maintenance
        Route::put('/maintenance/{id}', [MaintenanceController::class,'update']);
        Route::delete('/maintenance/{id}', [MaintenanceController::class,'destroy']);

        // Schedule
        Route::put('/inventories/{inventory}/schedule', [InventoryController::class,'updateSchedule']);
    });

    /* ----------  ADMIN ONLY  ---------- */
    Route::middleware('role:admin')->group(function () {

        Route::post('/register', [AuthController::class,'register']);

        Route::apiResource('brands',     BrandController::class)->except(['index','show']);
        Route::apiResource('categories', CategoryController::class)->except(['index','show']);
        Route::apiResource('item-types', ItemTypeController::class)->except(['index','show']);
        Route::apiResource('units',      LocationUnitController::class)->except(['index','show']);
        Route::apiResource('floors',     FloorController::class)->except(['index','show']);
        Route::apiResource('rooms',      RoomController::class)->except(['index','show']);
        Route::apiResource('divisions', DivisionController::class)->except(['index','show']);
        Route::apiResource('users', UserController::class)->except(['index', 'show', 'update']);

        Route::post('/qrcodes', [InventoryController::class,'createQrCode']);
        Route::put('/qrcodes/{id}', [InventoryController::class,'updateQrCode']);
        Route::delete('/qrcodes/{id}', [InventoryController::class,'deleteQrCode']);

        Route::get('/reports/inventories/pdf',   [ReportController::class,'exportInventoriesPdf']);
        Route::get('/reports/inventories/excel', [ReportController::class,'exportInventoriesExcel']);
    });
});