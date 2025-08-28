<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // app/Http/Controllers/UserController.php

public function me(Request $request)
{
    // Pastikan user sudah login (middleware auth:sanctum)
    $user = $request->user()->load('division'); // eager-load division

    return response()->json([
        'id'               => $user->id,
        'name'             => $user->name,
        'email'            => $user->email,
        'role'             => $user->role,
        'divisi'           => $user->division?->name,
        'isPjMaintenance'  => (bool) $user->is_pj_maintenance,
        'isRoomSupervisor' => (bool) $user->is_room_supervisor,
        'assignedRooms'    => [], // tambahkan logic kalau perlu
    ]);
}

public function index(Request $request)
{
    $query = User::with('division');

    // jika query string ?divisi=... ada, filter berdasarkan nama divisi
    if ($request->filled('divisi')) {
        $query->whereHas('division', function ($q) use ($request) {
            $q->where('name', $request->divisi);
        });
    }

    return $query->get();
}

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|string|email|max:255|unique:users',
                'password'              => 'required|string|min:6|confirmed',
                'divisi'                => 'required|string|max:255',
                'role'                  => 'required|string|in:admin,head,karyawan',
                'is_room_supervisor'    => 'sometimes|boolean',
                'is_pj_maintenance'     => 'sometimes|boolean',
            ]);

            // Pastikan nilai default boolean kalau tidak dikirim
            $validatedData['is_room_supervisor'] = $request->boolean('is_room_supervisor', false);
            $validatedData['is_pj_maintenance'] = $request->boolean('is_pj_maintenance', false);

            $user = User::create($validatedData);

            return response()->json($user, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah user: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
        return response()->json($user);
    }

// app/Http/Controllers/UserController.php  (bagian update)
public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $validatedData = $request->validate([
        'name'               => 'required|string|max:255',
        'email'              => 'required|email|unique:users,email,'.$id,
        'password'           => 'nullable|string|min:6|confirmed',
        'division_id'        => 'required|exists:divisions,id', // <-- pakai FK
        'role'               => 'required|in:admin,head,karyawan',
        'is_room_supervisor' => 'sometimes|boolean',
        'is_pj_maintenance'  => 'sometimes|boolean',
    ]);

    // password opsional
    if (!empty($validatedData['password'])) {
        $validatedData['password'] = bcrypt($validatedData['password']);
    } else {
        unset($validatedData['password']);
    }

    // simpan
    $user->update($validatedData);

    return response()->json($user);
}

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
