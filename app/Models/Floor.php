<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Floor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'unit_id'];

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
}
