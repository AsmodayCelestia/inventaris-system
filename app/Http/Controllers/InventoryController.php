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
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\Font\Font;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Label\LabelAlignment;
use Illuminate\Support\Arr;
use DataTables;

class InventoryController extends Controller
{

    private function canSeePrice(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false; // publik gak boleh lihat harga
        }

        $user->loadMissing('division');
        return $user->role === 'admin' ||
            $user->division?->name === 'Keuangan' ||
            ($user->role === 'head' && $user->division?->name === 'Umum');
    }

    private function canUpdatePrice(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false; // publik gak boleh update harga
        }

        $user->loadMissing('division');
        return $user->role === 'admin' || $user->division?->name === 'Keuangan';
    }

    /* -----------------------------------------------------------------
       |  METHOD LAMA (TIDAK BERUBAH)
       ----------------------------------------------------------------- */
/**
 * Tambahkan query whereIn jika request berisi array
 */
private function whereInArray($query, $request, $key)
{
    $values = $request->input($key);
    if (empty($values)) {
        return;
    }

    // Jika string (misal dari DataTables), pecah dengan koma
    if (is_string($values)) {
        $values = explode(',', $values);
    }

    // Filter array kosong
    $values = array_filter($values);

    if (!empty($values)) {
        $query->whereIn($key, $values);
    }
}



public function table(Request $request)
{
    $query = Inventory::with('item', 'room.floor', 'unit');

    /* ---------- pencarian ---------- */
    if ($search = $request->input('search.value')) {
        $query->where(function ($q) use ($search) {
            $q->where('inventory_number', 'like', "%{$search}%")
              ->orWhereHas('item', fn($b) => $b->where('name', 'like', "%{$search}%"));
        });
    }

    if ($status = $request->input('status')) {
        $query->where('status', $status);
    }

    /* ---------- filter array ---------- */
    $this->whereInArray($query, $request, 'unit_id');
    $this->whereInArray($query, $request, 'room_id');
    $this->whereInArray($query, $request, 'pj_id');

    /* ---------- filter floor via relasi ---------- */
$floorIds = $request->input('floor_id');
if (!empty($floorIds)) {
    $floorIds = is_array($floorIds) ? $floorIds : explode(',', $floorIds);
    $floorIds = array_map('intval', array_filter($floorIds));

    if (!empty($floorIds)) {
        $query->whereHas('room.floor', fn ($q) => $q->whereIn('floors.id', $floorIds));
    }
}

    /* ---------- grand total (sekarang sudah memperhitungkan semua filter) ---------- */
    $grandTotal = (clone $query)
        ->selectRaw('
            COALESCE(SUM(purchase_price), 0) as purchase,
            COALESCE(SUM(estimated_depreciation), 0) as depreciation
        ')
        ->first();

    /* ---------- hitung record ---------- */
    $recordsFiltered = (clone $query)->count();
    $recordsTotal    = Inventory::count();

    /* ---------- paginasi ---------- */
    $length = (int) $request->input('length', 10);
    $start  = (int) $request->input('start', 0);
    $items  = $query->offset($start)->limit($length)->get();

    return response()->json([
        'draw'            => (int) $request->input('draw', 1),
        'recordsTotal'    => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data'            => $items,
        'grand_total'     => [
            'purchase'     => (float) ($grandTotal->purchase ?? 0),
            'depreciation' => (float) ($grandTotal->depreciation ?? 0),
        ],
    ]);
}

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
    $rules = [
        'inventory_number' => 'required|string|unique:inventories',
        'inventory_item_id' => 'required|exists:inventory_items,id',
        'acquisition_source' => 'required|in:Beli,Hibah,Bantuan,-',
        'procurement_date' => 'required|date',
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
    ];

    if ($this->canSeePrice()) {
        $rules['purchase_price'] = 'required|numeric';
        $rules['estimated_depreciation'] = 'nullable|numeric';
    } else {
        $request->request->remove('purchase_price');
        $request->request->remove('estimated_depreciation');
    }

    $validatedData = $request->validate($rules);

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

    InventoryItem::where('id', $validatedData['inventory_item_id'])
        ->increment('quantity');

    $inventory->image_path = $imagePath;
    $inventory->qr_code_path = null;
    $inventory->save();
    $this->ensureQrGenerated($inventory);
    return response()->json($inventory->load(['item', 'room', 'unit']), 201);
}

