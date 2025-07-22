<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Ini adalah satu-satunya rute web yang akan melayani aplikasi Vue.js SPA kamu.
// Semua rute lain (seperti /login, /dashboard, /inventories) akan ditangani oleh Vue Router.
Route::get('/{any}', function () {
    return view('app'); // Mengembalikan view Blade tunggal yang akan menampung aplikasi Vue
})->where('any', '.*'); // Ini memastikan rute ini menangkap semua URL yang tidak cocok dengan rute API atau aset publik lainnya.
