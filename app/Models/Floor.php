<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity; // Import trait LogsActivity
use Spatie\Activitylog\LogOptions; // Import LogOptions untuk konfigurasi log

class Floor extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['number', 'unit_id'];

    public function unit()
    {
        return $this->belongsTo(LocationUnit::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable() // Melacak perubahan pada semua kolom yang ada di $fillable
            ->logOnlyDirty() // Hanya mencatat perubahan jika ada kolom yang benar-benar berubah
            ->dontSubmitEmptyLogs(); // Tidak mencatat log jika tidak ada perubahan yang terdeteksi
    }
}
