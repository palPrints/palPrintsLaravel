<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('demo data seeder creates an idempotent catalog and demo accounts', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $designer = User::query()->where('email', 'designer@palprint.test')->firstOrFail();

    expect($designer->hasRole('designer'))->toBeTrue()
        ->and(Hash::check('password', $designer->password))->toBeTrue();

    $this->assertDatabaseCount('users', 5)
        ->assertDatabaseCount('products', 2)
        ->assertDatabaseCount('variants', 7)
        ->assertDatabaseCount('provider_offerings', 2)
        ->assertDatabaseCount('pricing_rules', 3);

    expect(Product::query()->where('code', 'TSHIRT-CLASSIC')->exists())->toBeTrue();
});
