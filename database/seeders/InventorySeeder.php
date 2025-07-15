<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $items = json_decode(
            file_get_contents(database_path('data/inventories.json')),
            true
        );

        foreach ($items as $i => $item) {
            try {
                Inventory::create($item + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                dump("❌ Error on inventory row $i:");
                dump($item);
                throw $e;
            }
        }
    }
}
