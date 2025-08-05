<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity; // Import trait LogsActivity
use Spatie\Activitylog\LogOptions; // Import LogOptions untuk konfigurasi log

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
        'user_id' 
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    // Optional if you want to track who created the log
    public function responsiblePerson()
    {
        return $this->belongsTo(User::class, 'user_id'); 
    }

    public function inventoryMaintenances()
    {
        return $this->hasMany(\App\Models\InventoryMaintenance::class, 'user_id');
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable() // Melacak perubahan pada semua kolom yang ada di $fillable
            ->logOnlyDirty() // Hanya mencatat perubahan jika ada kolom yang benar-benar berubah
            ->dontSubmitEmptyLogs(); // Tidak mencatat log jika tidak ada perubahan yang terdeteksi
    }
}
