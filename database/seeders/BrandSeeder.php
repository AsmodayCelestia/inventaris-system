<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = json_decode(
            file_get_contents(database_path('data/brands.json')),
            true
        );

        Brand::insert(array_map(fn ($b) => $b + [
            'created_at' => now(),
            'updated_at' => now(),
        ], $brands));
    }
}
