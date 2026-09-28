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

        $product = \App\Models\Product::query()->where('is_active', true)->first();

        if (! $product) {
            return;
        }

        // Keep the seeder idempotent: never duplicate the demo designs.
        if (Design::where('designer_id', $designer->id)->exists()) {
            return;
        }

        // التصميم الأول
        Design::create([
            'designer_id' => $designer->id,
            'product_id' => $product->id,
            'title' => 'غزة في القلب',
            'description' => 'تصميم فلسطيني يعبر عن حب غزة.',
            'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&h=520&fit=crop&q=85',
            'status' => 'published',
            'base_price' => 25,
            'selling_price' => 50,
            'designer_profit' => 25,
        ]);

        // التصميم الثاني
        Design::create([
            'designer_id' => $designer->id,
            'product_id' => $product->id,
            'title' => 'فلسطين حرة',
            'description' => 'تصميم يحمل رسالة الحرية والانتماء.',
            'image' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=800&h=520&fit=crop&q=85',
            'status' => 'review',
            'base_price' => 22,
            'selling_price' => 45,
            'designer_profit' => 23,
        ]);

        // التصميم الثالث
        Design::create([
            'designer_id' => $designer->id,
            'product_id' => $product->id,
            'title' => 'تراث فلسطيني',
            'description' => 'تصميم مستوحى من التراث الفلسطيني.',
            'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&h=520&fit=crop&q=85',
            'status' => 'draft',
            'base_price' => 18,
            'selling_price' => 40,
            'designer_profit' => 22,
        ]);

        // التصميم الرابع
        Design::create([
            'designer_id' => $designer->id,
            'product_id' => $product->id,
            'title' => 'زيتون فلسطين',
            'description' => 'تصميم مستوحى من شجرة الزيتون الفلسطينية.',
            'image' => 'https://images.unsplash.com/photo-1549490349-8643362247b5?w=800&h=520&fit=crop&q=85',
            'status' => 'rejected',
            'base_price' => 30,
            'selling_price' => 60,
            'designer_profit' => 30,
            'rejection_reason' => 'تحتاج تنسيق ألوان أفضل.',
        ]);
    }
}