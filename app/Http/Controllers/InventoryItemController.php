<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem; // Asumsi kamu punya model InventoryItem
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage; // Untuk handle upload gambar

class InventoryItemController extends Controller
{
    /**
     * Display a listing of the resource (Daftar semua master barang).
     * Dapat diakses oleh semua user yang terautentikasi (Admin, Head, Karyawan/Petugas).
     */
    public function index(Request $request)
    {
        // Eager load relasi brand, category, dan type untuk tampilan daftar
        $query = InventoryItem::with(['brand', 'category', 'type']);

        // Contoh filter/pencarian
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('item_code', 'like', '%' . $search . '%');
            });
        }
        // Tambahkan filter lain jika diperlukan (misal berdasarkan brand_id, category_id, type_id)

        $inventoryItems = $query->get();
        return response()->json($inventoryItems);
    }

    /**
     * Store a newly created resource in storage (Menambah master barang baru).
     * Hanya dapat diakses oleh Admin.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'item_code' => 'required|string|max:255|unique:inventory_items,item_code',
                'name' => 'required|string|max:255',
                'quantity' => 'required|integer|min:0', // Jumlah stok dari jenis barang ini
                'brand_id' => 'required|exists:brands,id',
                'category_id' => 'required|exists:categories,id',
                'type_id' => 'required|exists:item_types,id',
                'manufacturer' => 'nullable|string|max:255',
                'manufacture_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'isbn' => 'nullable|string|max:255|unique:inventory_items,isbn', // ISBN opsional dan unik
                'image' => 'nullable|image|max:2048', // Maks 2MB, field 'image' untuk upload
            ]);

            // Handle image upload jika ada
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('inventory_item_images', 'public');
                $validatedData['image_path'] = $imagePath; // Simpan path di kolom image_path
            } else {
                $validatedData['image_path'] = null;
            }
            // Hapus 'image' dari validatedData agar tidak mencoba disimpan ke DB jika kolomnya tidak ada
            unset($validatedData['image']); 

            $inventoryItem = InventoryItem::create($validatedData);
            return response()->json($inventoryItem, 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah master barang: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource (Menampilkan detail satu master barang).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function show($id)
    {
        // Eager load semua relasi yang relevan untuk detail
        $inventoryItem = InventoryItem::with(['brand', 'category', 'type'])->find($id);
        if (!$inventoryItem) {
            return response()->json(['message' => 'Master barang tidak ditemukan'], 404);
        }
        return response()->json($inventoryItem);
    }

    /**
     * Update the specified resource in storage (Memperbarui master barang).
     * Hanya dapat diakses oleh Admin.
     */
    public function update(Request $request, $id)
    {
        $inventoryItem = InventoryItem::find($id);
        if (!$inventoryItem) {
            return response()->json(['message' => 'Master barang tidak ditemukan'], 404);
        }

        try {
            $validatedData = $request->validate([
                'item_code' => 'required|string|max:255|unique:inventory_items,item_code,' . $id,
                'name' => 'required|string|max:255',
                'quantity' => 'required|integer|min:0',
                'brand_id' => 'required|exists:brands,id',
                'category_id' => 'required|exists:categories,id',
                'type_id' => 'required|exists:item_types,id',
                'manufacturer' => 'nullable|string|max:255',
                'manufacture_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'isbn' => 'nullable|string|max:255|unique:inventory_items,isbn,' . $id,
                'image' => 'nullable|image|max:2048', // Field 'image' untuk upload
            ]);

            // Handle image upload jika ada
            if ($request->hasFile('image')) {
                // Hapus gambar lama jika ada
                if ($inventoryItem->image_path) {
                    Storage::disk('public')->delete($inventoryItem->image_path);
                }
                $imagePath = $request->file('image')->store('inventory_item_images', 'public');
                $validatedData['image_path'] = $imagePath;
            } else {
                // Jika tidak ada file baru diupload, tapi ada request untuk menghapus/mengosongkan gambar
                // Contoh: jika frontend mengirim 'image_path' : null
                if (array_key_exists('image_path', $request->all()) && $request->image_path === null) {
                    if ($inventoryItem->image_path) {
                        Storage::disk('public')->delete($inventoryItem->image_path);
                    }
                    $validatedData['image_path'] = null;
                }
            }
            // Hapus 'image' dari validatedData
            unset($validatedData['image']);

            $inventoryItem->update($validatedData);
            return response()->json($inventoryItem);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui master barang: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Menghapus master barang).
     * Hanya dapat diakses oleh Admin.
     */
    public function destroy($id)
    {
        $inventoryItem = InventoryItem::find($id);
        if (!$inventoryItem) {
            return response()->json(['message' => 'Master barang tidak ditemukan'], 404);
        }

        try {
            // Hapus gambar terkait jika ada
            if ($inventoryItem->image_path) {
                Storage::disk('public')->delete($inventoryItem->image_path);
            }
            $inventoryItem->delete();
            return response()->json(['message' => 'Master barang berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus master barang: ' . $e->getMessage()], 500);
        }
    }
}
