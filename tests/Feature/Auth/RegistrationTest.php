<?php

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
        'role' => 'customer',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login', absolute: false));
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'role' => 'customer',
        'is_active' => true,
    ]);
});
