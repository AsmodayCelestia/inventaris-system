<?php

namespace App\Http\Controllers;

use App\Models\LocationUnit; // Asumsi kamu punya model LocationUnit
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LocationUnitController extends Controller
{
    /**
     * Display a listing of the resource (Daftar semua unit lokasi).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function index()
    {
        $locationUnits = LocationUnit::all();
        return response()->json($locationUnits);
    }

    /**
     * Store a newly created resource in storage (Menambah unit lokasi baru).
     * Hanya dapat diakses oleh Admin.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:location_units,name',
            ]);

            $locationUnit = LocationUnit::create($validatedData);
            return response()->json($locationUnit, 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah unit lokasi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource (Menampilkan detail satu unit lokasi).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function show($id)
    {
        $locationUnit = LocationUnit::find($id);
        if (!$locationUnit) {
            return response()->json(['message' => 'Unit lokasi tidak ditemukan'], 404);
        }
        return response()->json($locationUnit);
    }

    /**
     * Update the specified resource in storage (Memperbarui unit lokasi).
     * Hanya dapat diakses oleh Admin.
     */
    public function update(Request $request, $id)
    {
        $locationUnit = LocationUnit::find($id);
        if (!$locationUnit) {
            return response()->json(['message' => 'Unit lokasi tidak ditemukan'], 404);
        }

        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:location_units,name,' . $id,
            ]);

            $locationUnit->update($validatedData);
            return response()->json($locationUnit);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui unit lokasi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Menghapus unit lokasi).
     * Hanya dapat diakses oleh Admin.
     */
    public function destroy($id)
    {
        $locationUnit = LocationUnit::find($id);
        if (!$locationUnit) {
            return response()->json(['message' => 'Unit lokasi tidak ditemukan'], 404);
        }

        try {
            $locationUnit->delete();
            return response()->json(['message' => 'Unit lokasi berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus unit lokasi: ' . $e->getMessage()], 500);
        }
    }
}
