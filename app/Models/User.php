<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;   // ← tambahkan
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'division_id',        // ← ganti dari divisi
        'role',
        'is_room_supervisor',
        'is_pj_maintenance',
    ];

    /**
     * Hidden attributes for arrays.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts for attributes.
     */
    protected $casts = [
        'email_verified_at'     => 'datetime',
        'is_room_supervisor'    => 'boolean',
        'is_pj_maintenance'     => 'boolean',
    ];

    // ============================
    //          RELATIONS
    // ============================

    /**
     * Relasi ke Division.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Inventaris yang dia jadi penanggung jawab langsung (pj_id).
     */
    public function responsibleInventories()
    {
        return $this->hasMany(Inventory::class, 'pj_id');
    }

    /**
     * Maintenance yang dilakukan oleh user ini (user_id di inventory_maintenances).
     */
    public function performedMaintenances()
    {
        return $this->hasMany(InventoryMaintenance::class, 'user_id');
    }

    /**
     * Ruangan yang dia jadi penanggung jawab lokasi (pj_lokasi_id).
     */
    public function responsibleRooms()
    {
        return $this->hasMany(Room::class, 'pj_lokasi_id');
    }

    /**
     * Inventaris yang berada di ruangan-ruangan yang dia tangani.
     */
    public function inventoriesInResponsibleRooms()
    {
        return $this->hasManyThrough(
            Inventory::class,
            Room::class,
            'pj_lokasi_id',   // FK di tabel rooms yang mengarah ke user
            'room_id',        // FK di tabel inventories yang mengarah ke rooms
            'id',             // PK di user
            'id'              // PK di rooms
        );
    }

    // ============================
    //     MUTATORS & ACCESSORS
    // ============================

    /**
     * Auto hash password saat diset.
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn($value) => Hash::make($value),
        );
    }

    /**
     * Selalu return lowercase untuk role.
     */
    public function getRoleAttribute($value)
    {
        return strtolower($value);
    }

    // ============================
    //         LOGGING
    // ============================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /* Maintenance yang dia BUAT (creator) */
    public function createdMaintenances()
    {
        return $this->hasMany(InventoryMaintenance::class, 'creator_id');
    }

    /* Maintenance yang dia JALANKAN sebagai PJ */
    public function assignedMaintenances()
    {
        return $this->hasMany(InventoryMaintenance::class, 'user_id');
    }
}