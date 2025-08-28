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
use App\Http\Controllers\ActivityLogController; 


// use Endroid\QrCode\Builder\Builder;
// use Endroid\QrCode\Writer\PngWriter;
// use Endroid\QrCode\Label\Label;
// use Endroid\QrCode\Label\Font\Font;
// use Endroid\QrCode\Color\Color;
// use Endroid\QrCode\Label\LabelAlignment;



// Route::get('/qr-preview', function () {
//     $url = trim((string) (request('url') ?: 'https://qr.darwis.web.id'));
//     $inventoryNo = (string) (request('inv')  ?: 'INV-0000');
//     $color       = strtolower((string) (request('color') ?: 'black'));
//     $logo        = (string) (request('logo') ?: 'android-chrome-192x192.png');

//     $rgb = match ($color) {
//         'red'   => [255, 0,   0],
//         'green' => [0,   255, 0],
//         'blue'  => [0,   0,   255],
//         default => [0,   0,   0],
//     };

//     $logoPath = public_path($logo);
//     $logoPath = file_exists($logoPath) ? $logoPath : null;

//     $fontPath = base_path('vendor/endroid/qr-code/assets/noto_sans.otf');
//     $font     = new \Endroid\QrCode\Label\Font\Font($fontPath, 12);

//     $qr = \Endroid\QrCode\Builder\Builder::create()
//         ->writer(new \Endroid\QrCode\Writer\PngWriter())
//         ->data($url)
//         ->size(260)
//         ->margin(20)
//         ->foregroundColor(new \Endroid\QrCode\Color\Color(...$rgb))
//         ->backgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255))
//         ->logoPath($logoPath)
//         ->logoResizeToWidth(50)
//         ->labelText($inventoryNo)
//         ->labelFont($font)
//         // ❌ hapus labelAlignment()
//         ->build();

//     return response($qr->getString())
//            ->header('Content-Type', 'image/png');
// });
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// PUBLIC
Route::post('/login', [AuthController::class, 'login']);
Route::get('/inventories/scan/{id}', [InventoryController::class, 'showForScan']);
// routes/api.php
        // download
        Route::get('/qrcodes/download',   [InventoryController::class, 'downloadBulk']);   // ?ids=1,2,3 → zip

