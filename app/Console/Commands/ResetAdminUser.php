<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or reset the AgroTrace admin user credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = 'admin@agrotrace.com';

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'AdminAgro',
                'password' => bcrypt('Admin'),
                'role' => 'admin',
            ]
        );

        $this->info("Admin user ready: {$admin->email} (id: {$admin->id})");

        return self::SUCCESS;
    }
}
