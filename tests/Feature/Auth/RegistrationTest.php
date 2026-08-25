<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

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

test('designer registration creates a draft profile without a wallet', function () {
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
    $this->assertDatabaseMissing('wallets', ['user_id' => $user->id]);
});

test('print provider registration creates a draft profile without a wallet', function () {
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
    $this->assertDatabaseMissing('wallets', ['user_id' => $user->id]);
});
