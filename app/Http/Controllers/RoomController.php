<?php

namespace App\Http\Controllers;

use App\Models\Room; // Asumsi kamu punya model Room
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource (Daftar semua ruangan).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function index()
    {
        // Eager load relasi floor dan locationPersonInCharge
        $rooms = Room::with(['floor.unit', 'locationPersonInCharge'])->get();
        return response()->json($rooms);
    }

    /**
     * Store a newly created resource in storage (Menambah ruangan baru).
     * Hanya dapat diakses oleh Admin.
     */
public function store(Request $request)
{
    $data = $request->validate([
        'name'           => 'required|string|max:255',
        'floor_id'       => 'required|exists:floors,id',
        'pj_lokasi_id'   => 'nullable|exists:users,id',
    ]);

    $room = Room::create($data);

    // set supervisor flag
    if ($data['pj_lokasi_id']) {
        User::where('id', $data['pj_lokasi_id'])->update(['is_room_supervisor' => true]);
    }

    return response()->json($room->load('floor.unit', 'locationPersonInCharge'), 201);
}


    /**
     * Display the specified resource (Menampilkan detail satu ruangan).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function show($id)
    {
        // Eager load relasi floor dan locationPersonInCharge
    $room = Room::with(['floor.unit', 'locationPersonInCharge'])->find($id);
        if (!$room) {
            return response()->json(['message' => 'Ruangan tidak ditemukan'], 404);
        }
        return response()->json($room);
    }

    /**
     * Update the specified resource in storage (Memperbarui ruangan).
     * Hanya dapat diakses oleh Admin.
     */
public function update(Request $request, Room $room)
{
    $data = $request->validate([
        'name'           => 'sometimes|required|string|max:255',
        'floor_id'       => 'sometimes|required|exists:floors,id',
        'pj_lokasi_id'   => 'nullable|exists:users,id',
    ]);

    $oldPj = $room->pj_lokasi_id;

    $room->update($data);

    // hilangkan flag dari pj lama (kalau ada)
    if ($oldPj && $oldPj != ($data['pj_lokasi_id'] ?? null)) {
        User::where('id', $oldPj)->update(['is_room_supervisor' => false]);
    }

    // set flag ke pj baru (kalau ada)
    if ($data['pj_lokasi_id']) {
        User::where('id', $data['pj_lokasi_id'])->update(['is_room_supervisor' => true]);
    }

    return response()->json($room->load('floor.unit', 'locationPersonInCharge'));
}

    /**
     * Remove the specified resource from storage (Menghapus ruangan).
     * Hanya dapat diakses oleh Admin.
     */
    public function destroy($id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['message' => 'Ruangan tidak ditemukan'], 404);
        }

        try {
            $room->delete();
            return response()->json(['message' => 'Ruangan berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus ruangan: ' . $e->getMessage()], 500);
        }
    }
}
