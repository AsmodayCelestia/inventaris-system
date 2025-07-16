<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Pastikan ini di-import
use Spatie\Activitylog\Traits\LogsActivity; // Import trait LogsActivity
use Spatie\Activitylog\LogOptions; // Import LogOptions untuk konfigurasi log

class InventoryItem extends Model
{
    use HasFactory, LogsActivity; // <--- TAMBAHKAN HasFactory DAN LogsActivity DI SINI

    protected $fillable = [
        'item_code',
        'name',
        'quantity',
        'brand_id',
        'category_id',
        'type_id',
        'manufacturer',
        'manufacture_year',
        'isbn',
        'image_path'
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function type()
    {
        return $this->belongsTo(ItemType::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Konfigurasi untuk Activity Log.
     * Menentukan kolom mana yang akan dilacak perubahannya.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable() // Melacak perubahan pada semua kolom yang ada di $fillable
            ->logOnlyDirty() // Hanya mencatat perubahan jika ada kolom yang benar-benar berubah
            ->dontSubmitEmptyLogs(); // Tidak mencatat log jika tidak ada perubahan yang terdeteksi
    }
}
