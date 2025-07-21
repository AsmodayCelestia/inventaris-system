<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Casts\Attribute;
// use App\Models\InventoryMaintenance; // Baris ini bisa dihapus jika tidak digunakan langsung di sini

use Spatie\Activitylog\Traits\LogsActivity; // Import trait LogsActivity
use Spatie\Activitylog\LogOptions; // Import LogOptions untuk konfigurasi log
use Laravel\Sanctum\HasApiTokens; // <-- INI YANG HARUS DITAMBAHKAN!

class User extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity, HasApiTokens; // <--- Pastikan HasApiTokens ada di sini

    // Kolom yang bisa diisi mass-assignment
    protected $fillable = [
        'name',
        'email',
        'password',
        'divisi',
        'role',
    ];

    // Kolom yang disembunyikan saat serialisasi (dan tidak akan dilog oleh Spatie)
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Tipe cast
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * RELASI: User bertanggung jawab atas banyak inventaris (sebagai PJ Barang)
     */
    public function responsibleInventories()
    {
        return $this->hasMany(Inventory::class, 'pj_id');
    }

    /**
     * RELASI: User melakukan banyak record maintenance (sebagai petugas yang mengisi log)
     */
    public function performedMaintenances()
    {
        return $this->hasMany(InventoryMaintenance::class, 'user_id'); // Menunjuk ke user_id di tabel inventory_maintenances
    }

    /**
     * RELASI: User dapat menjadi PJ lokasi untuk banyak ruangan
     */
    public function responsibleRooms()
    {
        return $this->hasMany(Room::class, 'pj_lokasi_id');
    }

    /**
     * ACCESSOR/MUTATOR: Otomatis hash password saat di-set
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => Hash::make($value)
        );
    }

    /**
     * ACCESSOR: Role selalu huruf kecil
     */
    public function getRoleAttribute($value)
    {
        return strtolower($value);
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

    /**
     * Validasi bisa ditempatkan di FormRequest atau Service layer,
     * tapi bisa ditaruh di sini untuk dokumentasi:
     *
     * [
     * 'name' => 'required|string|max:255',
     * 'email' => 'required|email|unique:users,email',
     * 'password' => 'required|min:6',
     * 'role' => 'required|in:admin,karyawan'
     * ]
     */
}
