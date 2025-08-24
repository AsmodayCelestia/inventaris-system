<?php
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

    /* list model yg pernah logged (untuk dropdown) */
    public function models()
    {
        $models = Activity::select('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->mapWithKeys(fn($fqcn) => [class_basename($fqcn) => $fqcn]);
        return response()->json($models);
    }

    /* list user yg pernah mencatat */
    public function users()
    {
        $users = Activity::whereNotNull('causer_id')
            ->with('causer:id,name')
            ->get()
            ->pluck('causer.name', 'causer_id');
        return response()->json($users);
    }

    /* datatable utama */
    public function datatable(Request $request)
    {
        $query = Activity::with('causer')
            ->select('activity_log.*')
            ->orderByDesc('created_at');

        /* filter model (multi) */
        if ($request->filled('model_type')) {
            $types = is_array($request->model_type)
                ? $request->model_type
                : explode(',', $request->model_type);
            $query->whereIn('subject_type', $types);
        }

        /* filter user (multi) */
        if ($request->filled('user_id')) {
            $uids = is_array($request->user_id)
                ? $request->user_id
                : explode(',', $request->user_id);
            $query->whereIn('causer_id', $uids);
        }

        /* filter event (multi) */
        if ($request->filled('event')) {
            $events = is_array($request->event)
                ? $request->event
                : explode(',', $request->event);
            $query->whereIn('description', $events);
        }

        /* filter rentang tanggal */
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        /* keyword search */
        if ($keyword = $request->input('search')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('description', 'like', "%{$keyword}%")
                  ->orWhere('subject_type', 'like', "%{$keyword}%")
                  ->orWhereHas('causer', fn($q) => $q->where('name', 'like', "%{$keyword}%"))
                  ->orWhere('properties', 'like', "%{$keyword}%");
            });
        }

        $recordsTotal    = (clone $query)->count();
        $recordsFiltered = (clone $query)->count();

        $length = (int) ($request->input('per_page', 10));
        $start  = ((int) ($request->input('page', 1)) - 1) * $length;
        $logs   = (clone $query)->offset($start)->limit($length)->get();

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
        $activity = Activity::findOrFail($id);
        $props    = json_decode($activity->properties ?? '{}', true);
        return response()->json([
            'event'      => $activity->description,
            'causer'     => optional($activity->causer)->name ?? 'System',
            'subject'    => class_basename($activity->subject_type) . ' #' . $activity->subject_id,
            'created_at' => $activity->created_at,
            'old'        => $props['old'] ?? [],
            'new'        => $props['attributes'] ?? [],
        ]);
    }
}