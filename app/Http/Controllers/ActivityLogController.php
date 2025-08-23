<?php
// app/Http/Controllers/ActivityLogController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use DataTables;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

public function datatable(Request $request)
{
    $query = Activity::with('causer')
        ->select('activity_log.*')
        ->orderByDesc('created_at')
        ->when($request->model_type, fn($q, $type) =>
            $q->where('subject_type', 'like', "%{$type}%")
        );

    // ---------- SEARCH ----------
    if ($keyword = $request->input('search')) {
        $query->where(function ($q) use ($keyword) {
            $q->where('description', 'like', "%{$keyword}%")
              ->orWhere('subject_type', 'like', "%{$keyword}%")
              ->orWhereHas('causer', fn($q) => $q->where('name', 'like', "%{$keyword}%"))
              ->orWhere('properties', 'like', "%{$keyword}%"); // <-- tambahan
        });
    }


    // 1. total keseluruhan
    $recordsTotal = Activity::count();

    // 2. total setelah filter
    $recordsFiltered = (clone $query)->count();

    // 3. paginasi
    $length = (int) ($request->input('per_page', 10));
    $start  = ((int) ($request->input('page', 1)) - 1) * $length;

    $logs = (clone $query)
        ->offset($start)
        ->limit($length)
        ->get();

    // 4. mapping ke JSON
    $data = $logs->map(fn($row) => [
        'id'             => $row->id,
        'user'           => $row->causer->name ?? 'System',
        'description'    => $row->description,
        'model'          => class_basename($row->subject_type) . ' #' . $row->subject_id,
        'changed_fields' => $this->changedFields($row),
        'created_at'     => $row->created_at->diffForHumans(),
    ]);

    return response()->json([
        'data'            => $data,
        'recordsTotal'    => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
    ]);
}

private function changedFields($row)
{
    if ($row->description === 'created') return 'Data baru';

    $attrs = json_decode($row->properties ?? '{}', true)['attributes'] ?? [];
    $old   = json_decode($row->properties ?? '{}', true)['old'] ?? [];

    return collect($attrs)
        ->keys()
        ->filter(fn($k) => isset($old[$k]) && $old[$k] !== $attrs[$k])
        ->take(3)
        ->implode(', ') ?: '-';
}

public function detail($id)
{
    $activity = \Spatie\Activitylog\Models\Activity::find($id);

    if (!$activity) {
        return response()->json(['error' => 'Log tidak ditemukan'], 404);
    }

    $props = json_decode($activity->properties ?? '{}', true);

    return response()->json([
        'event'       => $activity->description,
        'causer'      => optional($activity->causer)->name ?? 'System',
        'subject'     => class_basename($activity->subject_type) . ' #' . $activity->subject_id,
        'created_at'  => $activity->created_at,
        'old'         => $props['old'] ?? [],
        'new'         => $props['attributes'] ?? [],
    ]);
}
}