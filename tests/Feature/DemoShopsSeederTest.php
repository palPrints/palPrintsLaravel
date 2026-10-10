<?php

use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Database\Seeders\DemoShopsSeeder;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('admin');

    // The real shop whose settings the demo shops copy: a t-shirt and a mug, each with one variant, one area and one price.
    $category = Category::create(['name' => 'منتجات', 'slug' => 'all']);
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('print_provider');
    $this->source = $owner->printProvider()->create(['company_name' => 'نور', 'approval_status' => 'approved', 'is_active' => true, 'phone' => '0599'])->primaryBranch();
    $this->source->update(['city' => 'غزة']);

    foreach (['tshirt-classic', 'mug-ceramic'] as $code) {
        $product = Product::create(['category_id' => $category->id, 'name' => $code, 'code' => $code, 'is_active' => true]);
        $variant = Variant::create(['product_id' => $product->id, 'sku' => strtoupper($code), 'is_active' => true]);
        $offering = $this->source->branchProductOfferings()->create([
            'product_id' => $product->id, 'base_price' => 20, 'currency' => 'ILS',
            'production_time_min' => 3, 'production_time_max' => 3, 'daily_capacity' => 10, 'is_active' => true,
        ]);
        $offering->branchOfferingVariants()->create(['variant_id' => $variant->id, 'is_available' => true]);
        $area = $offering->branchPrintAreas()->create(['code' => 'front', 'name' => 'أمامي', 'max_width_mm' => 300, 'max_height_mm' => 400, 'is_active' => true]);
        $method = App\Models\PrintingMethod::firstOrCreate(['code' => 'dtg'], ['name' => 'DTG', 'is_active' => true]);
        $capability = $area->branchPrintCapabilities()->create(['printing_method_id' => $method->id, 'applies_to_all_variants' => true, 'max_width_mm' => 300, 'max_height_mm' => 400, 'is_active' => true]);
        $offering->branchPricingRules()->create(['branch_print_capability_id' => $capability->id, 'min_quantity' => 1, 'pricing_type' => 'print', 'value_type' => 'fixed', 'amount' => 8, 'priority' => 10, 'is_active' => true]);
    }
});

test('the demo seeder adds three shops that copy the real shop with their own prices, and orders in every state', function () {
    (new DemoShopsSeeder())->run();

    $shops = User::where('email', 'like', DemoShopsSeeder::SHOP_EMAIL)->get();
    expect($shops)->toHaveCount(3)
        ->and(Order::where('order_number', 'like', 'DEMO-%')->pluck('status')->unique()->sort()->values()->all())
        ->toBe(['awaiting_payment_review', 'cancelled', 'confirmed', 'delivered', 'processing', 'ready', 'rejected', 'shipped']);

    $prices = $shops->map(fn ($shop) => (float) $shop->printProvider->primaryBranch()->branchProductOfferings()->orderBy('id')->first()->base_price)->all();
    expect($prices)->toBe([18.0, 22.0, 26.0]); // 20 x 0.9, 1.1, 1.3

    $copy = $shops->first()->printProvider->primaryBranch()->branchProductOfferings()->first();
    expect($copy->branchOfferingVariants()->count())->toBe(1)
        ->and($copy->branchPrintAreas()->first()->branchPrintCapabilities()->count())->toBe(1)
        ->and($copy->branchPricingRules()->count())->toBe(1);
});

test('running it again adds nothing', function () {
    (new DemoShopsSeeder())->run();
    $counts = [Order::count(), OrderItem::count(), User::count(), App\Models\BranchProductOffering::count()];

    (new DemoShopsSeeder())->run();

    expect([Order::count(), OrderItem::count(), User::count(), App\Models\BranchProductOffering::count()])->toBe($counts);
});

test('the demo orders show the admin real choices: three suggested shops for a new order and the rest for a refused one', function () {
    (new DemoShopsSeeder())->run();

    $html = $this->actingAs($this->admin)->get(route('admin.payment-notices'))->assertOk()->getContent();
    foreach (['مطبعة نابلس التجريبية', 'مطبعة الخليل التجريبية', 'مطبعة بيت لحم التجريبية'] as $name) {
        expect($html)->toContain($name);
    }

    $orders = $this->actingAs($this->admin)->get(route('admin.orders'))->assertOk()->getContent();
    preg_match_all('/data-reroute="([^"]+)"/', $orders, $matches);
    $choices = collect($matches[1])->map(fn ($json) => html_entity_decode($json));

    expect($choices)->toHaveCount(2); // the two refused orders
    // DEMO-1008 was refused by the real shop: the three demo shops are offered, the real one is not.
    expect($choices->contains(fn ($json) => str_contains($json, 'مطبعة نابلس التجريبية') && str_contains($json, 'مطبعة الخليل التجريبية') && ! str_contains($json, '"name":"نور"')))->toBeTrue();
    // DEMO-1009 was refused by the real shop and by the Nablus one: only two demo shops are left.
    expect($choices->contains(fn ($json) => ! str_contains($json, 'مطبعة نابلس التجريبية') && str_contains($json, 'مطبعة الخليل التجريبية')))->toBeTrue();

    expect(Notification::where('user_id', $this->admin->id)->where('type', 'order.rejected_by_provider')->count())->toBe(2);
});

test('the cleanup removes exactly the demo data and keeps the real shop and its orders', function () {
    $real = Order::create(['user_id' => User::factory()->create()->id, 'order_number' => 'PP-REAL-1', 'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'bank', 'subtotal' => 10, 'total_amount' => 10]);
    (new DemoShopsSeeder())->run();

    $removed = DemoShopsSeeder::clean();

    expect($removed['orders'])->toBe(11)
        ->and(Order::where('order_number', 'like', 'DEMO-%')->count())->toBe(0)
        ->and(User::where('email', 'like', 'demo.%@palprints.test')->count())->toBe(0)
        ->and(App\Models\PrintProviderBranch::count())->toBe(1)
        ->and(Order::find($real->id))->not->toBeNull()
        ->and($this->source->fresh()->branchProductOfferings()->count())->toBe(2)
        ->and(Notification::where('message', 'like', '%DEMO-%')->count())->toBe(0);
});

test('with no approved real shop to copy it stops with a clear message instead of making half of the data', function () {
    App\Models\PrintProvider::query()->update(['approval_status' => 'draft']);

    expect(fn () => (new DemoShopsSeeder())->run())->toThrow(RuntimeException::class, 'no approved print shop');
    expect(User::where('email', 'like', 'demo.%@palprints.test')->count())->toBe(0);
});
