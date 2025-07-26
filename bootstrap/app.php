<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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

        // Pastikan middleware CORS sudah ada di sini jika diperlukan
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    // PASTIKAN TIDAK ADA ->withProviders([...]) atau ->withFacades([...]) DI SINI
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