public function show(Inventory $inventory)
{
    $user = auth()->user()->loadMissing('division');

    \Log::info('debug permission', [
        'role'     => $user->role,
        'division' => $user->division?->name,
        'canSee'   => $this->canSeePrice(),
    ]);

    $inventory->load([
        'item',
        'room',
        'room.locationPersonInCharge',
        'unit',
        'personInCharge',
        'maintenances'
    ]);

    $data = $inventory->toArray();

    if (!$this->canSeePrice()) {
        Arr::forget($data, ['purchase_price', 'estimated_depreciation']);
    }

    return response()->json($data);
}

public function update(Request $request, $id)
{
    /* ---------- GATE ---------- */
    $user = auth()->user()->loadMissing('division');

    // Siapa pun boleh update, kecuali:
    // - karyawan bukan Keuangan
    $allowed =
        $user->role === 'admin' ||
        $user->role === 'head'  ||
        ($user->role === 'karyawan' && $user->division?->name === 'Keuangan');

    if (!$allowed) {
        return response()->json([
            'message' => 'Unauthorized. You do not have the required role.'
        ], 403);
    }
    /* ---------- END GATE ---------- */

    /* ---------- LOGIKA LAMA (TIDAK DIUBAH) ---------- */
    $inventory = Inventory::findOrFail($id);

    $rules = [
        'inventory_number'      => 'required|string|unique:inventories,inventory_number,' . $id,
        'inventory_item_id'     => 'required|exists:inventory_items,id',
        'acquisition_source'    => 'required|in:Beli,Hibah,Bantuan,-',
        'procurement_date'      => 'required|date',
        'status'                => 'required|in:Ada,Rusak,Perbaikan,Hilang,Dipinjam,-',
        'unit_id'               => 'required|exists:location_units,id',
        'room_id'               => 'required|exists:rooms,id',
        'expected_replacement'  => 'nullable|date',
        'last_checked_at'       => 'nullable|date',
        'description'           => 'nullable|string',
        'image'                 => 'nullable|image|max:2048',
        'remove_image'          => 'nullable|in:0,1',
    ];

    if ($this->canUpdatePrice()) {
        $rules['purchase_price']        = 'required|numeric';
        $rules['estimated_depreciation'] = 'nullable|numeric';
    } else {
        $request->request->remove('purchase_price');
        $request->request->remove('estimated_depreciation');
    }

    $validatedData = $request->validate($rules);

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

    public function showByQrCode($inventoryNumber)
    {
        $inventory = Inventory::with(['item', 'room', 'unit', 'personInCharge', 'maintenances'])
            ->where('id', $inventoryNumber)
            ->firstOrFail();

        $data = $inventory->toArray();

        if (!$this->canSeePrice()) {
            Arr::forget($data, ['purchase_price', 'estimated_depreciation']);
        }

        return response()->json($data);
    }



public function showForScan($id)
{
    $inventory = Inventory::with([
        'item',
        'room',
        'room.locationPersonInCharge',
        'unit',
        'personInCharge',
        'maintenances'
    ])->findOrFail($id);

    $data = $inventory->toArray();

        Arr::forget($data, [
            // field langsung
            'purchase_price',
            'estimated_depreciation',
            'created_at',
            'updated_at',
            'id',
            'inventory_item_id',
            'unit_id',
            'room_id',
            'pj_id',
            'expected_replacement',
            'last_checked_at',
            'maintenance_frequency_type',
            'maintenance_frequency_value',
            'last_maintenance_at',
            'next_due_date',
            'next_due_km',
            'last_odometer_reading',

            // relasi
            'maintenances',
        ]);
    
    return response()->json($data);
}
    /* -----------------------------------------------------------------
       |  METHOD BARU UNTUK FLOW HEAD (quantity sudah naik di master)
       ----------------------------------------------------------------- */
public function storeFromSlot(Request $request, $inventoryItem)
{
    $item = InventoryItem::findOrFail($inventoryItem);

    $room = \App\Models\Room::findOrFail($request->room_id);
    $floor = $room->floor;
    $hari = now()->format('d');
    $bulan = now()->format('m');
    $tahun = now()->format('Y');

    $counter = Inventory::count();
    $inventoryNumber = $tahun . $bulan . $hari . $floor->code . ($counter + 1);

    $rules = [
        'acquisition_source' => 'required|in:Beli,Hibah,Bantuan,-',
        'procurement_date'   => 'required|date',
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
    ];

    if ($this->canSeePrice()) {
        $rules['purchase_price'] = 'required|numeric';
        $rules['estimated_depreciation'] = 'nullable|numeric';
    } else {
        $request->request->remove('purchase_price');
        $request->request->remove('estimated_depreciation');
    }

    $validated = $request->validate($rules);

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

    $inventory = Inventory::create([
        ...$validated,
        'inventory_number' => $inventoryNumber,
        'inventory_item_id' => $item->id,
        'image_path'       => $imagePath,
        'qr_code_path'     => null,
    ]);
    $this->ensureQrGenerated($inventory);
    return response()->json($inventory->load(['item', 'room', 'unit']), 201);
}

    public function destroy(Inventory $inventory)
    {
        try {
            // helper inline
            $deleteCloudinary = function (?string $url, string $folder) {
                if (!$url || !str_contains($url, 'res.cloudinary.com')) {
                    return;
                }
                $path = parse_url($url, PHP_URL_PATH);            // /v123/folder/name.jpg
                $segments = explode('/', trim($path, '/'));        // [v123,folder,name.jpg]
                $publicId = $folder . '/' . pathinfo(end($segments), PATHINFO_FILENAME);
                Cloudinary::destroy($publicId);
            };

            // 1. hapus gambar barang
            $deleteCloudinary($inventory->image_path, 'inventories_images');

            // 2. hapus QR-code
            $deleteCloudinary($inventory->qr_code_path, 'inventories_qrcodes');

            // 3. hapus record
            $inventory->delete();

            return response()->json(['message' => 'Inventaris & asset terkait berhasil dihapus'], 200);

        } catch (\Exception $e) {
            \Log::error('Destroy inventory error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menghapus inventaris'], 500);
        }
    }

    // private helper di InventoryController
