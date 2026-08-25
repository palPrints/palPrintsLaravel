<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('customer is redirected to the customer dashboard', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('customer');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('customer.dashboard', absolute: false));
    expect($user->fresh()->last_login_at)->not->toBeNull();
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'action' => 'account.logged_in',
    ]);
});

test('draft designer is redirected to the designer dashboard', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('designer');
    $user->designerProfile()->create([
        'full_name' => $user->name,
        'approval_status' => 'draft',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('designer.dashboard', absolute: false));
});

test('approved designer is redirected to the designer dashboard', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('designer');
    $user->designerProfile()->create([
        'full_name' => $user->name,
        'approval_status' => 'approved',
        'approved_at' => now(),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('designer.dashboard', absolute: false));
});

test('draft print provider enters the dashboard and can view account status', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('print_provider');
    $user->printProvider()->create([
        'company_name' => 'Test Print Company',
        'approval_status' => 'draft',
        'is_active' => true,
    ]);

    $login = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $login->assertRedirect(route('print-provider.dashboard', absolute: false));
    $this->get('/account-status')
        ->assertOk()
        ->assertSee('مطبعة')
        ->assertSee('PRN-'.now()->format('Y').'-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT));
});

test('inactive users cannot stay authenticated', function () {
    $user = User::factory()->create(['is_active' => false]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('a customer cannot open an admin dashboard', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
});
