<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'floor_id'];

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

}