// AUTHENTICATED
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [UserController::class, 'me']);

    /* ----------  READ-ONLY UNTUK SEMUA USER  ---------- */
    Route::apiResource('brands',         BrandController::class)->only(['index','show']);
    Route::apiResource('categories',     CategoryController::class)->only(['index','show']);
    Route::apiResource('item-types',     ItemTypeController::class)->only(['index','show']);
    Route::apiResource('units',          LocationUnitController::class)->only(['index','show']);
    Route::get('/floors/by-units', [FloorController::class, 'byUnits']);
    Route::get('/rooms/by-units-floors', [RoomController::class, 'byUnitsFloors']);
    Route::apiResource('floors',         FloorController::class)->only(['index','show']);
    Route::apiResource('rooms',          RoomController::class)->only(['index','show']);
    Route::apiResource('inventory-items',InventoryItemController::class)->only(['index','show']);
    Route::get('/inventories/table', [InventoryController::class, 'table']);
    Route::apiResource('inventories',    InventoryController::class)->only(['index','show']);
    Route::apiResource('divisions', DivisionController::class)->only(['index','show']);

    /* ---------- FLOW BARU ---------- */
    Route::get('/maintenance/active', [MaintenanceController::class,'active']); // ✅ tambahkan ini
    Route::get('/maintenance/need', [MaintenanceController::class, 'need']);
    Route::patch('/maintenance/{id}/assign', [MaintenanceController::class, 'assign']);
    Route::patch('/maintenance/{id}/status', [MaintenanceController::class, 'updateStatus']);
    
    Route::get('/inventories/qr/{inventoryNumber}', [InventoryController::class,'showByQrCode']);
    Route::get('/maintenance/history', [MaintenanceController::class,'index']);
    Route::get('/maintenance/done-datatable', [MaintenanceController::class, 'doneDatatable']);
    Route::get('/maintenance/done', [MaintenanceController::class, 'done']);
    Route::get('/inventories/{inventory}/maintenance-done',[MaintenanceController::class, 'historyDone']);
    Route::post('/inventories/{inventoryId}/maintenance', [MaintenanceController::class,'store']);
    Route::get('/maintenance/{id}', [MaintenanceController::class,'show']);
    Route::put('/maintenance/{id}', [MaintenanceController::class,'update']);
    // Route::post('/maintenance/{id}', [MaintenanceController::class,'update']);

    Route::get('/dashboard/stats', [DashboardController::class,'getStats']);
    Route::get('/me/supervised-inventories', [InventoryController::class,'supervisedByMe']);
    Route::get('/me/assigned-inventories', function () {
        return auth()->user()->assignedMaintenanceInventories()->pluck('id');
    });

        Route::put('/inventories/{id}', [InventoryController::class, 'update']);
        Route::get('/inventory-items-datatable', [InventoryItemController::class, 'table'])
            ->middleware(['auth:sanctum', 'role:admin,head']);

    /* ----------  ADMIN & HEAD  ---------- */
    Route::middleware('role:admin,head')->group(function () {

    Route::get('/users',        [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);

        // master data
        Route::apiResource('brands',     BrandController::class)->except(['index','show']);
        Route::apiResource('categories', CategoryController::class)->except(['index','show']);
        Route::apiResource('item-types', ItemTypeController::class)->except(['index','show']);
        Route::apiResource('floors',     FloorController::class)->except(['index','show']);
        Route::apiResource('rooms',      RoomController::class)->except(['index','show']);
        // Inventory Items (full CUD)
        Route::apiResource('inventory-items', InventoryItemController::class)->except(['index','show']);

        // Inventories (full CUD)
        Route::apiResource('inventories', InventoryController::class)->except(['index','show', 'update']);

        // Flow HEAD : quantity → empty-slot → create
        Route::post('/inventory-items/{inventoryItem}/increase-quantity', [InventoryItemController::class,'increaseQuantity']);
        Route::get('/inventory-items/{inventoryItem}/empty-slots', [InventoryItemController::class,'getEmptySlots']);
        Route::post('/inventory-items/{inventoryItem}/inventories', [InventoryController::class,'storeFromSlot']);

        // Maintenance
        // Route::put('/maintenance/{id}', [MaintenanceController::class,'update']);
        Route::delete('/maintenance/{id}', [MaintenanceController::class,'destroy']);

        // Schedule
        Route::post('/maintenance/scheduled/{inventory}', [MaintenanceController::class, 'storeScheduled']);
        Route::put('/inventories/{inventory}/schedule', [InventoryController::class,'updateSchedule']);
    });

    /* ----------  ADMIN ONLY  ---------- */
    Route::middleware('role:admin')->group(function () {

        Route::post('/register', [AuthController::class,'register']);
        Route::apiResource('units',      LocationUnitController::class)->except(['index','show']);
        Route::apiResource('divisions', DivisionController::class)->except(['index','show']);
        Route::apiResource('users', UserController::class)->except(['index', 'show', 'update']);
        // bulk create / update / delete
        Route::post('/qrcodes/bulk',      [InventoryController::class, 'bulkCreateQr']);
        Route::put('/qrcodes/bulk',       [InventoryController::class, 'bulkUpdateQr']);
        Route::delete('/qrcodes/bulk',    [InventoryController::class, 'bulkDeleteQr']);

        Route::get('/activity-log', [ActivityLogController::class, 'index']);
        Route::get('/activity-log/datatable', [ActivityLogController::class, 'datatable']);
        Route::get('/reports/inventories/pdf',   [ReportController::class,'exportInventoriesPdf']);
        Route::get('/reports/inventories/excel', [ReportController::class,'exportInventoriesExcel']);
        Route::get('/activity-log/{id}/detail', [ActivityLogController::class, 'detail']);

        Route::get('/activity-log/datatable', [ActivityLogController::class, 'datatable']);
        Route::get('/activity-log/models',    [ActivityLogController::class, 'models']);
        Route::get('/activity-log/users',     [ActivityLogController::class, 'users']);
        Route::get('/activity-log/{id}/detail', [ActivityLogController::class, 'detail']);
    });
});