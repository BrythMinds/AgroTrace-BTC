<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ensures the AgroTrace admin user exists with the correct
     * credentials, regardless of the current state of the database.
     * This runs automatically on every deployment, before seeders,
     * so the admin account is always available.
     */
    public function up(): void
    {
        DB::table('users')->updateOrCreate(
            ['email' => 'admin@agrotrace.com'],
            [
                'name' => 'AdminAgro',
                'password' => bcrypt('Admin'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally left empty: we never want to delete the admin
     * user when rolling back migrations.
     */
    public function down(): void
    {
        //
    }
};
