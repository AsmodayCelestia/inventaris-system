<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'item_code',
        'name',
        'quantity',
        'brand_id',
        'category_id',
        'type_id',
        'manufacturer',
        'manufacture_year',
        'isbn',
        'image_path'
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function type()
    {
        return $this->belongsTo(ItemType::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

}


