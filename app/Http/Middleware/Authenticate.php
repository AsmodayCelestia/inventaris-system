<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log; // <-- TAMBAHKAN INI

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * Ini biasanya untuk aplikasi web tradisional. Untuk API, kita akan tangani di unauthenticated().
     */
    protected function redirectTo(Request $request): ?string
    {
        Log::debug('Authenticate Middleware: redirectTo called. expectsJson: ' . ($request->expectsJson() ? 'true' : 'false'));
        return $request->expectsJson() ? null : route('login');
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
protected function unauthenticated($request, array $guards)
{
    if ($request->expectsJson()) {
        throw new AuthenticationException('Unauthenticated.');
    }
    // Kalau bukan API, redirect bisa terjadi
    return redirect()->guest(route('login'));
}
}
