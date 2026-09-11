<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed (or reset) the AgroTrace admin user.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@agrotrace.com'],
            [
                'name' => 'AdminAgro',
                'password' => bcrypt('Admin'),
                'role' => 'admin',
            ]
        );
    }
}
