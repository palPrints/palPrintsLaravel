<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            $this->command?->warn('Admin seeding skipped: set ADMIN_EMAIL and ADMIN_PASSWORD first.');

            return;
        }

        $admin = User::updateOrCreate(
            [
                'email' => $email,
            ],
            [
                'name' => env('ADMIN_NAME', 'PalPrints Admin'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $admin->syncRoles(['admin']);
    }
}
