<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = json_decode(
            file_get_contents(database_path('data/categories.json')),
            true
        );

        Category::insert(array_map(fn ($c) => $c + [
            'created_at' => now(),
            'updated_at' => now(),
        ], $categories));
    }
}
