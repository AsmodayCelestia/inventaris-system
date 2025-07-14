<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $json  = file_get_contents(database_path('data/users.json'));
        $users = json_decode($json, true);

        foreach ($users as &$user) {
            $user['password']   = Hash::make($user['password']);
            $user['created_at'] = now();
            $user['updated_at'] = now();
        }
        User::insert($users);
    }
}
