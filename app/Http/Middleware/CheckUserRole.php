<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  // Parameter untuk menerima role yang diizinkan (misal: 'admin', 'karyawan')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Pastikan user sudah terautentikasi (sudah login)
        if (! $request->user()) {
            // Jika tidak ada user yang login, kembalikan response 401 Unauthorized
            return response()->json(['message' => 'Unauthenticated. Please log in.'], 401);
        }

        // 2. Cek apakah user yang login memiliki salah satu role yang diizinkan
        // Asumsi: Model User memiliki kolom 'role' (misal: 'admin', 'karyawan', 'head')
        if (! in_array($request->user()->role, $roles)) {
            // Jika user tidak memiliki role yang dibutuhkan, kembalikan response 403 Forbidden
            return response()->json(['message' => 'Unauthorized. You do not have the required role.'], 403);
        }

        // 3. Jika user terautentikasi dan memiliki role yang sesuai, lanjutkan request
        return $next($request);
    }
}
