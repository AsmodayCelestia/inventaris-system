<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'type_id');
    }
}
