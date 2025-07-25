<?php

namespace App\Http\Controllers;

use App\Models\ItemType; // Asumsi kamu punya model ItemType
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ItemTypeController extends Controller
{
    /**
     * Display a listing of the resource (Daftar semua jenis barang).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function index()
    {
        $itemTypes = ItemType::all();
        return response()->json($itemTypes);
    }

    /**
     * Store a newly created resource in storage (Menambah jenis barang baru).
     * Hanya dapat diakses oleh Admin.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:item_types,name',
            ]);

            $itemType = ItemType::create($validatedData);
            return response()->json($itemType, 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah jenis barang: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource (Menampilkan detail satu jenis barang).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function show($id)
    {
        $itemType = ItemType::find($id);
        if (!$itemType) {
            return response()->json(['message' => 'Jenis barang tidak ditemukan'], 404);
        }
        return response()->json($itemType);
    }

    /**
     * Update the specified resource in storage (Memperbarui jenis barang).
     * Hanya dapat diakses oleh Admin.
     */
    public function update(Request $request, $id)
    {
        $itemType = ItemType::find($id);
        if (!$itemType) {
            return response()->json(['message' => 'Jenis barang tidak ditemukan'], 404);
        }

        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:item_types,name,' . $id,
            ]);

            $itemType->update($validatedData);
            return response()->json($itemType);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui jenis barang: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Menghapus jenis barang).
     * Hanya dapat diakses oleh Admin.
     */
    public function destroy($id)
    {
        $itemType = ItemType::find($id);
        if (!$itemType) {
            return response()->json(['message' => 'Jenis barang tidak ditemukan'], 404);
        }

        try {
            $itemType->delete();
            return response()->json(['message' => 'Jenis barang berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus jenis barang: ' . $e->getMessage()], 500);
        }
    }
}
