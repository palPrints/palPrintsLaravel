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

        $admin = User::firstOrNew(['email' => $email]);

        // The seeder runs on every deploy, so an existing admin keeps the password they chose.
        if (! $admin->exists) {
            $admin->password = Hash::make($password);
        }

        $admin->forceFill([
            'name' => env('ADMIN_NAME', 'PalPrints Admin'),
            'email_verified_at' => $admin->email_verified_at ?? now(),
            'is_active' => true,
        ])->save();

        $admin->syncRoles(['admin']);
    }
}
