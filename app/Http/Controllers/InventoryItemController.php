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
    public function reserveSlot($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->increment('quantity');
        return response()->json(['quantity' => $item->quantity]);
    }

    public function autoIncrementQuantity($id)
    {
        InventoryItem::where('id', $id)->increment('quantity');
    }

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
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'type_id'  => 'required|exists:item_types,id',
            'manufacture_year' => 'nullable|integer|min:1900|max:' . now()->year,
            'isbn'     => 'nullable|string|max:255|unique:inventory_items,isbn',
            'image'    => 'nullable|image|max:2048',
        ]);

        // 1. generate kode barang
        $last = InventoryItem::latest('id')->first();
        $next = $last ? ((int) str_replace('ITM-', '', $last->item_code)) + 1 : 1;
        $validated['item_code'] = 'ITM-' . str_pad($next, 3, '0', STR_PAD_LEFT);

        // 2. otomatis isi manufacturer dari brand
        $validated['manufacturer'] = \App\Models\Brand::findOrFail($validated['brand_id'])->name;

        // 3. upload gambar jika ada
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('inventory_item_images', 'public');
        } else {
            $validated['image_path'] = null;
        }

        $item = InventoryItem::create($validated);
        return response()->json($item->load(['brand', 'category', 'type']), 201);

    } catch (ValidationException $e) {
        return response()->json(['message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Gagal menambah master barang: ' . $e->getMessage()], 500);
    }
}

/**
 * HEAD: Naikkan quantity master +1
 * POST /inventory-items/{id}/increase-quantity
 */
public function increaseQuantity($id)
{
    $item = InventoryItem::findOrFail($id);
    $item->increment('quantity');
    return response()->json(['quantity' => $item->quantity]);
}

/**
 * HEAD: Lihat daftar slot kosong
 * GET /inventory-items/{id}/empty-slots
 */
public function getEmptySlots($id)
{
    $item = InventoryItem::findOrFail($id);

    // Hitung jumlah unit fisik yang sudah dibuat dari inventory_item ini
    $totalUnit = \App\Models\Inventory::where('inventory_item_id', $item->id)->count();

    $emptySlots = max(0, $item->quantity - $totalUnit);

    return response()->json([
        'inventory_item_id' => $item->id,
        'quantity'          => $item->quantity,
        'total_unit'        => $totalUnit,
        'empty_slots'       => $emptySlots,
    ]);
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
    $item = InventoryItem::findOrFail($id);

    try {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'type_id'  => 'required|exists:item_types,id',
            'manufacture_year' => 'nullable|integer|min:1900|max:' . now()->year,
            'isbn'     => 'nullable|string|max:255|unique:inventory_items,isbn,' . $id,
            'image'    => 'nullable|image|max:2048',
        ]);

        // 1. otomatis isi manufacturer dari brand
        $validated['manufacturer'] = \App\Models\Brand::findOrFail($validated['brand_id'])->name;

        // 2. upload & hapus gambar lama jika ada file baru
        if ($request->hasFile('image')) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('inventory_item_images', 'public');
        } elseif ($request->boolean('remove_image')) {
            // opsional: frontend kirim flag remove_image = true
            Storage::disk('public')->delete($item->image_path);
            $validated['image_path'] = null;
        }

        $item->update($validated);
        return response()->json($item->load(['brand', 'category', 'type']));

    } catch (ValidationException $e) {
        return response()->json(['message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
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
    $item = InventoryItem::find($id);
    if (!$item) {
        return response()->json(['message' => 'Master barang tidak ditemukan'], 404);
    }

    try {
        // 1. Hapus gambar lokal (storage/public)
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }

        // 2. Hapus gambar di Cloudinary (jika pakai CloudinaryLabs)
        if ($item->image_path && str_contains($item->image_path, 'res.cloudinary.com')) {
            // extract public_id dari URL full
            $path = parse_url($item->image_path, PHP_URL_PATH);   // /v12345678/folder/name.jpg
            $segments = explode('/', trim($path, '/'));          // [v12345678, folder, name.jpg]
            $publicId = pathinfo(end($segments), PATHINFO_FILENAME); // name
            $folder   = $segments[count($segments) - 2];         // folder

            Cloudinary::destroy("{$folder}/{$publicId}");
        }

        $item->delete();

        return response()->json(['message' => 'Master barang berhasil dihapus'], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Gagal menghapus master barang: ' . $e->getMessage()], 500);
    }
}
}
