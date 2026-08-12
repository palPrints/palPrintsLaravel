<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // ========== 1. إنشاء الصلاحيات (Permissions) ==========

        $permissions = [
            'view users', 'edit users', 'delete users', 'manage users',
            'view designs', 'create designs', 'edit designs', 'delete designs', 'approve designs',
            'view orders', 'create orders', 'manage orders', 'assign orders',
            'view products', 'create products', 'edit products', 'delete products',
            'view payments', 'manage payments', 'approve withdrawals',
            'view reports', 'export reports', 'manage settings',
            'approve designers', 'approve print_providers'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ========== 2. إنشاء الأدوار (Roles) ==========

        // دور العميل
        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $customer->syncPermissions([
            'view designs',
            'create orders',
            'view orders',
        ]);

        // دور المصمم
        $designer = Role::firstOrCreate(['name' => 'designer', 'guard_name' => 'web']);
        $designer->syncPermissions([
            'view designs',
            'create designs',
            'edit designs',
            'delete designs',
            'view orders',
        ]);

        // دور المطبعة
        $printProvider = Role::firstOrCreate(['name' => 'print_provider', 'guard_name' => 'web']);
        $printProvider->syncPermissions([
            'view designs',
            'view orders',
            'manage orders',
            'view products',
        ]);

        // دور المدير (Admin) - كل الصلاحيات
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // ========== 3. إنشاء مستخدمين تجريبيين ==========

        $users = [
            ['name' => 'Admin', 'email' => 'admin@palprints.com', 'role' => 'admin'],
            ['name' => 'Customer', 'email' => 'customer@palprints.com', 'role' => 'customer'],
            ['name' => 'Designer', 'email' => 'designer@palprints.com', 'role' => 'designer'],
            ['name' => 'Print Provider', 'email' => 'print@palprints.com', 'role' => 'print_provider'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                    'role' => $userData['role'],
                    'is_active' => true,
                    'is_verified' => true,
                ]
            );
            $user->assignRole($userData['role']);
        }
    }
}
