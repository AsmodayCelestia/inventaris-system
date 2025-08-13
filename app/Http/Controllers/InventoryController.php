<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryItem;   // <─ tambahan
use App\Models\User;            // <─ tambahan
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class InventoryController extends Controller
{
    /* -----------------------------------------------------------------
       |  METHOD LAMA (TIDAK BERUBAH)
       ----------------------------------------------------------------- */

public function index(Request $request)
{
    $query = Inventory::with(['item', 'room', 'unit']);

    if ($request->has('inventory_item_id')) {
        $query->where('inventory_item_id', $request->inventory_item_id);
    }

    return response()->json($query->get());
}

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'inventory_number' => 'required|string|unique:inventories',
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'acquisition_source' => 'required|in:Beli,Hibah,Bantuan,-',
            'procurement_date' => 'required|date',
            'purchase_price' => 'required|numeric',
            'estimated_depreciation' => 'nullable|numeric',
            'status' => 'required|in:Ada,Rusak,Perbaikan,Hilang,Dipinjam,-',
            'unit_id' => 'required|exists:location_units,id',
            'room_id' => 'required|exists:rooms,id',
            'expected_replacement' => 'nullable|date',
            'last_checked_at' => 'nullable|date',
            'pj_id' => 'nullable|exists:users,id',
            'maintenance_frequency_type' => 'nullable|in:bulan,km,minggu,semester',
            'maintenance_frequency_value' => 'nullable|integer',
            'last_maintenance_at' => 'nullable|date',
            'next_due_date' => 'nullable|date',
            'next_due_km' => 'nullable|integer',
            'last_odometer_reading' => 'nullable|integer',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            try {
                $uploadedFile = $request->file('image');
                $result = Cloudinary::upload($uploadedFile->getRealPath(), [
                    'folder' => 'inventories_images',
                    'public_id' => Str::slug($request->inventory_number . '-' . time()),
                ]);
                $imagePath = $result->getSecurePath();
            } catch (\Exception $e) {
                \Log::error('Cloudinary upload error: ' . $e->getMessage());
                return response()->json(['message' => 'Gagal mengunggah gambar'], 500);
            }
        }

        $inventoryData = array_diff_key($validatedData, array_flip(['image']));
        $inventory = Inventory::create($inventoryData);

        // Auto-increment quantity master (untuk jalur ADMIN yg lewat /inventories)
        InventoryItem::where('id', $validatedData['inventory_item_id'])
                     ->increment('quantity');

        // QR-code sengaja TIDAK digenerate otomatis
        $inventory->image_path   = $imagePath;
        $inventory->qr_code_path = null;   // kita set null dulu
        $inventory->save();

        return response()->json($inventory->load(['item', 'room', 'unit']), 201);
    }

    public function show(Inventory $inventory)
    {
        $inventory->load(['item', 'room', 'room.locationPersonInCharge', 'unit', 'personInCharge', 'maintenances']);
        return response()->json($inventory);
    }

    public function update(Request $request, $id)
    {
        $inventory = Inventory::findOrFail($id);

        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Anda tidak memiliki akses untuk mengedit QR Code.'], 403);
        }

        $validatedData = $request->validate([
            'inventory_number' => 'required|string|unique:inventories,inventory_number,' . $id,
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'acquisition_source' => 'required|in:Beli,Hibah,Bantuan,-',
            'procurement_date' => 'required|date',
            'purchase_price' => 'required|numeric',
            'estimated_depreciation' => 'nullable|numeric',
            'status' => 'required|in:Ada,Rusak,Perbaikan,Hilang,Dipinjam,-',
            'unit_id' => 'required|exists:location_units,id',
            'room_id' => 'required|exists:rooms,id',
            'expected_replacement' => 'nullable|date',
            'last_checked_at' => 'nullable|date',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'nullable|in:0,1',
        ]);

        if ($request->has('remove_image') && $request->remove_image == '1' && $inventory->image_path) {
            $publicId = pathinfo(parse_url($inventory->image_path, PHP_URL_PATH), PATHINFO_FILENAME);
            Cloudinary::destroy('inventories_images/' . $publicId);
            $inventory->image_path = null;
        }

        if ($request->hasFile('image')) {
            if ($inventory->image_path) {
                $oldPublicId = pathinfo(parse_url($inventory->image_path, PHP_URL_PATH), PATHINFO_FILENAME);
                Cloudinary::destroy('inventories_images/' . $oldPublicId);
            }
            $uploadedImage = Cloudinary::upload($request->file('image')->getRealPath(), [
                'folder' => 'inventories_images'
            ]);
            $inventory->image_path = $uploadedImage->getSecurePath();
        }

        $inventory->fill(array_diff_key($validatedData, array_flip(['image', 'remove_image'])));
        $inventory->save();

        return response()->json([
            'message' => 'Inventory updated successfully',
            'data'    => $inventory->load(['item', 'room', 'unit']),
        ]);
    }

    public function updateSchedule(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'pj_id'                       => 'nullable|exists:users,id',
            'maintenance_frequency_type'  => 'nullable|in:bulan,km,minggu,semester',
            'maintenance_frequency_value' => 'nullable|integer',
            'last_maintenance_at'         => 'nullable|date',
            'next_due_date'               => 'nullable|date',
            'next_due_km'                 => 'nullable|integer',
            'last_odometer_reading'       => 'nullable|integer',
        ]);

        $inventory->update($validated);

        if ($request->pj_id) {
            User::where('id', $request->pj_id)
                ->where('is_pj_maintenance', false)
                ->update(['is_pj_maintenance' => true]);
        }

        return response()->json(['message' => 'Schedule updated successfully.']);
    }

    public function supervisedByMe()
    {
        $user = auth()->user();
        $inventories = Inventory::whereHas('room', fn ($q) => $q->where('supervisor_id', $user->id))->get();
        return response()->json($inventories);
    }

    public function destroy(Inventory $inventory)
    {
        try {
            if ($inventory->image_path) {
                $publicId = pathinfo(parse_url($inventory->image_path, PHP_URL_PATH), PATHINFO_FILENAME);
                Cloudinary::destroy('inventories_images/' . $publicId);
            }
            if ($inventory->qr_code_path) {
                $publicIdQr = pathinfo(parse_url($inventory->qr_code_path, PHP_URL_PATH), PATHINFO_FILENAME);
                Cloudinary::destroy('inventories_qrcodes/' . $publicIdQr);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to delete Cloudinary assets: ' . $e->getMessage());
        }
        $inventory->delete();
        return response()->json(['message' => 'Inventaris berhasil dihapus.']);
    }

    public function showByQrCode($inventoryNumber)
    {
        $inventory = Inventory::with(['item', 'room', 'unit', 'personInCharge', 'maintenances'])
            ->where('inventory_number', $inventoryNumber)
            ->firstOrFail();
        return response()->json($inventory);
    }

    /* -----------------------------------------------------------------
       |  QR-CODE MANUAL (TETAP ADA, TIDAK OTOMATIS)
       ----------------------------------------------------------------- */
    public function createQrCode(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Tidak memiliki akses.'], 403);
        }

        $inventory = Inventory::findOrFail($request->inventory_id);
        if ($inventory->qr_code_path) {
            return response()->json(['error' => 'QR Code sudah ada.'], 400);
        }

        try {
            $detailUrl = config('app.url') . '/inventories/' . $inventory->id;
            $qrResult  = Builder::create()
                ->writer(new PngWriter())
                ->data($detailUrl)
                ->size(500)
                ->margin(10)
                ->build();

            $qrFile = 'qrcodes/inventories/' . $inventory->inventory_number . '-' . Str::random(10) . '.png';
            Storage::disk('public')->put($qrFile, $qrResult->getString());

            $cloud = Cloudinary::upload(storage_path('app/public/' . $qrFile), [
                'folder'    => 'inventories_qrcodes',
                'public_id' => 'qr-' . Str::slug($inventory->inventory_number) . '-' . Str::random(8),
            ]);
            Storage::disk('public')->delete($qrFile);

            $inventory->qr_code_path = $cloud->getSecurePath();
            $inventory->save();

            return response()->json(['message' => 'QR Code berhasil dibuat.', 'data' => $inventory->load(['item', 'room', 'unit'])]);
        } catch (\Exception $e) {
            \Log::error('QR Code error: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal membuat QR Code.'], 500);
        }
    }

    public function updateQrCode(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Tidak memiliki akses.'], 403);
        }

        $inventory = Inventory::findOrFail($id);
        if (!$inventory->qr_code_path) {
            return response()->json(['error' => 'QR Code belum ada.'], 400);
        }

        try {
            $detailUrl = config('app.url') . '/inventories/' . $inventory->id;
            $qrResult  = Builder::create()
                ->writer(new PngWriter())
                ->data($detailUrl)
                ->size(500)
                ->margin(10)
                ->build();

            $qrFile = 'qrcodes/inventories/' . $inventory->inventory_number . '-' . Str::random(10) . '.png';
            Storage::disk('public')->put($qrFile, $qrResult->getString());

            $cloud = Cloudinary::upload(storage_path('app/public/' . $qrFile), [
                'folder'    => 'inventories_qrcodes',
                'public_id' => 'qr-' . Str::slug($inventory->inventory_number) . '-' . Str::random(8),
            ]);
            Storage::disk('public')->delete($qrFile);

            $inventory->qr_code_path = $cloud->getSecurePath();
            $inventory->save();

            return response()->json(['message' => 'QR Code berhasil diperbarui.', 'data' => $inventory->load(['item', 'room', 'unit'])]);
        } catch (\Exception $e) {
            \Log::error('QR Code error: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal memperbarui QR Code.'], 500);
        }
    }

    public function deleteQrCode(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Tidak memiliki akses.'], 403);
        }

        $inventory = Inventory::findOrFail($id);
        if (!$inventory->qr_code_path) {
            return response()->json(['error' => 'QR Code tidak ada.'], 400);
        }

        try {
            $publicIdQr = pathinfo(parse_url($inventory->qr_code_path, PHP_URL_PATH), PATHINFO_FILENAME);
            Cloudinary::destroy('inventories_qrcodes/' . $publicIdQr);
            $inventory->qr_code_path = null;
            $inventory->save();
            return response()->json(['message' => 'QR Code berhasil dihapus.']);
        } catch (\Exception $e) {
            \Log::error('Failed to delete QR Code: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menghapus QR Code.'], 500);
        }
    }

    /* -----------------------------------------------------------------
       |  METHOD BARU UNTUK FLOW HEAD (quantity sudah naik di master)
       ----------------------------------------------------------------- */
    public function storeFromSlot(Request $request, $inventoryItem)
    {
        $item = InventoryItem::findOrFail($inventoryItem);

        // Hitung slot kosong
        $totalUnit = Inventory::where('inventory_item_id', $item->id)->count();
        if ($totalUnit >= $item->quantity) {
            return response()->json(['message' => 'Slot kosong habis'], 422);
        }

        // Generate nomor inventaris
        $running = $totalUnit + 1;
        $inventoryNumber = $item->item_code . '-' . str_pad($running, 3, '0', STR_PAD_LEFT);

        // Validasi input
        $validated = $request->validate([
            'acquisition_source' => 'required|in:Beli,Hibah,Bantuan,-',
            'procurement_date'   => 'required|date',
            'purchase_price'     => 'required|numeric',
            'estimated_depreciation' => 'nullable|numeric',
            'status'             => 'required|in:Ada,Rusak,Perbaikan,Hilang,Dipinjam,-',
            'unit_id'            => 'required|exists:location_units,id',
            'room_id'            => 'required|exists:rooms,id',
            'expected_replacement' => 'nullable|date',
            'last_checked_at'    => 'nullable|date',
            'pj_id'              => 'nullable|exists:users,id',
            'maintenance_frequency_type'  => 'nullable|in:bulan,km,minggu,semester',
            'maintenance_frequency_value' => 'nullable|integer',
            'last_maintenance_at' => 'nullable|date',
            'next_due_date'      => 'nullable|date',
            'next_due_km'        => 'nullable|integer',
            'last_odometer_reading' => 'nullable|integer',
            'description'        => 'nullable|string',
            'image'              => 'nullable|image|max:2048',
        ]);

        // Upload image (reuse logic)
        $imagePath = null;
        if ($request->hasFile('image')) {
            try {
                $uploadedFile = $request->file('image');
                $result = Cloudinary::upload($uploadedFile->getRealPath(), [
                    'folder' => 'inventories_images',
                    'public_id' => Str::slug($inventoryNumber . '-' . time()),
                ]);
                $imagePath = $result->getSecurePath();
            } catch (\Exception $e) {
                \Log::error('Cloudinary upload error: ' . $e->getMessage());
                return response()->json(['message' => 'Gagal mengunggah gambar'], 500);
            }
        }

        // QR-code sengaji kosong
        $inventory = Inventory::create([
            ...$validated,
            'inventory_number' => $inventoryNumber,
            'inventory_item_id'=> $item->id,
            'image_path'       => $imagePath,
            'qr_code_path'     => null,
        ]);

        return response()->json($inventory->load(['item', 'room', 'unit']), 201);
    }
}