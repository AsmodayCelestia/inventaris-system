<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'floor_id',
        'pj_lokasi_id', // Tambahkan ini
    ];


    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function locationPersonInCharge()
    {
        return $this->belongsTo(User::class, 'pj_lokasi_id');
    }
}
