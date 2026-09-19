<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Design;
use App\Models\User;

class DesignSeeder extends Seeder
{
    public function run(): void
    {
        // الحصول على المصمم التجريبي
        $designer = User::where('email', 'designer@palprint.test')->first();

        // إذا لم نجد المصمم نوقف الـ Seeder
        if (!$designer) {
            return;
        }

        // التصميم الأول
        Design::create([
            'designer_id' => $designer->id,
            'title' => 'غزة في القلب',
            'description' => 'تصميم فلسطيني يعبر عن حب غزة.',
            'image' => null,
            'status' => 'published',
        ]);

        // التصميم الثاني
        Design::create([
            'designer_id' => $designer->id,
            'title' => 'فلسطين حرة',
            'description' => 'تصميم يحمل رسالة الحرية والانتماء.',
            'image' => null,
            'status' => 'review',
        ]);

        // التصميم الثالث
        Design::create([
            'designer_id' => $designer->id,
            'title' => 'تراث فلسطيني',
            'description' => 'تصميم مستوحى من التراث الفلسطيني.',
            'image' => null,
            'status' => 'draft',
        ]);

        // التصميم الرابع
        Design::create([
            'designer_id' => $designer->id,
            'title' => 'زيتون فلسطين',
            'description' => 'تصميم مستوحى من شجرة الزيتون الفلسطينية.',
            'image' => null,
            'status' => 'rejected',
        ]);
    }
}