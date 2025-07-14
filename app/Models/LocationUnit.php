<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LocationUnit extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function floors()
    {
        return $this->hasMany(Floor::class); 
    }
}
