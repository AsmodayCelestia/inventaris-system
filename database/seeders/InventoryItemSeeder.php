<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryItem;

class InventoryItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = json_decode(
            file_get_contents(database_path('data/inventory_items.json')),
            true
        );

        InventoryItem::insert(array_map(fn ($i) => $i + [
            'created_at' => now(),
            'updated_at' => now(),
        ], $items));
    }
}
