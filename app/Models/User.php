<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\InventoryMaintenance;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Kolom yang bisa diisi mass-assignment
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    // Kolom yang disembunyikan saat serialisasi
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Tipe cast
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * RELASI: User memiliki banyak data maintenance (jika dia petugas/pj)
     */
    public function maintenances()
    {
        return $this->hasMany(InventoryMaintenance::class, 'pj_id');
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
     * Validasi bisa ditempatkan di FormRequest atau Service layer,
     * tapi bisa ditaruh di sini untuk dokumentasi:
     *
     * [
     *   'name' => 'required|string|max:255',
     *   'email' => 'required|email|unique:users,email',
     *   'password' => 'required|min:6',
     *   'role' => 'required|in:admin,karyawan'
     * ]
     */
}