// di InventoryController, ubah method ensureQrGenerated() menjadi:
private function ensureQrGenerated(Inventory $inventory): void
{
    if ($inventory->qr_code_path) {
        return;
    }

    try {
        // parameter styling sama dengan route /qr-preview
        $detailUrl   = config('app.short_url') . '/' . $inventory->id;
        $inventoryNo = $inventory->inventory_number;
        $logo        = 'android-chrome-192x192.png';
        $logoPath    = public_path($logo);
        $logoPath    = file_exists($logoPath) ? $logoPath : null;

        $fontPath = base_path('vendor/endroid/qr-code/assets/noto_sans.otf');
        $font     = new \Endroid\QrCode\Label\Font\Font($fontPath, 12);

        $qrResult = \Endroid\QrCode\Builder\Builder::create()
            ->writer(new \Endroid\QrCode\Writer\PngWriter())
            ->data($detailUrl)
            ->size(260)
            ->margin(20)
            ->foregroundColor(new \Endroid\QrCode\Color\Color(0, 0, 0))   // hitam
            ->backgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255))
            ->logoPath($logoPath)
            ->logoResizeToWidth(50)
            ->labelText($inventoryNo)
            ->labelFont($font)
            ->build();

        // simpan ke Cloudinary seperti sebelumnya
        $qrFile = 'qrcodes/inventories/' . $inventoryNo . '-' . Str::random(10) . '.png';
        Storage::disk('public')->put($qrFile, $qrResult->getString());

        $cloud = Cloudinary::upload(storage_path('app/public/' . $qrFile), [
            'folder'    => 'inventories_qrcodes',
            'public_id' => 'qr-' . Str::slug($inventoryNo) . '-' . Str::random(8),
        ]);
        Storage::disk('public')->delete($qrFile);

        $inventory->qr_code_path = $cloud->getSecurePath();
        $inventory->saveQuietly();

    } catch (\Exception $e) {
        \Log::error('QR helper error: ' . $e->getMessage());
    }
}

