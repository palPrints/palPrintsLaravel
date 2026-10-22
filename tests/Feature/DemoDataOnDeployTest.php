<?php

use App\Models\Order;
use App\Models\PrintProvider;
use App\Models\Product;
use App\Models\User;
use App\Support\DemoData;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

test('a site with too few print shops gets the demo catalogue, shops, customers and orders', function () {
    $result = DemoData::seedIfNeeded();

    expect($result['seeded'])->toBeTrue()
        ->and($result['shops_before'])->toBe(0)
        ->and($result['shops_after'])->toBeGreaterThanOrEqual(4)
        ->and(Product::where('code', 'TSHIRT-CLASSIC')->exists())->toBeTrue()
        ->and(Order::where('order_number', 'like', 'DEMO-%')->exists())->toBeTrue()
        ->and(User::where('email', 'like', 'demo.shop%@palprints.test')->count())->toBe(9);
});

test('nothing is seeded when the site already has enough print shops', function () {
    config(['demo.min_shops' => 2]);
    foreach ([1, 2] as $n) {
        User::factory()->create()->printProvider()->create(['company_name' => 'مطبعة '.$n, 'approval_status' => 'approved', 'is_active' => true]);
    }

    expect(DemoData::seedIfNeeded())->toMatchArray(['seeded' => false, 'shops_before' => 2])
        ->and(Order::count())->toBe(0);
});

test('on a production site the demo admin cannot sign in', function () {
    $this->app['env'] = 'production';

    DemoData::seedIfNeeded();

    $admin = User::where('email', DemoData::DEMO_ADMIN_EMAIL)->first();
    expect($admin)->not->toBeNull()
        ->and($admin->is_active)->toBeFalse()
        ->and(Hash::check('password', $admin->password))->toBeFalse();

    // the demo shops stay usable for trying the site
    expect(PrintProvider::whereHas('user', fn ($q) => $q->where('email', 'like', 'demo.shop%@palprints.test'))->count())->toBe(9);
});

test('the command reports what it did', function () {
    $this->artisan('demo:seed-if-needed')->expectsOutputToContain('Demo data seeded')->assertSuccessful();
});
