<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity; // Import trait LogsActivity
use Spatie\Activitylog\LogOptions; // Import LogOptions untuk konfigurasi log
use Illuminate\Support\Facades\Storage;

class InventoryMaintenance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'inventory_id',
        'inspection_date',
        'issue_found',
        'solution_taken',
        'notes',
        'status',
        'photo_1',
        'photo_2',
        'photo_3',
        'user_id',
    ];

    /* -------------------------------------------------
       URL mutator untuk Vue (agar langsung full URL)
    -------------------------------------------------- */
    protected $appends = [
        'photo_1_url',
        'photo_2_url',
        'photo_3_url',
        'status_label',
    ];

public function getPhoto1UrlAttribute()
{
    return $this->photo_1 ?? null;
}

public function getPhoto2UrlAttribute()
{
    return $this->photo_2 ?? null;
}

public function getPhoto3UrlAttribute()
{
    return $this->photo_3 ?? null;
}

    /* -------------------------------------------------
       Label status untuk Vue
    -------------------------------------------------- */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'planning' => 'Direncanakan',
            'done'     => 'Selesai',
            default    => ucfirst($this->status),
        };
    }

    /* -------------------------------------------------
       Relasi yang sudah ada
    -------------------------------------------------- */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function responsiblePerson()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