/* ----------------------------------------------------------
   |  BULK QR-CODE
   ---------------------------------------------------------- */
public function bulkCreateQr(Request $request)
{
    $ids = $request->input('inventory_ids', []);
    $done = 0;
    foreach (Inventory::whereIn('id', $ids)->cursor() as $inv) {
        if (!$inv->qr_code_path) {
            $this->ensureQrGenerated($inv);
            $done++;
        }
    }
    return response()->json(['message' => "$done QR code berhasil dibuat"]);
}

public function bulkUpdateQr(Request $request)
{
    $ids = $request->input('inventory_ids', []);
    $done = 0;

    foreach (Inventory::whereIn('id', $ids)->cursor() as $inv) {
        // 1. Hapus QR lama
        if ($inv->qr_code_path) {
            $publicId = pathinfo(parse_url($inv->qr_code_path, PHP_URL_PATH), PATHINFO_FILENAME);
            Cloudinary::destroy('inventories_qrcodes/' . $publicId);
            $inv->qr_code_path = null; // reset dulu
            $inv->saveQuietly();
        }

        // 2. Generate ulang
        $this->ensureQrGenerated($inv);
        $done++;
    }
    return response()->json(['message' => "$done QR code berhasil diperbarui"]);
}

public function bulkDeleteQr(Request $request)
{
    $ids  = $request->input('inventory_ids', []);
    $done = 0;

    foreach (Inventory::whereIn('id', $ids)->cursor() as $inv) {
        if ($inv->qr_code_path) {
            // ambil full path setelah /image/upload/, lalu hilangkan ekstensi
            $path     = parse_url($inv->qr_code_path, PHP_URL_PATH); // /v12345/inventories_qrcodes/qr-ABC123.png
            $segments = explode('/', trim($path, '/'));
            $publicId = implode('/', array_slice($segments, 2));     // inventories_qrcodes/qr-ABC123

            Cloudinary::destroy($publicId);
            $inv->update(['qr_code_path' => null]);
            $done++;
        }
    }

    return response()->json(['message' => "$done QR code berhasil dihapus"]);
}

/* ----------------------------------------------------------
   |  DOWNLOAD
   ---------------------------------------------------------- */

public function downloadBulk(Request $request)
{
    $ids = $request->input('ids'); // 1,2,3
    abort_if(empty($ids), 400, 'Tidak ada inventaris dipilih');

    $inventories = Inventory::whereIn('id', explode(',', $ids))
                            ->whereNotNull('qr_code_path')
                            ->get();

    abort_if($inventories->isEmpty(), 404, 'Tidak ada QR code');

    // Jika hanya 1 file → langsung stream
    if ($inventories->count() === 1) {
        $file = file_get_contents($inventories->first()->qr_code_path);
        return response($file, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$inventories->first()->inventory_number.'.png"',
        ]);
    }

    // Lebih dari 1 → zip
    $zip = new \ZipArchive();
    $zipName = storage_path('app/public/qr-codes-' . now()->timestamp . '.zip');
    $zip->open($zipName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    foreach ($inventories as $inv) {
        $file = file_get_contents($inv->qr_code_path);
        $zip->addFromString($inv->inventory_number . '.png', $file);
    }
    $zip->close();

    return response()->download($zipName, 'qr-codes.zip', [
            'Content-Type' => 'application/zip'
        ])->deleteFileAfterSend(true);
}

}