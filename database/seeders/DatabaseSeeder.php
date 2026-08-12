<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // تشغيل Seeder الأدوار والصلاحيات أولاً
        $this->call(RoleAndPermissionSeeder::class);

        // بعدين نقدر نضيف مستخدمين إضافيين إذا حبينا
        // User::factory(10)->create();
    }
}
