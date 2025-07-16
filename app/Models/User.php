<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\InventoryMaintenance; // Sudah tidak perlu diuse jika hanya digunakan dalam method

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'divisi',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    // ... (accessor dan mutator password & role)

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => Hash::make($value)
        );
    }

    public function getRoleAttribute($value)
    {
        return strtolower($value);
    }
}