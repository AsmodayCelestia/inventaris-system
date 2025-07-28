<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Cloudinary', \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Kalau request dari ngrok, jangan pakai hot reload
        if (request()->getHost() && str_contains(request()->getHost(), 'ngrok-free.app')) {
            config(['app.asset_url' => env('ASSET_URL')]); // Pakai ASSET_URL dari .env
            config(['vite.hot_file' => null]); // Matikan Vite HMR agar Blade load build file
        }
    }
}
