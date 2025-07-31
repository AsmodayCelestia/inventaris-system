<?php

namespace App\Http\Controllers;

use App\Models\Room; // Asumsi kamu punya model Room
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
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:rooms,name', 
                'floor_id' => 'required|exists:floors,id', 
                'pj_lokasi_id' => 'nullable|exists:users,id', // <-- TAMBAHKAN VALIDASI INI
            ]);

            $room = Room::create($validatedData);
            return response()->json($room, 201); 
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422); 
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah ruangan: ' . $e->getMessage()], 500);
        }
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
    public function update(Request $request, $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['message' => 'Ruangan tidak ditemukan'], 404);
        }

        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:rooms,name,' . $id, 
                'floor_id' => 'required|exists:floors,id',
                'pj_lokasi_id' => 'nullable|exists:users,id', // <-- TAMBAHKAN VALIDASI INI
            ]);

            $room->update($validatedData);
            return response()->json($room);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui ruangan: ' . $e->getMessage()], 500);
        }
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
