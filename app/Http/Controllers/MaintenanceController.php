<?php

namespace App\Http\Controllers;

use App\Models\InventoryMaintenance;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\User;

class MaintenanceController extends Controller
{
    /* ---------- INDEX ---------- */
    public function index(Request $request)
    {
        $query = InventoryMaintenance::with([
            'inventory.item',
            'responsiblePerson',
            'creator'
        ]);

        $user     = Auth::user();
        $division = $user->division?->name;

        if (in_array($user->role, ['admin', 'head'])) {
            // bebas
        } elseif ($user->role === 'karyawan' && $division === 'Keuangan') {
            $query->where('status', 'done');
        } elseif ($user->role === 'karyawan' && $division === 'Umum') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'karyawan') {
            $query->whereHas('inventory.room', fn ($q) => $q->where('pj_lokasi_id', $user->id));
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
            'status'           => 'required|in:reported,on_progress,handled,done,cancelled',
            'pj_id'            => [
                Rule::requiredIf(fn () => $request->status !== 'reported'),
                'nullable',
                'exists:users,id',
            ],
            'cost'             => 'nullable|numeric|min:0',
            'photo_1'          => 'nullable|image|max:2048',
            'photo_2'          => 'nullable|image|max:2048',
            'photo_3'          => 'nullable|image|max:2048',
            'odometer_reading' => 'nullable|integer',
        ]);

        $data = [
            'inventory_id'    => $inventoryId,
            'inspection_date' => $validated['inspection_date'],
            'issue_found'     => $validated['issue_found']     ?? null,
            'solution_taken'  => $validated['solution_taken']  ?? null,
            'notes'           => $validated['notes']           ?? null,
            'status'          => $validated['status'],
            'cost'            => $validated['cost']            ?? null,
            'user_id'         => $validated['pj_id']           ?? null,
            'creator_id'      => Auth::id(),
            'odometer_reading'=> $validated['odometer_reading']?? null,
        ];

        for ($i = 1; $i <= 3; $i++) {
            $key = "photo_$i";
            if ($request->hasFile($key)) {
                $uploaded = Cloudinary::upload($request->file($key)->getRealPath(), [
                    'folder'    => 'maintenance_photos',
                    'public_id' => Str::slug("inv-{$inventoryId}-maint-$key-".time()),
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

public function show($id)
{
    $maintenance = InventoryMaintenance::with([
        'inventory.item',
        'responsiblePerson',
        'creator'
    ])->findOrFail($id);

    $user     = Auth::user();
    $division = $user->division?->name;

    if ($maintenance->status === 'reported') {
        return response()->json($maintenance);
    }

    /* ---- untuk status lainnya ---- */
    if (in_array($user->role, ['admin', 'head'])) {
        return response()->json($maintenance);
    }
    if ($user->role === 'karyawan' && $division === 'Keuangan') {
        return $maintenance->status === 'done'
            ? response()->json($maintenance)
            : response()->json(['message' => 'Forbidden'], 403);
    }
    if ($user->role === 'karyawan') {
        if ($maintenance->user_id === $user->id ||
            $maintenance->inventory->room->pj_lokasi_id === $user->id) {
            return response()->json($maintenance);
        }
    }
    return response()->json(['message' => 'Forbidden'], 403);
}

    /* ---------- UPDATE ---------- */
    public function update(Request $request, $id)
    {
        $maintenance = InventoryMaintenance::findOrFail($id);
        $user        = Auth::user();
        $division    = $user->division?->name;
        $isSuper     = $maintenance->inventory->room->pj_lokasi_id === $user->id;

        $canEdit = match (true) {
            in_array($user->role, ['admin', 'head'])            => true,
            $user->role === 'karyawan' && $division === 'Umum'  => $maintenance->user_id === $user->id,
            $user->role === 'karyawan' && $division === 'Keuangan' => false,
            $isSuper                                            => true,
            default                                             => false,
        };

        if (!$canEdit) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $rules = [];

        if (in_array($user->role, ['admin', 'head'])) {
            $rules = [
                'inspection_date'  => 'required|date',
                'issue_found'      => 'nullable|string',
                'solution_taken'   => 'nullable|string',
                'notes'            => 'nullable|string',
                'status'           => 'required|in:reported,on_progress,handled,done,cancelled',
                'cost'             => 'nullable|numeric|min:0',
                'user_id'          => 'nullable|exists:users,id',
                'photo_1'          => 'nullable|image|max:2048',
                'photo_2'          => 'nullable|image|max:2048',
                'photo_3'          => 'nullable|image|max:2048',
            ];
        } elseif ($user->role === 'karyawan' && $division === 'Umum') {
            $rules = [
                'inspection_date'  => 'required|date',
                'issue_found'      => 'nullable|string',
                'solution_taken'   => 'nullable|string',
                'notes'            => 'nullable|string',
                'status'           => 'required|in:handled,done,cancelled',
                'cost'             => 'nullable|numeric|min:0',
                'photo_1'          => 'nullable|image|max:2048',
                'photo_2'          => 'nullable|image|max:2048',
                'photo_3'          => 'nullable|image|max:2048',
            ];
        } elseif ($isSuper) {
            $rules = [
                'issue_found' => 'nullable|string',
                'notes'       => 'nullable|string',
                'status'      => 'required|in:done',
            ];
        }

        $validated = $request->validate($rules);

        for ($i = 1; $i <= 3; $i++) {
            $key = "photo_$i";
            if (isset($rules[$key]) && $request->hasFile($key) && $maintenance->$key) {
                $publicId = pathinfo(parse_url($maintenance->$key, PHP_URL_PATH), PATHINFO_FILENAME);
                Cloudinary::destroy('maintenance_photos/'.$publicId);
            }
        }

        for ($i = 1; $i <= 3; $i++) {
            $key = "photo_$i";
            if (isset($rules[$key]) && $request->hasFile($key)) {
                $uploaded = Cloudinary::upload($request->file($key)->getRealPath(), [
                    'folder'    => 'maintenance_photos',
                    'public_id' => Str::slug("inv-{$maintenance->inventory_id}-maint-$key-".time()),
                ]);
                $validated[$key] = $uploaded->getSecurePath();
            }
        }

        $maintenance->update($validated);

        if ($maintenance->status === 'done') {
            $maintenance->inventory->update(['last_maintenance_at' => $maintenance->inspection_date]);
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

    /* ---------- historyDone ---------- */
    public function historyDone(Inventory $inventory)
    {
        $user = Auth::user();

        $hasAccess = match (true) {
            in_array($user->role, ['admin', 'head']) => true,
            $user->role === 'karyawan' && $user->division?->name === 'Keuangan' => true,
            $user->id === optional($inventory->room)->pj_lokasi_id => true,
            default => false,
        };

        if (!$hasAccess) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(
            $inventory->maintenances()
                ->where('status', 'done')
                ->with(['inventory.item', 'responsiblePerson', 'creator'])
                ->orderBy('inspection_date', 'desc')
                ->get()
        );
    }

/* ---------- MAINTENANCE NEEDED ---------- */
public function need(Request $request)
{
    $query = InventoryMaintenance::with([
        'inventory.item',
        'inventory.room',
        'responsiblePerson',
        'creator'
    ])
    ->whereIn('status', ['reported', 'on_progress'])   // <-- tambahkan ini
    ->orderBy('inspection_date', 'desc');

    return response()->json($query->get());
}

    /* ---------- MAINTENANCE AKTIF ---------- */
    public function active(Request $request)
    {
        $query = InventoryMaintenance::with([
            'inventory.item',
            'inventory.room',
            'responsiblePerson',
            'creator'
        ])
        ->whereIn('status', ['on_progress', 'handled'])
        ->orderBy('inspection_date', 'desc');

        $user = Auth::user();
        $division = $user->division?->name;

        if (in_array($user->role, ['admin', 'head'])) {
            // bebas
        } elseif ($user->role === 'karyawan' && $division === 'Umum') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'karyawan' && $division === 'Keuangan') {
            return response()->json(['data' => []]);
        } elseif ($user->role === 'karyawan') {
            $query->whereHas('inventory.room', fn ($q) => $q->where('pj_lokasi_id', $user->id));
        } else {
            $query->where('creator_id', $user->id);
        }

        return response()->json($query->get());
    }

    /* ---------- MAINTENANCE DONE ---------- */
    public function done(Request $request)
    {
        $query = InventoryMaintenance::with([
            'inventory.item',
            'inventory.room',
            'responsiblePerson',
            'creator'
        ])
        ->where('status', 'done')
        ->orderBy('inspection_date', 'desc');

        $user = Auth::user();
        $division = $user->division?->name;

        if (in_array($user->role, ['admin', 'head'])) {
            // bebas
        } elseif ($user->role === 'karyawan' && $division === 'Keuangan') {
            // bebas lihat yang done
        } elseif ($user->role === 'karyawan' && $division === 'Umum') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'karyawan') {
            $query->whereHas('inventory.room', fn ($q) => $q->where('pj_lokasi_id', $user->id));
        } else {
            $query->where('creator_id', $user->id);
        }

        return response()->json($query->get());
    }

    /* ---------- ASSIGN ---------- */
    public function assign(Request $request, $id)
    {
        $maintenance = InventoryMaintenance::where('status', 'reported')->findOrFail($id);
        $user        = Auth::user();

        $validated = $request->validate(['user_id' => 'required|exists:users,id']);

        if ($user->role === 'karyawan' && $user->division?->name === 'Umum') {
            if ($validated['user_id'] != $user->id) {
                return response()->json(['message' => 'Tidak boleh assign ke orang lain'], 403);
            }
        } elseif (!in_array($user->role, ['admin', 'head'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $maintenance->update([
            'user_id' => $validated['user_id'],
            'status'  => 'on_progress',
        ]);
        // ── set flag di user yang bersangkutan ──
        User::where('id', $validated['user_id'])
            ->where('is_pj_maintenance', 0)
            ->update(['is_pj_maintenance' => 1]);

            return response()->json($maintenance);
    }

    /* ---------- UPDATE STATUS ---------- */
    public function updateStatus(Request $request, $id)
    {
        $maintenance = InventoryMaintenance::findOrFail($id);
        $user        = Auth::user();

        $validated = $request->validate([
            'status' => ['required', 'in:handled,done,cancelled'],
        ]);

        // VALIDASI CANCEL
        if ($validated['status'] === 'cancelled') {
            $canCancel = match (true) {
                in_array($user->role, ['admin', 'head']) => true,
                $user->role === 'karyawan' && 
                $user->division?->name === 'Umum' && 
                $user->id === $maintenance->user_id => true,
                default => false,
            };
            
            if (!$canCancel) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        } elseif ($validated['status'] === 'handled') {
            if ($user->id !== $maintenance->user_id) {
                return response()->json(['message' => 'Hanya PJ yang bisa menandai handled'], 403);
            }
        } else {
            $isSuper = $maintenance->inventory->room->pj_lokasi_id === $user->id;
            if (!in_array($user->role, ['admin', 'head']) && !$isSuper) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $maintenance->update(['status' => $validated['status']]);

        if ($validated['status'] === 'done') {
            $maintenance->inventory->update(['last_maintenance_at' => $maintenance->inspection_date]);
        }

        return response()->json($maintenance);
    }
}