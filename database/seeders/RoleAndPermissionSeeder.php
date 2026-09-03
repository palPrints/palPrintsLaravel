<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // تنظيف Cache الخاص بالصلاحيات
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ==============================
        // 1. إنشاء الصلاحيات
        // ==============================

        $permissions = [
            // Users
            'view users',
            'edit users',
            'delete users',
            'manage users',

            // Designs
            'view designs',
            'create designs',
            'edit designs',
            'delete designs',
            'approve designs',

            // Orders
            'view orders',
            'create orders',
            'manage orders',
            'assign orders',

            // Products
            'view products',
            'create products',
            'edit products',
            'delete products',

            // Payments
            'view payments',
            'manage payments',
            'approve withdrawals',

            // Reports & Settings
            'view reports',
            'export reports',
            'manage settings',

            // Approvals
            'approve designers',
            'approve print_providers',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // ==============================
        // 2. Customer
        // ==============================

        $customer = Role::firstOrCreate([
            'name' => 'customer',
            'guard_name' => 'web',
        ]);

        $customer->syncPermissions([
            'view designs',
            'create orders',
            'view orders',
        ]);

        // ==============================
        // 3. Designer
        // ==============================

        $designer = Role::firstOrCreate([
            'name' => 'designer',
            'guard_name' => 'web',
        ]);

        $designer->syncPermissions([
            'view designs',
            'create designs',
            'edit designs',
            'delete designs',
            'view orders',
        ]);

        // ==============================
        // 4. Print Provider
        // ==============================

        $printProvider = Role::firstOrCreate([
            'name' => 'print_provider',
            'guard_name' => 'web',
        ]);

        $printProvider->syncPermissions([
            'view designs',
            'view orders',
            'manage orders',
            'view products',
        ]);

        // ==============================
        // 5. Delivery Partner
        // ==============================

        $deliveryPartner = Role::firstOrCreate([
            'name' => 'delivery_partner',
            'guard_name' => 'web',
        ]);

        $deliveryPartner->syncPermissions([
            'view orders',
        ]);

        // ==============================
        // 6. Admin
        // ==============================

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions(Permission::all());

        // تنظيف Cache بعد إنشاء الأدوار والصلاحيات
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
