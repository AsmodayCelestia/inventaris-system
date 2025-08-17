<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = json_decode(
            file_get_contents(database_path('data/divisions.json')),
            true
        );

        Division::insert(array_map(fn ($d) => $d + [
            'created_at' => now(),
            'updated_at' => now(),
        ], $divisions));
    }
}