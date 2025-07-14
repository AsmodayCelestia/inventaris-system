<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'inspection_date',
        'issue_found',
        'solution_taken',
        'notes',
        'status',
        'photo_1',
        'photo_2',
        'photo_3',
        'user_id' 
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    // Optional if you want to track who created the log
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id'); 
    }
}
