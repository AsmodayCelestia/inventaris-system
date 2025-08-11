<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;
use App\Models\User;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class InventoryController extends Controller
{
    public function index()
    {
        $inventories = Inventory::with(['item', 'room', 'unit'])->get();
        return response()->json($inventories);
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

        // QR Code generation
        $qrCodePath = null;
        try {
            $detailUrl = config('app.url') . '/inventories/' . $inventory->id;

            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($detailUrl)
                ->size(500)
                ->margin(10)
                ->build();

            $qrCodeFileName = 'qrcodes/inventories/' . $inventory->inventory_number . '-' . Str::random(10) . '.png';
            Storage::disk('public')->put($qrCodeFileName, $qrResult->getString());

            $resultQr = Cloudinary::upload(storage_path('app/public/' . $qrCodeFileName), [
                'folder' => 'inventories_qrcodes',
                'public_id' => 'qr-' . Str::slug($inventory->inventory_number) . '-' . Str::random(8),
            ]);
            $qrCodePath = $resultQr->getSecurePath();
            Storage::disk('public')->delete($qrCodeFileName);
        } catch (\Exception $e) {
            \Log::error('QR Code error: ' . $e->getMessage());
        }

        $inventory->image_path = $imagePath;
        $inventory->qr_code_path = $qrCodePath;
        $inventory->save();

        return response()->json($inventory->load(['item', 'room', 'unit']), 201);
    }

    public function show(Inventory $inventory)
    {
        $inventory->load(['item', 'room', 'unit', 'personInCharge', 'maintenances']);
        return response()->json($inventory);
    }

public function update(Request $request, $id)
{
    $inventory = Inventory::findOrFail($id);

    // Cek apakah user memiliki hak akses untuk edit QR Code
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
        'data' => $inventory->load(['item', 'room', 'unit']),
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

    // ⚡ Auto-flag
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

        $inventories = Inventory::whereHas('room', function ($query) use ($user) {
            $query->where('supervisor_id', $user->id);
        })->get();

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

    public function createQrCode(Request $request)
    {
        // Cek apakah user adalah admin
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.'], 403);
        }

        $inventory = Inventory::findOrFail($request->inventory_id);

        // Cek kalau QR Code udah ada
        if ($inventory->qr_code_path) {
            return response()->json(['error' => 'QR Code sudah ada dan tidak dapat diubah.'], 400);
        }

        try {
            $qrCodeData = config('app.url') . '/inventories/' . $inventory->id;
            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($qrCodeData)
                ->size(500)
                ->margin(10)
                ->build();

            $qrCodeFileName = 'qrcodes/inventories/' . $inventory->inventory_number . '-' . Str::random(10) . '.png';
            Storage::disk('public')->put($qrCodeFileName, $qrResult->getString());

            $resultQr = Cloudinary::upload(storage_path('app/public/' . $qrCodeFileName), [
                'folder' => 'inventories_qrcodes',
                'public_id' => 'qr-' . Str::slug($inventory->inventory_number) . '-' . Str::random(8),
            ]);
            $qrCodePath = $resultQr->getSecurePath();
            Storage::disk('public')->delete($qrCodeFileName);

            $inventory->qr_code_path = $qrCodePath;
            $inventory->save();

            return response()->json(['message' => 'QR Code berhasil dibuat.', 'data' => $inventory->load(['item', 'room', 'unit'])]);
        } catch (\Exception $e) {
            \Log::error('QR Code error: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal membuat QR Code.'], 500);
        }
    }

    public function updateQrCode(Request $request, $id)
    {
        // Cek apakah user adalah admin
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.'], 403);
        }

        $inventory = Inventory::findOrFail($id);

        // Cek kalau QR Code udah ada
        if (!$inventory->qr_code_path) {
            return response()->json(['error' => 'QR Code belum ada. Silakan bikin QR Code terlebih dahulu.'], 400);
        }

        try {
            $qrCodeData = config('app.url') . '/inventories/' . $inventory->id;
            $qrResult = Builder::create()
                ->writer(new PngWriter())
                ->data($qrCodeData)
                ->size(500)
                ->margin(10)
                ->build();

            $qrCodeFileName = 'qrcodes/inventories/' . $inventory->inventory_number . '-' . Str::random(10) . '.png';
            Storage::disk('public')->put($qrCodeFileName, $qrResult->getString());

            $resultQr = Cloudinary::upload(storage_path('app/public/' . $qrCodeFileName), [
                'folder' => 'inventories_qrcodes',
                'public_id' => 'qr-' . Str::slug($inventory->inventory_number) . '-' . Str::random(8),
            ]);
            $qrCodePath = $resultQr->getSecurePath();
            Storage::disk('public')->delete($qrCodeFileName);

            $inventory->qr_code_path = $qrCodePath;
            $inventory->save();

            return response()->json(['message' => 'QR Code berhasil diperbarui.', 'data' => $inventory->load(['item', 'room', 'unit'])]);
        } catch (\Exception $e) {
            \Log::error('QR Code error: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal memperbarui QR Code.'], 500);
        }
    }

    public function deleteQrCode(Request $request, $id)
    {
        // Cek apakah user adalah admin
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.'], 403);
        }

        $inventory = Inventory::findOrFail($id);

        // Cek kalau QR Code ada
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
}
