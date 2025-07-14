<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemType;

class ItemTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = json_decode(
            file_get_contents(database_path('data/item_types.json')),
            true
        );

        ItemType::insert(array_map(fn ($t) => $t + [
            'created_at' => now(),
            'updated_at' => now(),
        ], $types));
    }
}
