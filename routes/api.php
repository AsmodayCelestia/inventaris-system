<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController; // Untuk AuthController
use App\Http\Controllers\Controller; // Untuk MainController

// Route untuk login (ini tetap PUBLIC karena user belum punya token untuk login)
Route::post('/login', [AuthController::class, 'login']);
// Route::post('/register', [AuthController::class, 'register']);

// Semua route di bawah ini butuh login dulu (authentication)
// Middleware 'auth:sanctum' akan memastikan user sudah terautentikasi dengan token
Route::middleware('auth:sanctum')->group(function () {

    // Route yang butuh authentication saja (tanpa role check spesifik)
    // Route::get('/my-performance', [Controller::class, 'myPerformance']);

    // Route yang butuh authentication + authorization ADMIN
    // Middleware 'role:admin' akan memastikan user yang login adalah admin
    Route::middleware('role:admin')->group(function () {
        // Hanya user dengan role 'admin' yang bisa mengakses route ini
        Route::post('/register', [AuthController::class, 'register']); // <-- INI DIA!

        // Route::delete('/user/{id}/reward/{performanceId}', [Controller::class, 'deleteUserReward']);
        // Route::get('/user/{id}/rewards', [Controller::class, 'rewardsByUserId']);
        // Tambahkan route khusus admin lainnya di sini
    });

    // Route yang butuh authentication + authorization KARYAWAN
    // Middleware 'role:karyawan' akan memastikan user yang login adalah karyawan
    // Route::middleware('role:karyawan')->group(function () {
    //     Route::post('/rewards', [Controller::class, 'storeReward']);
    //     Route::get('/my-rewards', [Controller::class, 'myRewards']);
    //     // Tambahkan route khusus karyawan lainnya di sini
    // });

});
