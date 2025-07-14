<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_number',
        'item_code',
        'acquisition_source',
        'procurement_date',
        'purchase_price',
        'estimated_depreciation',
        'status',
        'unit_id',
        'floor_id', 
        'room_id',
        'expected_replacement',
        'last_checked_at',
        'pj_id'
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }


    public function personInCharge()
    {
        return $this->belongsTo(User::class, 'pj_id');
    }

    public function unit()
    {
        return $this->belongsTo(LocationUnit::class, 'unit_id');
    }

    public function floor()
    {
        return $this->belongsTo(Floor::class); 
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function maintenances()
    {
        return $this->hasMany(InventoryMaintenance::class);
    }
}
