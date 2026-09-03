<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $roles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->whereIn('roles.name', ['designer', 'print_provider'])
            ->select('users.id', 'users.name', 'roles.name as role')
            ->get();

        foreach ($roles as $account) {
            if ($account->role === 'designer') {
                DB::table('designer_profiles')->insertOrIgnore([
                    'user_id' => $account->id,
                    'full_name' => $account->name,
                    'approval_status' => 'draft',
                    'total_sales' => 0,
                    'total_earnings' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($account->role === 'print_provider') {
                DB::table('print_providers')->insertOrIgnore([
                    'user_id' => $account->id,
                    'company_name' => $account->name,
                    'approval_status' => 'draft',
                    'total_orders' => 0,
                    'total_earnings' => 0,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('wallets')->insertOrIgnore([
                'user_id' => $account->id,
                'total_balance' => 0,
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_withdrawn' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Existing account data is intentionally preserved on rollback.
    }
};
