<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity; // Import trait LogsActivity
use Spatie\Activitylog\LogOptions; // Import LogOptions untuk konfigurasi log

class ItemType extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name'];

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'type_id');
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable() // Melacak perubahan pada semua kolom yang ada di $fillable
            ->logOnlyDirty() // Hanya mencatat perubahan jika ada kolom yang benar-benar berubah
            ->dontSubmitEmptyLogs(); // Tidak mencatat log jika tidak ada perubahan yang terdeteksi
    }
}
