<?php

namespace App\Http\Controllers;

use App\Models\Brand; // Asumsi kamu punya model Brand
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource (Daftar semua merk).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function index()
    {
        $brands = Brand::all();
        return response()->json($brands);
    }

    /**
     * Store a newly created resource in storage (Menambah merk baru).
     * Hanya dapat diakses oleh Admin.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:brands,name', // Asumsi ada kolom 'name' di tabel 'brands'
            ]);

            $brand = Brand::create($validatedData);
            return response()->json($brand, 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah merk: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource (Menampilkan detail satu merk).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function show($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json(['message' => 'Merk tidak ditemukan'], 404);
        }
        return response()->json($brand);
    }

    /**
     * Update the specified resource in storage (Memperbarui merk).
     * Hanya dapat diakses oleh Admin.
     */
    public function update(Request $request, $id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json(['message' => 'Merk tidak ditemukan'], 404);
        }

        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:brands,name,' . $id, // Unique kecuali ID ini sendiri
            ]);

            $brand->update($validatedData);
            return response()->json($brand);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui merk: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Menghapus merk).
     * Hanya dapat diakses oleh Admin.
     */
    public function destroy($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return response()->json(['message' => 'Merk tidak ditemukan'], 404);
        }

        try {
            $brand->delete();
            return response()->json(['message' => 'Merk berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus merk: ' . $e->getMessage()], 500);
        }
    }
}
