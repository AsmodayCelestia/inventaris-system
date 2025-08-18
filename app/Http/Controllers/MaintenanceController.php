<?php

namespace App\Http\Controllers;

use App\Models\InventoryMaintenance;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MaintenanceController extends Controller
{
    /* ---------- INDEX ---------- */
    public function index(Request $request)
    {
        $query = InventoryMaintenance::with(['inventory.item', 'responsiblePerson']);
        $user  = Auth::user();
        $division = $user->division()->value('name');

        if (in_array($user->role, ['admin', 'head'])) {
            // bebas
        } elseif ($user->role === 'karyawan' && $division === 'Keuangan') {
            $query->where('status', 'done');
        } elseif ($user->role === 'karyawan' && $division === 'Umum') {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('inventory_id')) {
            $query->where('inventory_id', $request->inventory_id);
        }

        return response()->json(
            $query->orderBy('inspection_date', 'desc')->get()
        );
    }

    /* ---------- STORE ---------- */
    public function store(Request $request, $inventoryId)
    {
        $inventory = Inventory::findOrFail($inventoryId);

        $validated = $request->validate([
            'inspection_date'  => 'required|date',
            'issue_found'      => 'nullable|string',
            'solution_taken'   => 'nullable|string',
            'notes'            => 'nullable|string',
            'status'           => 'required|in:planning,done',
            'pj_id'            => 'required|exists:users,id',
            'cost'             => 'nullable|numeric|min:0',
            'photo_1'          => 'nullable|image|max:2048',
            'photo_2'          => 'nullable|image|max:2048',
            'photo_3'          => 'nullable|image|max:2048',
        ]);

        $data = [
            'inventory_id'    => $inventoryId,
            'inspection_date' => $validated['inspection_date'],
            'issue_found'     => $validated['issue_found']     ?? null,
            'solution_taken'  => $validated['solution_taken']  ?? null,
            'notes'           => $validated['notes']           ?? null,
            'status'          => $validated['status'],
            'cost'            => $validated['cost']            ?? null,
            'user_id'         => $validated['pj_id'],
        ];

        for ($i = 1; $i <= 3; $i++) {
            $key = "photo_$i";
            if ($request->hasFile($key)) {
                $uploaded = Cloudinary::upload($request->file($key)->getRealPath(), [
                    'folder'    => 'maintenance_photos',
                    'public_id' => Str::slug("inv-$inventoryId-maint-$key-".time()),
                ]);
                $data[$key] = $uploaded->getSecurePath();
            }
        }

        $maintenance = InventoryMaintenance::create($data);

        if ($maintenance->status === 'done') {
            $inventory->last_maintenance_at = $maintenance->inspection_date;
            $inventory->save();
        }

        return response()->json($maintenance, 201);
    }

/* ---------- SHOW ---------- */
public function show($id)
{
    $maintenance = InventoryMaintenance::with(['inventory.item', 'responsiblePerson'])
                                       ->findOrFail($id);
    $user     = Auth::user();
    $division = $user->division()->value('name');

    // 1. Admin / Head bebas
    if (in_array($user->role, ['admin', 'head'])) {
        return response()->json($maintenance);
    }

    // 2. Keuangan hanya boleh lihat DONE
    if ($user->role === 'karyawan' && $division === 'Keuangan') {
        if ($maintenance->status !== 'done') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json($maintenance);
    }

    // 3. Umum boleh lihat jika:
    //    a) dia PJ-nya, atau
    //    b) dia pengawas ruangan barang ini
    if ($user->role === 'karyawan') {
        if (
            $maintenance->user_id === $user->id ||
            $maintenance->inventory->room->pj_lokasi_id === $user->id
        ) {
            return response()->json($maintenance);
        }
        return response()->json(['message' => 'Forbidden'], 403);
    }

    // 4. Selain itu langsung 403
    return response()->json(['message' => 'Forbidden'], 403);
}

/* ---------- UPDATE ---------- */
public function update(Request $request, $id)
{
    $maintenance = InventoryMaintenance::findOrFail($id);
    $user        = Auth::user();
    $division    = $user->division()->value('name');

    /* ---------- Gate ---------- */
    if (! in_array($user->role, ['admin', 'head'])) {
        if ($user->role === 'karyawan' && $division === 'Umum') {
            if ($maintenance->user_id !== $user->id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        } elseif ($user->role === 'karyawan' && $division === 'Keuangan') {
            if ($maintenance->status !== 'done') {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        } else {
            return response()->json(['message' => 'Forbidden'], 403);
        }
    }

    /* ---------- Validasi ---------- */
    $validated = $request->validate([
        'inspection_date'  => 'required|date',
        'issue_found'      => 'nullable|string',
        'solution_taken'   => 'nullable|string',
        'notes'            => 'nullable|string',
        'status'           => 'required|in:planning,done',
        'cost'             => 'nullable|numeric|min:0',
        'user_id'          => 'sometimes|exists:users,id',
        'photo_1'          => 'nullable|image|max:2048',
        'photo_2'          => 'nullable|image|max:2048',
        'photo_3'          => 'nullable|image|max:2048',
    ]);

    /* ---------- Simpan status lama ---------- */
    $oldStatus = $maintenance->status;

    /* ---------- Update record ---------- */
    $update = [
        'inspection_date' => $validated['inspection_date'],
        'issue_found'     => $validated['issue_found']     ?? null,
        'solution_taken'  => $validated['solution_taken']  ?? null,
        'notes'           => $validated['notes']           ?? null,
        'status'          => $validated['status'],
        'cost'            => $validated['cost']            ?? null,
        'user_id'         => $validated['user_id']         ?? $maintenance->user_id,
    ];

    /* ---------- Foto ---------- */
    for ($i = 1; $i <= 3; $i++) {
        $key = "photo_$i";
        if ($request->boolean("remove_photo_$i") && $maintenance->$key) {
            $publicId = pathinfo(parse_url($maintenance->$key, PHP_URL_PATH), PATHINFO_FILENAME);
            Cloudinary::destroy('maintenance_photos/'.$publicId);
            $update[$key] = null;
        }
        if ($request->hasFile($key)) {
            if ($maintenance->$key) {
                $publicId = pathinfo(parse_url($maintenance->$key, PHP_URL_PATH), PATHINFO_FILENAME);
                Cloudinary::destroy('maintenance_photos/'.$publicId);
            }
            $uploaded = Cloudinary::upload($request->file($key)->getRealPath(), [
                'folder'    => 'maintenance_photos',
                'public_id' => Str::slug("inv-{$maintenance->inventory_id}-maint-$key-".time()),
            ]);
            $update[$key] = $uploaded->getSecurePath();
        }
    }

    $maintenance->update($update);

    /* ---------- Auto-create next maintenance ---------- */
    if ($oldStatus === 'planning' && $maintenance->status === 'done') {
        $inventory = $maintenance->inventory;
        $inventory->last_maintenance_at = $maintenance->inspection_date;
        $inventory->save();

        $nextDate = Carbon::parse($maintenance->inspection_date)
                    ->addMonths($inventory->maintenance_interval_month ?? 1);

        InventoryMaintenance::create([
            'inventory_id'    => $inventory->id,
            'inspection_date' => $nextDate,
            'status'          => 'planning',
            'user_id'         => $maintenance->user_id, // tetap sama
        ]);
    }

    return response()->json($maintenance);
}

    /* ---------- DESTROY ---------- */
    public function destroy($id)
    {
        $maintenance = InventoryMaintenance::findOrFail($id);

        for ($i = 1; $i <= 3; $i++) {
            $key = "photo_$i";
            if ($maintenance->$key) {
                $publicId = pathinfo(parse_url($maintenance->$key, PHP_URL_PATH), PATHINFO_FILENAME);
                Cloudinary::destroy('maintenance_photos/'.$publicId);
            }
        }

        $maintenance->delete();
        return response()->json(['message' => 'Riwayat maintenance berhasil dihapus']);
    }

    public function historyDone(Inventory $inventory)
{
    $user = Auth::user();

    /* cek hak akses: admin/head atau pengawas ruangan */
    if (
        ! in_array($user->role, ['admin', 'head']) &&
        $user->id !== optional($inventory->room)->pj_lokasi_id
    ) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $data = $inventory->maintenances()
                      ->where('status', 'done')
                      ->with(['inventory.item', 'responsiblePerson'])
                      ->orderBy('inspection_date', 'desc')
                      ->get();

    return response()->json($data);
}
}