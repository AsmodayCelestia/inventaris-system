<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary; // Pastikan ini di-import
use SimpleSoftwareIO\QrCode\Facades\QrCode; // Pastikan ini di-import
use Illuminate\Support\Str; // Pastikan ini di-import

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inventories = Inventory::with(['item', 'room', 'unit'])->get();
        return response()->json($inventories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
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
            'image' => 'nullable|image|max:2048', // Validasi untuk file gambar (max 2MB)
        ]);

        // 2. Proses Upload Gambar ke Cloudinary (Jika Ada)
        $imagePath = null;
        if ($request->hasFile('image')) {
            try {
                $uploadedFile = $request->file('image');
                $result = Cloudinary::upload($uploadedFile->getRealPath(), [
                    'folder' => 'inventories_images', // Folder di Cloudinary
                    'public_id' => Str::slug($request->inventory_number . '-' . time()), // Nama file unik
                ]);
                $imagePath = $result->getSecurePath(); // Ambil URL aman dari Cloudinary
            } catch (\Exception $e) {
                \Log::error('Cloudinary image upload failed: ' . $e->getMessage());
                return response()->json(['message' => 'Gagal mengunggah gambar: ' . $e->getMessage()], 500);
            }
        }

        // 3. Buat Entri Inventaris Baru (Simpan data tanpa QR Code dan Image Path dulu)
        // Gunakan array_diff_key untuk mengecualikan 'image' dari validatedData
        $inventoryData = array_diff_key($validatedData, array_flip(['image']));
        $inventory = Inventory::create($inventoryData);

        // 4. Generate & Upload QR Code
        $qrCodePath = null;
        try {
            // URL detail inventaris. Sesuaikan domain aplikasi kamu di .env (APP_URL)!
            $detailUrl = config('app.url') . '/inventories/' . $inventory->id;

            // Generate QR Code sebagai string base64 atau simpan sebagai file lokal sementara
            $qrCodeData = QrCode::format('png')
                                ->size(300)
                                ->generate($detailUrl);

            // Simpan QR Code secara sementara ke disk lokal
            $qrCodeFileName = 'qrcodes/inventories/' . $inventory->inventory_number . '-' . Str::random(10) . '.png';
            Storage::disk('public')->put($qrCodeFileName, $qrCodeData);

            // Upload QR Code ke Cloudinary
            $resultQr = Cloudinary::upload(storage_path('app/public/' . $qrCodeFileName), [
                'folder' => 'inventories_qrcodes', // Folder di Cloudinary
                'public_id' => 'qr-' . Str::slug($inventory->inventory_number), // Nama public_id unik untuk QR
            ]);
            $qrCodePath = $resultQr->getSecurePath();

            // Hapus file QR code sementara dari disk lokal
            Storage::disk('public')->delete($qrCodeFileName);

        } catch (\Exception $e) {
            \Log::error('Gagal generate atau upload QR Code untuk inventaris ' . $inventory->id . ': ' . $e->getMessage());
            // Lanjutkan tanpa QR jika tidak fatal, atau kembalikan error jika ini kritis
        }

        // 5. Update Inventaris dengan Image Path dan QR Code Path
        $inventory->image_path = $imagePath;
        $inventory->qr_code_path = $qrCodePath;
        $inventory->save(); // Simpan perubahan

        return response()->json($inventory->load(['item', 'room', 'unit']), 201); // Load relasi untuk respons
    }

    /**
     * Display the specified resource.
     */
    public function show(Inventory $inventory)
    {
        // Pastikan relasi juga dimuat jika diperlukan untuk detail
        $inventory->load(['item', 'room', 'unit', 'personInCharge', 'maintenances']);
        return response()->json($inventory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inventory $inventory)
    {
        // 1. Validasi Input
        $validatedData = $request->validate([
            'inventory_number' => 'required|string|unique:inventories,inventory_number,' . $inventory->id,
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'acquisition_source' => 'required|in:Beli,Hibah,Bantuan,-',
            'procurement_date' => 'required|date',
            'purchase_price' => 'required|numeric',
            'estimated_depreciation' => 'nullable|numeric',
            'status' => 'required|in:Ada,Rusak,Perbaikan', // 'Ada', 'Rusak', 'Perbaikan', 'Hilang', 'Dipinjam', '-'
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
            'image' => 'nullable|image|max:2048', // 'nullable' karena gambar bisa tidak diubah
            'remove_image' => 'nullable|boolean', // Flag dari frontend untuk menghapus gambar
        ]);

        // 2. Proses Upload Gambar ke Cloudinary (Jika Ada Gambar Baru atau Dihapus)
        if ($request->hasFile('image')) {
            try {
                // Hapus gambar lama di Cloudinary jika ada
                if ($inventory->image_path) {
                    $publicId = pathinfo(parse_url($inventory->image_path, PHP_URL_PATH), PATHINFO_FILENAME);
                    Cloudinary::destroy('inventories_images/' . $publicId); // Hapus dari folder di Cloudinary
                }

                $uploadedFile = $request->file('image');
                $result = Cloudinary::upload($uploadedFile->getRealPath(), [
                    'folder' => 'inventories_images',
                    'public_id' => Str::slug($request->inventory_number . '-' . time()),
                ]);
                $inventory->image_path = $result->getSecurePath();
            } catch (\Exception $e) {
                \Log::error('Cloudinary image upload failed during update: ' . $e->getMessage());
                return response()->json(['message' => 'Gagal mengunggah gambar baru: ' . $e->getMessage()], 500);
            }
        } elseif ($request->input('remove_image') && $inventory->image_path) {
            // Jika ada flag remove_image dan memang ada gambar lama
            try {
                $publicId = pathinfo(parse_url($inventory->image_path, PHP_URL_PATH), PATHINFO_FILENAME);
                Cloudinary::destroy('inventories_images/' . $publicId);
                $inventory->image_path = null; // Set path ke null di database
            } catch (\Exception $e) {
                \Log::error('Cloudinary image deletion failed during update: ' . $e->getMessage());
                // Lanjutkan update data lainnya meskipun gagal hapus gambar lama
            }
        }

        // 3. Regenerate QR Code (Selalu regenerate agar QR code selalu up-to-date)
        try {
            // Hapus QR Code lama di Cloudinary jika ada (berdasarkan public_id lama)
            if ($inventory->qr_code_path) {
                // Ekstrak public_id dari URL QR Code lama
                $oldPublicId = 'qr-' . Str::slug($inventory->getOriginal('inventory_number')); // Gunakan inventory_number lama
                Cloudinary::destroy('inventories_qrcodes/' . $oldPublicId);
            }
            
            $detailUrl = config('app.url') . '/inventories/' . $inventory->id;
            $qrCodeData = QrCode::format('png')
                                ->size(300)
                                ->generate($detailUrl);

            // Simpan QR Code secara sementara ke disk lokal
            $qrCodeFileName = 'qrcodes/inventories/' . $inventory->inventory_number . '-' . Str::random(10) . '.png';
            Storage::disk('public')->put($qrCodeFileName, $qrCodeData);

            // Upload QR Code ke Cloudinary
            $resultQr = Cloudinary::upload(storage_path('app/public/' . $qrCodeFileName), [
                'folder' => 'inventories_qrcodes', // Folder di Cloudinary
                'public_id' => 'qr-' . Str::slug($request->inventory_number), // Nama public_id unik untuk QR
            ]);
            $qrCodePath = $resultQr->getSecurePath();

            // Hapus file QR code sementara dari disk lokal
            Storage::disk('public')->delete($qrCodeFileName);

        } catch (\Exception $e) {
            \Log::error('Gagal regenerate atau upload QR Code untuk inventaris ' . $inventory->id . ': ' . $e->getMessage());
        }

        // 4. Update data inventaris (kecuali file 'image' dan 'remove_image')
        $inventoryData = array_diff_key($validatedData, array_flip(['image', 'remove_image']));
        $inventory->fill($inventoryData);
        $inventory->save();

        return response()->json($inventory->load(['item', 'room', 'unit']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventory)
    {
        // Hapus gambar dan QR Code dari Cloudinary saat inventaris dihapus
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
            \Log::error('Failed to delete Cloudinary assets for inventory ' . $inventory->id . ': ' . $e->getMessage());
            // Lanjutkan menghapus record meskipun gagal menghapus aset di Cloudinary
        }

        $inventory->delete();
        return response()->json(['message' => 'Inventaris berhasil dihapus.']);
    }

    // Tambahkan metode showByQrCode jika belum ada
    public function showByQrCode($inventoryNumber)
    {
        $inventory = Inventory::with(['item', 'room', 'unit', 'personInCharge', 'maintenances'])
                                ->where('inventory_number', $inventoryNumber)
                                ->firstOrFail();
        return response()->json($inventory);
    }
}
