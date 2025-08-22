<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;           // 1
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InventoryMaintenance extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;            // 2

    protected $fillable = [
        'inventory_id',
        'inspection_date',
        'issue_found',
        'solution_taken',
        'notes',
        'status',
        'cost',
        'photo_1',
        'photo_2',
        'photo_3',
        'creator_id',
        'user_id',
    ];

    protected $appends = [
        'photo_1_url',
        'photo_2_url',
        'photo_3_url',
        'status_label',
    ];

    /* URL mutator */
    public function getPhoto1UrlAttribute() { return $this->photo_1 ?? null; }
    public function getPhoto2UrlAttribute() { return $this->photo_2 ?? null; }
    public function getPhoto3UrlAttribute() { return $this->photo_3 ?? null; }

    /* Label status baru */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'reported'    => 'Dilaporkan',
            'on_progress' => 'Diproses',
            'handled'     => 'Ditangani',
            'done'        => 'Selesai',
            'cancelled'   => 'Dibatalkan',
        };
    }

    /* Relasi */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function responsiblePerson()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
    
    /* Activity-log */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /* Scope helper (opsional) */
    public function scopeNeedAction($query)
    {
        return $query->where('status', 'reported');
    }
}