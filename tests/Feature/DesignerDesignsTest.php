<?php

use App\Models\Category;
use App\Models\Design;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DesignerDashboardSeeder;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('designer designs page loads real design data from the database', function () {
    $designer = User::factory()->create();
    $designer->assignRole('designer');
    $designer->designerProfile()->create([
        'full_name' => $designer->name,
        'approval_status' => 'approved',
    ]);

    $category = Category::create([
        'name' => 'ملابس',
        'slug' => 'apparel-demo',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'تيشيرت كاجوال',
        'code' => 'TSHIRT-001',
        'description' => 'منتج تجريبي.',
        'image' => 'products/demo-shirt.jpg',
        'is_active' => true,
    ]);

    Design::create([
        'designer_id' => $designer->id,
        'product_id' => $product->id,
        'title' => 'تصميم تجريبي أول',
        'description' => 'وصف أول تصميم تجريبي',
        'image' => 'designs/demo-one.jpg',
        'status' => 'review',
        'base_price' => 25,
        'selling_price' => 50,
        'designer_profit' => 25,
    ]);

    Design::create([
        'designer_id' => $designer->id,
        'product_id' => $product->id,
        'title' => 'تصميم تجريبي ثاني',
        'description' => 'وصف ثاني تصميم تجريبي',
        'image' => 'designs/demo-two.jpg',
        'status' => 'published',
        'base_price' => 30,
        'selling_price' => 60,
        'designer_profit' => 30,
    ]);

    $this->actingAs($designer)
        ->get(route('designer.designs.index'))
        ->assertOk()
        ->assertViewIs('designer.designs.index')
        ->assertSee('window.palPrintsDesignerDesigns', false)
        ->assertSee('تصميم تجريبي أول', false)
        ->assertSee('تصميم تجريبي ثاني', false);
});

test('designer dashboard seeder still creates demo designs when no catalog product exists', function () {
    Product::query()->delete();

    (new DesignerDashboardSeeder())->run();

    $designer = User::query()->where('email', 'hhh@gmail.com')->first();

    expect($designer)->not->toBeNull()
        ->and(Product::query()->where('is_active', true)->count())->toBeGreaterThan(0)
        ->and(Design::query()->where('designer_id', $designer->id)->count())->toBeGreaterThan(0);
});
