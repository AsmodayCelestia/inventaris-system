<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Hanya ini yang kita butuhkan untuk Auth::attempt
use Illuminate\Validation\ValidationException; // Untuk menangkap error validasi

class AuthController extends Controller
{
    // try
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users',
                'password' => 'required|string|min:6|confirmed', // 'confirmed' akan mencari password_confirmation
                'divisi'   => 'required|string|max:255',
                'role'     => 'required|in:admin,karyawan,head', // Pastikan 'head' juga ada di sini
            ]);

            // Password akan di-hash otomatis oleh mutator di model User
            $user = User::create($validatedData); // Menggunakan $validatedData setelah validate

            return response()->json(['message' => 'User berhasil didaftarkan.', 'id' => $user->id], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan saat mendaftarkan user: ' . $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            $credentials = $request->only('email', 'password');

            // Cukup gunakan Auth::attempt. Ini akan mencari user dan memverifikasi password.
            if (!Auth::attempt($credentials)) {
                return response()->json(['message' => 'Kredensial tidak valid.'], 401);
            }

            $user = Auth::user(); // Dapatkan user yang berhasil login
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil.', // Tambahkan pesan sukses
                'Authorization' => 'Bearer ' . $token,
                'role'          => $user->role,
                'email'         => $user->email,
                'id'            => $user->id,    // <-- Tambahkan ID
                'name'          => $user->name,  // <-- Tambahkan Nama
                'divisi'        => $user->divisi, // <-- Tambahkan Divisi
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan saat login: ' . $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Hapus token yang sedang digunakan oleh user ini
            $request->user()->currentAccessToken()->delete(); 

            return response()->json(['message' => 'Logout berhasil.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan saat logout: ' . $e->getMessage()], 500);
        }
    }
}
