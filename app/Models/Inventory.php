<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity; // Import trait LogsActivity
use Spatie\Activitylog\LogOptions; // Import LogOptions untuk konfigurasi log

class Inventory extends Model
{
    use HasFactory, LogsActivity;

    // Kolom-kolom yang bisa diisi secara mass-assignment
    protected $fillable = [
        'inventory_number',
        'inventory_item_id', // Pastikan ini ada dan sesuai dengan foreignId di migrasi
        'acquisition_source',
        'procurement_date',
        'purchase_price',
        'estimated_depreciation',
        'status',
        'unit_id',
        // 'floor_id', // Kolom ini dihapus dari migrasi inventories, jadi jangan di fillable
        'room_id',
        'expected_replacement',
        'last_checked_at',
        'pj_id',
        'maintenance_frequency_type',
        'maintenance_frequency_value',
        'last_maintenance_at',
        // Kolom-kolom baru untuk jadwal maintenance yang terpisah
        'next_due_date',
        'next_due_km',
        'last_odometer_reading',
        'image_path', // TAMBAHKAN BARIS INI
        'qr_code_path', // TAMBAHKAN BARIS INI
    ];

    // Relasi ke model InventoryItem (barang master)
    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    // Relasi ke model User (penanggung jawab barang)
    public function personInCharge()
    {
        return $this->belongsTo(User::class, 'pj_id');
    }

    // Relasi ke model LocationUnit (unit lokasi)
    public function unit()
    {
        return $this->belongsTo(LocationUnit::class, 'unit_id');
    }

    // Relasi ke model Floor (lantai lokasi)
    // Jika floor_id dihapus dari tabel inventories, relasi ini bisa dihapus
    // atau diakses melalui $inventory->room->floor
    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    // Relasi ke model Room (ruangan lokasi)
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Relasi ke model InventoryMaintenance (riwayat maintenance)
    public function maintenances()
    {
        return $this->hasMany(InventoryMaintenance::class);
    }

    /**
     * Menghitung nilai jatuh tempo berikutnya berdasarkan tipe frekuensi.
     * Mengembalikan array ['type' => 'date|km', 'value' => Carbon|int|null]
     * atau null jika tidak ada data yang cukup.
     */
    public function calculateNextDue(): ?array
    {
        // Jika tidak ada data maintenance terakhir atau frekuensi, kembalikan null
        if (!$this->last_maintenance_at || !$this->maintenance_frequency_type || !$this->maintenance_frequency_value) {
            return null;
        }

        // Parse tanggal maintenance terakhir sebagai objek Carbon
        $start = Carbon::parse($this->last_maintenance_at);

        // Menggunakan match expression untuk penanganan tipe frekuensi yang berbeda
        return match ($this->maintenance_frequency_type) {
            'bulan' => ['type' => 'date', 'value' => $start->addMonths($this->maintenance_frequency_value)],
            'minggu' => ['type' => 'date', 'value' => $start->addWeeks($this->maintenance_frequency_value)],
            'semester' => ['type' => 'date', 'value' => $start->addMonths(6 * $this->maintenance_frequency_value)],
            'km' => [
                'type' => 'km',
                // Jika last_odometer_reading ada, tambahkan nilai frekuensi
                // Jika tidak ada, kembalikan null untuk nilai KM
                'value' => $this->last_odometer_reading ? ($this->last_odometer_reading + $this->maintenance_frequency_value) : null
            ],
            default => null, // Tipe frekuensi tidak dikenal
        };
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
