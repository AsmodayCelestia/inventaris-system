<?php

namespace App\Http\Controllers;

use App\Models\Floor; // Asumsi kamu punya model Floor
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FloorController extends Controller
{
    /**
     * Display a listing of the resource (Daftar semua lantai).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function index()
    {
        // Untuk menampilkan nama unit juga, bisa gunakan with()
        $floors = Floor::with('unit')->get(); 
        return response()->json($floors);
    }
    public function indexByUnit($unitId)
    {
        $floors = Floor::where('unit_id', $unitId)->get();
        return response()->json($floors);
    }


    /**
     * Store a newly created resource in storage (Menambah lantai baru).
     * Hanya dapat diakses oleh Admin.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'number' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('floors')->where(function ($query) use ($request) {
                        return $query->where('unit_id', $request->unit_id);
                    }),
                ],
                'unit_id' => 'required|exists:location_units,id',
            ]);

            $floor = Floor::create($validatedData);
            return response()->json($floor, 201); 
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422); 
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah lantai: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Display the specified resource (Menampilkan detail satu lantai).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function show($id)
    {
        $floor = Floor::with('unit')->find($id); // Load relasi unit
        if (!$floor) {
            return response()->json(['message' => 'Lantai tidak ditemukan'], 404);
        }
        return response()->json($floor);
    }

    /**
     * Update the specified resource in storage (Memperbarui lantai).
     * Hanya dapat diakses oleh Admin.
     */
    public function update(Request $request, $id)
    {
        $floor = Floor::find($id);
        if (!$floor) {
            return response()->json(['message' => 'Lantai tidak ditemukan'], 404);
        }

        try {
            $validatedData = $request->validate([
                'number' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('floors')->where(function ($query) use ($request) {
                        return $query->where('unit_id', $request->unit_id);
                    })->ignore($id),
                ],
                'unit_id' => 'required|exists:location_units,id',
            ]);

            $floor->update($validatedData);
            return response()->json($floor);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui lantai: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Remove the specified resource from storage (Menghapus lantai).
     * Hanya dapat diakses oleh Admin.
     */
    public function destroy($id)
    {
        $floor = Floor::find($id);
        if (!$floor) {
            return response()->json(['message' => 'Lantai tidak ditemukan'], 404);
        }

        try {
            $floor->delete();
            return response()->json(['message' => 'Lantai berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus lantai: ' . $e->getMessage()], 500);
        }
    }
}
