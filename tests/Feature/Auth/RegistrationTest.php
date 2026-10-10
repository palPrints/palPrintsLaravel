<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Schema;

test('the database has the tables the site works with', function () {
    foreach ([
        'users', 'password_reset_tokens', 'sessions', 'roles', 'model_has_roles', 'social_accounts', 'audit_logs',
        'designer_profiles', 'print_providers', 'print_provider_branches', 'branch_product_offerings', 'approval_requests',
        'categories', 'products', 'attributes', 'attribute_values', 'product_attributes', 'product_attribute_values',
        'variants', 'variant_values', 'printing_methods', 'designs', 'design_favorites',
        'addresses', 'carts', 'cart_items', 'orders', 'order_items', 'payments', 'print_files',
        'wallets', 'wallet_transactions', 'withdrawal_requests', 'user_notifications',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("missing table: {$table}");
    }
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'customer',
        'terms' => '1',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login', absolute: false));
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    $this->assertTrue(User::where('email', 'test@example.com')->firstOrFail()->hasRole('customer'));
});

test('designer registration creates a draft profile', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $response = $this->post('/register', [
        'name' => 'Designer User',
        'email' => 'designer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'designer',
        'terms' => '1',
    ]);

    $user = User::where('email', 'designer@example.com')->firstOrFail();
    $response->assertRedirect(route('login', absolute: false));
    expect($user->hasRole('designer'))->toBeTrue();
    $this->assertDatabaseHas('designer_profiles', [
        'user_id' => $user->id,
        'approval_status' => 'draft',
    ]);
});

test('print provider registration creates a draft profile', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $response = $this->post('/register', [
        'name' => 'Print Company',
        'email' => 'printer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'print_provider',
        'terms' => '1',
    ]);

    $user = User::where('email', 'printer@example.com')->firstOrFail();
    $response->assertRedirect(route('login', absolute: false));
    expect($user->hasRole('print_provider'))->toBeTrue();
    $this->assertDatabaseHas('print_providers', [
        'user_id' => $user->id,
        'company_name' => 'Print Company',
        'approval_status' => 'draft',
    ]);
});
