<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DesignerDashboardSeeder extends Seeder
{
    public function run(): void
    {
        // Demo account with a known password: never seed it in production.
        if (app()->isProduction()) {
            return;
        }

        $designer = User::updateOrCreate(['email' => 'hhh@gmail.com'], [
            'name' => 'Lina Designer',
            'password' => Hash::make('123123123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $designer->syncRoles(['designer']);

        DesignerProfile::updateOrCreate(['user_id' => $designer->id], [
            'full_name' => 'Lina Designer',
            'approval_status' => 'approved',
            'total_sales' => 12,
            'total_earnings' => 480,
        ]);

        $wallet = Wallet::updateOrCreate(
            ['user_id' => $designer->id],
            [
                'total_balance' => 480,
                'available_balance' => 245,
                'pending_balance' => 180,
                'total_withdrawn' => 55,
                'last_updated' => now(),
            ],
        );

        $transactions = [
            ['reference_id' => 'TRX-DEMO-00521', 'amount' => 42, 'status' => 'pending', 'description' => 'أرباح تصميم غزة في القلب'],
            ['reference_id' => 'TRX-DEMO-00520', 'amount' => 35, 'status' => 'available', 'description' => 'أرباح تصميم فلسطين حرة'],
            ['reference_id' => 'TRX-DEMO-00519', 'amount' => 28, 'status' => 'complete', 'description' => 'أرباح تصميم تراث فلسطيني'],
            ['reference_id' => 'TRX-DEMO-00518', 'amount' => 56, 'status' => 'complete', 'description' => 'أرباح تصميم زيتون فلسطين'],
        ];

        foreach ($transactions as $data) {
            WalletTransaction::updateOrCreate(
                ['reference_id' => $data['reference_id']],
                [
                    'wallet_id' => $wallet->id,
                    'type' => 'design_profit',
                    'amount' => $data['amount'],
                    'balance_after' => 480,
                    'description' => $data['description'],
                    'status' => $data['status'],
                ],
            );
        }

        $product = Product::query()->where('is_active', true)->first();

        if (! $product) {
            $category = Category::query()->firstOrCreate(
                ['slug' => 'apparel-demo'],
                ['name' => 'Apparel Demo', 'is_active' => true],
            );

            $product = Product::query()->create([
                'category_id' => $category->id,
                'name' => 'Demo T-Shirt',
                'code' => 'DEMO-DESIGN-001',
                'description' => 'منتج تجريبي لتوضيح تصاميم المصمم.',
                'image' => 'front/assets/images/customer/products/1.png',
                'is_active' => true,
            ]);
        }

        $designs = [
            ['title' => 'غزة في القلب', 'status' => 'published', 'designer_profit' => 42, 'published_at' => now()->subDays(2), 'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&h=520&fit=crop&q=85'],
            ['title' => 'فلسطين حرة', 'status' => 'review', 'designer_profit' => 35, 'published_at' => null, 'image' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=800&h=520&fit=crop&q=85'],
            ['title' => 'تراث فلسطيني', 'status' => 'draft', 'designer_profit' => 28, 'published_at' => null, 'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&h=520&fit=crop&q=85'],
            ['title' => 'زيتون فلسطين', 'status' => 'published', 'designer_profit' => 56, 'published_at' => now()->subDays(12), 'image' => 'https://images.unsplash.com/photo-1549490349-8643362247b5?w=800&h=520&fit=crop&q=85'],
        ];

        foreach ($designs as $data) {
            Design::updateOrCreate(
                ['designer_id' => $designer->id, 'title' => $data['title']],
                [
                    'product_id' => $product->id,
                    'description' => 'بيانات تجريبية للوحة المصمم.',
                    'image' => $data['image'],
                    'status' => $data['status'],
                    'base_price' => 25,
                    'selling_price' => 50,
                    'designer_profit' => $data['designer_profit'],
                    'submitted_at' => $data['status'] === 'review' ? now()->subDay() : null,
                    'published_at' => $data['published_at'],
                ],
            );
        }

        Notification::updateOrCreate(
            ['user_id' => $designer->id, 'title' => 'تم نشر تصميمك التجريبي'],
            [
                'type' => 'design_published',
                'message' => 'تم نشر تصميم غزة في القلب بنجاح.',
                'is_read' => false,
            ],
        );

        Notification::updateOrCreate(
            ['user_id' => $designer->id, 'title' => 'تصميم قيد المراجعة'],
            [
                'type' => 'design_review',
                'message' => 'تصميم فلسطين حرة بانتظار المراجعة.',
                'is_read' => true,
                'read_at' => now(),
            ],
        );

        $this->command?->info('Designer dashboard demo data seeded. Login: hhh@gmail.com / 123123123');
    }
}
