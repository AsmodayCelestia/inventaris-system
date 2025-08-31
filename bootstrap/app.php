<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
// Hapus baris ini jika ada: use Illuminate\Support\Facades\Facade;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckUserRole::class,
            'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\AuthenticateWithApiTokens::class,
        ]);
            $middleware->statefulApi();   // hanya perlu kalau pakai Sanctum cookie
    $middleware->cors([
        'allowed_origins' => [
            'https://inventaris-laravel12-ko0da0841-herus-projects.vercel.app',
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'supports_credentials' => false, // ubah jadi true kalau pakai cookie / Sanctum SPA
    ]);

        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withProviders([
        // SimpleSoftwareIO\QrCode\QrCodeServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
