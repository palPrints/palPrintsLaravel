<?php

namespace App\Services;

use App\Models\DesignerProfile;
use App\Models\PrintProvider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountProvisioningService
{
    public function create(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => Hash::make($attributes['password']),
                'locale' => $attributes['locale'] ?? app()->getLocale(),
                'is_active' => true,
            ]);

            if (! empty($attributes['email_verified_at'])) {
                $user->forceFill([
                    'email_verified_at' => $attributes['email_verified_at'],
                ])->saveQuietly();
            }

            $user->syncRoles([$attributes['account_type']]);

            if ($attributes['account_type'] === 'designer') {
                DesignerProfile::create([
                    'user_id' => $user->id,
                    'full_name' => $user->name,
                    'approval_status' => 'draft',
                ]);
            }

            if ($attributes['account_type'] === 'print_provider') {
                PrintProvider::create([
                    'user_id' => $user->id,
                    'company_name' => $user->name,
                    'approval_status' => 'draft',
                    'is_active' => true,
                ]);
            }

            return $user;
        });
    }
}
