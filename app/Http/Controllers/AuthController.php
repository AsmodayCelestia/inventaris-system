<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\InventoryMaintenance;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|email|unique:users',
                'password'              => 'required|string|min:6|confirmed',
                'division_id' => 'required|exists:divisions,id',
                'role'                  => 'required|in:admin,karyawan,head',
            ]);

            $user = User::create($validatedData);

            return response()->json([
                'message' => 'User berhasil didaftarkan.',
                'id' => $user->id
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mendaftarkan user: ' . $e->getMessage()
            ], 500);
        }
    }

public function login(Request $request)
{
    try {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Kredensial tidak valid.'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        // ✅ Cek apakah user ini pernah jadi penanggung jawab maintenance
        $isPjMaintenance = \App\Models\InventoryMaintenance::where('user_id', $user->id)->exists();

        return response()->json([
            'message'         => 'Login berhasil.',
            'Authorization'   => 'Bearer ' . $token,
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'divisi'          => $user->division->name,
            'role'            => $user->role,
            'isPjMaintenance' => \App\Models\InventoryMaintenance::where('user_id', $user->id)->exists(),
            'isRoomSupervisor'=> $user->is_room_supervisor ?? false,
'assignedRooms' => $user->responsibleRooms
                    ->pluck('name')   // atau 'id'
                    ->filter()        // ← hilangkan null
                    ->values()
                    ->toArray(),
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validasi gagal.',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat login: ' . $e->getMessage()
        ], 500);
    }
}


    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logout berhasil.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat logout: ' . $e->getMessage()
            ], 500);
        }
    }
}


