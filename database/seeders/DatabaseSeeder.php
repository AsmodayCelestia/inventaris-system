<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BrandSeeder::class,
            CategorySeeder::class,
            ItemTypeSeeder::class,
            UserSeeder::class,
            LocationSeeder::class,
            InventoryItemSeeder::class,
            InventorySeeder::class, 
        ]);
    }
}
