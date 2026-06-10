<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate: never overwrites an existing admin (a changed password
        // survives deploys). Seeds the default admin only once, on a fresh DB.
        User::firstOrCreate(
            ['email' => 'admin@crynova.io'],
            [
                'name'     => 'Admin',
                'password' => bcrypt('changeme123!'),
                'role'     => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
