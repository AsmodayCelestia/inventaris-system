<?php

namespace App\Http\Controllers;

use App\Models\InventoryMaintenance; // Asumsi kamu punya model InventoryMaintenance
use App\Models\Inventory; // Perlu untuk update last_maintenance_at di Inventory
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth; // Untuk mendapatkan user yang sedang login
use Illuminate\Support\Facades\Storage; // Untuk handle upload foto

class MaintenanceController extends Controller
{
    /**
     * Display a listing of the resource (Daftar semua riwayat maintenance).
     * Dapat diakses oleh semua user yang terautentikasi.
     * Bisa difilter berdasarkan inventory_id atau lainnya.
     */
    public function index(Request $request)
    {
        $query = InventoryMaintenance::with(['inventory.item', 'createdBy']); // Eager load relasi

        if ($request->has('inventory_id') && $request->inventory_id !== '') {
            $query->where('inventory_id', $request->inventory_id);
        }

        // Urutkan berdasarkan tanggal pemeriksaan terbaru
        $maintenances = $query->orderBy('inspection_date', 'desc')->get();

        return response()->json($maintenances);
    }

    /**
     * Store a newly created resource in storage (Menambah riwayat maintenance baru).
     * Dapat diakses oleh Admin, Head, atau Karyawan/Petugas.
     */
    public function store(Request $request, $inventoryId)
    {
        try {
            $validatedData = $request->validate([
                'inspection_date' => 'required|date',
                'issue_found' => 'nullable|string',
                'solution_taken' => 'nullable|string',
                'notes' => 'nullable|string',
                'status' => 'required|string|in:planning,done', // Sesuaikan status
                'photo_1' => 'nullable|image|max:2048', // Maks 2MB
                'photo_2' => 'nullable|image|max:2048',
                'photo_3' => 'nullable|image|max:2048',
            ]);

            // Pastikan inventarisnya ada
            $inventory = Inventory::find($inventoryId);
            if (!$inventory) {
                return response()->json(['message' => 'Inventaris tidak ditemukan'], 404);
            }

            $validatedData['inventory_id'] = $inventoryId;
            $validatedData['user_id'] = Auth::id(); // Otomatis mengisi user_id dari user yang login

            // Handle photo uploads
            for ($i = 1; $i <= 3; $i++) {
                $photoField = 'photo_' . $i;
                if ($request->hasFile($photoField)) {
                    $photoPath = $request->file($photoField)->store('maintenance_photos', 'public');
                    $validatedData[$photoField] = $photoPath;
                } else {
                    $validatedData[$photoField] = null; // Pastikan null jika tidak ada foto
                }
            }

            $maintenance = InventoryMaintenance::create($validatedData);

            // Update last_maintenance_at di tabel Inventory jika statusnya 'done'
            if ($maintenance->status === 'done') {
                $inventory->last_maintenance_at = $maintenance->inspection_date;
                // Jika kamu punya logika untuk next_due_date/km di Inventory, panggil di sini
                // $inventory->next_due_date = $inventory->calculateNextDue()['value']; // Contoh
                $inventory->save();
            }

            return response()->json($maintenance, 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah riwayat maintenance: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource (Menampilkan detail satu riwayat maintenance).
     * Dapat diakses oleh semua user yang terautentikasi.
     */
    public function show($id)
    {
        $maintenance = InventoryMaintenance::with(['inventory.item', 'createdBy'])->find($id); // Eager load relasi
        if (!$maintenance) {
            return response()->json(['message' => 'Riwayat maintenance tidak ditemukan'], 404);
        }
        return response()->json($maintenance);
    }

    /**
     * Update the specified resource in storage (Memperbarui riwayat maintenance).
     * Hanya dapat diakses oleh Admin atau Head.
     */
    public function update(Request $request, $id)
    {
        $maintenance = InventoryMaintenance::find($id);
        if (!$maintenance) {
            return response()->json(['message' => 'Riwayat maintenance tidak ditemukan'], 404);
        }

        try {
            $validatedData = $request->validate([
                'inspection_date' => 'required|date',
                'issue_found' => 'nullable|string',
                'solution_taken' => 'nullable|string',
                'notes' => 'nullable|string',
                'status' => 'required|string|in:planning,done',
                'photo_1' => 'nullable|image|max:2048',
                'photo_2' => 'nullable|image|max:2048',
                'photo_3' => 'nullable|image|max:2048',
            ]);

            // Handle photo uploads (hapus yang lama, simpan yang baru)
            for ($i = 1; $i <= 3; $i++) {
                $photoField = 'photo_' . $i;
                if ($request->hasFile($photoField)) {
                    // Hapus foto lama jika ada
                    if ($maintenance->$photoField) {
                        Storage::disk('public')->delete($maintenance->$photoField);
                    }
                    $photoPath = $request->file($photoField)->store('maintenance_photos', 'public');
                    $validatedData[$photoField] = $photoPath;
                } 
                // Jika request tidak memiliki file untuk photoField, tapi ada nilai di request (misal null untuk hapus foto)
                // else if (array_key_exists($photoField, $request->all())) {
                //     if ($maintenance->$photoField) {
                //         Storage::disk('public')->delete($maintenance->$photoField);
                //     }
                //     $validatedData[$photoField] = null;
                // }
            }

            $maintenance->update($validatedData);

            // Update last_maintenance_at di tabel Inventory jika statusnya 'done'
            if ($maintenance->status === 'done') {
                $inventory = $maintenance->inventory;
                if ($inventory) {
                    $inventory->last_maintenance_at = $maintenance->inspection_date;
                    // $inventory->next_due_date = $inventory->calculateNextDue()['value']; // Contoh
                    $inventory->save();
                }
            }

            return response()->json($maintenance);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui riwayat maintenance: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Menghapus riwayat maintenance).
     * Hanya dapat diakses oleh Admin atau Head.
     */
    public function destroy($id)
    {
        $maintenance = InventoryMaintenance::find($id);
        if (!$maintenance) {
            return response()->json(['message' => 'Riwayat maintenance tidak ditemukan'], 404);
        }

        try {
            // Hapus foto terkait jika ada
            for ($i = 1; $i <= 3; $i++) {
                $photoField = 'photo_' . $i;
                if ($maintenance->$photoField) {
                    Storage::disk('public')->delete($maintenance->$photoField);
                }
            }
            $maintenance->delete();
            return response()->json(['message' => 'Riwayat maintenance berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus riwayat maintenance: ' . $e->getMessage()], 500);
        }
    }
}
