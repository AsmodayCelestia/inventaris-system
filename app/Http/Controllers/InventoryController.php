<?php

namespace App\Http\Controllers;

use App\Models\Inventory; // Asumsi kamu punya model Inventory
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage; // Untuk handle upload gambar jika diaktifkan

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource (Daftar semua inventaris).
     * Dapat diakses oleh semua user yang terautentikasi (Admin, Head, Karyawan/Petugas).
     */
    public function index(Request $request)
    {
        // Eager load relasi yang dibutuhkan untuk tampilan daftar
        $query = Inventory::with(['item', 'unit', 'room', 'personInCharge']);

        // Contoh filter berdasarkan status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Contoh pencarian berdasarkan nama atau kode inventaris
        if ($request->has('searchQuery') && $request->searchQuery !== '') {
            $search = $request->searchQuery;
            $query->where(function ($q) use ($search) {
                $q->where('inventory_number', 'like', '%' . $search . '%')
                  ->orWhereHas('item', function ($q_item) use ($search) { // Cari di relasi item
                      $q_item->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Tambahkan filter lain sesuai kebutuhan (locationUnit, floor, room, dll.)
        if ($request->has('unit_id') && $request->unit_id !== '') {
            $query->where('unit_id', $request->unit_id);
        }
        if ($request->has('room_id') && $request->room_id !== '') {
            $query->where('room_id', $request->room_id);
        }
        // Jika ingin filter berdasarkan floor, harus join ke rooms lalu ke floors
        // atau pastikan ada relasi di model Room ke Floor
        // Contoh:
        // if ($request->has('floor_id') && $request->floor_id !== '') {
        //     $query->whereHas('room.floor', function ($q_floor) use ($request) {
        //         $q_floor->where('id', $request->floor_id);
        //     });
        // }


        $inventories = $query->get(); 
        return response()->json($inventories);
    }

    /**
     * Store a newly created resource in storage (Menambah inventaris baru).
     * Hanya dapat diakses oleh Admin atau Head.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'inventory_number' => 'required|string|max:255|unique:inventories,inventory_number',
                'inventory_item_id' => 'required|exists:inventory_items,id', // Relasi ke InventoryItem
                'acquisition_source' => 'required|string|max:255', 
                'procurement_date' => 'required|date',
                'purchase_price' => 'required|numeric|min:0',
                'estimated_depreciation' => 'required|numeric|min:0',
                'status' => 'required|string|in:Tersedia,Rusak,Dalam Perbaikan,Hilang,Dipinjam,Tidak Tersedia', 
                'unit_id' => 'required|exists:location_units,id', // Relasi ke LocationUnit
                'room_id' => 'required|exists:rooms,id', // Relasi ke Room
                'expected_replacement' => 'nullable|date|after_or_equal:procurement_date',
                'last_checked_at' => 'nullable|date',
                'pj_id' => 'nullable|exists:users,id', // Penanggung jawab (relasi ke User)
                'maintenance_frequency_type' => 'nullable|string|in:bulan,minggu,semester,km',
                'maintenance_frequency_value' => 'nullable|numeric|min:0',
                'last_maintenance_at' => 'nullable|date',
                'next_due_date' => 'nullable|date',
                'next_due_km' => 'nullable|numeric',
                // 'image' => 'nullable|image|max:2048', // Jika ada upload gambar
            ]);

            // Handle image upload jika ada
            // if ($request->hasFile('image')) {
            //     $imagePath = $request->file('image')->store('inventory_images', 'public');
            //     $validatedData['image_path'] = $imagePath; // Simpan path gambar di kolom yang sesuai
            // }

            $inventory = Inventory::create($validatedData);
            return response()->json($inventory, 201); 
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422); 
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah inventaris: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource (Menampilkan detail satu inventaris).
     * Dapat diakses oleh semua user yang terautentikasi (Admin, Head, Karyawan/Petugas).
     */
    public function show($id)
    {
        // Eager load semua relasi yang relevan untuk detail
        $inventory = Inventory::with(['item', 'unit', 'room', 'personInCharge', 'maintenances'])->find($id); 
        if (!$inventory) {
            return response()->json(['message' => 'Inventaris tidak ditemukan'], 404);
        }
        return response()->json($inventory);
    }

    /**
     * Display the specified resource by QR Code (Menampilkan detail inventaris berdasarkan nomor inventaris/QR Code).
     * Dapat diakses oleh Petugas/Admin/Head setelah scan.
     */
    public function showByQrCode($inventoryNumber)
    {
        // Eager load semua relasi yang relevan untuk detail QR
        $inventory = Inventory::with(['item', 'unit', 'room', 'personInCharge', 'maintenances'])
                              ->where('inventory_number', $inventoryNumber) // Sesuaikan dengan kolom no_inventaris
                              ->first();
        if (!$inventory) {
            return response()->json(['message' => 'Inventaris dengan QR Code ini tidak ditemukan'], 404);
        }
        return response()->json($inventory);
    }

    /**
     * Update the specified resource in storage (Memperbarui inventaris).
     * Hanya dapat diakses oleh Admin atau Head.
     */
    public function update(Request $request, $id)
    {
        $inventory = Inventory::find($id);
        if (!$inventory) {
            return response()->json(['message' => 'Inventaris tidak ditemukan'], 404);
        }

        try {
            $validatedData = $request->validate([
                'inventory_number' => 'required|string|max:255|unique:inventories,inventory_number,' . $id,
                'inventory_item_id' => 'required|exists:inventory_items,id',
                'acquisition_source' => 'required|string|max:255', 
                'procurement_date' => 'required|date',
                'purchase_price' => 'required|numeric|min:0',
                'estimated_depreciation' => 'required|numeric|min:0',
                'status' => 'required|string|in:Tersedia,Rusak,Dalam Perbaikan,Hilang,Dipinjam,Tidak Tersedia',
                'unit_id' => 'required|exists:location_units,id',
                'room_id' => 'required|exists:rooms,id',
                'expected_replacement' => 'nullable|date|after_or_equal:procurement_date',
                'last_checked_at' => 'nullable|date',
                'pj_id' => 'nullable|exists:users,id',
                'maintenance_frequency_type' => 'nullable|string|in:bulan,minggu,semester,km',
                'maintenance_frequency_value' => 'nullable|numeric|min:0',
                'last_maintenance_at' => 'nullable|date',
                'next_due_date' => 'nullable|date',
                'next_due_km' => 'nullable|numeric',
                // 'image' => 'nullable|image|max:2048', // Jika ada upload gambar
            ]);

            // Handle image upload jika ada
            // if ($request->hasFile('image')) {
            //     // Hapus gambar lama jika ada
            //     // if ($inventory->image_path) {
            //     //     Storage::disk('public')->delete($inventory->image_path);
            //     // }
            //     $imagePath = $request->file('image')->store('inventory_images', 'public');
            //     $validatedData['image_path'] = $imagePath;
            // }

            $inventory->update($validatedData);
            return response()->json($inventory);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui inventaris: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Menghapus inventaris).
     * Hanya dapat diakses oleh Admin atau Head.
     */
    public function destroy($id)
    {
        $inventory = Inventory::find($id);
        if (!$inventory) {
            return response()->json(['message' => 'Inventaris tidak ditemukan'], 404);
        }

        try {
            // Hapus gambar terkait jika ada
            // if ($inventory->image_path) {
            //     Storage::disk('public')->delete($inventory->image_path);
            // }
            $inventory->delete();
            return response()->json(['message' => 'Inventaris berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus inventaris: ' . $e->getMessage()], 500);
        }
    }
}
