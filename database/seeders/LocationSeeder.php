<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LocationUnit;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Units
        $units = json_decode(
            file_get_contents(database_path('data/location_units.json')),
            true
        );
        LocationUnit::insert(array_map(fn ($u) => $u + [
            'created_at' => now(),
            'updated_at' => now(),
        ], $units));

        // 3. Floors
        $floors = json_decode(
            file_get_contents(database_path('data/floors.json')),
            true
        );
        Floor::insert(array_map(fn ($f) => $f + [
            'created_at' => now(),
            'updated_at' => now(),
        ], $floors));

        // 4. Rooms
        $rooms = json_decode(
            file_get_contents(database_path('data/rooms.json')),
            true
        );
        Room::insert(array_map(fn ($r) => $r + [
            'created_at' => now(),
            'updated_at' => now(),
        ], $rooms));
    }
}
