<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

// PUBLIC (no auth)
Route::post('/login', [Controller::class, 'login']);
Route::post('/register', [Controller::class, 'register']);

// Semua route di bawah ini butuh login dulu (authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Route yang butuh authentication saja (tanpa role check)
    Route::get('/my-performance', [Controller::class, 'myPerformance']);

    // Route yang butuh authentication + authorization admin
    Route::middleware('role:admin')->group(function () {
        Route::delete('/user/{id}/reward/{performanceId}', [Controller::class, 'deleteUserReward']);
        Route::get('/user/{id}/rewards', [Controller::class, 'rewardsByUserId']);
        // dst...
    });

    // Route yang butuh authentication + authorization karyawan
    Route::middleware('role:karyawan')->group(function () {
        Route::post('/rewards', [Controller::class, 'storeReward']);
        Route::get('/my-rewards', [Controller::class, 'myRewards']);
        // dst...
    });

});
