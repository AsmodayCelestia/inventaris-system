<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash; // Meskipun mutator di model sudah ada, ini tetap berguna untuk validasi atau jika ingin manual

class UserController extends Controller
{
    /**
     * Display a listing of the resource (Daftar semua user).
     * Hanya dapat diakses oleh Admin.
     */
    public function index()
    {
        // Bisa eager load relasi jika diperlukan di daftar user
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage (Menambah user baru).
     * Hanya dapat diakses oleh Admin.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed', // 'confirmed' akan mencari password_confirmation
                'divisi' => 'required|string|max:255',
                'role' => 'required|string|in:admin,head,karyawan', // Sesuaikan role yang valid
            ]);

            // Password akan di-hash otomatis oleh mutator di model User
            $user = User::create($validatedData);

            return response()->json($user, 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah user: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource (Menampilkan detail satu user).
     * Hanya dapat diakses oleh Admin.
     */
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage (Memperbarui user).
     * Hanya dapat diakses oleh Admin.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id, // Unique kecuali ID ini sendiri
                'password' => 'nullable|string|min:6|confirmed', // Password opsional saat update
                'divisi' => 'required|string|max:255',
                'role' => 'required|string|in:admin,head,karyawan', // Sesuaikan role yang valid
            ]);

            // Handle password update secara kondisional
            if (isset($validatedData['password'])) {
                // Mutator di model akan otomatis hash password jika di-set
                $user->password = $validatedData['password'];
            }
            
            // Hapus password dari validatedData agar tidak di-update 2x atau jika tidak ada perubahan
            unset($validatedData['password']); 
            unset($validatedData['password_confirmation']); // Hapus juga konfirmasi password

            $user->update($validatedData); // Update sisa field lainnya

            return response()->json($user);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui user: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Menghapus user).
     * Hanya dapat diakses oleh Admin.
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        try {
            $user->delete();
            return response()->json(['message' => 'User berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus user: ' . $e->getMessage()], 500);
        }
    }
}
